<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type PaginatedData, type PurchaseOrder } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight, Banknote, Building2, CheckCircle2, CircleDollarSign,
    Clock, RotateCcw, Search, SlidersHorizontal, Wallet, X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Décaissements', href: '#' },
];

const props = defineProps<{
    orders: PaginatedData<PurchaseOrder>;
    boutiques: Boutique[];
    stats: { unpaid: number; partially_paid: number; paid: number };
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
    router.get(route('decaissements.index'), localFilters.value, { preserveState: true, replace: true });
}
function clearFilters() {
    localFilters.value = { boutique_id: '', payment_status: '', search: '' };
}

const hasActiveFilters = computed(() =>
    localFilters.value.boutique_id || localFilters.value.payment_status || localFilters.value.search,
);

const paymentConfig = {
    unpaid:         { label: 'Non décaissé',         bg: 'bg-gray-50',   text: 'text-gray-600',   dot: 'bg-gray-400',   border: 'border-gray-100' },
    partially_paid: { label: 'Partiellement décaissé', bg: 'bg-orange-50', text: 'text-orange-700', dot: 'bg-orange-500', border: 'border-orange-100' },
    paid:           { label: 'Entièrement décaissé',  bg: 'bg-emerald-50',text: 'text-emerald-700',dot: 'bg-emerald-500',border: 'border-emerald-100' },
} as const;

type PaymentKey = keyof typeof paymentConfig;

function getPaymentKey(order: PurchaseOrder): PaymentKey {
    if (order.payment_status === 'paid') return 'paid';
    if (order.payment_status === 'partially_paid') return 'partially_paid';
    return 'unpaid';
}

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

function totalDecaisse(order: PurchaseOrder): number {
    return (order.decaissements ?? []).reduce((s, d) => s + Number(d.montant), 0);
}

function progressPct(order: PurchaseOrder): number {
    const total = Number(order.amount);
    if (!total) return 0;
    return Math.min(100, Math.round((totalDecaisse(order) / total) * 100));
}
</script>

