<script setup lang="ts">
import PriceComparator from '@/components/PriceComparator.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Article, type Boutique, type BreadcrumbItem, type Fournisseur } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { AlertTriangle, ArrowLeft, ChevronDown, Copy, FileText, Loader2, Package, Plus, Send, ShieldAlert, Store, Trash2, Upload, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const props = defineProps<{
    boutique?: Boutique | null;
    boutiques: Boutique[];
    articles: Article[];
    fournisseurs: Fournisseur[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Mes commandes', href: '/purchase-orders' },
    { title: 'Nouvelle commande', href: '/purchase-orders/create' },
];

// ---- Types ----
type LineForm = {
    article_id: number | '';
    fournisseur_id: number | '';
    quantity: number;
    unit_price: number;
    note: string;
};

type BestPrix = { price: number; supplierName: string };

type LineUiState = {
    articleSearch: string;
    articleOpen: boolean;
    touched: boolean;
    bestCataloguePrix: BestPrix | null;
};

// ---- Lignes ----
const lines = ref<LineForm[]>([]);
const lineUiStates = ref<LineUiState[]>([]);

const addLine = () => {
    lines.value.push({ article_id: '', fournisseur_id: '', quantity: 1, unit_price: 0, note: '' });
    lineUiStates.value.push({ articleSearch: '', articleOpen: false, touched: false, bestCataloguePrix: null });
};

const removeLine = (i: number) => {
    lines.value.splice(i, 1);
    lineUiStates.value.splice(i, 1);
};

const duplicateLine = (i: number) => {
    lines.value.splice(i + 1, 0, { ...lines.value[i] });
    lineUiStates.value.splice(i + 1, 0, {
        articleSearch: '',
        articleOpen: false,
        touched: true,
        bestCataloguePrix: lineUiStates.value[i].bestCataloguePrix,
    });
};

// ---- Alerte prix anormal ----
const SEUIL_ALERTE_PCT = 20;

const fetchBestPrix = async (i: number, articleId: number) => {
    try {
        const res = await axios.get<{ fournisseur_name: string; unit_price: number }[]>(
            `/articles/${articleId}/prix-fournisseurs`
        );
        lineUiStates.value[i].bestCataloguePrix = res.data.length > 0
            ? { price: res.data[0].unit_price, supplierName: res.data[0].fournisseur_name }
            : null;
    } catch {
        lineUiStates.value[i].bestCataloguePrix = null;
    }
};

const lineAnomalies = computed(() =>
    lines.value.map((line, i) => {
        const best = lineUiStates.value[i]?.bestCataloguePrix;
        const price = line.unit_price;
        if (!best || price <= 0 || best.price <= 0) return null;
        const diffPct = ((price - best.price) / best.price) * 100;
        return diffPct >= SEUIL_ALERTE_PCT
            ? { diffPct: Math.round(diffPct), bestPrice: best.price, supplierName: best.supplierName }
            : null;
    })
);

// ---- Combobox article ----
const filteredArticles = (i: number) => {
    const search = (lineUiStates.value[i]?.articleSearch ?? '').toLowerCase().trim();
    if (!search) return props.articles;
    return props.articles.filter(a =>
        a.name.toLowerCase().includes(search) ||
        ((a as any).reference?.toLowerCase() ?? '').includes(search)
    );
};

const onArticleFocus = (i: number) => {
    lineUiStates.value[i].articleSearch = '';
    lineUiStates.value[i].articleOpen = true;
};

const onArticleInput = (i: number, value: string) => {
    lineUiStates.value[i].articleSearch = value;
    if (lines.value[i].article_id) {
        lines.value[i].article_id = '';
        lines.value[i].unit_price = 0;
    }
    lineUiStates.value[i].articleOpen = true;
};

const onArticleBlur = (i: number) => {
    setTimeout(() => {
        lineUiStates.value[i].articleOpen = false;
        if (!lines.value[i].article_id) {
            lineUiStates.value[i].articleSearch = '';
        }
    }, 150);
};

