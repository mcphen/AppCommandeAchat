<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type Article, type Boutique, type BreadcrumbItem, type Fournisseur, type PurchaseOrderLine } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Upload, X, FileText, Loader2, Send, Store, Plus, Trash2, Package, ChevronDown, ShieldAlert } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    boutique?: Boutique | null;
    articles: Article[];
    fournisseurs: Fournisseur[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Mes commandes', href: '/purchase-orders' },
    { title: 'Nouvelle commande', href: '/purchase-orders/create' },
];

// ---- Lignes ----
type LineForm = {
    article_id: number | '';
    fournisseur_id: number | '';
    quantity: number;
    unit_price: number;
    note: string;
};

const lines = ref<LineForm[]>([]);

const addLine = () => {
    lines.value.push({ article_id: '', fournisseur_id: '', quantity: 1, unit_price: 0, note: '' });
};

const removeLine = (i: number) => {
    lines.value.splice(i, 1);
};

const onArticleChange = (i: number) => {
    const line = lines.value[i];
    const article = props.articles.find(a => a.id === Number(line.article_id));
    if (article) {
        line.unit_price = Number(article.unit_price ?? 0);
    }
};

const lineSubtotal = (line: LineForm) => line.quantity * line.unit_price;

const linesTotal = computed(() =>
    lines.value.reduce((sum, l) => sum + lineSubtotal(l), 0)
);

const hasLines = computed(() => lines.value.length > 0);

// Détection fournisseurs non homologués
const nonApprovedWarnings = computed(() => {
    const warnings: string[] = [];
    // Fournisseur au niveau commande
    if (form.fournisseur_id) {
        const f = props.fournisseurs.find(f => f.id === Number(form.fournisseur_id));
        if (f && !f.is_approved) warnings.push(`"${f.name}" (commande)`);
    }
    // Fournisseurs au niveau lignes
    for (const line of lines.value) {
        if (line.fournisseur_id) {
            const f = props.fournisseurs.find(f => f.id === Number(line.fournisseur_id));
            if (f && !f.is_approved && !warnings.find(w => w.includes(f.name))) {
                warnings.push(`"${f.name}" (ligne)`);
            }
        }
    }
    return warnings;
});

const articleLabel = (id: number | '') => {
    if (!id) return '';
    const a = props.articles.find(a => a.id === Number(id));
    return a ? a.name : '';
};

const articleUnit = (id: number | '') => {
    if (!id) return '';
    const a = props.articles.find(a => a.id === Number(id));
    return a?.unit ?? '';
};

// ---- Formulaire ----
const form = useForm({
    title:          '',
    description:    '',
    amount:         '',
    fournisseur_id: '' as number | '',
    attachments:    [] as File[],
    lines:          [] as LineForm[],
});

const dragOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const addFiles = (files: FileList | File[]) => {
    const pdfs = Array.from(files).filter(f => f.type === 'application/pdf');
    form.attachments = [...form.attachments, ...pdfs];
};
const onDrop = (e: DragEvent) => { dragOver.value = false; if (e.dataTransfer?.files) addFiles(e.dataTransfer.files); };
const onFileChange = (e: Event) => { const input = e.target as HTMLInputElement; if (input.files) addFiles(input.files); };
const removeFile = (i: number) => { form.attachments = form.attachments.filter((_, idx) => idx !== i); };
const formatSize = (b: number) => b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : (b / 1024).toFixed(0) + ' KB';
const formatAmount = (v: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(v);

const submit = () => {
    if (!props.boutique) return;
    form.lines = lines.value;
    form.post(route('purchase-orders.store'), { forceFormData: true });
};
</script>

<template>
    <Head title="Nouvelle commande" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 w-full sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('purchase-orders.index')" class="rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Nouvelle commande</h1>
                    <p class="text-sm text-muted-foreground">Remplissez les informations de votre demande d'achat</p>
                </div>
            </div>

            <!-- Boutique -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                        <Store class="h-5 w-5 text-blue-600" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Boutique émettrice</p>
                        <template v-if="props.boutique">
                            <p class="text-base font-semibold text-foreground">{{ props.boutique.name }}</p>
                            <p class="text-sm text-muted-foreground">{{ props.boutique.code }}<template v-if="props.boutique.city"> · {{ props.boutique.city }}</template></p>
                        </template>
                        <template v-else>
                            <p class="text-sm font-medium text-red-600">Votre compte n'est rattaché à aucune boutique.</p>
                            <p class="text-sm text-muted-foreground">Un administrateur doit d'abord vous affecter à une boutique.</p>
                        </template>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-5">

                <!-- Fournisseur de la commande (optionnel) -->
                <div class="rounded-2xl border bg-card p-5 shadow-sm flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Fournisseur de la commande</h2>
                            <p class="text-xs text-muted-foreground mt-0.5">Optionnel — peut aussi être précisé ligne par ligne</p>
                        </div>
                    </div>
                    <div class="relative">
                        <select v-model="form.fournisseur_id"
                            class="h-10 w-full rounded-xl border border-input bg-background px-4 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors appearance-none">
                            <option value="">— Aucun fournisseur —</option>
                            <option v-for="f in fournisseurs" :key="f.id" :value="f.id">{{ f.name }} ({{ f.code }})</option>
                        </select>
                        <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                    </div>
                </div>

                <!-- Informations générales -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <h2 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Informations générales</h2>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="title">Titre <span class="text-red-500">*</span></label>
                        <input id="title" v-model="form.title" type="text" placeholder="Ex : Achat de fournitures de bureau"
                            class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                            :class="{ 'border-red-400': form.errors.title }" />
                        <p v-if="form.errors.title" class="text-xs text-red-500">{{ form.errors.title }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="description">Description <span class="text-red-500">*</span></label>
                        <textarea id="description" v-model="form.description" rows="3"
                            placeholder="Décrivez précisément l'objet de cette commande…"
                            class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-none"
                            :class="{ 'border-red-400': form.errors.description }" />
                        <p v-if="form.errors.description" class="text-xs text-red-500">{{ form.errors.description }}</p>
                    </div>
                </div>

                <!-- Lignes de commande -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Lignes de commande</h2>
                            <p class="text-xs text-muted-foreground mt-0.5">Optionnel — ou saisissez un montant global ci-dessous</p>
                        </div>
                        <button type="button" @click="addLine"
                            class="inline-flex items-center gap-1.5 rounded-xl bg-primary/10 px-3 py-1.5 text-xs font-semibold text-primary hover:bg-primary/20 transition-colors">
                            <Plus class="h-3.5 w-3.5" /> Ajouter une ligne
                        </button>
                    </div>

                    <!-- Lignes -->
                    <div v-if="lines.length > 0" class="flex flex-col gap-3">

                        <!-- En-tête tableau -->
                        <div class="hidden grid-cols-12 gap-2 px-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground sm:grid">
                            <div class="col-span-4">Article</div>
                            <div class="col-span-2">Fournisseur</div>
                            <div class="col-span-1 text-center">Qté</div>
                            <div class="col-span-2 text-right">Prix unit.</div>
                            <div class="col-span-2 text-right">Sous-total</div>
                            <div class="col-span-1"></div>
                        </div>

                        <div v-for="(line, i) in lines" :key="i"
                            class="rounded-xl border bg-muted/20 p-3 flex flex-col gap-3 sm:grid sm:grid-cols-12 sm:items-center sm:gap-2 sm:p-2">

                            <!-- Article -->
                            <div class="sm:col-span-4">
                                <label class="text-xs text-muted-foreground sm:hidden mb-1 block">Article</label>
                                <div class="relative">
                                    <select v-model="line.article_id" @change="onArticleChange(i)"
                                        class="h-9 w-full rounded-lg border border-input bg-background px-3 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors appearance-none">
                                        <option value="">— Choisir un article —</option>
                                        <option v-for="a in articles" :key="a.id" :value="a.id">{{ a.name }}</option>
                                    </select>
                                    <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground pointer-events-none" />
                                </div>
                                <p v-if="line.article_id" class="text-xs text-muted-foreground mt-0.5 px-1">Unité : {{ articleUnit(line.article_id) }}</p>
                            </div>

                            <!-- Fournisseur -->
                            <div class="sm:col-span-2">
                                <label class="text-xs text-muted-foreground sm:hidden mb-1 block">Fournisseur</label>
                                <div class="relative">
                                    <select v-model="line.fournisseur_id"
                                        class="h-9 w-full rounded-lg border border-input bg-background px-3 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors appearance-none">
                                        <option value="">— Optionnel —</option>
                                        <option v-for="f in fournisseurs" :key="f.id" :value="f.id">{{ f.name }}</option>
                                    </select>
                                    <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground pointer-events-none" />
                                </div>
                            </div>

                            <!-- Quantité -->
                            <div class="sm:col-span-1">
                                <label class="text-xs text-muted-foreground sm:hidden mb-1 block">Quantité</label>
                                <input v-model.number="line.quantity" type="number" min="0.01" step="0.01"
                                    class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm text-center focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors" />
                            </div>

                            <!-- Prix unitaire -->
                            <div class="sm:col-span-2">
                                <label class="text-xs text-muted-foreground sm:hidden mb-1 block">Prix unitaire (FCFA)</label>
                                <input v-model.number="line.unit_price" type="number" min="0" step="1"
                                    class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm text-right focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors" />
                            </div>

                            <!-- Sous-total -->
                            <div class="sm:col-span-2 text-right">
                                <label class="text-xs text-muted-foreground sm:hidden mb-1 block">Sous-total</label>
                                <p class="text-sm font-semibold text-foreground">{{ formatAmount(lineSubtotal(line)) }}</p>
                            </div>

                            <!-- Supprimer -->
                            <div class="sm:col-span-1 flex justify-end sm:justify-center">
                                <button type="button" @click="removeLine(i)"
                                    class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Note (pleine largeur) -->
                            <div class="sm:col-span-12">
                                <input v-model="line.note" type="text" placeholder="Note ou précision (optionnel)…"
                                    class="h-8 w-full rounded-lg border border-input bg-background px-3 text-xs focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors" />
                            </div>
                        </div>

                        <!-- Total lignes -->
                        <div class="flex items-center justify-between rounded-xl bg-primary/5 border border-primary/20 px-4 py-3">
                            <span class="text-sm font-semibold text-foreground">Total des lignes</span>
                            <span class="text-lg font-bold text-primary">{{ formatAmount(linesTotal) }}</span>
                        </div>
                    </div>

                    <!-- Montant manuel (si pas de lignes) -->
                    <div v-if="!hasLines" class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="amount">
                            Montant estimé (FCFA) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground text-sm font-medium">FCFA</span>
                            <input id="amount" v-model="form.amount" type="number" min="0" step="1" placeholder="0"
                                class="h-10 w-full rounded-xl border border-input bg-background pl-16 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.amount }" />
                        </div>
                        <p v-if="form.errors.amount" class="text-xs text-red-500">{{ form.errors.amount }}</p>
                    </div>

                    <div v-if="hasLines" class="flex items-center gap-2 rounded-xl bg-muted/40 px-4 py-2.5 text-xs text-muted-foreground">
                        <Package class="h-3.5 w-3.5 shrink-0" />
                        Le montant de la commande sera calculé automatiquement depuis les lignes.
                    </div>
                </div>

                <!-- Pièces jointes -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <div>
                        <h2 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Pièces jointes</h2>
                        <p class="text-xs text-muted-foreground mt-1">Fichiers PDF uniquement, 10 Mo max par fichier</p>
                    </div>
                    <div class="relative rounded-xl border-2 border-dashed p-8 text-center transition-colors cursor-pointer"
                        :class="dragOver ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50 hover:bg-muted/30'"
                        @dragover.prevent="dragOver = true" @dragleave="dragOver = false" @drop.prevent="onDrop" @click="fileInput?.click()">
                        <input ref="fileInput" type="file" accept=".pdf,application/pdf" multiple class="hidden" @change="onFileChange" />
                        <div class="flex flex-col items-center gap-3">
                            <div class="rounded-xl bg-muted p-3"><Upload class="h-6 w-6 text-muted-foreground" /></div>
                            <div>
                                <p class="text-sm font-medium text-foreground">Glissez vos fichiers PDF ici</p>
                                <p class="text-xs text-muted-foreground mt-1">ou <span class="text-primary font-medium">cliquez pour parcourir</span></p>
                            </div>
                        </div>
                    </div>
                    <div v-if="form.attachments.length > 0" class="flex flex-col gap-2">
                        <div v-for="(file, i) in form.attachments" :key="i"
                            class="flex items-center justify-between rounded-xl border bg-muted/30 px-4 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50">
                                    <FileText class="h-4 w-4 text-red-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ file.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatSize(file.size) }}</p>
                                </div>
                            </div>
                            <button type="button" @click.stop="removeFile(i)" class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600">
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Alerte fournisseurs non homologués -->
                <div v-if="nonApprovedWarnings.length > 0"
                    class="rounded-2xl border border-amber-200 bg-amber-50 p-4 flex items-start gap-3">
                    <ShieldAlert class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
                    <div>
                        <p class="text-sm font-semibold text-amber-800">Fournisseur{{ nonApprovedWarnings.length > 1 ? 's' : '' }} non homologué{{ nonApprovedWarnings.length > 1 ? 's' : '' }}</p>
                        <p class="text-xs text-amber-700 mt-0.5">
                            {{ nonApprovedWarnings.join(', ') }} — ces fournisseurs n'ont pas encore été validés par l'administration. La commande peut quand même être soumise.
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('purchase-orders.index')" class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors">
                        Annuler
                    </Link>
                    <button type="submit" :disabled="form.processing || !props.boutique"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:opacity-70">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Send v-else class="h-4 w-4" />
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