<template>
    <Head title="Décaissements" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">

            <!-- En-tête -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Décaissements</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Enregistrement des paiements pour les commandes approuvées
                    </p>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <button
                    @click="localFilters.payment_status = localFilters.payment_status === 'unpaid' ? '' : 'unpaid'"
                    class="flex items-center gap-4 rounded-2xl border p-5 text-left shadow-sm transition-all hover:shadow-md"
                    :class="localFilters.payment_status === 'unpaid' ? 'border-gray-300 bg-gray-100 ring-2 ring-gray-200' : 'bg-card hover:bg-muted/20'"
                >
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100">
                        <Clock class="h-6 w-6 text-gray-500" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-foreground">{{ stats.unpaid }}</p>
                        <p class="text-xs font-medium text-muted-foreground">Non décaissées</p>
                    </div>
                </button>

                <button
                    @click="localFilters.payment_status = localFilters.payment_status === 'partially_paid' ? '' : 'partially_paid'"
                    class="flex items-center gap-4 rounded-2xl border p-5 text-left shadow-sm transition-all hover:shadow-md"
                    :class="localFilters.payment_status === 'partially_paid' ? 'border-orange-300 bg-orange-50 ring-2 ring-orange-200' : 'bg-card hover:bg-muted/20'"
                >
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-orange-100">
                        <Wallet class="h-6 w-6 text-orange-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-foreground">{{ stats.partially_paid }}</p>
                        <p class="text-xs font-medium text-muted-foreground">Partiellement décaissées</p>
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
                        <p class="text-xs font-medium text-muted-foreground">Entièrement décaissées</p>
                    </div>
                </button>
            </div>

            <!-- Filtres -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-48">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="localFilters.search"
                        type="text"
                        placeholder="Titre, numéro BC…"
                        class="w-full rounded-xl border border-input bg-background py-2 pl-9 pr-3 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <SlidersHorizontal class="h-4 w-4 text-muted-foreground" />
                    <select
                        v-model="localFilters.payment_status"
                        class="rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="unpaid">Non décaissé</option>
                        <option value="partially_paid">Partiellement décaissé</option>
                        <option value="paid">Entièrement décaissé</option>
                    </select>
                    <select
                        v-if="isAdmin"
                        v-model="localFilters.boutique_id"
                        class="rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                    >
                        <option value="">Toutes les boutiques</option>
                        <option v-for="b in boutiques" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <button
                        v-if="hasActiveFilters"
                        @click="clearFilters"
                        class="flex items-center gap-1.5 rounded-xl border px-3 py-2 text-sm text-muted-foreground hover:bg-muted transition-colors"
                    >
                        <X class="h-3.5 w-3.5" /> Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Liste -->
            <div v-if="orders.data.length" class="flex flex-col gap-3">
                <div
                    v-for="order in orders.data"
                    :key="order.id"
                    class="rounded-2xl border bg-card shadow-sm transition-shadow hover:shadow-md"
                    :class="paymentConfig[getPaymentKey(order)].border"
                >
                    <div class="flex items-center gap-4 p-5">
                        <!-- Icône statut -->
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                            :class="paymentConfig[getPaymentKey(order)].bg"
                        >
                            <CheckCircle2
                                v-if="order.payment_status === 'paid'"
                                class="h-5 w-5 text-emerald-600"
                            />
                            <Wallet
                                v-else-if="order.payment_status === 'partially_paid'"
                                class="h-5 w-5 text-orange-600"
                            />
                            <Banknote v-else class="h-5 w-5 text-gray-400" />
                        </div>

                        <!-- Infos -->
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-if="order.order_number"
                                    class="font-mono text-xs font-bold text-blue-700"
                                >{{ order.order_number }}</span>
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="[paymentConfig[getPaymentKey(order)].bg, paymentConfig[getPaymentKey(order)].text]"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="paymentConfig[getPaymentKey(order)].dot" />
                                    {{ paymentConfig[getPaymentKey(order)].label }}
                                </span>
                            </div>
                            <p class="mt-0.5 truncate font-semibold text-foreground">{{ order.title }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                <span v-if="order.boutique" class="flex items-center gap-1">
                                    <Building2 class="h-3 w-3" /> {{ order.boutique.name }}
                                </span>
                                <span v-if="order.fournisseur" class="flex items-center gap-1">
                                    <CircleDollarSign class="h-3 w-3" /> {{ order.fournisseur.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Montant -->
                        <div class="hidden shrink-0 text-right sm:block">
                            <p class="font-bold text-foreground">{{ formatAmount(order.amount) }}</p>
                            <p class="text-xs text-emerald-600 font-medium">
                                Décaissé : {{ formatAmount(totalDecaisse(order)) }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Approuvée le {{ order.submitted_at ? formatDate(order.submitted_at) : '—' }}
                            </p>
                        </div>

                        <!-- Progression -->
                        <div class="hidden w-28 shrink-0 lg:block">
                            <div class="mb-1 flex items-center justify-between">
                                <span class="text-xs text-muted-foreground">Décaissement</span>
                                <span
                                    class="text-xs font-bold"
                                    :class="progressPct(order) >= 100 ? 'text-emerald-600' : 'text-orange-600'"
                                >{{ progressPct(order) }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="progressPct(order) >= 100 ? 'bg-emerald-500' : 'bg-orange-400'"
                                    :style="{ width: `${progressPct(order)}%` }"
                                />
                            </div>
                            <p class="mt-1 text-right text-[10px] text-muted-foreground">
                                {{ (order.decaissements ?? []).length }} opération(s)
                            </p>
                        </div>

                        <!-- Lien -->
                        <Link
                            :href="route('decaissements.show', order.id)"
                            class="ml-2 flex shrink-0 items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold text-foreground transition-colors hover:bg-muted"
                        >
                            Décaisser <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>
            </div>

            <EmptyState
                v-else-if="hasActiveFilters"
                :icon="SlidersHorizontal"
                icon-bg="bg-slate-100"
                icon-color="text-slate-400"
                title="Aucun résultat"
                description="Aucune commande ne correspond aux filtres appliqués."
            >
                <button
                    class="inline-flex items-center gap-2 rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                    @click="clearFilters"
                >
                    <RotateCcw class="h-4 w-4" /> Effacer les filtres
                </button>
            </EmptyState>
            <EmptyState
                v-else
                :icon="Banknote"
                icon-bg="bg-amber-50"
                icon-color="text-amber-500"
                title="Aucune commande approuvée"
                description="Les commandes approuvées par le dernier validateur apparaissent ici pour le décaissement."
            />

            <!-- Pagination -->
            <div v-if="orders.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in orders.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted text-muted-foreground'"
                        v-html="link.label"
                    />
                    <span v-else class="rounded-lg px-3 py-1.5 text-sm text-muted-foreground opacity-40" v-html="link.label" />
                </template>
            </div>

        </div>
    </AppLayout>
</template>
