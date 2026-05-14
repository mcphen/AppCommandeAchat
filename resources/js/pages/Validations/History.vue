<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type PaginatedData, type PurchaseOrder, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Building2, Calendar, CheckCircle2, Clock, FileText, Filter,
    History, RotateCcw, XCircle, AlertCircle, Ban, FileEdit, Download,
} from 'lucide-vue-next';
import { computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Validations', href: '/validations' },
    { title: 'Historique', href: '/validations/historique' },
];

const props = defineProps<{
    orders: PaginatedData<PurchaseOrder>;
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

const statusConfig: Record<string, { label: string; classes: string; dot: string; icon: unknown }> = {
    draft:          { label: 'Brouillon',         classes: 'bg-slate-100 text-slate-600',    dot: 'bg-slate-400',   icon: FileEdit },
    pending:        { label: 'En attente',         classes: 'bg-amber-100 text-amber-700',    dot: 'bg-amber-500',   icon: Clock },
    needs_revision: { label: 'Révision demandée',  classes: 'bg-orange-100 text-orange-700',  dot: 'bg-orange-500',  icon: AlertCircle },
    approved:       { label: 'Approuvée',          classes: 'bg-emerald-100 text-emerald-700', dot: 'bg-emerald-500', icon: CheckCircle2 },
    rejected:       { label: 'Rejetée',            classes: 'bg-red-100 text-red-700',        dot: 'bg-red-500',     icon: XCircle },
    cancelled:      { label: 'Annulée',            classes: 'bg-rose-100 text-rose-700',      dot: 'bg-rose-500',    icon: Ban },
};

const statusOptions = [
    { value: '', label: 'Tous les statuts' },
    { value: 'pending',        label: 'En attente' },
    { value: 'approved',       label: 'Approuvées' },
    { value: 'rejected',       label: 'Rejetées' },
    { value: 'needs_revision', label: 'Révision demandée' },
    { value: 'cancelled',      label: 'Annulées' },
    { value: 'draft',          label: 'Brouillons' },
];

const formatAmount = (value: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(value));

const formatDate = (date: string | null | undefined) =>
    date ? new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' }) : '—';

const hasActiveFilters = computed(() =>
    !!props.filters.status || !!props.filters.boutique_id || !!props.filters.date_from || !!props.filters.date_to
);

const applyFilters = (partial: Record<string, string | undefined>) => {
    router.get(route('validations.history'), { ...props.filters, ...partial }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    router.get(route('validations.history'), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

// Résumé : combien de commandes par statut dans la page courante
const statCounts = computed(() => {
    const counts: Record<string, number> = {};
    for (const order of props.orders.data) {
        counts[order.status] = (counts[order.status] ?? 0) + 1;
    }
    return counts;
});

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    Object.entries(props.filters).forEach(([key, value]) => {
        if (value) params.set(key, value);
    });
    const query = params.toString();
    return query ? `${route('validations.history.export')}?${query}` : route('validations.history.export');
});
</script>

<template>
    <Head title="Historique des validations" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- En-tête -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h1 class="flex items-center gap-2 text-xl font-bold text-foreground sm:text-2xl">
                        <History class="h-6 w-6 text-primary" />
                        Historique des validations
                    </h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        <template v-if="user?.role?.slug === 'admin'">
                            Toutes les commandes — vue globale
                        </template>
                        <template v-else>
                            Toutes les commandes passées par votre niveau — <span class="font-medium text-foreground">{{ user?.validation_level?.name }}</span>
                        </template>
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2 rounded-xl border border-primary/20 bg-primary/5 px-4 py-2">
                    <FileText class="h-4 w-4 text-primary" />
                    <span class="text-sm font-semibold text-primary">{{ orders.total }}</span>
                    <span class="text-sm text-muted-foreground">commande{{ orders.total !== 1 ? 's' : '' }}</span>
                </div>
            </div>

            <div class="flex justify-end">
                <a
                    :href="exportUrl"
                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                >
                    <Download class="h-4 w-4" />
                    Export Excel
                </a>
            </div>

            <!-- Filtres -->
            <div class="rounded-2xl border bg-card p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Statut -->
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Statut</label>
                        <select
                            :value="filters.status ?? ''"
                            class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                            @change="applyFilters({ status: ($event.target as HTMLSelectElement).value || undefined })"
                        >
                            <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                        </select>
                    </div>

                    <!-- Boutique -->
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Boutique</label>
                        <select
                            :value="filters.boutique_id ?? ''"
                            class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                            @change="applyFilters({ boutique_id: ($event.target as HTMLSelectElement).value || undefined })"
                        >
                            <option value="">Toutes les boutiques</option>
                            <option v-for="b in boutiques" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>

                    <!-- Date de -->
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Soumise à partir du</label>
                        <input
                            type="date"
                            :value="filters.date_from ?? ''"
                            class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                            @change="applyFilters({ date_from: ($event.target as HTMLInputElement).value || undefined })"
                        />
                    </div>

                    <!-- Date à -->
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Jusqu'au</label>
                        <div class="flex items-center gap-2">
                            <input
                                type="date"
                                :value="filters.date_to ?? ''"
                                class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                @change="applyFilters({ date_to: ($event.target as HTMLInputElement).value || undefined })"
                            />
                            <button
                                v-if="hasActiveFilters"
                                type="button"
                                title="Réinitialiser"
                                class="shrink-0 rounded-xl border p-2.5 text-muted-foreground transition-colors hover:bg-muted"
                                @click="resetFilters"
                            >
                                <RotateCcw class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tableau -->
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <template v-if="orders.data.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/30">
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Commande</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell sm:px-6">Demandeur</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:table-cell sm:px-6">Boutique</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Montant</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Statut</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:table-cell sm:px-6">Soumise le</th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="order in orders.data"
                                    :key="order.id"
                                    class="transition-colors hover:bg-muted/20"
                                    :class="{ 'opacity-60': order.status === 'cancelled' }"
                                >
                                    <!-- Commande -->
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/10 sm:flex">
                                                <FileText class="h-4 w-4 text-primary" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="max-w-[150px] truncate font-semibold text-foreground sm:max-w-xs">{{ order.title }}</p>
                                                <p v-if="order.reference" class="mt-0.5 font-mono text-xs text-muted-foreground">{{ order.reference }}</p>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Demandeur -->
                                    <td class="hidden px-4 py-4 md:table-cell sm:px-6">
                                        <span class="text-sm text-foreground">{{ order.user?.name }}</span>
                                    </td>

                                    <!-- Boutique -->
                                    <td class="hidden px-4 py-4 lg:table-cell sm:px-6">
                                        <div class="flex items-center gap-2">
                                            <Building2 class="h-4 w-4 shrink-0 text-muted-foreground" />
                                            <span class="text-sm text-foreground">{{ order.boutique?.name ?? '—' }}</span>
                                        </div>
                                    </td>

                                    <!-- Montant -->
                                    <td class="px-4 py-4 sm:px-6">
                                        <span class="text-xs font-bold text-foreground sm:text-sm">{{ formatAmount(order.amount) }}</span>
                                    </td>

                                    <!-- Statut -->
                                    <td class="px-4 py-4 sm:px-6">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="statusConfig[order.status]?.classes ?? 'bg-slate-100 text-slate-600'"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status]?.dot" />
                                            {{ statusConfig[order.status]?.label ?? order.status }}
                                        </span>
                                    </td>

                                    <!-- Soumise le -->
                                    <td class="hidden px-4 py-4 xl:table-cell sm:px-6">
                                        <div class="flex items-center gap-1.5 text-sm text-muted-foreground">
                                            <Calendar class="h-3.5 w-3.5 shrink-0" />
                                            {{ formatDate(order.submitted_at) }}
                                        </div>
                                    </td>

                                    <!-- Action -->
                                    <td class="px-4 py-4 text-right sm:px-6">
                                        <Link
                                            :href="route('validations.history.show', { purchase_order: order.uuid })"
                                            class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-muted sm:gap-2 sm:px-4"
                                        >
                                            <History class="h-4 w-4" />
                                            <span class="hidden sm:inline">Détails</span>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="orders.last_page > 1" class="flex flex-col items-center gap-3 border-t px-4 py-4 sm:flex-row sm:justify-between sm:px-6">
                        <p class="text-sm text-muted-foreground">{{ orders.from }}–{{ orders.to }} sur {{ orders.total }}</p>
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
                </template>

                <!-- État vide avec filtres actifs -->
                <EmptyState
                    v-else-if="hasActiveFilters"
                    :icon="Filter"
                    icon-bg="bg-slate-100"
                    icon-color="text-slate-400"
                    title="Aucun résultat"
                    description="Aucune commande ne correspond aux filtres appliqués."
                    :bordered="false"
                >
                    <button
                        class="inline-flex items-center gap-2 rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                        @click="resetFilters"
                    >
                        <RotateCcw class="h-4 w-4" />
                        Réinitialiser les filtres
                    </button>
                </EmptyState>

                <!-- État vide sans filtres -->
                <EmptyState
                    v-else
                    :icon="History"
                    icon-bg="bg-primary/10"
                    icon-color="text-primary"
                    title="Aucune commande dans l'historique"
                    description="Aucune commande n'a encore été soumise à votre niveau de validation."
                    :action-href="route('validations.index')"
                    action-label="Voir les commandes en attente"
                    :bordered="false"
                />
            </div>
        </div>
    </AppLayout>
</template>
