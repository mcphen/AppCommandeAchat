<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type Article, type BreadcrumbItem, type CatalogueTarif, type Fournisseur, type PurchaseOrder } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import {
    AlertCircle,
    ArrowLeft,
    ArrowRight,
    CalendarDays,
    CheckCircle,
    CheckCircle2,
    ChevronDown,
    Clock,
    Download,
    Eye,
    FileStack,
    FileText,
    FileUp,
    Mail,
    MapPin,
    Package,
    Pencil,
    Phone,
    Plus,
    ShieldAlert,
    ShieldCheck,
    Tag,
    Trash2,
    Truck,
    Upload,
    Wallet,
    X,
} from 'lucide-vue-next';
import { computed, onMounted, ref } from 'vue';

const props = defineProps<{
    fournisseur: Fournisseur;
    orders: PurchaseOrder[];
    stats: Record<string, number>;
    articles: Article[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Fournisseurs', href: '/admin/fournisseurs' },
    { title: props.fournisseur.name, href: '#' },
];

const statusConfig = {
    draft: {
        label: 'Brouillon',
        badge: 'bg-slate-100 text-slate-700 dark:bg-slate-900/70 dark:text-slate-300',
        dot: 'bg-slate-400',
    },
    pending: {
        label: 'En attente',
        badge: 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300',
        dot: 'bg-amber-500',
    },
    approved: {
        label: 'Approuvee',
        badge: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300',
        dot: 'bg-emerald-500',
    },
    rejected: {
        label: 'Refusee',
        badge: 'bg-red-50 text-red-700 dark:bg-red-950/30 dark:text-red-300',
        dot: 'bg-red-500',
    },
} as const;

const approvedOrders = computed(() => props.orders.filter((order) => order.status === 'approved'));
const pendingOrders = computed(() => props.orders.filter((order) => order.status === 'pending'));
const rejectedOrders = computed(() => props.orders.filter((order) => order.status === 'rejected'));

const totalSpend = computed(() => props.orders.reduce((sum, order) => sum + Number(order.amount ?? 0), 0));
const averageOrderAmount = computed(() => (props.orders.length ? totalSpend.value / props.orders.length : 0));
const recentOrder = computed(
    () => [...props.orders].sort((left, right) => new Date(right.created_at).getTime() - new Date(left.created_at).getTime())[0] ?? null,
);
const approvalRate = computed(() => (props.orders.length ? Math.round((approvedOrders.value.length / props.orders.length) * 100) : 0));

const formatAmount = (value: number) =>
    new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        minimumFractionDigits: 0,
    }).format(value);

const formatDate = (value: string) =>
    new Date(value).toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    });

const formatAmountShort = (value: number) => {
    if (value >= 1_000_000_000) return `${(value / 1_000_000_000).toFixed(1)} Md`;
    if (value >= 1_000_000) return `${(value / 1_000_000).toFixed(1)} M`;
    if (value >= 1_000) return `${(value / 1_000).toFixed(0)} K`;
    return value.toString();
};

// ─── Onglets ──────────────────────────────────────────────────────────────────
type Tab = 'commandes' | 'catalogue';
const activeTab = ref<Tab>('commandes');

// ─── Catalogue fournisseur ────────────────────────────────────────────────────
const catalogue = ref<CatalogueTarif[]>([]);
const catalogueLoading = ref(false);
const showTarifModal = ref(false);
const editingTarif = ref<CatalogueTarif | null>(null);
const deletingTarif = ref<number | null>(null);

const tarifForm = ref({
    article_id: '' as number | '',
    unit_price: '' as number | '',
    reference_fournisseur: '',
    delai_livraison_jours: '' as number | '',
    valide_jusqu_au: '',
    notes: '',
    is_active: true,
});
const tarifErrors = ref<Record<string, string>>({});
const tarifSaving = ref(false);

const loadCatalogue = async () => {
    catalogueLoading.value = true;
    try {
        const res = await axios.get<CatalogueTarif[]>(`/admin/fournisseurs/${props.fournisseur.id}/catalogue`);
        catalogue.value = res.data;
    } finally {
        catalogueLoading.value = false;
    }
};

onMounted(loadCatalogue);

const openAddTarif = () => {
    editingTarif.value = null;
    tarifForm.value = { article_id: '', unit_price: '', reference_fournisseur: '', delai_livraison_jours: '', valide_jusqu_au: '', notes: '', is_active: true };
    tarifErrors.value = {};
    showTarifModal.value = true;
};

const openEditTarif = (t: CatalogueTarif) => {
    editingTarif.value = t;
    tarifForm.value = {
        article_id: t.article_id,
        unit_price: t.unit_price,
        reference_fournisseur: t.reference_fournisseur ?? '',
        delai_livraison_jours: t.delai_livraison_jours ?? '',
        valide_jusqu_au: t.valide_jusqu_au ?? '',
        notes: t.notes ?? '',
        is_active: t.is_active,
    };
    tarifErrors.value = {};
    showTarifModal.value = true;
};

const saveTarif = async () => {
    tarifSaving.value = true;
    tarifErrors.value = {};
    try {
        if (editingTarif.value) {
            await axios.put(`/admin/fournisseurs/${props.fournisseur.id}/catalogue/${editingTarif.value.id}`, tarifForm.value);
        } else {
            await axios.post(`/admin/fournisseurs/${props.fournisseur.id}/catalogue`, tarifForm.value);
        }
        showTarifModal.value = false;
        await loadCatalogue();
    } catch (err: any) {
        if (err.response?.status === 422) {
            const raw = err.response.data.errors ?? {};
            Object.keys(raw).forEach(k => { tarifErrors.value[k] = raw[k][0]; });
        }
    } finally {
        tarifSaving.value = false;
    }
};

