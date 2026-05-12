<script setup lang="ts">
import PriceComparator from '@/components/PriceComparator.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Article, type BreadcrumbItem, type Fournisseur, type PurchaseOrder } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { AlertTriangle, ArrowLeft, Upload, X, FileText, Loader2, Save, Trash2, Store, Plus, Package, ChevronDown, ShieldAlert } from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    order: PurchaseOrder;
    articles: Article[];
    fournisseurs: Fournisseur[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Mes commandes', href: '/purchase-orders' },
    { title: 'Modifier', href: '#' },
];

// ---- Lignes ----
type LineForm = {
    article_id: number | '';
    fournisseur_id: number | '';
    quantity: number;
    unit_price: number;
    note: string;
};

const lines = ref<LineForm[]>(
    (props.order.lines ?? []).map(l => ({
        article_id:     l.article_id ?? '',
        fournisseur_id: l.fournisseur_id ?? '',
        quantity:       Number(l.quantity),
        unit_price:     Number(l.unit_price),
        note:           l.note ?? '',
    }))
);

// ---- Alerte prix anormal ----
type BestPrix = { price: number; supplierName: string };
const SEUIL_ALERTE_PCT = 20;
const lineBestPrix = ref<(BestPrix | null)[]>([]);

const fetchBestPrix = async (i: number, articleId: number) => {
    try {
        const res = await axios.get<{ fournisseur_name: string; unit_price: number }[]>(
            `/articles/${articleId}/prix-fournisseurs`
        );
        lineBestPrix.value[i] = res.data.length > 0
            ? { price: res.data[0].unit_price, supplierName: res.data[0].fournisseur_name }
            : null;
    } catch {
        lineBestPrix.value[i] = null;
    }
};

const lineAnomalies = computed(() =>
    lines.value.map((line, i) => {
        const best = lineBestPrix.value[i];
        const price = line.unit_price;
        if (!best || price <= 0 || best.price <= 0) return null;
        const diffPct = ((price - best.price) / best.price) * 100;
        return diffPct >= SEUIL_ALERTE_PCT
            ? { diffPct: Math.round(diffPct), bestPrice: best.price, supplierName: best.supplierName }
            : null;
    })
);

onMounted(() => {
    // Initialise les best prix pour les lignes existantes
    lines.value.forEach((line, i) => {
        lineBestPrix.value[i] = null;
        if (line.article_id) fetchBestPrix(i, Number(line.article_id));
    });
});

const addLine = () => {
    lines.value.push({ article_id: '', fournisseur_id: '', quantity: 1, unit_price: 0, note: '' });
    lineBestPrix.value.push(null);
};

const removeLine = (i: number) => {
    lines.value.splice(i, 1);
    lineBestPrix.value.splice(i, 1);
};

const onArticleChange = (i: number) => {
    const line = lines.value[i];
    const article = props.articles.find(a => a.id === Number(line.article_id));
    if (article) {
        line.unit_price = Number(article.unit_price ?? 0);
        fetchBestPrix(i, article.id);
    } else {
        lineBestPrix.value[i] = null;
    }
};

const lineSubtotal = (line: LineForm) => line.quantity * line.unit_price;
const linesTotal = computed(() => lines.value.reduce((sum, l) => sum + lineSubtotal(l), 0));
const hasLines = computed(() => lines.value.length > 0);

