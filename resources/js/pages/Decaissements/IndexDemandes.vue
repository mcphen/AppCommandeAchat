<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type DisbursementRequest, type PaginatedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight, Banknote, Building2, CheckCircle2,
    CircleDollarSign, Clock, Search, SlidersHorizontal, X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Décaissements DD', href: '#' },
];

const props = defineProps<{
    demandes: PaginatedData<DisbursementRequest>;
    boutiques: Boutique[];
    stats: { unpaid: number; paid: number; total: number };
    filters: { boutique_id: string; payment_status: string; search: string };
}>();

const page = usePage();
const isAdmin = computed(() => (page.props as any).auth?.user?.role?.slug === 'admin');

const localFilters = ref({
    boutique_id:    props.filters.boutique_id,
    payment_status: props.filters.payment_status,
    search:         props.filters.search,
});

let searchTimeout: ReturnType<typeof setTimeout>;
watch(() => localFilters.value.search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 350);
});
watch([() => localFilters.value.payment_status, () => localFilters.value.boutique_id], applyFilters);

function applyFilters() {
    router.get(route('caissier.demandes.index'), localFilters.value, { preserveState: true, replace: true });
}
function clearFilters() {
    localFilters.value = { boutique_id: '', payment_status: '', search: '' };
}

const hasActiveFilters = computed(() =>
    localFilters.value.boutique_id || localFilters.value.payment_status || localFilters.value.search,
);

const paymentConfig = {
    unpaid: { label: 'En attente de paiement', bg: 'bg-orange-50', text: 'text-orange-700', dot: 'bg-orange-500', border: 'border-orange-100' },
    paid:   { label: 'Payé',                   bg: 'bg-emerald-50',text: 'text-emerald-700',dot: 'bg-emerald-500',border: 'border-emerald-100' },
} as const;

type PaymentKey = 'paid' | 'unpaid';

function getPaymentKey(demande: DisbursementRequest): PaymentKey {
    return demande.payment_status === 'paid' ? 'paid' : 'unpaid';
}

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
</script>

