<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type PaginatedData, type Project, type PurchaseOrder, type SharedData, type User, type ValidationLevel } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { AlertCircle, Bell, Building2, CheckCircle2, ChevronDown, Download, Eye, FileSpreadsheet, FileText, Filter, HardHat, RotateCcw, Search, ShieldCheck, ShoppingCart, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Commandes', href: '/purchase-orders' },
];

const props = defineProps<{
    orders: PaginatedData<PurchaseOrder>;
    amountTotal: number;
    boutiques: Boutique[];
    projects: Pick<Project, 'id' | 'code' | 'name'>[];
    demandeurs: Pick<User, 'id' | 'name'>[];
    levels: Pick<ValidationLevel, 'id' | 'name' | 'order'>[];
    levelsCount: number;
    filters: {
        boutique_id?: string;
        project_id?: string;
        status?: string;
        user_id?: string;
        date_from?: string;
        date_to?: string;
        amount_min?: string;
        amount_max?: string;
        level_order?: string;
        search?: string;
    };
}>();

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.role?.slug === 'admin');

const showFilters = ref(
    !!(
        props.filters.boutique_id ||
        props.filters.project_id ||
        props.filters.status ||
        props.filters.user_id ||
        props.filters.date_from ||
        props.filters.date_to ||
        props.filters.amount_min ||
        props.filters.amount_max ||
        props.filters.level_order
    ),
);
const showExportMenu = ref(false);

const localFilters = ref({
    search: props.filters.search ?? '',
    boutique_id: props.filters.boutique_id ?? '',
    project_id: props.filters.project_id ?? '',
    status: props.filters.status ?? '',
    user_id: props.filters.user_id ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
    amount_min: props.filters.amount_min ?? '',
    amount_max: props.filters.amount_max ?? '',
    level_order: props.filters.level_order ?? '',
});

const activeFilterCount = computed(() => Object.values(localFilters.value).filter((value) => value !== '').length);
const hasActiveFilters = computed(() => activeFilterCount.value > 0);
const visibleOrders = computed(() => props.orders.data);
const draftCount = computed(() => visibleOrders.value.filter((order) => order.status === 'draft').length);
const pendingCount = computed(() => visibleOrders.value.filter((order) => order.status === 'pending').length);
const approvedCount = computed(() => visibleOrders.value.filter((order) => order.status === 'approved').length);
const rejectedCount = computed(() => visibleOrders.value.filter((order) => order.status === 'rejected').length);

const statusConfig = {
    draft: { label: 'Brouillon', classes: 'bg-slate-100 text-slate-700', dot: 'bg-slate-400' },
    pending: { label: 'En attente', classes: 'bg-amber-50 text-amber-700', dot: 'bg-amber-500' },
    needs_revision: { label: 'Revision demandee', classes: 'bg-orange-50 text-orange-700', dot: 'bg-orange-500' },
    approved: { label: 'Approuvee', classes: 'bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500' },
    rejected: { label: 'Refusee', classes: 'bg-red-50 text-red-700', dot: 'bg-red-500' },
} as const;

const formatAmount = (value: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(value));

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const boutiqueName = (id?: string) => props.boutiques.find((boutique) => String(boutique.id) === id)?.name ?? 'Société';
const projectName = (id?: string) => props.projects.find((project) => String(project.id) === id)?.name ?? 'Chantier';
const demandeurName = (id?: string) => props.demandeurs.find((demandeur) => String(demandeur.id) === id)?.name ?? 'Demandeur';
const levelName = (order?: string) => props.levels.find((level) => String(level.order) === order)?.name ?? `Niveau ${order}`;

const progressLabel = (order: PurchaseOrder) => {
    if (order.status === 'approved') return `Circuit termine (${props.levelsCount}/${props.levelsCount})`;
    if (order.status === 'rejected') return 'Validation interrompue';
    if (order.status === 'needs_revision') return 'Revision demandee';
    if (order.status === 'pending' && order.current_level_order) {
        const done = Math.max(order.current_level_order - 1, 0);
        return `${done}/${props.levelsCount} valide${done > 1 ? 's' : ''} - niveau ${order.current_level_order}`;
    }
    return 'Non soumise';
};