const nonApprovedWarnings = computed(() => {
    const warnings: string[] = [];
    if (form.fournisseur_id) {
        const f = props.fournisseurs.find(f => f.id === Number(form.fournisseur_id));
        if (f && !f.is_approved) warnings.push(`"${f.name}" (commande)`);
    }
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

const articleUnit = (id: number | '') => {
    if (!id) return '';
    return props.articles.find(a => a.id === Number(id))?.unit ?? '';
};

const articleById = (id: number | '') => {
    if (!id) return null;
    return props.articles.find(a => a.id === Number(id)) ?? null;
};

const onPriceSelected = (i: number, payload: { fournisseur_id: number; unit_price: number }) => {
    lines.value[i].fournisseur_id = payload.fournisseur_id;
    lines.value[i].unit_price = payload.unit_price;
};

const formatAmount = (v: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(v);

// ---- Formulaire ----
const form = useForm({
    title:                   props.order.title,
    description:             props.order.description,
    amount:                  props.order.amount,
    fournisseur_id:          (props.order.fournisseur_id ?? '') as number | '',
    attachments:             [] as File[],
    deleted_attachment_ids:  [] as number[],
    lines:                   [] as LineForm[],
    _method:                 'PUT',
});

const existingAttachments = ref(props.order.attachments ?? []);
const dragOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const markDelete = (id: number) => {
    existingAttachments.value = existingAttachments.value.filter(a => a.id !== id);
    form.deleted_attachment_ids.push(id);
};
const addFiles = (files: FileList | File[]) => {
    form.attachments = [...form.attachments, ...Array.from(files).filter(f => f.type === 'application/pdf')];
};
const onDrop = (e: DragEvent) => { dragOver.value = false; if (e.dataTransfer?.files) addFiles(e.dataTransfer.files); };
const onFileChange = (e: Event) => { const input = e.target as HTMLInputElement; if (input.files) addFiles(input.files); };
const removeNewFile = (i: number) => { form.attachments = form.attachments.filter((_, idx) => idx !== i); };
const formatSize = (b: number) => b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : (b / 1024).toFixed(0) + ' KB';

const submit = () => {
    form.lines = lines.value;
    form.post(route('purchase-orders.update', props.order.uuid), { forceFormData: true });
};
</script>

<template>
    <Head title="Modifier la commande" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 w-full sm:gap-6 sm:p-6">

            <div class="flex items-center gap-4">
                <Link :href="route('purchase-orders.index')" class="rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Modifier la commande</h1>
                    <p class="text-sm text-muted-foreground">
                        <span v-if="order.status === 'rejected'" class="text-red-600 font-medium">Commande refusée</span>
                        <span v-else>Brouillon</span>
                        — corrigez puis re-soumettez
                    </p>
                </div>
            </div>

            <!-- Motif de refus -->
            <div v-if="order.status === 'rejected' && order.validation_logs?.length" class="rounded-2xl border border-red-200 bg-red-50 p-5">
                <p class="text-sm font-semibold text-red-800 mb-1">Motif du dernier refus</p>
                <p class="text-sm text-red-700">{{ order.validation_logs[0]?.comment }}</p>
            </div>

            <!-- Boutique -->
            <div v-if="order.boutique" class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                        <Store class="h-5 w-5 text-blue-600" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Boutique émettrice</p>
                        <p class="text-base font-semibold text-foreground">{{ order.boutique.name }}</p>
                        <p class="text-sm text-muted-foreground">{{ order.boutique.code }}<template v-if="order.boutique.city"> · {{ order.boutique.city }}</template></p>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-5">

                <!-- Fournisseur de la commande (optionnel) -->
                <div class="rounded-2xl border bg-card p-5 shadow-sm flex flex-col gap-3">
                    <div>
                        <h2 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Fournisseur de la commande</h2>
                        <p class="text-xs text-muted-foreground mt-0.5">Optionnel — peut aussi être précisé ligne par ligne</p>
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
                        <input id="title" v-model="form.title" type="text"
                            class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                            :class="{ 'border-red-400': form.errors.title }" />
                        <p v-if="form.errors.title" class="text-xs text-red-500">{{ form.errors.title }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="description">Description <span class="text-red-500">*</span></label>
                        <textarea id="description" v-model="form.description" rows="4"
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

                    <div v-if="lines.length > 0" class="flex flex-col gap-3">
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
                                <div class="mt-0.5 px-1 flex flex-wrap items-center gap-x-3 gap-y-0.5">
                                    <p v-if="line.article_id" class="text-xs text-muted-foreground">Unité : {{ articleUnit(line.article_id) }}</p>
                                    <PriceComparator
                                        v-if="line.article_id"
                                        :article="articleById(line.article_id)"
                                        :current-fournisseur-id="line.fournisseur_id"
                                        :current-price="line.unit_price"
                                        @select="onPriceSelected(i, $event)"
                                    />
                                </div>
                            </div>

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

                            <div class="sm:col-span-1">
                                <label class="text-xs text-muted-foreground sm:hidden mb-1 block">Quantité</label>
                                <input v-model.number="line.quantity" type="number" min="0.01" step="0.01"
                                    class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm text-center focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors" />
                            </div>

                            <div class="sm:col-span-2">
                                <label class="text-xs text-muted-foreground sm:hidden mb-1 block">Prix unitaire (FCFA)</label>
                                <input v-model.number="line.unit_price" type="number" min="0" step="1"
                                    class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm text-right focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                    :class="{ 'border-amber-400 focus:border-amber-400 focus:ring-amber-200': lineAnomalies[i] }" />
                                <div v-if="lineAnomalies[i]"
                                    class="mt-1 flex items-start gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-2 py-1.5 text-xs text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300">
                                    <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                    <span>
                                        +{{ lineAnomalies[i]!.diffPct }}% vs meilleur tarif
                                        ({{ formatAmount(lineAnomalies[i]!.bestPrice) }} — {{ lineAnomalies[i]!.supplierName }})
                                    </span>
                                </div>
                            </div>

                            <div class="sm:col-span-2 text-right">
                                <label class="text-xs text-muted-foreground sm:hidden mb-1 block">Sous-total</label>
                                <p class="text-sm font-semibold text-foreground">{{ formatAmount(lineSubtotal(line)) }}</p>
                            </div>

                            <div class="sm:col-span-1 flex justify-end sm:justify-center">
                                <button type="button" @click="removeLine(i)"
                                    class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>

                            <div class="sm:col-span-12">
                                <input v-model="line.note" type="text" placeholder="Note ou précision (optionnel)…"
                                    class="h-8 w-full rounded-lg border border-input bg-background px-3 text-xs focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors" />
                            </div>
                        </div>

                        <!-- Total -->
                        <div class="flex items-center justify-between rounded-xl bg-primary/5 border border-primary/20 px-4 py-3">
                            <span class="text-sm font-semibold text-foreground">Total des lignes</span>
                            <span class="text-lg font-bold text-primary">{{ formatAmount(linesTotal) }}</span>
                        </div>
                    </div>

                    <!-- Montant manuel si pas de lignes -->
                    <div v-if="!hasLines" class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="amount">Montant (FCFA) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground text-sm font-medium">FCFA</span>
                            <input id="amount" v-model="form.amount" type="number" min="0"
                                class="h-10 w-full rounded-xl border border-input bg-background pl-16 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.amount }" />
                        </div>
                        <p v-if="form.errors.amount" class="text-xs text-red-500">{{ form.errors.amount }}</p>
                    </div>

                    <div v-if="hasLines" class="flex items-center gap-2 rounded-xl bg-muted/40 px-4 py-2.5 text-xs text-muted-foreground">
                        <Package class="h-3.5 w-3.5 shrink-0" />
                        Le montant sera calculé automatiquement depuis les lignes.
                    </div>
                </div>

                <!-- Pièces jointes -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <div>
                        <h2 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Pièces jointes</h2>
                        <p class="text-xs text-muted-foreground mt-1">PDF uniquement · 10 Mo max par fichier</p>
                    </div>

                    <div v-if="existingAttachments.length > 0" class="flex flex-col gap-2">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Fichiers actuels</p>
                        <div v-for="att in existingAttachments" :key="att.id"
                            class="flex items-center justify-between rounded-xl border bg-muted/30 px-4 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50">
                                    <FileText class="h-4 w-4 text-red-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ att.file_name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatSize(att.file_size) }}</p>
                                </div>
                            </div>
                            <button type="button" @click="markDelete(att.id)" class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="relative rounded-xl border-2 border-dashed p-6 text-center cursor-pointer transition-colors"
                        :class="dragOver ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50 hover:bg-muted/20'"
                        @dragover.prevent="dragOver = true" @dragleave="dragOver = false" @drop.prevent="onDrop" @click="fileInput?.click()">
                        <input ref="fileInput" type="file" accept=".pdf" multiple class="hidden" @change="onFileChange" />
                        <div class="flex flex-col items-center gap-2">
                            <Upload class="h-5 w-5 text-muted-foreground" />
                            <p class="text-sm text-muted-foreground">Ajouter des fichiers PDF</p>
                        </div>
                    </div>

                    <div v-if="form.attachments.length > 0" class="flex flex-col gap-2">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Nouveaux fichiers</p>
                        <div v-for="(file, i) in form.attachments" :key="i"
                            class="flex items-center justify-between rounded-xl border border-primary/30 bg-primary/5 px-4 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                    <FileText class="h-4 w-4 text-primary" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ file.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatSize(file.size) }}</p>
                                </div>
                            </div>
                            <button type="button" @click.stop="removeNewFile(i)" class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600">
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

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('purchase-orders.show', order.uuid)" class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors">
                        Annuler
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:opacity-70">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
