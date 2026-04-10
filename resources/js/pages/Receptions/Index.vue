<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type PaginatedData, type PurchaseOrder } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight, Building2, CheckCircle2, ClipboardCheck, Clock,
    Package, RotateCcw, Search, SlidersHorizontal, Truck, X,
} from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Réceptions / Livraisons', href: '#' },
];

const props = defineProps<{
    orders: PaginatedData<PurchaseOrder>;
    boutiques: Boutique[];
    filters: { delivery_status: string; boutique_id: string; search: string };
    stats: { ordered: number; partially_received: number; received: number };
}>();

const page = usePage();
const isAdmin = computed(() => (page.props as any).auth?.user?.role?.slug === 'admin');

// ─── Filtres ──────────────────────────────────────────────────────────────────
const localFilters = ref({
    delivery_status: props.filters.delivery_status,
    boutique_id:     props.filters.boutique_id,
    search:          props.filters.search,
});

let searchTimeout: ReturnType<typeof setTimeout>;
watch(() => localFilters.value.search, () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applyFilters, 350);
});
watch([() => localFilters.value.delivery_status, () => localFilters.value.boutique_id], applyFilters);

function applyFilters() {
    router.get(route('receptions.index'), localFilters.value, { preserveState: true, replace: true });
}
function clearFilters() {
    localFilters.value = { delivery_status: '', boutique_id: '', search: '' };
}

const hasActiveFilters = computed(() =>
    localFilters.value.delivery_status || localFilters.value.boutique_id || localFilters.value.search
);

// ─── Helpers ──────────────────────────────────────────────────────────────────
const deliveryConfig = {
    ordered:             { label: 'Commandée',           bg: 'bg-blue-50',   text: 'text-blue-700',   dot: 'bg-blue-500',   border: 'border-blue-100' },
    partially_received:  { label: 'Reçue partiellement', bg: 'bg-orange-50', text: 'text-orange-700', dot: 'bg-orange-500', border: 'border-orange-100' },
    received:            { label: 'Reçue entièrement',   bg: 'bg-emerald-50',text: 'text-emerald-700',dot: 'bg-emerald-500',border: 'border-emerald-100' },
} as const;

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const globalProgress = (order: PurchaseOrder): number => {
    const lines = order.lines ?? [];
    if (!lines.length) return order.delivery_status === 'received' ? 100 : 0;
    const totalOrdered  = lines.reduce((s, l) => s + Number(l.quantity), 0);
    const totalReceived = lines.reduce((s, l) => s + Number(l.quantity_received_total ?? 0), 0);
    if (!totalOrdered) return 0;
    return Math.min(100, Math.round((totalReceived / totalOrdered) * 100));
};
</script>