<template>
    <Head title="Décaissements — Demandes" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">

            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Demandes de décaissement</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Paiements à effectuer suite aux demandes approuvées
                    </p>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="flex items-center gap-4 rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-100">
                        <CircleDollarSign class="h-6 w-6 text-indigo-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-foreground">{{ stats.total }}</p>
                        <p class="text-xs font-medium text-muted-foreground">Total approuvées</p>
                    </div>
                </div>

                <button
                    @click="localFilters.payment_status = localFilters.payment_status === 'unpaid' ? '' : 'unpaid'"
                    class="flex items-center gap-4 rounded-2xl border p-5 text-left shadow-sm transition-all hover:shadow-md"
                    :class="localFilters.payment_status === 'unpaid' ? 'border-orange-300 bg-orange-50 ring-2 ring-orange-200' : 'bg-card hover:bg-muted/20'"
                >
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-orange-100">
                        <Clock class="h-6 w-6 text-orange-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-foreground">{{ stats.unpaid }}</p>
                        <p class="text-xs font-medium text-muted-foreground">En attente de paiement</p>
                    </div>
                </button>

                <button
                    @click="localFilters.payment_status = localFilters.payment_status === 'paid' ? '' : 'paid'"
                    class="flex items-center gap-4 rounded-2xl border p-5 text-left shadow-sm transition-all hover:shadow-md"
                    :class="localFilters.payment_status === 'paid' ? 'border-emerald-300 bg-emerald-50 ring-2 ring-emerald-200' : 'bg-card hover:bg-muted/20'"
                >
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100">
                        <CheckCircle2 class="h-6 w-6 text-emerald-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-foreground">{{ stats.paid }}</p>
                        <p class="text-xs font-medium text-muted-foreground">Payées</p>
                    </div>
                </button>
            </div>

            <!-- Filtres -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Recherche -->
                <div class="relative flex-1 min-w-52">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground pointer-events-none" />
                    <input
                        v-model="localFilters.search"
                        type="text"
                        placeholder="Référence, titre…"
                        class="w-full rounded-xl border border-input bg-white dark:bg-zinc-900 pl-9 pr-4 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                    />
                </div>

                <!-- Boutique (admin) -->
                <div v-if="isAdmin && boutiques.length" class="flex items-center gap-1.5 rounded-xl border bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                    <Building2 class="h-4 w-4 text-muted-foreground" />
                    <select v-model="localFilters.boutique_id" class="bg-transparent text-foreground focus:outline-none">
                        <option value="">Toutes les boutiques</option>
                        <option v-for="b in boutiques" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
                    </select>
                </div>

                <!-- Statut paiement -->
                <div class="flex items-center gap-1.5 rounded-xl border bg-white dark:bg-zinc-900 px-3 py-2 text-sm">
                    <SlidersHorizontal class="h-4 w-4 text-muted-foreground" />
                    <select v-model="localFilters.payment_status" class="bg-transparent text-foreground focus:outline-none">
                        <option value="">Tous les statuts</option>
                        <option value="unpaid">En attente</option>
                        <option value="paid">Payée</option>
                    </select>
                </div>

                <!-- Reset -->
                <button
                    v-if="hasActiveFilters"
                    @click="clearFilters"
                    class="flex items-center gap-1.5 rounded-xl border border-red-200 px-3 py-2 text-sm text-red-600 hover:bg-red-50"
                >
                    <X class="h-3.5 w-3.5" /> Réinitialiser
                </button>
            </div>

            <!-- Liste -->
            <div v-if="demandes.data.length === 0">
                <EmptyState
                    title="Aucune demande de décaissement"
                    description="Il n'y a pas encore de demande approuvée correspondant à vos critères."
                    icon="banknote"
                />
            </div>

            <div v-else class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/40">
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Référence</th>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Objet</th>
                            <th class="hidden px-4 py-3 text-left font-semibold text-muted-foreground sm:table-cell">Demandeur</th>
                            <th class="hidden px-4 py-3 text-left font-semibold text-muted-foreground md:table-cell">Nature</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted-foreground">Montant</th>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Statut paiement</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="demande in demandes.data"
                            :key="demande.id"
                            class="group transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-indigo-700">
                                {{ demande.reference }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-medium text-foreground line-clamp-1">{{ demande.title }}</span>
                                <div class="mt-0.5 flex items-center gap-2 text-xs text-muted-foreground">
                                    <span v-if="demande.boutique" class="flex items-center gap-1">
                                        <Building2 class="h-3 w-3" /> {{ demande.boutique.name }}
                                    </span>
                                    <span>{{ formatDate(demande.updated_at) }}</span>
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 text-muted-foreground sm:table-cell">
                                {{ demande.user?.name ?? '—' }}
                            </td>
                            <td class="hidden px-4 py-3 text-muted-foreground md:table-cell text-xs">
                                {{ demande.nature_operation?.name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-foreground tabular-nums">
                                {{ formatAmount(demande.amount) }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="[paymentConfig[getPaymentKey(demande)].bg, paymentConfig[getPaymentKey(demande)].text, 'border', paymentConfig[getPaymentKey(demande)].border]"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="paymentConfig[getPaymentKey(demande)].dot" />
                                    {{ paymentConfig[getPaymentKey(demande)].label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link
                                    :href="route('caissier.demandes.show', demande.uuid)"
                                    class="inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold text-foreground hover:bg-muted"
                                >
                                    Traiter <ArrowRight class="h-3.5 w-3.5" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pagination -->
                <div v-if="demandes.last_page > 1" class="flex items-center justify-between border-t px-5 py-4">
                    <p class="text-sm text-muted-foreground">
                        {{ demandes.from }}–{{ demandes.to }} sur {{ demandes.total }}
                    </p>
                    <div class="flex gap-2">
                        <Link
                            v-if="demandes.prev_page_url"
                            :href="demandes.prev_page_url"
                            preserve-state
                            class="rounded-lg border px-3 py-1.5 text-sm hover:bg-muted"
                        >← Précédent</Link>
                        <Link
                            v-if="demandes.next_page_url"
                            :href="demandes.next_page_url"
                            preserve-state
                            class="rounded-lg border px-3 py-1.5 text-sm hover:bg-muted"
                        >Suivant →</Link>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