const destroyTarif = async (t: CatalogueTarif) => {
    if (!confirm(`Supprimer le tarif pour "${t.article_name}" ?`)) return;
    deletingTarif.value = t.id;
    try {
        await axios.delete(`/admin/fournisseurs/${props.fournisseur.id}/catalogue/${t.id}`);
        catalogue.value = catalogue.value.filter(x => x.id !== t.id);
    } finally {
        deletingTarif.value = null;
    }
};

const formatPrice = (v: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(v);

// Articles non encore référencés dans le catalogue (pour le select d'ajout)
const articlesDisponibles = computed(() => {
    if (editingTarif.value) return props.articles;
    const ids = new Set(catalogue.value.map(t => t.article_id));
    return props.articles.filter(a => !ids.has(a.id) && a.is_active);
});

// ─── Import CSV / Excel ───────────────────────────────────────────────────────
type ImportStep = 'upload' | 'mapping' | 'rapport';

const showImportModal  = ref(false);
const importStep       = ref<ImportStep>('upload');

// Étape 1 — upload
const importFile       = ref<File | null>(null);
const importDragging   = ref(false);
const importParsing    = ref(false);
const importParseError = ref('');

// Étape 2 — mapping
const importHeaders    = ref<string[]>([]);
const importPreview    = ref<(string | number | null)[][]>([]);
const importTotalRows  = ref(0);
const importTempKey    = ref('');
const importExtension  = ref('');
const importConfirming = ref(false);
const importConfirmErr = ref('');

const importMapping = ref({
    reference_article:     '',
    prix:                  '',
    reference_fournisseur: '',
    delai_livraison_jours: '',
    valide_jusqu_au:       '',
    notes:                 '',
});

// Étape 3 — rapport
const importResult = ref<{ imported: number; updated: number; errors: { row: number; message: string }[] } | null>(null);

const resetImport = () => {
    importStep.value        = 'upload';
    importFile.value        = null;
    importParsing.value     = false;
    importParseError.value  = '';
    importHeaders.value     = [];
    importPreview.value     = [];
    importTempKey.value     = '';
    importExtension.value   = '';
    importConfirmErr.value  = '';
    importMapping.value     = { reference_article: '', prix: '', reference_fournisseur: '', delai_livraison_jours: '', valide_jusqu_au: '', notes: '' };
    importResult.value      = null;
};

const openImportModal = () => { resetImport(); showImportModal.value = true; };

const handleImportDrop = (e: DragEvent) => {
    importDragging.value = false;
    const f = e.dataTransfer?.files?.[0];
    if (f) importFile.value = f;
};

const handleImportSelect = (e: Event) => {
    const f = (e.target as HTMLInputElement).files?.[0];
    if (f) importFile.value = f;
};

const autoDetect = (candidates: string[]): string =>
    importHeaders.value.find(h => candidates.some(c => h.toLowerCase().includes(c.toLowerCase()))) ?? '';

const parseImportFile = async () => {
    if (!importFile.value) return;
    importParsing.value    = true;
    importParseError.value = '';
    const fd = new FormData();
    fd.append('file', importFile.value);
    try {
        const res = await axios.post(`/admin/fournisseurs/${props.fournisseur.id}/catalogue/import/parse`, fd);
        importHeaders.value   = res.data.headers;
        importPreview.value   = res.data.preview;
        importTotalRows.value = res.data.total_rows;
        importTempKey.value   = res.data.temp_key;
        importExtension.value = res.data.extension;
        importMapping.value = {
            reference_article:     autoDetect(['reference', 'ref', 'code', 'article']),
            prix:                  autoDetect(['prix', 'price', 'tarif', 'montant', 'cout']),
            reference_fournisseur: autoDetect(['ref_fns', 'ref_fournisseur', 'reference_fournisseur']),
            delai_livraison_jours: autoDetect(['delai', 'livraison', 'jours']),
            valide_jusqu_au:       autoDetect(['valide', 'validite', 'expiration']),
            notes:                 autoDetect(['notes', 'note', 'commentaire', 'remarque']),
        };
        importStep.value = 'mapping';
    } catch (err: any) {
        importParseError.value = err.response?.data?.message ?? 'Erreur lors de la lecture du fichier.';
    } finally {
        importParsing.value = false;
    }
};

const confirmImport = async () => {
    if (!importMapping.value.reference_article || !importMapping.value.prix) return;
    importConfirming.value = true;
    importConfirmErr.value = '';
    try {
        const res = await axios.post(`/admin/fournisseurs/${props.fournisseur.id}/catalogue/import/confirm`, {
            temp_key:  importTempKey.value,
            extension: importExtension.value,
            mapping:   importMapping.value,
        });
        importResult.value = res.data;
        importStep.value   = 'rapport';
        await loadCatalogue();
    } catch (err: any) {
        importConfirmErr.value = err.response?.data?.message ?? "Erreur lors de l'import.";
    } finally {
        importConfirming.value = false;
    }
};

const importMappingValid = computed(() =>
    importMapping.value.reference_article !== '' && importMapping.value.prix !== ''
);
</script>

<template>
    <Head :title="`Historique - ${fournisseur.name}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">Administration fournisseurs</p>
                    <h1 class="mt-1 text-2xl font-bold text-foreground">{{ fournisseur.name }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Fiche detaillee, statut d'homologation et historique des commandes liees a ce partenaire.
                    </p>
                </div>

                <div
                    class="inline-flex items-center gap-2 self-start rounded-xl border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary"
                >
                    <ShieldCheck class="h-4 w-4" />
                    Administration
                </div>
            </div>

            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div
                            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl"
                            :class="
                                fournisseur.is_approved
                                    ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-300'
                                    : 'bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-300'
                            "
                        >
                            <Truck class="h-8 w-8" />
                        </div>

                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-if="fournisseur.is_approved"
                                    class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300"
                                >
                                    <ShieldCheck class="h-3.5 w-3.5" />
                                    Homologue
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300"
                                >
                                    <ShieldAlert class="h-3.5 w-3.5" />
                                    Non homologue
                                </span>

                                <span
                                    class="rounded-full px-3 py-1 text-xs font-medium"
                                    :class="
                                        fournisseur.is_active
                                            ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ fournisseur.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>

                            <p class="mt-3 text-sm font-semibold text-foreground">Code fournisseur</p>
                            <p class="font-mono text-sm text-muted-foreground">{{ fournisseur.code }}</p>

                            <div class="mt-4 grid gap-2 sm:grid-cols-2">
                                <div
                                    v-if="fournisseur.email"
                                    class="flex items-center gap-2 rounded-xl border bg-muted/20 px-3 py-2 text-sm text-muted-foreground"
                                >
                                    <Mail class="h-4 w-4 shrink-0" />
                                    <span class="truncate">{{ fournisseur.email }}</span>
                                </div>
                                <div
                                    v-if="fournisseur.phone"
                                    class="flex items-center gap-2 rounded-xl border bg-muted/20 px-3 py-2 text-sm text-muted-foreground"
                                >
                                    <Phone class="h-4 w-4 shrink-0" />
                                    <span>{{ fournisseur.phone }}</span>
                                </div>
                                <div
                                    v-if="fournisseur.city"
                                    class="flex items-center gap-2 rounded-xl border bg-muted/20 px-3 py-2 text-sm text-muted-foreground sm:col-span-2"
                                >
                                    <MapPin class="h-4 w-4 shrink-0" />
                                    <span>{{ fournisseur.city }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 xl:w-[360px]">
                        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Depenses totales</p>
                                <p class="mt-3 text-xl font-bold text-foreground">{{ formatAmountShort(totalSpend) }} FCFA</p>
                                <p class="mt-1 text-xs text-muted-foreground">toutes commandes confondues</p>
                            </div>

                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Taux d'approbation</p>
                                <p class="mt-3 text-xl font-bold text-foreground">{{ approvalRate }}%</p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ approvedOrders.length }} commande(s) approuvee(s)</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <Link
                                :href="route('admin.fournisseurs.index')"
                                class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                            >
                                <ArrowLeft class="h-4 w-4" />
                                Retour
                            </Link>
                            <Link
                                :href="route('admin.fournisseurs.edit', fournisseur.id)"
                                class="inline-flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-sm font-semibold text-amber-700 transition-colors hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300 dark:hover:bg-amber-950/50"
                            >
                                <Pencil class="h-4 w-4" />
                                Modifier
                            </Link>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Commandes</p>
                        <div class="rounded-xl bg-blue-50 p-2 text-blue-600 dark:bg-blue-950/30 dark:text-blue-300">
                            <FileStack class="h-4 w-4" />
                        </div>
                    </div>
                    <p class="mt-3 text-2xl font-bold text-foreground">{{ stats.total }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">historique total</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Budget approuve</p>
                        <div class="rounded-xl bg-emerald-50 p-2 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-300">
                            <Wallet class="h-4 w-4" />
                        </div>
                    </div>
                    <p class="mt-3 text-2xl font-bold text-foreground">{{ formatAmountShort(stats.budget_approved) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">FCFA deja valides</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">En attente</p>
                        <div class="rounded-xl bg-amber-50 p-2 text-amber-600 dark:bg-amber-950/30 dark:text-amber-300">
                            <Clock class="h-4 w-4" />
                        </div>
                    </div>
                    <p class="mt-3 text-2xl font-bold text-foreground">{{ stats.pending }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ formatAmountShort(stats.budget_pending) }} FCFA a arbitrer</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Panier moyen</p>
                        <div class="rounded-xl bg-violet-50 p-2 text-violet-600 dark:bg-violet-950/30 dark:text-violet-300">
                            <CheckCircle2 class="h-4 w-4" />
                        </div>
                    </div>
                    <p class="mt-3 text-2xl font-bold text-foreground">{{ formatAmountShort(Math.round(averageOrderAmount)) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">FCFA par commande</p>
                </div>
            </section>

            <!-- Onglets -->
            <div class="flex gap-1 rounded-xl border bg-card p-1 shadow-sm w-fit">
                <button
                    type="button"
                    @click="activeTab = 'commandes'"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors"
                    :class="activeTab === 'commandes'
                        ? 'bg-primary text-primary-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground hover:bg-muted'"
                >
                    <FileText class="h-4 w-4" />
                    Commandes
                    <span class="rounded-full bg-white/20 px-1.5 py-0.5 text-xs" :class="activeTab === 'commandes' ? '' : 'bg-muted'">
                        {{ stats.total }}
                    </span>
                </button>
                <button
                    type="button"
                    @click="activeTab = 'catalogue'"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition-colors"
                    :class="activeTab === 'catalogue'
                        ? 'bg-primary text-primary-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground hover:bg-muted'"
                >
                    <Tag class="h-4 w-4" />
                    Catalogue des prix
                    <span class="rounded-full px-1.5 py-0.5 text-xs" :class="activeTab === 'catalogue' ? 'bg-white/20' : 'bg-muted text-muted-foreground'">
                        {{ catalogue.length }}
                    </span>
                </button>
            </div>

            <!-- Onglet Commandes -->
            <div v-show="activeTab === 'commandes'" class="grid gap-6 xl:grid-cols-[0.92fr_1.08fr]">
                <div class="space-y-6">
                    <section class="rounded-2xl border bg-card p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-primary/10 p-2 text-primary">
                                <Truck class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-foreground">Profil fournisseur</h2>
                                <p class="text-sm text-muted-foreground">Informations utiles pour le suivi administratif et les demandes d'achat.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3">
                            <div class="rounded-2xl border bg-muted/20 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Identite</p>
                                <div class="mt-3 grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <p class="text-xs text-muted-foreground">Nom</p>
                                        <p class="text-sm font-semibold text-foreground">{{ fournisseur.name }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Code</p>
                                        <p class="font-mono text-sm text-foreground">{{ fournisseur.code }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Email</p>
                                        <p class="text-sm font-medium text-foreground">{{ fournisseur.email || 'Non renseigne' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Telephone</p>
                                        <p class="text-sm font-medium text-foreground">{{ fournisseur.phone || 'Non renseigne' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Ville</p>
                                        <p class="text-sm font-medium text-foreground">{{ fournisseur.city || 'Non renseignee' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Adresse</p>
                                        <p class="text-sm font-medium text-foreground">{{ fournisseur.address || 'Non renseignee' }}</p>
                                    </div>
                                </div>
                            </div>

                            <div
                                class="rounded-2xl border p-4"
                                :class="
                                    fournisseur.is_approved
                                        ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/30'
                                        : 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30'
                                "
                            >
                                <div class="flex items-start gap-3">
                                    <ShieldCheck
                                        v-if="fournisseur.is_approved"
                                        class="mt-0.5 h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-300"
                                    />
                                    <ShieldAlert v-else class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-300" />

                                    <div>
                                        <p
                                            class="text-sm font-semibold"
                                            :class="
                                                fournisseur.is_approved
                                                    ? 'text-emerald-800 dark:text-emerald-200'
                                                    : 'text-amber-800 dark:text-amber-200'
                                            "
                                        >
                                            {{ fournisseur.is_approved ? 'Fournisseur homologue' : 'Fournisseur non homologue' }}
                                        </p>
                                        <p
                                            class="mt-1 text-sm"
                                            :class="
                                                fournisseur.is_approved
                                                    ? 'text-emerald-700 dark:text-emerald-300'
                                                    : 'text-amber-700 dark:text-amber-300'
                                            "
                                        >
                                            {{
                                                fournisseur.is_approved
                                                    ? 'Les commandes associees peuvent etre traitees sans alerte supplementaire.'
                                                    : "Les prochaines commandes afficheront une alerte tant que l'homologation reste en attente."
                                            }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border bg-card p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-violet-50 p-2 text-violet-600 dark:bg-violet-950/30 dark:text-violet-300">
                                <CalendarDays class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-foreground">Activite recente</h2>
                                <p class="text-sm text-muted-foreground">Lecture rapide des derniers signaux lies a ce fournisseur.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 md:grid-cols-3 xl:grid-cols-1">
                            <div class="rounded-2xl border bg-muted/20 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Derniere commande</p>
                                <p class="mt-3 text-sm font-semibold text-foreground">
                                    {{ recentOrder ? recentOrder.title : 'Aucune commande' }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ recentOrder ? formatDate(recentOrder.created_at) : 'Pas encore d activite' }}
                                </p>
                            </div>

                            <div class="rounded-2xl border bg-muted/20 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Approuvees</p>
                                <p class="mt-3 text-sm font-semibold text-foreground">{{ approvedOrders.length }} commande(s)</p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ rejectedOrders.length }} refusee(s), {{ pendingOrders.length }} en attente
                                </p>
                            </div>

                            <div class="rounded-2xl border bg-muted/20 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Montant moyen</p>
                                <p class="mt-3 text-sm font-semibold text-foreground">{{ formatAmount(averageOrderAmount) }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">base sur l'historique disponible</p>
                            </div>
                        </div>
                    </section>
                </div>

                <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                    <div class="flex flex-col gap-2 border-b px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="flex items-center gap-2 text-base font-semibold text-foreground">
                                <FileText class="h-4 w-4 text-muted-foreground" />
                                Historique des commandes
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">{{ orders.length }} commande(s) rattachee(s) a ce fournisseur.</p>
                        </div>

                        <div class="inline-flex items-center gap-2 rounded-xl border bg-muted/20 px-3 py-2 text-xs font-medium text-muted-foreground">
                            <Wallet class="h-4 w-4" />
                            Total: {{ formatAmount(totalSpend) }}
                        </div>
                    </div>

                    <div v-if="orders.length === 0" class="px-5 py-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-muted/40 text-muted-foreground">
                            <FileStack class="h-6 w-6" />
                        </div>
                        <p class="mt-4 text-sm font-semibold text-foreground">Aucune commande associee</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            L'historique d'achat apparaitra ici des qu'une commande utilisera ce fournisseur.
                        </p>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-muted/20">
                                <tr class="border-b">
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Commande</th>
                                    <th
                                        class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground md:table-cell"
                                    >
                                        Demandeur
                                    </th>
                                    <th
                                        class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground sm:table-cell"
                                    >
                                        Société
                                    </th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Montant</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Statut</th>
                                    <th
                                        class="hidden px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground sm:table-cell"
                                    >
                                        Date
                                    </th>
                                    <th class="px-4 py-3" />
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-border/60">
                                <tr v-for="order in orders" :key="order.id" class="transition-colors hover:bg-muted/20">
                                    <td class="px-5 py-4 align-top">
                                        <p class="max-w-[240px] truncate font-semibold text-foreground">{{ order.title }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">#{{ order.id }}</p>
                                    </td>
                                    <td class="hidden px-4 py-4 align-top text-sm text-muted-foreground md:table-cell">
                                        {{ order.user?.name ?? '-' }}
                                    </td>
                                    <td class="hidden px-4 py-4 align-top text-sm text-muted-foreground sm:table-cell">
                                        {{ order.boutique?.name ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 text-right align-top font-semibold text-foreground">
                                        {{ formatAmount(Number(order.amount)) }}
                                    </td>
                                    <td class="px-4 py-4 text-center align-top">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                            :class="statusConfig[order.status]?.badge"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status]?.dot" />
                                            {{ statusConfig[order.status]?.label }}
                                        </span>
                                    </td>
                                    <td class="hidden px-4 py-4 text-center align-top text-xs text-muted-foreground sm:table-cell">
                                        {{ formatDate(order.created_at) }}
                                    </td>
                                    <td class="px-4 py-4 text-right align-top">
                                        <Link
                                            :href="route('validations.show', order.id)"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-100 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300 dark:hover:bg-blue-950/50"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                            Voir
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <!-- Onglet Catalogue des prix -->
            <div v-show="activeTab === 'catalogue'" class="space-y-4">
                <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                    <div class="flex flex-col gap-2 border-b px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <h2 class="flex items-center gap-2 text-base font-semibold text-foreground">
                                <Tag class="h-4 w-4 text-muted-foreground" />
                                Catalogue des prix
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Tarifs proposes par ce fournisseur, utilises pour la comparaison automatique lors des commandes.
                            </p>
                        </div>
                        <div class="flex flex-wrap items-center gap-2 self-start">
                            <a
                                :href="`/admin/fournisseurs/${fournisseur.id}/catalogue/template`"
                                download
                                class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                            >
                                <Download class="h-4 w-4" />
                                Modèle CSV
                            </a>
                            <button
                                type="button"
                                @click="openImportModal"
                                class="inline-flex items-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-700 transition-colors hover:bg-violet-100 dark:border-violet-900 dark:bg-violet-950/30 dark:text-violet-300 dark:hover:bg-violet-950/50"
                            >
                                <FileUp class="h-4 w-4" />
                                Importer CSV/Excel
                            </button>
                            <button
                                type="button"
                                @click="openAddTarif"
                                :disabled="articlesDisponibles.length === 0"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-50"
                            >
                                <Plus class="h-4 w-4" />
                                Ajouter un tarif
                            </button>
                        </div>
                    </div>

                    <!-- Chargement -->
                    <div v-if="catalogueLoading" class="flex items-center justify-center gap-2 py-12 text-sm text-muted-foreground">
                        <div class="h-5 w-5 animate-spin rounded-full border-2 border-primary border-t-transparent" />
                        Chargement…
                    </div>

                    <!-- Vide -->
                    <div v-else-if="catalogue.length === 0" class="px-5 py-12 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-muted/40 text-muted-foreground">
                            <Tag class="h-6 w-6" />
                        </div>
                        <p class="mt-4 text-sm font-semibold text-foreground">Aucun tarif enregistre</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Ajoutez les prix proposes par ce fournisseur pour chaque article afin d'activer la comparaison automatique.
                        </p>
                    </div>

                    <!-- Tableau -->
                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-muted/20">
                                <tr class="border-b">
                                    <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Article</th>
                                    <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground md:table-cell">Categorie</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Prix unitaire</th>
                                    <th class="hidden px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground sm:table-cell">Delai</th>
                                    <th class="hidden px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground lg:table-cell">Valide jusqu'au</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Statut</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border/60">
                                <tr v-for="t in catalogue" :key="t.id" class="transition-colors hover:bg-muted/20">
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                                <Package class="h-4 w-4" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="truncate font-semibold text-foreground">{{ t.article_name }}</p>
                                                <p class="mt-0.5 font-mono text-xs text-muted-foreground">
                                                    {{ t.article_reference ?? 'Sans ref.' }} · {{ t.article_unit }}
                                                    <span v-if="t.reference_fournisseur" class="ml-1">· Réf.fns: {{ t.reference_fournisseur }}</span>
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden px-4 py-4 md:table-cell">
                                        <span v-if="t.category_name" class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300">
                                            {{ t.category_name }}
                                        </span>
                                        <span v-else class="text-xs text-muted-foreground">—</span>
                                    </td>
                                    <td class="px-4 py-4 text-right font-bold text-foreground">{{ formatPrice(t.unit_price) }}</td>
                                    <td class="hidden px-4 py-4 text-center text-sm text-muted-foreground sm:table-cell">
                                        <span v-if="t.delai_livraison_jours" class="inline-flex items-center gap-1">
                                            <Clock class="h-3.5 w-3.5" />
                                            {{ t.delai_livraison_jours }}j
                                        </span>
                                        <span v-else>—</span>
                                    </td>
                                    <td class="hidden px-4 py-4 text-center text-xs text-muted-foreground lg:table-cell">
                                        {{ t.valide_jusqu_au ? new Date(t.valide_jusqu_au).toLocaleDateString('fr-FR') : '—' }}
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <span
                                            class="rounded-full px-2.5 py-1 text-xs font-medium"
                                            :class="t.is_active ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300' : 'bg-muted text-muted-foreground'"
                                        >
                                            {{ t.is_active ? 'Actif' : 'Inactif' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button
                                                type="button"
                                                @click="openEditTarif(t)"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300 dark:hover:bg-amber-950/50"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                                Modifier
                                            </button>
                                            <button
                                                type="button"
                                                @click="destroyTarif(t)"
                                                :disabled="deletingTarif === t.id"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 transition-colors hover:bg-red-100 disabled:opacity-50 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300 dark:hover:bg-red-950/50"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                                Supprimer
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>

    <!-- ─── Modal Import CSV / Excel ────────────────────────────────────── -->
    <Teleport to="body">
        <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0" enter-to-class="opacity-100" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showImportModal = false" />
                <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 scale-95 translate-y-2" enter-to-class="opacity-100 scale-100 translate-y-0" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 scale-100 translate-y-0" leave-to-class="opacity-0 scale-95 translate-y-2">
                    <div v-if="showImportModal" class="relative flex max-h-[92vh] w-full max-w-2xl flex-col rounded-2xl bg-card shadow-2xl">

                        <!-- En-tête -->
                        <div class="flex shrink-0 items-center justify-between border-b px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 dark:bg-violet-950/30">
                                    <FileUp class="h-5 w-5 text-violet-600 dark:text-violet-300" />
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-foreground">Import du catalogue</h2>
                                    <p class="text-xs text-muted-foreground">CSV ou Excel — jusqu'à 10 Mo</p>
                                </div>
                            </div>
                            <!-- Fil d'étapes -->
                            <div class="hidden items-center gap-1 sm:flex">
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="importStep === 'upload' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'">1 · Fichier</span>
                                <ArrowRight class="h-3.5 w-3.5 text-muted-foreground" />
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="importStep === 'mapping' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'">2 · Colonnes</span>
                                <ArrowRight class="h-3.5 w-3.5 text-muted-foreground" />
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="importStep === 'rapport' ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'">3 · Rapport</span>
                            </div>
                            <button @click="showImportModal = false" class="ml-2 rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-muted">
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Corps -->
                        <div class="flex-1 overflow-y-auto p-6">

                            <!-- ── Étape 1 : Upload ── -->
                            <div v-if="importStep === 'upload'" class="space-y-4">
                                <!-- Zone drag & drop -->
                                <div
                                    class="relative flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed p-10 text-center transition-colors"
                                    :class="importDragging ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50 hover:bg-muted/30'"
                                    @dragover.prevent="importDragging = true"
                                    @dragleave.prevent="importDragging = false"
                                    @drop.prevent="handleImportDrop"
                                >
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-muted/60 text-muted-foreground">
                                        <Upload class="h-6 w-6" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-foreground">Glissez votre fichier ici</p>
                                        <p class="mt-1 text-sm text-muted-foreground">ou cliquez pour sélectionner (.csv, .xlsx, .xls)</p>
                                    </div>
                                    <input type="file" accept=".csv,.xlsx,.xls,.txt" class="absolute inset-0 cursor-pointer opacity-0" @change="handleImportSelect" />
                                </div>

                                <!-- Fichier sélectionné -->
                                <div v-if="importFile" class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 dark:border-emerald-900 dark:bg-emerald-950/30">
                                    <CheckCircle class="h-4 w-4 shrink-0 text-emerald-600 dark:text-emerald-300" />
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-semibold text-foreground">{{ importFile.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ (importFile.size / 1024).toFixed(1) }} Ko</p>
                                    </div>
                                    <button type="button" @click="importFile = null" class="ml-auto rounded-lg p-1 text-muted-foreground hover:bg-muted">
                                        <X class="h-3.5 w-3.5" />
                                    </button>
                                </div>

                                <!-- Erreur parse -->
                                <div v-if="importParseError" class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                                    <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                                    {{ importParseError }}
                                </div>

                                <!-- Aide colonnes attendues -->
                                <div class="rounded-xl border bg-muted/30 p-4">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Colonnes reconnues</p>
                                    <div class="mt-2 flex flex-wrap gap-2">
                                        <span v-for="col in ['reference_article *', 'prix *', 'reference_fournisseur', 'delai_livraison_jours', 'valide_jusqu_au', 'notes']" :key="col" class="rounded-lg border bg-background px-2.5 py-1 font-mono text-xs text-foreground">{{ col }}</span>
                                    </div>
                                    <p class="mt-2 text-xs text-muted-foreground">* obligatoires — vous pourrez mapper les colonnes à l'étape suivante.</p>
                                </div>
                            </div>

                            <!-- ── Étape 2 : Mapping ── -->
                            <div v-else-if="importStep === 'mapping'" class="space-y-5">
                                <p class="text-sm text-muted-foreground">
                                    Associez chaque champ à la colonne correspondante de votre fichier
                                    (<span class="font-semibold text-foreground">{{ importTotalRows }} ligne(s)</span> détectée(s)).
                                </p>

                                <!-- Grille de mapping -->
                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div v-for="field in [
                                        { key: 'reference_article',     label: 'Référence article',      required: true  },
                                        { key: 'prix',                   label: 'Prix unitaire (FCFA)',    required: true  },
                                        { key: 'reference_fournisseur',  label: 'Réf. fournisseur',        required: false },
                                        { key: 'delai_livraison_jours',  label: 'Délai livraison (jours)', required: false },
                                        { key: 'valide_jusqu_au',        label: 'Valide jusqu\'au',        required: false },
                                        { key: 'notes',                  label: 'Notes',                   required: false },
                                    ]" :key="field.key" class="flex flex-col gap-1.5">
                                        <label class="text-sm font-medium text-foreground">
                                            {{ field.label }}
                                            <span v-if="field.required" class="text-red-500"> *</span>
                                        </label>
                                        <div class="relative">
                                            <select
                                                v-model="(importMapping as any)[field.key]"
                                                class="h-10 w-full rounded-xl border border-input bg-background px-4 pr-10 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                                :class="{ 'border-red-400 bg-red-50/50': field.required && !(importMapping as any)[field.key] }"
                                            >
                                                <option value="">— Ignorer —</option>
                                                <option v-for="h in importHeaders" :key="h" :value="h">{{ h }}</option>
                                            </select>
                                            <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                        </div>
                                    </div>
                                </div>

                                <!-- Aperçu -->
                                <div>
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Aperçu (5 premières lignes)</p>
                                    <div class="overflow-x-auto rounded-xl border">
                                        <table class="min-w-full text-xs">
                                            <thead class="bg-muted/30">
                                                <tr>
                                                    <th v-for="h in importHeaders" :key="h" class="px-3 py-2 text-left font-semibold text-muted-foreground whitespace-nowrap">{{ h }}</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-border/60">
                                                <tr v-for="(row, ri) in importPreview" :key="ri" class="hover:bg-muted/20">
                                                    <td v-for="(cell, ci) in row" :key="ci" class="px-3 py-2 text-foreground whitespace-nowrap">{{ cell ?? '' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Erreur confirm -->
                                <div v-if="importConfirmErr" class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                                    <AlertCircle class="mt-0.5 h-4 w-4 shrink-0" />
                                    {{ importConfirmErr }}
                                </div>
                            </div>

                            <!-- ── Étape 3 : Rapport ── -->
                            <div v-else-if="importStep === 'rapport' && importResult" class="space-y-5">
                                <!-- Stats -->
                                <div class="grid grid-cols-3 gap-3">
                                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-center dark:border-emerald-900 dark:bg-emerald-950/30">
                                        <p class="text-2xl font-bold text-foreground">{{ importResult.imported }}</p>
                                        <p class="mt-1 text-xs font-semibold text-emerald-700 dark:text-emerald-300">Nouveaux</p>
                                    </div>
                                    <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-center dark:border-blue-900 dark:bg-blue-950/30">
                                        <p class="text-2xl font-bold text-foreground">{{ importResult.updated }}</p>
                                        <p class="mt-1 text-xs font-semibold text-blue-700 dark:text-blue-300">Mis à jour</p>
                                    </div>
                                    <div class="rounded-2xl border p-4 text-center" :class="importResult.errors.length ? 'border-red-200 bg-red-50 dark:border-red-900 dark:bg-red-950/30' : 'border-border bg-muted/20'">
                                        <p class="text-2xl font-bold text-foreground">{{ importResult.errors.length }}</p>
                                        <p class="mt-1 text-xs font-semibold" :class="importResult.errors.length ? 'text-red-700 dark:text-red-300' : 'text-muted-foreground'">Erreurs</p>
                                    </div>
                                </div>

                                <!-- Succès global -->
                                <div v-if="importResult.imported + importResult.updated > 0" class="flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300">
                                    <CheckCircle class="h-4 w-4 shrink-0" />
                                    {{ importResult.imported + importResult.updated }} tarif(s) importé(s) avec succès — le catalogue a été mis à jour.
                                </div>

                                <!-- Détail des erreurs -->
                                <div v-if="importResult.errors.length" class="space-y-2">
                                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Détail des erreurs</p>
                                    <div class="max-h-48 overflow-y-auto rounded-xl border">
                                        <table class="min-w-full text-xs">
                                            <thead class="bg-muted/30">
                                                <tr>
                                                    <th class="px-3 py-2 text-left font-semibold text-muted-foreground">Ligne</th>
                                                    <th class="px-3 py-2 text-left font-semibold text-muted-foreground">Message</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-border/60">
                                                <tr v-for="err in importResult.errors" :key="err.row" class="bg-red-50/50 dark:bg-red-950/10">
                                                    <td class="px-3 py-2 font-mono font-semibold text-red-700 dark:text-red-300">{{ err.row }}</td>
                                                    <td class="px-3 py-2 text-red-700 dark:text-red-300">{{ err.message }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pied -->
                        <div class="flex shrink-0 items-center justify-between border-t px-6 py-4">
                            <button v-if="importStep !== 'upload'" type="button" @click="importStep = importStep === 'rapport' ? 'mapping' : 'upload'" class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted">
                                Retour
                            </button>
                            <button v-else type="button" @click="showImportModal = false" class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted">
                                Annuler
                            </button>

                            <div class="flex items-center gap-2">
                                <button v-if="importStep === 'rapport'" type="button" @click="resetImport" class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted">
                                    Nouvel import
                                </button>
                                <button v-if="importStep === 'rapport'" type="button" @click="showImportModal = false" class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90">
                                    <CheckCircle class="h-4 w-4" />
                                    Fermer
                                </button>
                                <button v-else-if="importStep === 'upload'" type="button" @click="parseImportFile" :disabled="!importFile || importParsing" class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-60">
                                    <div v-if="importParsing" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                    <ArrowRight v-else class="h-4 w-4" />
                                    {{ importParsing ? 'Analyse…' : 'Continuer' }}
                                </button>
                                <button v-else-if="importStep === 'mapping'" type="button" @click="confirmImport" :disabled="!importMappingValid || importConfirming" class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-60">
                                    <div v-if="importConfirming" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                    <FileUp v-else class="h-4 w-4" />
                                    {{ importConfirming ? 'Import en cours…' : 'Lancer l\'import' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>

    <!-- ─── Modal Ajouter / Modifier tarif ──────────────────────────────── -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showTarifModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="showTarifModal = false" />

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                >
                    <div v-if="showTarifModal" class="relative flex max-h-[90vh] w-full max-w-lg flex-col rounded-2xl bg-card shadow-2xl">
                        <!-- En-tête -->
                        <div class="flex shrink-0 items-center justify-between border-b px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50 dark:bg-violet-950/30">
                                    <Tag class="h-5 w-5 text-violet-600 dark:text-violet-300" />
                                </div>
                                <h2 class="text-base font-semibold text-foreground">
                                    {{ editingTarif ? 'Modifier le tarif' : 'Ajouter un tarif' }}
                                </h2>
                            </div>
                            <button @click="showTarifModal = false" class="rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-muted">
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Corps -->
                        <div class="flex-1 overflow-y-auto p-6">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                                <!-- Article -->
                                <div class="flex flex-col gap-1.5 sm:col-span-2">
                                    <label class="text-sm font-medium text-foreground">Article <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <select
                                            v-model="tarifForm.article_id"
                                            :disabled="!!editingTarif"
                                            class="h-10 w-full rounded-xl border border-input bg-background px-4 pr-10 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 disabled:opacity-60"
                                            :class="{ 'border-red-400': tarifErrors.article_id }"
                                        >
                                            <option value="">— Choisir un article —</option>
                                            <option v-for="a in articlesDisponibles" :key="a.id" :value="a.id">
                                                {{ a.name }}{{ a.reference ? ` (${a.reference})` : '' }}
                                            </option>
                                        </select>
                                        <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    </div>
                                    <p v-if="tarifErrors.article_id" class="text-xs text-red-500">{{ tarifErrors.article_id }}</p>
                                </div>

                                <!-- Prix unitaire -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground">Prix unitaire (FCFA) <span class="text-red-500">*</span></label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground">FCFA</span>
                                        <input
                                            v-model.number="tarifForm.unit_price"
                                            type="number"
                                            min="0"
                                            step="1"
                                            placeholder="0"
                                            class="h-10 w-full rounded-xl border border-input bg-background pl-16 pr-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                            :class="{ 'border-red-400': tarifErrors.unit_price }"
                                        />
                                    </div>
                                    <p v-if="tarifErrors.unit_price" class="text-xs text-red-500">{{ tarifErrors.unit_price }}</p>
                                </div>

                                <!-- Référence fournisseur -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground">Réf. fournisseur</label>
                                    <input
                                        v-model="tarifForm.reference_fournisseur"
                                        type="text"
                                        placeholder="Ex : REF-FNS-001"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 font-mono text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    />
                                </div>

                                <!-- Délai de livraison -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground">Délai de livraison (jours)</label>
                                    <input
                                        v-model.number="tarifForm.delai_livraison_jours"
                                        type="number"
                                        min="0"
                                        placeholder="Ex : 7"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    />
                                </div>

                                <!-- Date de validité -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground">Valide jusqu'au</label>
                                    <input
                                        v-model="tarifForm.valide_jusqu_au"
                                        type="date"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                        :class="{ 'border-red-400': tarifErrors.valide_jusqu_au }"
                                    />
                                    <p v-if="tarifErrors.valide_jusqu_au" class="text-xs text-red-500">{{ tarifErrors.valide_jusqu_au }}</p>
                                </div>

                                <!-- Notes -->
                                <div class="flex flex-col gap-1.5 sm:col-span-2">
                                    <label class="text-sm font-medium text-foreground">Notes</label>
                                    <textarea
                                        v-model="tarifForm.notes"
                                        rows="2"
                                        placeholder="Conditions particulières, remises, incoterms…"
                                        class="w-full resize-none rounded-xl border border-input bg-background px-4 py-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    />
                                </div>

                                <!-- Actif -->
                                <div class="flex items-center gap-3 pt-1 sm:col-span-2">
                                    <button
                                        type="button"
                                        @click="tarifForm.is_active = !tarifForm.is_active"
                                        :class="tarifForm.is_active ? 'bg-primary' : 'bg-muted'"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                    >
                                        <span
                                            :class="tarifForm.is_active ? 'translate-x-6' : 'translate-x-1'"
                                            class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                                        />
                                    </button>
                                    <span class="text-sm font-medium text-foreground">Tarif actif</span>
                                </div>
                            </div>
                        </div>

                        <!-- Pied -->
                        <div class="flex shrink-0 items-center justify-end gap-3 border-t px-6 py-4">
                            <button
                                type="button"
                                @click="showTarifModal = false"
                                class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                            >
                                Annuler
                            </button>
                            <button
                                type="button"
                                @click="saveTarif"
                                :disabled="tarifSaving"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-70"
                            >
                                <div v-if="tarifSaving" class="h-4 w-4 animate-spin rounded-full border-2 border-white border-t-transparent" />
                                {{ editingTarif ? 'Enregistrer' : 'Ajouter le tarif' }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