<template>
    <Head title="Réceptions / Livraisons" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">

            <!-- En-tête page -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Réceptions & Livraisons</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Suivi de l'état de réception des bons de commande approuvés</p>
                </div>
            </div>

            <!-- KPI cards -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <button
                    @click="localFilters.delivery_status = localFilters.delivery_status === 'ordered' ? '' : 'ordered'"
                    class="flex items-center gap-4 rounded-2xl border p-5 text-left shadow-sm transition-all hover:shadow-md"
                    :class="localFilters.delivery_status === 'ordered' ? 'border-blue-300 bg-blue-50 ring-2 ring-blue-200' : 'bg-card hover:bg-muted/20'"
                >
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-blue-100">
                        <Truck class="h-6 w-6 text-blue-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-foreground">{{ stats.ordered }}</p>
                        <p class="text-xs font-medium text-muted-foreground">En attente de livraison</p>
                    </div>
                </button>
                <button
                    @click="localFilters.delivery_status = localFilters.delivery_status === 'partially_received' ? '' : 'partially_received'"
                    class="flex items-center gap-4 rounded-2xl border p-5 text-left shadow-sm transition-all hover:shadow-md"
                    :class="localFilters.delivery_status === 'partially_received' ? 'border-orange-300 bg-orange-50 ring-2 ring-orange-200' : 'bg-card hover:bg-muted/20'"
                >
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-orange-100">
                        <Package class="h-6 w-6 text-orange-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-foreground">{{ stats.partially_received }}</p>
                        <p class="text-xs font-medium text-muted-foreground">Reçues partiellement</p>
                    </div>
                </button>
                <button
                    @click="localFilters.delivery_status = localFilters.delivery_status === 'received' ? '' : 'received'"
                    class="flex items-center gap-4 rounded-2xl border p-5 text-left shadow-sm transition-all hover:shadow-md"
                    :class="localFilters.delivery_status === 'received' ? 'border-emerald-300 bg-emerald-50 ring-2 ring-emerald-200' : 'bg-card hover:bg-muted/20'"
                >
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-100">
                        <CheckCircle2 class="h-6 w-6 text-emerald-600" />
                    </div>
                    <div>
                        <p class="text-2xl font-bold text-foreground">{{ stats.received }}</p>
                        <p class="text-xs font-medium text-muted-foreground">Entièrement réceptionnées</p>
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
                        placeholder="Rechercher un titre, un numéro BC..."
                        class="w-full rounded-xl border border-input bg-background py-2 pl-9 pr-3 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                    />
                </div>
                <div class="flex items-center gap-2">
                    <SlidersHorizontal class="h-4 w-4 text-muted-foreground" />
                    <select
                        v-model="localFilters.delivery_status"
                        class="rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                    >
                        <option value="">Tous les statuts</option>
                        <option value="ordered">Commandée</option>
                        <option value="partially_received">Reçue partiellement</option>
                        <option value="received">Entièrement reçue</option>
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
                        <X class="h-3.5 w-3.5" />
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Liste -->
            <div v-if="orders.data.length" class="flex flex-col gap-3">
                <div
                    v-for="order in orders.data"
                    :key="order.id"
                    class="rounded-2xl border bg-card shadow-sm transition-shadow hover:shadow-md"
                    :class="order.delivery_status ? deliveryConfig[order.delivery_status]?.border : 'border-border'"
                >
                    <div class="flex items-center gap-4 p-5">
                        <!-- Icône statut -->
                        <div
                            class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                            :class="order.delivery_status ? deliveryConfig[order.delivery_status]?.bg : 'bg-muted'"
                        >
                            <CheckCircle2
                                v-if="order.delivery_status === 'received'"
                                class="h-5 w-5 text-emerald-600"
                            />
                            <Package
                                v-else-if="order.delivery_status === 'partially_received'"
                                class="h-5 w-5 text-orange-600"
                            />
                            <Truck v-else class="h-5 w-5 text-blue-600" />
                        </div>

                        <!-- Infos principales -->
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-if="order.order_number"
                                    class="font-mono text-xs font-bold text-blue-700"
                                >{{ order.order_number }}</span>
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="[deliveryConfig[order.delivery_status!]?.bg, deliveryConfig[order.delivery_status!]?.text]"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="deliveryConfig[order.delivery_status!]?.dot" />
                                    {{ deliveryConfig[order.delivery_status!]?.label }}
                                </span>
                            </div>
                            <p class="mt-0.5 truncate font-semibold text-foreground">{{ order.title }}</p>
                            <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                <span v-if="order.boutique" class="flex items-center gap-1">
                                    <Building2 class="h-3 w-3" /> {{ order.boutique.name }}
                                </span>
                                <span v-if="order.fournisseur" class="flex items-center gap-1">
                                    <Truck class="h-3 w-3" /> {{ order.fournisseur.name }}
                                </span>
                                <span v-if="isAdmin && order.user" class="flex items-center gap-1">
                                    <Clock class="h-3 w-3" /> {{ order.user.name }}
                                </span>
                            </div>
                        </div>

                        <!-- Montant + date -->
                        <div class="hidden shrink-0 text-right sm:block">
                            <p class="font-bold text-foreground">{{ formatAmount(order.amount) }}</p>
                            <p class="text-xs text-muted-foreground">
                                Commandée le {{ order.ordered_at ? formatDate(order.ordered_at) : '—' }}
                            </p>
                            <p v-if="order.fully_received_at" class="text-xs text-emerald-600">
                                Clôturée le {{ formatDate(order.fully_received_at) }}
                            </p>
                        </div>

                        <!-- Progression -->
                        <div class="hidden shrink-0 w-28 lg:block">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-muted-foreground">Progression</span>
                                <span
                                    class="text-xs font-bold"
                                    :class="globalProgress(order) >= 100 ? 'text-emerald-600' : 'text-orange-600'"
                                >{{ globalProgress(order) }}%</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-muted overflow-hidden">
                                <div
                                    class="h-full rounded-full transition-all"
                                    :class="globalProgress(order) >= 100 ? 'bg-emerald-500' : 'bg-orange-400'"
                                    :style="{ width: `${globalProgress(order)}%` }"
                                />
                            </div>
                            <p class="mt-1 text-right text-[10px] text-muted-foreground">
                                {{ order.receptions?.length ?? 0 }} réception(s)
                            </p>
                        </div>

                        <!-- Lien -->
                        <Link
                            :href="route('purchase-orders.show', order.id)"
                            class="ml-2 flex shrink-0 items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold text-foreground transition-colors hover:bg-muted"
                        >
                            Voir <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>

                    <!-- Barre de progression lignes (si lignes chargées) -->
                    <div v-if="(order.lines?.length ?? 0) > 0 && order.delivery_status !== 'received'" class="border-t px-5 pb-4 pt-3">
                        <div class="flex flex-wrap gap-2">
                            <div
                                v-for="line in order.lines"
                                :key="line.id"
                                class="flex items-center gap-2 rounded-lg border bg-muted/30 px-3 py-1.5 text-xs"
                            >
                                <span class="font-medium text-foreground truncate max-w-[140px]">{{ line.article?.name ?? `Ligne #${line.id}` }}</span>
                                <span class="text-muted-foreground">·</span>
                                <span
                                    :class="Number(line.quantity_received_total ?? 0) >= Number(line.quantity) ? 'text-emerald-600 font-semibold' : 'text-orange-600 font-semibold'"
                                >
                                    {{ line.quantity_received_total ?? 0 }}
                                </span>
                                <span class="text-muted-foreground">/ {{ line.quantity }} {{ line.article?.unit }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- État vide -->
            <EmptyState
                v-else-if="hasActiveFilters"
                :icon="SlidersHorizontal"
                icon-bg="bg-slate-100"
                icon-color="text-slate-400"
                title="Aucun résultat"
                description="Aucune livraison ne correspond aux filtres appliqués. Modifiez ou effacez les filtres pour voir toutes les livraisons."
            >
                <button
                    class="inline-flex items-center gap-2 rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                    @click="clearFilters"
                >
                    <RotateCcw class="h-4 w-4" />
                    Effacer les filtres
                </button>
            </EmptyState>
            <EmptyState
                v-else
                :icon="Truck"
                icon-bg="bg-sky-50"
                icon-color="text-sky-500"
                title="Aucune livraison en cours"
                description="Les livraisons apparaissent ici dès qu'une commande est confirmée par l'administration et en attente de réception."
                :action-href="route('purchase-orders.index')"
                action-label="Voir les commandes"
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
