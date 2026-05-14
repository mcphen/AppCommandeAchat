<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type DisbursementRequest, type NatureOperation, type PaginatedData, type SharedData, type User } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Banknote, Building2, CheckCircle2, Download, Eye, FileText, Filter, Pencil, Plus, RotateCcw, Search, Send, ShieldCheck, Trash2, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Demandes de décaissement', href: route('disbursement-requests.index') },
];

const props = defineProps<{
    orders: PaginatedData<DisbursementRequest>;
    boutiques: Boutique[];
    demandeurs: Pick<User, 'id' | 'name'>[];
    natureOperations: NatureOperation[];
    levelsCount: number;
    filters: {
        boutique_id?: string;
        nature_operation_id?: string;
        status?: string;
        user_id?: string;
        date_from?: string;
        date_to?: string;
        search?: string;
    };
}>();

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.role?.slug === 'admin');
const canSeeBoutiqueUsers = computed(() => props.demandeurs.length > 1);

const showFilters = ref(
    !!(
        props.filters.boutique_id ||
        props.filters.nature_operation_id ||
        props.filters.status ||
        props.filters.user_id ||
        props.filters.date_from ||
        props.filters.date_to
    ),
);

const localFilters = ref({
    search: props.filters.search ?? '',
    boutique_id: props.filters.boutique_id ?? '',
    nature_operation_id: props.filters.nature_operation_id ?? '',
    status: props.filters.status ?? '',
    user_id: props.filters.user_id ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

const activeFilterCount = computed(() => Object.values(localFilters.value).filter((v) => v !== '').length);
const hasActiveFilters = computed(() => activeFilterCount.value > 0);
const visibleOrders = computed(() => props.orders.data);
const draftCount = computed(() => visibleOrders.value.filter((o) => o.status === 'draft').length);
const pendingCount = computed(() => visibleOrders.value.filter((o) => o.status === 'pending').length);
const approvedCount = computed(() => visibleOrders.value.filter((o) => o.status === 'approved').length);
const rejectedCount = computed(() => visibleOrders.value.filter((o) => o.status === 'rejected').length);
const cancelledCount = computed(() => visibleOrders.value.filter((o) => o.status === 'cancelled').length);
const visibleAmountTotal = computed(() =>
    visibleOrders.value
        .filter((o) => o.status !== 'cancelled')
        .reduce((sum, o) => sum + Number(o.amount || 0), 0),
);

const statusConfig = {
    draft: { label: 'Brouillon', classes: 'bg-slate-100 text-slate-700', dot: 'bg-slate-400' },
    pending: { label: 'En attente', classes: 'bg-amber-50 text-amber-700', dot: 'bg-amber-500' },
    needs_revision: { label: 'Révision demandée', classes: 'bg-orange-50 text-orange-700', dot: 'bg-orange-500' },
    approved: { label: 'Approuvée', classes: 'bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500' },
    rejected: { label: 'Refusée', classes: 'bg-red-50 text-red-700', dot: 'bg-red-500' },
    cancelled: { label: 'Annulée', classes: 'bg-red-100 text-red-700', dot: 'bg-red-500' },
} as const;

const formatAmount = (value: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(value));

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const boutiqueName = (id?: string) => props.boutiques.find((b) => String(b.id) === id)?.name ?? 'Boutique';
const demandeurName = (id?: string) => props.demandeurs.find((d) => String(d.id) === id)?.name ?? 'Demandeur';
const natureName = (id?: string) => props.natureOperations.find((n) => String(n.id) === id)?.name ?? `Nature ${id}`;

const applyFilters = () => {
    const params: Record<string, string> = {};
    Object.entries(localFilters.value).forEach(([k, v]) => { if (v) params[k] = v; });
    router.get(route('disbursement-requests.index'), params, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.value = { search: '', boutique_id: '', nature_operation_id: '', status: '', user_id: '', date_from: '', date_to: '' };
    router.get(route('disbursement-requests.index'), {}, { preserveState: true, preserveScroll: true, replace: true });
};

const exportUrl = () => {
    const params = new URLSearchParams();
    Object.entries(localFilters.value).forEach(([key, value]) => {
        if (value) params.set(key, value);
    });

    const query = params.toString();
    return query ? `${route('disbursement-requests.export')}?${query}` : route('disbursement-requests.export');
};

const canEdit = (order: DisbursementRequest) =>
    (order.user_id === user.value?.id || isAdmin.value) && (order.status === 'draft' || order.status === 'needs_revision');

const canDelete = (order: DisbursementRequest) =>
    (order.user_id === user.value?.id || isAdmin.value) && (order.status === 'draft' || order.status === 'rejected');

const canSubmit = (order: DisbursementRequest) =>
    (order.user_id === user.value?.id || isAdmin.value) && (order.status === 'draft' || order.status === 'needs_revision');

const confirmDelete = async (order: DisbursementRequest) => {
    const result = await Swal.fire({
        title: 'Supprimer cette demande ?',
        text: `"${order.title}" sera définitivement supprimée.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) router.delete(route('disbursement-requests.destroy', { disbursement_request: order.uuid }));
};

const submitOrder = async (order: DisbursementRequest) => {
    const result = await Swal.fire({
        title: 'Soumettre à la validation ?',
        text: `La demande "${order.title}" sera envoyée pour approbation.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Soumettre',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) router.post(route('disbursement-requests.submit', { disbursement_request: order.uuid }));
};
</script>

<template>
    <Head title="Demandes de décaissement" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">

            <!-- Header -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">
                        {{ isAdmin ? 'Pilotage des décaissements' : 'Suivi des décaissements' }}
                    </p>
                    <h1 class="mt-1 text-2xl font-bold text-foreground">
                        {{ isAdmin ? 'Demandes de décaissement du groupe' : (canSeeBoutiqueUsers ? 'Demandes de décaissement de ma boutique' : 'Mes demandes de décaissement') }}
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{
                            isAdmin
                                ? 'Visualisez les demandes du groupe, leur circuit de validation et leur niveau de progression.'
                                : (canSeeBoutiqueUsers
                                    ? 'Visualisez les demandes de tous les demandeurs de votre boutique pour éviter les doublons.'
                                    : 'Retrouvez vos demandes de décaissement, leurs statuts et les actions utiles depuis une seule page.')
                        }}
                    </p>
                </div>

                <div class="inline-flex items-center gap-2 self-start rounded-xl border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary">
                    <ShieldCheck class="h-4 w-4" />
                    {{ isAdmin ? 'Vue administration' : 'Vue demandeur' }}
                </div>
            </div>

            <!-- Summary card -->
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <Banknote class="h-8 w-8" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Portefeuille décaissements</p>
                            <h2 class="mt-1 text-xl font-semibold text-foreground">{{ isAdmin ? 'Pilotage central des demandes' : 'Tableau de suivi des décaissements' }}</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Filtrez et suivez les décaissements depuis une interface alignée avec le reste du back-office.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:w-[560px]">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Portefeuille</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ orders.total }} demande(s)</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ boutiques.length }} boutique(s) couvertes</p>
                        </div>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Validation</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ levelsCount }} niveau(x)</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ pendingCount }} dossier(s) en attente sur la page</p>
                        </div>
                        <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Résultats</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ visibleOrders.length }} ligne(s) visibles</p>
                            <p class="mt-1 text-xs text-muted-foreground">page {{ orders.current_page }} sur {{ orders.last_page }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Stats -->
            <section class="grid gap-3 sm:grid-cols-3 xl:grid-cols-5">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ orders.total }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">demande(s) recensées</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Brouillons</p>
                    <p class="mt-2 text-2xl font-bold text-slate-600">{{ draftCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">sur la page courante</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Approuvées</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ approvedCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">prêtes à être traitées</p>
                </div>
                <div class="rounded-2xl border border-red-100 bg-red-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Annulées</p>
                    <p class="mt-2 text-2xl font-bold text-red-600">{{ cancelledCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">sur la page courante</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Montant page</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ formatAmount(visibleAmountTotal) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">hors annulées</p>
                </div>
            </section>

            <!-- Search + Actions bar -->
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div class="relative lg:max-w-xl lg:flex-1">
                    <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="localFilters.search"
                        type="text"
                        placeholder="Rechercher par titre ou description..."
                        class="h-11 w-full rounded-xl border border-input bg-background pl-10 pr-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                        @keyup.enter="applyFilters"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a
                        :href="exportUrl()"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                    >
                        <Download class="h-4 w-4" />
                        Export Excel
                    </a>

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

                    <Link
                        :href="route('disbursement-requests.create')"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        Nouvelle demande
                    </Link>
                </div>
            </div>

            <!-- Filters panel -->
            <section class="rounded-2xl border bg-card shadow-sm">
                <div class="flex flex-col gap-2 border-b px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-foreground">Filtres et pilotage</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Affinez la liste par boutique, nature, statut, demandeur ou dates.</p>
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
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Boutique</label>
                                <select
                                    v-model="localFilters.boutique_id"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                >
                                    <option value="">Toutes</option>
                                    <option v-for="b in boutiques" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Nature</label>
                                <select
                                    v-model="localFilters.nature_operation_id"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                >
                                    <option value="">Toutes</option>
                                    <option v-for="n in natureOperations" :key="n.id" :value="String(n.id)">{{ n.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Statut</label>
                                <select
                                    v-model="localFilters.status"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                >
                                    <option value="">Tous</option>
                                    <option value="draft">Brouillon</option>
                                    <option value="pending">En attente</option>
                                    <option value="needs_revision">Révision demandée</option>
                                    <option value="approved">Approuvée</option>
                                    <option value="rejected">Refusée</option>
                                    <option value="cancelled">Annulée</option>
                                </select>
                            </div>

                            <div v-if="canSeeBoutiqueUsers && demandeurs.length">
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Demandeur</label>
                                <select
                                    v-model="localFilters.user_id"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                >
                                    <option value="">Tous</option>
                                    <option v-for="d in demandeurs" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Date création (de)</label>
                                <input
                                    v-model="localFilters.date_from"
                                    type="date"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                />
                            </div>

                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Date création (à)</label>
                                <input
                                    v-model="localFilters.date_to"
                                    type="date"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
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
                                    Réinitialiser
                                </button>
                                <button class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90" @click="applyFilters">
                                    Appliquer
                                </button>
                            </div>
                        </div>
                    </div>
                </Transition>

                <!-- Active filter chips -->
                <div v-if="hasActiveFilters" class="flex flex-wrap gap-2 px-5 py-4" :class="showFilters ? 'border-t' : ''">
                    <span v-if="filters.boutique_id" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Boutique : {{ boutiqueName(filters.boutique_id) }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.boutique_id = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.nature_operation_id" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Nature : {{ natureName(filters.nature_operation_id) }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.nature_operation_id = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.status" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Statut : {{ statusConfig[filters.status as keyof typeof statusConfig]?.label ?? filters.status }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.status = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.user_id" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Demandeur : {{ demandeurName(filters.user_id) }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.user_id = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.date_from || filters.date_to" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Période : {{ filters.date_from || '...' }} → {{ filters.date_to || '...' }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.date_from = ''; localFilters.date_to = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.search" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Recherche : {{ filters.search }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.search = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                </div>
            </section>

            <!-- Empty states -->
            <EmptyState
                v-if="orders.data.length === 0 && hasActiveFilters"
                :icon="Filter"
                icon-bg="bg-slate-100"
                icon-color="text-slate-400"
                title="Aucun résultat"
                description="Aucune demande ne correspond aux filtres appliqués. Ajustez les critères ou relancez une recherche plus large."
            >
                <button
                    class="inline-flex items-center gap-2 rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                    @click="resetFilters"
                >
                    <RotateCcw class="h-4 w-4" />
                    Réinitialiser les filtres
                </button>
            </EmptyState>

            <EmptyState
                v-else-if="orders.data.length === 0"
                :icon="Banknote"
                icon-bg="bg-primary/10"
                icon-color="text-primary"
                title="Aucune demande pour l instant"
                description="Créez votre première demande de décaissement puis suivez son avancement jusqu à l approbation."
                :action-href="route('disbursement-requests.create')"
                action-label="Créer une demande"
            />

            <!-- Table -->
            <section v-else class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="flex flex-col gap-2 border-b px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-foreground">Liste des demandes</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ orders.from }} à {{ orders.to }} sur {{ orders.total }} demande(s) affichée(s).
                        </p>
                    </div>
                    <div class="inline-flex items-center gap-2 rounded-xl border bg-muted/20 px-3 py-2 text-xs font-medium text-muted-foreground">
                        <CheckCircle2 class="h-4 w-4" />
                        {{ pendingCount }} en attente, {{ rejectedCount }} refusée(s) sur cette page
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/20">
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Demande</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground md:table-cell">Boutique</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground lg:table-cell">Nature</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground lg:table-cell">Montant</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Statut</th>
                                <th v-if="canSeeBoutiqueUsers" class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground xl:table-cell">Demandeur</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground xl:table-cell">Date</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Actions</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-border/60">
                            <tr
                                v-for="order in orders.data"
                                :key="order.id"
                                class="transition-colors hover:bg-muted/20"
                                :class="order.status === 'cancelled' ? 'opacity-60 bg-red-50/40' : ''"
                            >
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                            :class="order.status === 'cancelled' ? 'bg-red-100 text-red-400' : 'bg-primary/10 text-primary'"
                                        >
                                            <FileText class="h-4 w-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <p
                                                class="truncate font-semibold text-foreground"
                                                :class="order.status === 'cancelled' ? 'line-through text-muted-foreground' : ''"
                                            >{{ order.title }}</p>
                                            <p v-if="order.reference" class="font-mono text-xs text-muted-foreground">{{ order.reference }}</p>
                                            <p v-if="order.status === 'cancelled'" class="mt-0.5 text-xs font-semibold text-red-600">Demande annulée</p>
                                        </div>
                                    </div>
                                </td>

                                <td class="hidden px-4 py-4 md:table-cell">
                                    <div class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700">
                                        <Building2 class="h-3.5 w-3.5" />
                                        {{ order.boutique?.name ?? 'Non renseignée' }}
                                    </div>
                                </td>

                                <td class="hidden px-4 py-4 text-muted-foreground lg:table-cell">
                                    {{ order.nature_operation?.name ?? '—' }}
                                </td>

                                <td class="hidden px-4 py-4 font-medium text-foreground lg:table-cell">
                                    <span :class="order.status === 'cancelled' ? 'line-through text-muted-foreground' : ''">
                                        {{ formatAmount(order.amount) }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                        :class="statusConfig[order.status]?.classes ?? 'bg-muted text-muted-foreground'"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status]?.dot ?? 'bg-current'" />
                                        {{ statusConfig[order.status]?.label ?? order.status }}
                                    </span>
                                </td>

                                <td v-if="canSeeBoutiqueUsers" class="hidden px-4 py-4 xl:table-cell">
                                    <div v-if="order.user" class="flex items-center gap-2">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-violet-100 text-xs font-semibold text-violet-700">
                                            {{ order.user.name.split(' ').map((n: string) => n[0]).slice(0, 2).join('').toUpperCase() }}
                                        </div>
                                        <span class="truncate text-sm text-foreground">{{ order.user.name }}</span>
                                    </div>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </td>

                                <td class="hidden px-4 py-4 text-muted-foreground xl:table-cell">{{ formatDate(order.created_at) }}</td>

                                <td class="px-5 py-4 text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-2">
                                        <Link
                                            :href="route('disbursement-requests.show', { disbursement_request: order.uuid })"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-100"
                                            title="Voir"
                                        >
                                            <Eye class="h-3.5 w-3.5" />
                                            <span class="hidden 2xl:inline">Voir</span>
                                        </Link>

                                        <template v-if="canEdit(order) || canSubmit(order) || canDelete(order)">
                                            <Link
                                                v-if="canEdit(order)"
                                                :href="route('disbursement-requests.edit', { disbursement_request: order.uuid })"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100"
                                                title="Modifier"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                                <span class="hidden 2xl:inline">Modifier</span>
                                            </Link>

                                            <button
                                                v-if="canSubmit(order)"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-700 transition-colors hover:bg-emerald-100"
                                                title="Soumettre"
                                                @click="submitOrder(order)"
                                            >
                                                <Send class="h-3.5 w-3.5" />
                                                <span class="hidden 2xl:inline">Soumettre</span>
                                            </button>

                                            <button
                                                v-if="canDelete(order)"
                                                class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 transition-colors hover:bg-red-100"
                                                title="Supprimer"
                                                @click="confirmDelete(order)"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                                <span class="hidden 2xl:inline">Supprimer</span>
                                            </button>
                                        </template>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
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