const selectArticle = (i: number, article: Article) => {
    lines.value[i].article_id = article.id;
    lines.value[i].unit_price = Number((article as any).unit_price ?? 0);
    lineUiStates.value[i].articleSearch = '';
    lineUiStates.value[i].articleOpen = false;
    lineUiStates.value[i].touched = true;
    fetchBestPrix(i, article.id);
};

const clearArticle = (i: number) => {
    lines.value[i].article_id = '';
    lines.value[i].unit_price = 0;
    lineUiStates.value[i].touched = true;
    lineUiStates.value[i].bestCataloguePrix = null;
};

// ---- Computed ----
const lineSubtotal = (line: LineForm) => line.quantity * line.unit_price;
const linesTotal = computed(() => lines.value.reduce((sum, l) => sum + lineSubtotal(l), 0));
const hasLines = computed(() => lines.value.length > 0);
const anyLineTouched = computed(() => lineUiStates.value.some(s => s.touched));

const amountIsZero = computed(() => {
    if (hasLines.value) return linesTotal.value <= 0;
    return !form.amount || Number(form.amount) <= 0;
});

// Erreur total à zéro : seulement après interaction ou tentative de submit
const showLinesTotalError = computed(() =>
    hasLines.value && linesTotal.value <= 0 && (anyLineTouched.value || submitAttempted.value)
);

const articleUnit = (id: number | '') => {
    if (!id) return '';
    return props.articles.find(a => a.id === Number(id))?.unit ?? '';
};

const articleLabel = (id: number | '') => {
    if (!id) return '';
    return props.articles.find(a => a.id === Number(id))?.name ?? '';
};

const articleById = (id: number | '') => {
    if (!id) return null;
    return props.articles.find(a => a.id === Number(id)) ?? null;
};

const onPriceSelected = (i: number, payload: { fournisseur_id: number; unit_price: number }) => {
    lines.value[i].fournisseur_id = payload.fournisseur_id;
    lines.value[i].unit_price = payload.unit_price;
    lineUiStates.value[i].touched = true;
    // Passe automatiquement en mode "par ligne" si pas déjà le cas
    if (fournisseurScope.value === 'none') {
        fournisseurScope.value = 'ligne';
    }
};

// ---- Mode fournisseur ----
type FournisseurScope = 'none' | 'commande' | 'ligne';
const fournisseurScope = ref<FournisseurScope>('none');

watch(fournisseurScope, (mode) => {
    if (mode !== 'commande') form.fournisseur_id = '';
    if (mode !== 'ligne') lines.value.forEach(l => { l.fournisseur_id = ''; });
});

// ---- Fournisseurs non homologués ----
const nonApprovedWarnings = computed(() => {
    const warnings: string[] = [];
    if (fournisseurScope.value === 'commande' && form.fournisseur_id) {
        const f = props.fournisseurs.find(f => f.id === Number(form.fournisseur_id));
        if (f && !f.is_approved) warnings.push(`"${f.name}"`);
    }
    if (fournisseurScope.value === 'ligne') {
        for (const line of lines.value) {
            if (line.fournisseur_id) {
                const f = props.fournisseurs.find(f => f.id === Number(line.fournisseur_id));
                if (f && !f.is_approved && !warnings.find(w => w.includes(f.name))) {
                    warnings.push(`"${f.name}"`);
                }
            }
        }
    }
    return warnings;
});

// ---- Formulaire ----
const form = useForm({
    title:          '',
    description:    '',
    amount:         '',
    order_date:     new Date().toISOString().split('T')[0],
    boutique_id:    props.boutique?.id ?? ('' as number | ''),
    fournisseur_id: '' as number | '',
    and_submit:     false,
    attachments:    [] as File[],
    lines:          [] as LineForm[],
});

const submitAttempted = ref(false);
const submitting = ref(false);

// ---- Fichiers ----
const dragOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);
const uploadError = ref('');
const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024;

