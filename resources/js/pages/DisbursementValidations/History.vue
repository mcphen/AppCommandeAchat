<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type DisbursementRequest, type PaginatedData, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Banknote, Building2, Clock, Download, Eye, Filter, History, RotateCcw, ShieldCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Validations DD', href: route('disbursement-validations.index') },
    { title: 'Historique', href: '#' },
];

const props = defineProps<{
    orders: PaginatedData<DisbursementRequest>;
    boutiques: Boutique[];
    filters: {
        status?: string;
        boutique_id?: string;
        date_from?: string;
        date_to?: string;
    };
}>();

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.role?.slug === 'admin');

const localFilters = ref({
    status: props.filters.status ?? '',
    boutique_id: props.filters.boutique_id ?? '',
    date_from: props.filters.date_from ?? '',
    date_to: props.filters.date_to ?? '',
});

const applyFilters = () => {
    const params: Record<string, string> = {};
    Object.entries(localFilters.value).forEach(([key, value]) => {
        if (value) params[key] = value;
    });
    router.get(route('disbursement-validations.history'), params, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.value = { status: '', boutique_id: '', date_from: '', date_to: '' };
    router.get(route('disbursement-validations.history'), {}, { preserveState: true, preserveScroll: true, replace: true });
};

const statusConfig = {
    draft: { label: 'Brouillon', classes: 'bg-slate-100 text-slate-700' },
    pending: { label: 'En attente', classes: 'bg-amber-50 text-amber-700' },
    needs_revision: { label: 'Révision', classes: 'bg-orange-50 text-orange-700' },
    approved: { label: 'Approuvée', classes: 'bg-emerald-50 text-emerald-700' },
    rejected: { label: 'Refusée', classes: 'bg-red-50 text-red-700' },
    cancelled: { label: 'Annulée', classes: 'bg-slate-100 text-slate-600' },
} as const;

const formatAmount = (value: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(value));

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    Object.entries(localFilters.value).forEach(([key, value]) => {
        if (value) params.set(key, value);
    });
    const query = params.toString();
    return query ? `${route('disbursement-validations.history.export')}?${query}` : route('disbursement-validations.history.export');
});

const visibleOrders = computed(() => props.orders.data);
const approvedCount = computed(() => visibleOrders.value.filter((order) => order.status === 'approved').length);
const rejectedCount = computed(() => visibleOrders.value.filter((order) => order.status === 'rejected').length);
const visibleAmountTotal = computed(() =>
    visibleOrders.value.reduce((sum, order) => sum + Number(order.amount || 0), 0),
);
const activeFilterCount = computed(() => Object.values(localFilters.value).filter((value) => value !== '').length);
</script>