const validatorsSummary = (order: PurchaseOrder): string => {
    if (!order.validation_logs?.length) return '';
    return order.validation_logs
        .filter((log) => log.action === 'approved')
        .map((log) => {
            const initials = log.user?.name?.split(' ').map((name) => name[0]).join('').toUpperCase() ?? '?';
            const level = log.validation_level?.name ?? `N${log.validation_level_id}`;
            return `${level}: ${initials}.`;
        })
        .join(' - ');
};

const rejectedBy = (order: PurchaseOrder) => {
    if (order.status !== 'rejected') return null;
    return order.validation_logs?.find((log) => log.action === 'rejected') ?? null;
};

const applyFilters = () => {
    const params: Record<string, string> = {};
    Object.entries(localFilters.value).forEach(([key, value]) => {
        if (value) params[key] = value;
    });
    router.get(route('purchase-orders.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    localFilters.value = { search: '', boutique_id: '', project_id: '', status: '', user_id: '', date_from: '', date_to: '', amount_min: '', amount_max: '', level_order: '' };
    router.get(route('purchase-orders.index'), {}, { preserveState: true, preserveScroll: true, replace: true });
};

const exportUrl = (format: string) => {
    const params = new URLSearchParams();
    Object.entries(localFilters.value).forEach(([key, value]) => {
        if (value) params.append(key, value);
    });
    const qs = params.toString();
    return route('purchase-orders.export', { format }) + (qs ? `?${qs}` : '');
};

const openOrderInNewTab = (order: PurchaseOrder) => {
    window.open(route('purchase-orders.show', order.id), '_blank');
};

const remindDemandeur = async (order: PurchaseOrder) => {
    const result = await Swal.fire({
        title: 'Relancer le demandeur ?',
        text: `Un email de rappel sera envoyé à ${order.user?.name ?? 'le demandeur'} pour compléter et soumettre la commande "${order.title}".`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Relancer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) {
        router.post(route('purchase-orders.remind', order.id), {}, { preserveScroll: true, preserveState: true });
    }
};

</script>

<template>
    <Head :title="isAdmin ? 'Commandes du groupe' : 'Mes commandes'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">
                        {{ isAdmin ? 'Pilotage des achats' : 'Suivi des achats' }}
                    </p>
                    <h1 class="mt-1 text-2xl font-bold text-foreground">{{ isAdmin ? 'Commandes du groupe' : 'Mes commandes' }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            isAdmin
                                ? 'Visualisez les demandes du groupe, leur circuit de validation et leur niveau de progression.'
                                : 'Retrouvez vos demandes d achat, leurs statuts et les actions utiles depuis une seule page.'
                        }}
                    </p>
                </div>

                <div class="inline-flex items-center gap-2 self-start rounded-xl border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary">
                    <ShieldCheck class="h-4 w-4" />
                    {{ isAdmin ? 'Vue administration' : 'Vue demandeur' }}
                </div>
            </div>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ orders.total }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">commande(s) recensees</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Brouillons</p>
                    <p class="mt-2 text-2xl font-bold text-slate-600">{{ draftCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">sur la page courante</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Approuvees</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ approvedCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">pretes a etre traitees</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Montant</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ formatAmount(props.amountTotal) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">cumule de toutes les commandes filtrees</p>
                </div>
            </section>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="relative lg:max-w-xl lg:flex-1">
                    <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="localFilters.search"
                        type="text"
                        placeholder="Rechercher par titre, description ou numero..."
                        class="h-11 w-full rounded-xl border border-input bg-background pl-10 pr-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <div class="relative">
                        <div v-if="showExportMenu" class="fixed inset-0 z-10" @click="showExportMenu = false" />
                        <button
                            class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                            @click="showExportMenu = !showExportMenu"
                        >
                            <Download class="h-4 w-4" />
                            Exporter
                            <ChevronDown class="h-3.5 w-3.5 opacity-60" />
                        </button>
                        <Transition
                            enter-active-class="transition duration-100 ease-out"
                            enter-from-class="opacity-0 scale-95"
                            enter-to-class="opacity-100 scale-100"
                            leave-active-class="transition duration-75 ease-in"
                            leave-from-class="opacity-100 scale-100"
                            leave-to-class="opacity-0 scale-95"
                        >
                            <div v-if="showExportMenu" class="absolute right-0 top-full z-20 mt-2 w-48 overflow-hidden rounded-xl border bg-card shadow-lg">
                                <a :href="exportUrl('csv')" class="flex items-center gap-3 px-4 py-3 text-sm text-foreground transition-colors hover:bg-muted" @click="showExportMenu = false">
                                    <FileText class="h-4 w-4 text-muted-foreground" />
                                    Export CSV
                                </a>
                                <a :href="exportUrl('excel')" class="flex items-center gap-3 px-4 py-3 text-sm text-foreground transition-colors hover:bg-muted" @click="showExportMenu = false">
                                    <FileSpreadsheet class="h-4 w-4 text-emerald-600" />
                                    Export Excel
                                </a>
                                <a :href="exportUrl('pdf')" target="_blank" class="flex items-center gap-3 px-4 py-3 text-sm text-foreground transition-colors hover:bg-muted" @click="showExportMenu = false">
                                    <FileText class="h-4 w-4 text-red-500" />
                                    Export PDF
                                </a>
                            </div>
                        </Transition>
                    </div>

                    <button
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition-colors"
                        :class="showFilters ? 'border-primary bg-primary/5 text-primary' : 'text-foreground hover:bg-muted'"
                        @click="showFilters = !showFilters"
                    >
                        <Filter class="h-4 w-4" />
                        Filtres
                        <span v-if="activeFilterCount > 0" class="flex h-5 min-w-5 items-center justify-center rounded-full bg-primary px-1.5 text-[10px] font-bold text-primary-foreground">
                            {{ activeFilterCount }}
                        </span>
                    </button>

                    <button
                        class="inline-flex items-center gap-2 rounded-xl border border-primary/20 bg-primary/5 px-4 py-2.5 text-sm font-semibold text-primary transition-colors hover:bg-primary/10"
                        @click="applyFilters"
                    >
                        Rechercher
                    </button>

                </div>
            </div>
            <section class="rounded-2xl border bg-card shadow-sm">
                <div class="flex flex-col gap-2 border-b px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-foreground">Filtres et pilotage</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Affinez la liste par société, statut, demandeur, dates ou montant.</p>
                    </div>

                    <div class="inline-flex items-center gap-2 rounded-xl border bg-muted/20 px-3 py-2 text-xs font-medium text-muted-foreground">
                        <CheckCircle2 class="h-4 w-4" />
                        {{ levelsCount }} niveau(x) de validation
                    </div>
                </div>

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-1"
                >
                    <div v-if="showFilters" class="space-y-4 px-5 py-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Société</label>
                                <select
                                    v-model="localFilters.boutique_id"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                >
                                    <option value="">Toutes</option>
                                    <option v-for="boutique in boutiques" :key="boutique.id" :value="String(boutique.id)">{{ boutique.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Chantier</label>
                                <select
                                    v-model="localFilters.project_id"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                >
                                    <option value="">Tous</option>
                                    <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Statut</label>
                                <select
                                    v-model="localFilters.status"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    @change="localFilters.level_order = ''"
                                >
                                    <option value="">Tous</option>
                                    <option value="draft">Brouillon</option>
                                    <option value="pending">En attente</option>
                                    <option value="needs_revision">Revision demandee</option>
                                    <option value="approved">Approuvee</option>
                                    <option value="rejected">Refusee</option>
                                </select>
                            </div>

                            <div v-if="localFilters.status === 'pending'">
                                <label class="mb-1.5 block text-xs font-medium text-amber-600">Niveau de validation</label>
                                <select
                                    v-model="localFilters.level_order"
                                    class="h-10 w-full rounded-xl border border-amber-300 bg-amber-50 px-3 text-sm text-foreground transition-colors focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-300/40"
                                >
                                    <option value="">Tous les niveaux</option>
                                    <option v-for="level in levels" :key="level.id" :value="String(level.order)">Niveau {{ level.order }} - {{ level.name }}</option>
                                </select>
                            </div>

                            <div v-if="isAdmin">
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Demandeur</label>
                                <select
                                    v-model="localFilters.user_id"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                >
                                    <option value="">Tous</option>
                                    <option v-for="demandeur in demandeurs" :key="demandeur.id" :value="String(demandeur.id)">{{ demandeur.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Date creation (de)</label>
                                <input
                                    v-model="localFilters.date_from"
                                    type="date"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Date creation (a)</label>
                                <input
                                    v-model="localFilters.date_to"
                                    type="date"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Montant min (XOF)</label>
                                <input
                                    v-model="localFilters.amount_min"
                                    type="number"
                                    min="0"
                                    placeholder="0"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Montant max (XOF)</label>
                                <input
                                    v-model="localFilters.amount_max"
                                    type="number"
                                    min="0"
                                    placeholder="Illimite"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground placeholder:text-muted-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                />
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-center sm:justify-between">
                            <p class="text-xs text-muted-foreground">
                                {{ activeFilterCount }} filtre{{ activeFilterCount > 1 ? 's' : '' }} actif{{ activeFilterCount > 1 ? 's' : '' }}
                            </p>

                            <div class="flex flex-wrap items-center gap-2">
                                <button class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-muted" @click="resetFilters">
                                    <RotateCcw class="h-4 w-4" />
                                    Reinitialiser
                                </button>
                                <button class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90" @click="applyFilters">
                                    Appliquer
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>

                <div v-if="hasActiveFilters" class="flex flex-wrap gap-2 px-5 py-4" :class="showFilters ? 'border-t' : ''">
                    <span v-if="filters.boutique_id" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Société : {{ boutiqueName(filters.boutique_id) }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.boutique_id = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.project_id" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Chantier : {{ projectName(filters.project_id) }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.project_id = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.status" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Statut : {{ statusConfig[filters.status as keyof typeof statusConfig]?.label ?? filters.status }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.status = ''; localFilters.level_order = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.level_order" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Niveau : {{ levelName(filters.level_order) }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.level_order = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.user_id" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Demandeur : {{ demandeurName(filters.user_id) }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.user_id = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.date_from || filters.date_to" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Periode : {{ filters.date_from || '...' }} -> {{ filters.date_to || '...' }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.date_from = ''; localFilters.date_to = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.amount_min || filters.amount_max" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Montant : {{ filters.amount_min || '0' }} -> {{ filters.amount_max || 'illimite' }} XOF
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.amount_min = ''; localFilters.amount_max = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.search" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Recherche : {{ filters.search }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.search = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                </div>
            </section>
            <EmptyState
                v-if="orders.data.length === 0 && hasActiveFilters"
                :icon="Filter"
                icon-bg="bg-slate-100"
                icon-color="text-slate-400"
                title="Aucun resultat"
                description="Aucune commande ne correspond aux filtres appliques. Ajustez les criteres ou relancez une recherche plus large."
            >
                <button
                    class="inline-flex items-center gap-2 rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                    @click="resetFilters"
                >
                    <RotateCcw class="h-4 w-4" />
                    Reinitialiser les filtres
                </button>
            </EmptyState>

            <EmptyState
                v-else-if="orders.data.length === 0"
                :icon="ShoppingCart"
                icon-bg="bg-primary/10"
                icon-color="text-primary"
                title="Aucune commande pour l instant"
                description="Les commandes importees depuis Sage100 apparaitront ici des qu elles seront synchronisees."
            />

            <section v-else class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="flex flex-col gap-2 border-b px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-foreground">Liste des commandes</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ orders.from }} a {{ orders.to }} sur {{ orders.total }} commande(s) affichee(s).
                        </p>
                    </div>

                    <div class="inline-flex items-center gap-2 rounded-xl border bg-muted/20 px-3 py-2 text-xs font-medium text-muted-foreground">
                        <CheckCircle2 class="h-4 w-4" />
                        {{ pendingCount }} en attente, {{ rejectedCount }} refusee(s) sur cette page
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/20">
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Commande</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground md:table-cell">
                                    Société
                                </th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground lg:table-cell">
                                    Montant
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Statut</th>
                                <th v-if="isAdmin" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground xl:table-cell">
                                    Demandeur
                                </th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground xl:table-cell">
                                    Date
                                </th>
                                <th class="sticky right-0 z-10 bg-muted/95 px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground shadow-[-4px_0_6px_-4px_rgba(0,0,0,0.15)] backdrop-blur">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-border/60">
                            <tr
                                v-for="order in orders.data"
                                :key="order.id"
                                class="group cursor-pointer transition-colors hover:bg-muted/20"
                                @click="openOrderInNewTab(order)"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                            <FileText class="h-4 w-4" />
                                        </div>

                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-foreground">{{ order.title }}</p>
                                            <span v-if="order.project" class="mt-1 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 font-mono text-[11px] font-bold text-amber-700">
                                                <HardHat class="h-3 w-3" />
                                                {{ order.project.name }}
                                            </span>
                                            <p class="mt-1 text-xs text-muted-foreground">{{ progressLabel(order) }}</p>
                                            <p v-if="validatorsSummary(order)" class="mt-1 text-xs font-medium text-emerald-600">
                                                {{ validatorsSummary(order) }}
                                            </p>
                                            <p v-if="rejectedBy(order)" class="mt-1 text-xs font-medium text-red-500">
                                                Refuse par {{ rejectedBy(order)?.user?.name }} ({{ rejectedBy(order)?.validation_level?.name }})
                                            </p>
                                        </div>
                                    </div>
                                </td>

                                <td class="hidden px-4 py-4 md:table-cell">
                                    <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                        <Building2 class="h-3.5 w-3.5" />
                                        {{ order.boutique?.name ?? 'Non renseignee' }}
                                    </div>
                                </td>

                                <td class="hidden px-4 py-4 font-medium text-foreground lg:table-cell">{{ formatAmount(order.amount) }}</td>

                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="statusConfig[order.status]?.classes ?? 'bg-muted text-muted-foreground'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status]?.dot ?? 'bg-current'" />
                                        {{ statusConfig[order.status]?.label ?? order.status }}
                                    </span>
                                </td>

                                <td v-if="isAdmin" class="hidden px-4 py-4 xl:table-cell">
                                    <div v-if="order.user" class="flex items-center gap-2">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-semibold text-violet-700">
                                            {{ order.user.name.split(' ').map((name: string) => name[0]).slice(0, 2).join('').toUpperCase() }}
                                        </div>
                                        <span class="truncate text-sm text-foreground">{{ order.user.name }}</span>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground">-</span>
                                </td>

                                <td class="hidden px-4 py-4 text-muted-foreground xl:table-cell">{{ formatDate(order.order_date ?? order.created_at) }}</td>

                                <td
                                    class="sticky right-0 z-10 bg-card px-5 py-4 text-right shadow-[-4px_0_6px_-4px_rgba(0,0,0,0.15)] transition-colors group-hover:bg-muted/40"
                                    @click.stop
                                >
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <Link
                                            :href="route('purchase-orders.show', order.id)"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-100"
                                            title="Voir"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                            <span class="hidden 2xl:inline">Voir</span>
                                        </Link>

                                        <a
                                            :href="route('purchase-orders.pdf', order.id)"
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 transition-colors hover:bg-indigo-100"
                                            title="Telecharger PDF"
                                        >
                                            <Download class="h-3.5 w-3.5" />
                                            <span class="hidden 2xl:inline">PDF</span>
                                        </a>

                                        <button
                                            v-if="isAdmin && order.status === 'draft' && order.user?.role"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100"
                                            :title="order.last_reminder_sent_at ? `Dernière relance : ${formatDate(order.last_reminder_sent_at)}` : 'Relancer le demandeur'"
                                            @click="remindDemandeur(order)"
                                        >
                                            <Bell class="h-3.5 w-3.5" />
                                            <span class="hidden 2xl:inline">Relancer</span>
                                        </button>
                                        <span
                                            v-else-if="isAdmin && order.status === 'draft'"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700"
                                            title="Aucun demandeur identifié : complétez et soumettez cette commande vous-même."
                                        >
                                            <AlertCircle class="h-3.5 w-3.5" />
                                            <span class="hidden 2xl:inline">Sans demandeur</span>
                                        </span>

                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="orders.last_page > 1" class="flex flex-col items-center gap-3 border-t px-5 py-4 sm:flex-row sm:justify-between">
                    <p class="text-sm text-muted-foreground">{{ orders.from }}-{{ orders.to }} sur {{ orders.total }}</p>

                    <div class="flex flex-wrap items-center justify-center gap-1">
                        <Link
                            v-for="link in orders.links"
                            :key="link.label"
                            :href="link.url ?? '#'"
                            :class="[
                                'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                                link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted',
                                !link.url ? 'pointer-events-none opacity-40' : '',
                            ]"
                        >
                            <span v-html="link.label" />
                        </Link>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