const addFiles = (files: FileList | File[]) => {
    const oversizedFiles: string[] = [];
    const pdfs = Array.from(files).filter((file) => {
        if (file.size > MAX_FILE_SIZE_BYTES) {
            oversizedFiles.push(`${file.name} (${formatSize(file.size)})`);
            return false;
        }

        return file.type === 'application/pdf';
    });

    form.attachments = [...form.attachments, ...pdfs];
    uploadError.value = oversizedFiles.length > 0
        ? `Fichier${oversizedFiles.length > 1 ? 's' : ''} refusé${oversizedFiles.length > 1 ? 's' : ''} : ${oversizedFiles.join(', ')}. Taille maximale : 10 Mo par fichier.`
        : '';
};
const onDrop = (e: DragEvent) => { dragOver.value = false; if (e.dataTransfer?.files) addFiles(e.dataTransfer.files); };
const onFileChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    if (input.files) addFiles(input.files);
    input.value = '';
};
const removeFile = (i: number) => { form.attachments = form.attachments.filter((_, idx) => idx !== i); };
const formatSize = (b: number) => b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : (b / 1024).toFixed(0) + ' KB';
const formatAmount = (v: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(v);

// ---- Alerte modifications non sauvegardées ----
const formTouched = computed(() =>
    !!form.title || !!form.description || !!form.amount || lines.value.length > 0
);

const beforeUnloadHandler = (e: BeforeUnloadEvent) => {
    if (formTouched.value && !submitting.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};

onMounted(() => {
    window.addEventListener('beforeunload', beforeUnloadHandler);
    const cleanup = router.on('before', () => {
        if (formTouched.value && !submitting.value) {
            return confirm('Vous avez des modifications non sauvegardées. Quitter la page quand même ?');
        }
    });
    onUnmounted(cleanup);
});

onUnmounted(() => {
    window.removeEventListener('beforeunload', beforeUnloadHandler);
});

// ---- Soumission ----
const submit = (andSend = false) => {
    submitAttempted.value = true;
    form.and_submit = andSend;
    form.lines = lines.value;
    submitting.value = true;
    form.post(route('purchase-orders.store'), {
        forceFormData: true,
        onFinish: () => { submitting.value = false; },
        onError: (errors) => {
            // Erreurs de validation classiques : ne rien faire (affichées dans le formulaire)
            if (Object.keys(errors).length > 0) return;
            Swal.fire({
                icon: 'error',
                title: 'Une erreur est survenue',
                text: 'Le serveur a rencontré un problème lors de l\'enregistrement. Veuillez réessayer.',
                confirmButtonText: 'OK',
                confirmButtonColor: '#4f46e5',
            });
        },
    });
};
</script>

<template>
    <Head title="Nouvelle commande" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 w-full sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('purchase-orders.index')"
                    class="rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Nouvelle commande</h1>
                    <p class="text-sm text-muted-foreground">Remplissez les informations de votre demande d'achat</p>
                </div>
            </div>

            <!-- Alert danger : pas de boutique rattachée -->
            <div v-if="!props.boutique"
                class="rounded-2xl border border-red-300 bg-red-50 p-4 flex items-start gap-3">
                <AlertTriangle class="h-5 w-5 text-red-600 shrink-0 mt-0.5" />
                <div>
                    <p class="text-sm font-semibold text-red-800">Aucune boutique rattachée à votre compte</p>
                    <p class="text-xs text-red-700 mt-0.5">
                        Vous pouvez quand même soumettre une demande en sélectionnant une boutique ci-dessous,
                        ou laisser ce champ vide.
                    </p>
                </div>
            </div>

            <!-- Boutique -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                        <Store class="h-5 w-5 text-blue-600" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Boutique émettrice</p>
                        <template v-if="props.boutique">
                            <p class="text-base font-semibold text-foreground">{{ props.boutique.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ props.boutique.code }}
                                <template v-if="props.boutique.city"> · {{ props.boutique.city }}</template>
                            </p>
                        </template>
                        <template v-else>
                            <div class="relative mt-2">
                                <select v-model="form.boutique_id"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-4 pr-10 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors appearance-none"
                                    :class="{ 'border-red-400': form.errors.boutique_id }">
                                    <option value="">— Aucune boutique —</option>
                                    <option v-for="b in props.boutiques" :key="b.id" :value="b.id">
                                        {{ b.name }}<template v-if="b.city"> · {{ b.city }}</template>
                                    </option>
                                </select>
                                <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                            </div>
                            <p v-if="form.errors.boutique_id" class="text-xs text-red-500 mt-1">{{ form.errors.boutique_id }}</p>
                        </template>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit(false)" class="flex flex-col gap-5">

                <!-- Fournisseur -->
                <div class="rounded-2xl border bg-card p-5 shadow-sm flex flex-col gap-4">
                    <div>
                        <h2 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Fournisseur</h2>
                        <p class="text-xs text-muted-foreground mt-0.5">Optionnel — choisissez comment renseigner le fournisseur</p>
                    </div>

                    <!-- Toggle mode -->
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button" @click="fournisseurScope = 'none'"
                            :class="fournisseurScope === 'none'
                                ? 'border-primary bg-primary/10 text-primary ring-1 ring-primary/30'
                                : 'border-border bg-background text-muted-foreground hover:border-primary/40 hover:text-foreground'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border px-3 py-3 text-center transition-all">
                            <X class="h-4 w-4" />
                            <span class="text-xs font-semibold leading-tight">Aucun</span>
                        </button>
                        <button type="button" @click="fournisseurScope = 'commande'"
                            :class="fournisseurScope === 'commande'
                                ? 'border-primary bg-primary/10 text-primary ring-1 ring-primary/30'
                                : 'border-border bg-background text-muted-foreground hover:border-primary/40 hover:text-foreground'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border px-3 py-3 text-center transition-all">
                            <Store class="h-4 w-4" />
                            <span class="text-xs font-semibold leading-tight">Pour toute la commande</span>
                        </button>
                        <button type="button" @click="fournisseurScope = 'ligne'"
                            :class="fournisseurScope === 'ligne'
                                ? 'border-primary bg-primary/10 text-primary ring-1 ring-primary/30'
                                : 'border-border bg-background text-muted-foreground hover:border-primary/40 hover:text-foreground'"
                            class="flex flex-col items-center gap-1.5 rounded-xl border px-3 py-3 text-center transition-all">
                            <Package class="h-4 w-4" />
                            <span class="text-xs font-semibold leading-tight">Par article</span>
                        </button>
                    </div>

                    <!-- Sélecteur commande -->
                    <div v-if="fournisseurScope === 'commande'" class="relative">
                        <select v-model="form.fournisseur_id"
                            class="h-10 w-full rounded-xl border border-input bg-background px-4 pr-10 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors appearance-none">
                            <option value="">— Choisir un fournisseur —</option>
                            <option v-for="f in fournisseurs" :key="f.id" :value="f.id">{{ f.name }} ({{ f.code }})</option>
                        </select>
                        <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                    </div>

                    <!-- Info mode par ligne -->
                    <div v-if="fournisseurScope === 'ligne'"
                        class="flex items-center justify-between rounded-xl bg-primary/5 border border-primary/20 px-4 py-2.5">
                        <div class="flex items-center gap-2 text-xs text-primary">
                            <Package class="h-3.5 w-3.5 shrink-0" />
                            Le fournisseur sera renseigné sur chaque ligne ci-dessous.
                        </div>
                        <button v-if="!hasLines" type="button" @click="addLine"
                            class="text-xs font-semibold text-primary hover:underline shrink-0 ml-3">
                            + Ajouter une ligne
                        </button>
                    </div>
                </div>

                <!-- Informations générales -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <h2 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Informations générales</h2>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground" for="title">
                                Titre <span class="text-red-500">*</span>
                            </label>
                            <input id="title" v-model="form.title" type="text"
                                placeholder="Ex : Achat de fournitures de bureau"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.title }" />
                            <p v-if="form.errors.title" class="text-xs text-red-500">{{ form.errors.title }}</p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground" for="order_date">Date de la commande</label>
                            <input id="order_date" v-model="form.order_date" type="date"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.order_date }" />
                            <p v-if="form.errors.order_date" class="text-xs text-red-500">{{ form.errors.order_date }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="description">
                            Description <span class="text-red-500">*</span>
                        </label>
                        <textarea id="description" v-model="form.description" rows="3"
                            placeholder="Décrivez précisément l'objet de cette commande…"
                            class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-none"
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
                        <div class="hidden gap-2 px-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground sm:grid"
                            :class="fournisseurScope === 'ligne' ? 'grid-cols-13' : 'grid-cols-11'">
                            <div :class="fournisseurScope === 'ligne' ? 'col-span-4' : 'col-span-6'">Article</div>
                            <div v-if="fournisseurScope === 'ligne'" class="col-span-2">Fournisseur</div>
                            <div class="col-span-1 text-center">Qté</div>
                            <div class="col-span-2 text-right">Prix unit.</div>
                            <div class="col-span-2 text-right">Sous-total</div>
                            <div class="col-span-2"></div>
                        </div>

                        <div v-for="(line, i) in lines" :key="i"
                            class="rounded-xl border bg-muted/20 p-3 flex flex-col gap-2 sm:grid sm:items-start sm:gap-2 sm:p-2"
                            :class="fournisseurScope === 'ligne' ? 'sm:grid-cols-13' : 'sm:grid-cols-11'">

                            <!-- Article (combobox) -->
                            <div :class="fournisseurScope === 'ligne' ? 'sm:col-span-4' : 'sm:col-span-6'">
                                <label class="text-xs text-muted-foreground sm:hidden mb-1 block">Article</label>
                                <div class="relative">
                                    <!-- Input combobox -->
                                    <input
                                        :value="lineUiStates[i].articleOpen
                                            ? lineUiStates[i].articleSearch
                                            : articleLabel(line.article_id)"
                                        @focus="onArticleFocus(i)"
                                        @input="onArticleInput(i, ($event.target as HTMLInputElement).value)"
                                        @blur="onArticleBlur(i)"
                                        type="text"
                                        placeholder="Rechercher un article…"
                                        class="h-9 w-full rounded-lg border bg-background px-3 pr-8 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                        :class="[
                                            line.article_id ? 'border-primary/40' : 'border-input',
                                            lineUiStates[i].touched && !line.article_id ? 'border-amber-400' : ''
                                        ]"
                                    />
                                    <!-- Clear button -->
                                    <button v-if="line.article_id" type="button"
                                        @mousedown.prevent="clearArticle(i)"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground">
                                        <X class="h-3.5 w-3.5" />
                                    </button>
                                    <ChevronDown v-else class="absolute right-2 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground pointer-events-none" />

                                    <!-- Dropdown -->
                                    <div v-if="lineUiStates[i].articleOpen"
                                        class="absolute z-50 mt-1 w-full rounded-xl border border-border bg-card shadow-lg overflow-hidden">
                                        <div class="max-h-48 overflow-y-auto">
                                            <button v-for="a in filteredArticles(i)" :key="a.id"
                                                type="button"
                                                @mousedown.prevent="selectArticle(i, a)"
                                                class="w-full text-left px-3 py-2 text-sm hover:bg-primary/5 transition-colors flex items-center justify-between gap-2">
                                                <span class="font-medium text-foreground">{{ a.name }}</span>
                                                <span v-if="a.unit" class="text-xs text-muted-foreground shrink-0">{{ a.unit }}</span>
                                            </button>
                                            <p v-if="filteredArticles(i).length === 0"
                                                class="px-3 py-2 text-sm text-muted-foreground text-center">
                                                Aucun résultat
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Unité + warnings + comparateur -->
                                <div class="mt-0.5 px-1 flex flex-wrap items-center gap-x-3 gap-y-0.5">
                                    <p v-if="line.article_id && articleUnit(line.article_id)"
                                        class="text-xs text-muted-foreground">
                                        Unité : {{ articleUnit(line.article_id) }}
                                    </p>
                                    <p v-if="lineUiStates[i].touched && !line.article_id"
                                        class="text-xs text-amber-600">
                                        Aucun article sélectionné
                                    </p>
                                    <p v-if="line.article_id && line.unit_price === 0"
                                        class="text-xs text-amber-600">
                                        Prix unitaire à 0 — pensez à le renseigner
                                    </p>
                                    <!-- Comparateur de prix fournisseurs -->
                                    <PriceComparator
                                        v-if="line.article_id"
                                        :article="articleById(line.article_id)"
                                        :current-fournisseur-id="line.fournisseur_id"
                                        :current-price="line.unit_price"
                                        @select="onPriceSelected(i, $event)"
                                    />
                                </div>
                            </div>

                            <!-- Fournisseur (mode "par ligne" uniquement) -->
                            <div v-if="fournisseurScope === 'ligne'" class="sm:col-span-2">
                                <label class="text-xs text-muted-foreground sm:hidden mb-1 block">Fournisseur</label>
                                <div class="relative">
                                    <select v-model="line.fournisseur_id"
                                        class="h-9 w-full rounded-lg border border-input bg-background px-3 pr-8 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors appearance-none">
                                        <option value="">— Choisir —</option>
                                        <option v-for="f in fournisseurs" :key="f.id" :value="f.id">{{ f.name }}</option>
                                    </select>
                                    <ChevronDown class="absolute right-2.5 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-muted-foreground pointer-events-none" />
                                </div>
                            </div>

                            <!-- Quantité -->
                            <div class="sm:col-span-1">
                                <label class="text-xs text-muted-foreground sm:hidden mb-1 block">Qté</label>
                                <input v-model.number="line.quantity" type="number" min="0.01" step="0.01"
                                    @change="lineUiStates[i].touched = true"
                                    class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm text-center text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors" />
                            </div>

                            <!-- Prix unitaire -->
                            <div class="sm:col-span-2">
                                <label class="text-xs text-muted-foreground sm:hidden mb-1 block">Prix unit. (FCFA)</label>
                                <input v-model.number="line.unit_price" type="number" min="0" step="1"
                                    @change="lineUiStates[i].touched = true"
                                    class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm text-right text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
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

                            <!-- Sous-total -->
                            <div class="sm:col-span-2 text-right sm:pt-1.5">
                                <label class="text-xs text-muted-foreground sm:hidden mb-1 block">Sous-total</label>
                                <p class="text-sm font-semibold text-foreground">{{ formatAmount(lineSubtotal(line)) }}</p>
                            </div>

                            <!-- Actions ligne -->
                            <div class="sm:col-span-2 flex justify-end sm:justify-center gap-1 sm:pt-0.5">
                                <button type="button" @click="duplicateLine(i)" title="Dupliquer"
                                    class="rounded-lg p-1.5 hover:bg-primary/10 transition-colors text-muted-foreground hover:text-primary">
                                    <Copy class="h-4 w-4" />
                                </button>
                                <button type="button" @click="removeLine(i)" title="Supprimer"
                                    class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>

                            <!-- Note (pleine largeur) -->
                            <div :class="fournisseurScope === 'ligne' ? 'sm:col-span-13' : 'sm:col-span-11'">
                                <input v-model="line.note" type="text"
                                    placeholder="Note ou précision (optionnel)…"
                                    class="h-8 w-full rounded-lg border border-input bg-background px-3 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors" />
                            </div>
                        </div>

                        <!-- Total lignes -->
                        <div class="flex items-center justify-between rounded-xl px-4 py-3"
                            :class="showLinesTotalError ? 'bg-red-50 border border-red-200' : 'bg-primary/5 border border-primary/20'">
                            <span class="text-sm font-semibold"
                                :class="showLinesTotalError ? 'text-red-700' : 'text-foreground'">
                                Total des lignes
                            </span>
                            <span class="text-lg font-bold"
                                :class="showLinesTotalError ? 'text-red-600' : 'text-primary'">
                                {{ formatAmount(linesTotal) }}
                            </span>
                        </div>
                        <p v-if="showLinesTotalError || form.errors.lines" class="text-xs text-red-500">
                            {{ form.errors.lines ?? 'Le total des lignes doit être supérieur à 0 FCFA.' }}
                        </p>
                    </div>

                    <!-- Montant manuel (si pas de lignes) -->
                    <div v-if="!hasLines" class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="amount">
                            Montant estimé (FCFA) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground text-sm font-medium">FCFA</span>
                            <input id="amount" v-model="form.amount" type="number" min="0" step="1" placeholder="0"
                                class="h-10 w-full rounded-xl border border-input bg-background pl-16 pr-4 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.amount || (form.amount !== '' && Number(form.amount) <= 0) }" />
                        </div>
                        <p v-if="form.errors.amount" class="text-xs text-red-500">{{ form.errors.amount }}</p>
                        <p v-else-if="form.amount !== '' && Number(form.amount) <= 0" class="text-xs text-red-500">
                            Le montant doit être supérieur à 0.
                        </p>
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
                        <p class="text-xs text-muted-foreground mt-1">Fichiers PDF uniquement · 10 Mo max par fichier</p>
                    </div>
                    <div class="relative rounded-xl border-2 border-dashed p-8 text-center transition-colors cursor-pointer"
                        :class="uploadError
                            ? 'border-red-300 bg-red-50/60'
                            : dragOver
                                ? 'border-primary bg-primary/5'
                                : 'border-border hover:border-primary/50 hover:bg-muted/30'"
                        @dragover.prevent="dragOver = true"
                        @dragleave="dragOver = false"
                        @drop.prevent="onDrop"
                        @click="fileInput?.click()">
                        <input ref="fileInput" type="file" accept=".pdf,application/pdf" multiple class="hidden" @change="onFileChange" />
                        <div class="flex flex-col items-center gap-3">
                            <div class="rounded-xl bg-muted p-3">
                                <Upload class="h-6 w-6 text-muted-foreground" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-foreground">Glissez vos fichiers PDF ici</p>
                                <p class="text-xs text-muted-foreground mt-1">ou <span class="text-primary font-medium">cliquez pour parcourir</span></p>
                            </div>
                        </div>
                    </div>
                    <p v-if="uploadError" class="flex items-start gap-2 text-xs text-red-600">
                        <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                        <span>{{ uploadError }}</span>
                    </p>
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
                            <button type="button" @click.stop="removeFile(i)"
                                class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600">
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
                        <p class="text-sm font-semibold text-amber-800">
                            Fournisseur{{ nonApprovedWarnings.length > 1 ? 's' : '' }} non homologué{{ nonApprovedWarnings.length > 1 ? 's' : '' }}
                        </p>
                        <p class="text-xs text-amber-700 mt-0.5">
                            {{ nonApprovedWarnings.join(', ') }} — non validé{{ nonApprovedWarnings.length > 1 ? 's' : '' }} par l'administration.
                            La commande peut quand même être soumise.
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 flex-wrap">
                    <Link :href="route('purchase-orders.index')"
                        class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors">
                        Annuler
                    </Link>
                    <button type="submit" :disabled="form.processing || amountIsZero"
                        class="inline-flex items-center gap-2 rounded-xl border border-primary/30 bg-primary/10 px-5 py-2.5 text-sm font-semibold text-primary shadow-sm hover:bg-primary/20 transition-colors disabled:opacity-50">
                        <Loader2 v-if="form.processing && !form.and_submit" class="h-4 w-4 animate-spin" />
                        <FileText v-else class="h-4 w-4" />
                        Enregistrer en brouillon
                    </button>
                    <button type="button" @click="submit(true)" :disabled="form.processing || amountIsZero"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:opacity-50">
                        <Loader2 v-if="form.processing && form.and_submit" class="h-4 w-4 animate-spin" />
                        <Send v-else class="h-4 w-4" />
                        Enregistrer et soumettre
                    </button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