<template>
    <Head title="Historique — Validations DD" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">
                        {{ isAdmin ? 'Pilotage historique DD' : 'Historique de validation' }}
                    </p>
                    <h1 class="mt-1 text-2xl font-bold text-foreground">Validations DD traitées</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ isAdmin ? 'Analysez les demandes déjà instruites et leur issue par boutique.' : "Retrouvez l'historique des demandes de décaissement que vous avez traitées." }}
                    </p>
                </div>

                <div class="inline-flex items-center gap-2 self-start rounded-xl border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary">
                    <ShieldCheck class="h-4 w-4" />
                    {{ isAdmin ? 'Vue administration' : 'Vue validateur' }}
                </div>
            </div>

            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <History class="h-8 w-8" />
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Historique validations DD</p>
                            <h2 class="mt-1 text-xl font-semibold text-foreground">Suivi des décisions déjà rendues</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Filtrez l'historique des validations et exportez les dossiers traités.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:w-[560px]">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Historique</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ orders.total }} demande(s)</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ boutiques.length }} boutique(s) couvertes</p>
                        </div>

                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Approuvées</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ approvedCount }} dossier(s)</p>
                            <p class="mt-1 text-xs text-muted-foreground">sur la page courante</p>
                        </div>

                        <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Résultats</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ visibleOrders.length }} ligne(s) visibles</p>
                            <p class="mt-1 text-xs text-muted-foreground">page {{ orders.current_page }} sur {{ orders.last_page }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ orders.total }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">dossier(s) retrouvés</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Approuvées</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600">{{ approvedCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">sur la page courante</p>
                </div>

                <div class="rounded-2xl border border-red-100 bg-red-50 p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-red-700">Refusées</p>
                    <p class="mt-2 text-2xl font-bold text-red-600">{{ rejectedCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">sur la page courante</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Montant page</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ formatAmount(visibleAmountTotal) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">sur les lignes affichées</p>
                </div>
            </section>

            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                                <Filter class="h-5 w-5" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-foreground">Filtres d'historique</p>
                                <p class="text-xs text-muted-foreground">Affinez les dossiers affichés par statut, boutique et période.</p>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-2">
                            <Link :href="route('disbursement-validations.index')" class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted">
                                File d'attente
                            </Link>
                            <a
                                :href="exportUrl"
                                class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                            >
                                <Download class="h-4 w-4" />
                                Export Excel
                            </a>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-muted-foreground">Statut</label>
                            <select v-model="localFilters.status" class="h-11 rounded-xl border border-input bg-background px-3 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                                <option value="">Tous</option>
                                <option value="pending">En attente</option>
                                <option value="approved">Approuvée</option>
                                <option value="rejected">Refusée</option>
                            </select>
                        </div>
                        <div v-if="boutiques.length" class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-muted-foreground">Boutique</label>
                            <div class="relative">
                                <select v-model="localFilters.boutique_id" class="h-11 w-full appearance-none rounded-xl border border-input bg-background px-3 pr-10 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                                    <option value="">Toutes</option>
                                    <option v-for="b in boutiques" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
                                </select>
                                <Building2 class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            </div>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-muted-foreground">Du</label>
                            <input v-model="localFilters.date_from" type="date" class="h-11 rounded-xl border border-input bg-background px-3 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-xs font-medium text-muted-foreground">Au</label>
                            <input v-model="localFilters.date_to" type="date" class="h-11 rounded-xl border border-input bg-background px-3 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2">
                        <button @click="resetFilters" class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted">
                            <RotateCcw class="h-4 w-4" /> Réinitialiser
                        </button>
                        <button @click="applyFilters" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90">
                            <Filter class="h-4 w-4" /> Appliquer
                            <span v-if="activeFilterCount > 0" class="rounded-full bg-primary-foreground/20 px-1.5 py-0.5 text-[10px] font-bold text-primary-foreground">
                                {{ activeFilterCount }}
                            </span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                <EmptyState v-if="orders.data.length === 0" title="Aucun résultat" description="Aucune demande de décaissement ne correspond à ces critères." :icon="Clock" />
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b bg-muted/30">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Référence</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Titre</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Demandeur</th>
                                <th class="px-4 py-3 text-right font-semibold text-foreground">Montant</th>
                                <th class="px-4 py-3 text-center font-semibold text-foreground">Statut</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Soumis le</th>
                                <th class="px-4 py-3 text-right font-semibold text-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="order in orders.data" :key="order.id" class="transition-colors hover:bg-muted/20">
                                <td class="px-4 py-3 font-mono text-xs text-foreground/80">{{ order.reference }}</td>
                                <td class="px-4 py-3 font-medium text-foreground">
                                    {{ order.title }}
                                    <p v-if="order.boutique" class="text-xs text-foreground/65">{{ order.boutique.name }}</p>
                                </td>
                                <td class="px-4 py-3 text-foreground/80">{{ order.user?.name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-medium tabular-nums text-foreground">{{ formatAmount(order.amount) }}</td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium" :class="statusConfig[order.status]?.classes">
                                        {{ statusConfig[order.status]?.label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-foreground/80">{{ order.submitted_at ? formatDate(order.submitted_at) : '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('disbursement-validations.history.show', { disbursement_request: order.uuid })" class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-medium text-foreground transition-colors hover:bg-muted">
                                        <Eye class="h-3.5 w-3.5" /> Détails
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="orders.last_page > 1" class="flex items-center justify-between border-t px-4 py-3 text-sm text-muted-foreground">
                    <span>Page {{ orders.current_page }} / {{ orders.last_page }} • {{ orders.total }} résultat(s)</span>
                    <div class="flex gap-1">
                        <Link v-if="orders.prev_page_url" :href="orders.prev_page_url" class="rounded-lg px-3 py-1.5 hover:bg-accent">Précédent</Link>
                        <Link v-if="orders.next_page_url" :href="orders.next_page_url" class="rounded-lg px-3 py-1.5 hover:bg-accent">Suivant</Link>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
