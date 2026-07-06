<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type DisbursementRequest, type PaginatedData, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Banknote, Building2, Clock, Download, Eye, Loader2, ShieldCheck } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Validations DD', href: route('disbursement-validations.index') },
];

const props = defineProps<{
    orders: PaginatedData<DisbursementRequest>;
    boutiques: Boutique[];
    levelsCount: number;
    filters: { boutique_id?: string };
}>();

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.role?.slug === 'admin');

// Infinite scroll state
const allOrders = ref<DisbursementRequest[]>([...props.orders.data]);
const currentPage = ref(props.orders.current_page);
const lastPage = ref(props.orders.last_page);
const total = ref(props.orders.total);
const isLoadingMore = ref(false);
const isAppending = ref(false);
const sentinel = ref<HTMLElement | null>(null);
let observer: IntersectionObserver | null = null;

watch(
    () => props.orders,
    (newOrders) => {
        if (isAppending.value) {
            allOrders.value = [...allOrders.value, ...newOrders.data];
        } else {
            allOrders.value = [...newOrders.data];
        }
        currentPage.value = newOrders.current_page;
        lastPage.value = newOrders.last_page;
        total.value = newOrders.total;
        isAppending.value = false;
    },
);

const boutique_id = ref(props.filters.boutique_id ?? '');
watch(boutique_id, (value) => {
    isAppending.value = false;
    router.get(route('disbursement-validations.index'), { boutique_id: value || undefined }, { preserveState: true, replace: true });
});

const loadMore = () => {
    if (isLoadingMore.value || currentPage.value >= lastPage.value) return;
    isLoadingMore.value = true;
    isAppending.value = true;

    const params: Record<string, string> = { page: String(currentPage.value + 1) };
    if (boutique_id.value) params.boutique_id = boutique_id.value;

    router.get(route('disbursement-validations.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['orders'],
        onFinish: () => { isLoadingMore.value = false; },
    });
};

const setupObserver = () => {
    observer?.disconnect();
    observer = new IntersectionObserver(
        (entries) => { if (entries[0].isIntersecting) loadMore(); },
        { rootMargin: '300px' },
    );
    if (sentinel.value) observer.observe(sentinel.value);
};

onMounted(() => setupObserver());
onUnmounted(() => observer?.disconnect());
watch(sentinel, (el) => { if (el) setupObserver(); });

const exportUrl = computed(() => boutique_id.value
    ? `${route('disbursement-validations.export')}?boutique_id=${encodeURIComponent(boutique_id.value)}`
    : route('disbursement-validations.export'));

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const pendingCount = computed(() => allOrders.value.filter((o) => o.status === 'pending').length);
const visibleAmountTotal = computed(() => allOrders.value.reduce((sum, o) => sum + Number(o.amount || 0), 0));
const hasMore = computed(() => currentPage.value < lastPage.value);
</script>

<template>
    <Head title="Validations — Demandes de décaissement" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">
                        {{ isAdmin ? 'Pilotage des validations DD' : 'File de validation' }}
                    </p>
                    <h1 class="mt-1 text-2xl font-bold text-foreground">Demandes de décaissement à valider</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ isAdmin ? 'Supervisez les dossiers en attente et leur répartition par boutique.' : 'Retrouvez les dossiers en attente de votre approbation.' }}
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
                            <Banknote class="h-8 w-8" />
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Portefeuille validations DD</p>
                            <h2 class="mt-1 text-xl font-semibold text-foreground">Tableau de suivi des demandes à approuver</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Filtrez les dossiers en attente et accédez rapidement à leur instruction.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:w-[560px]">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">Portefeuille</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ total }} demande(s)</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ boutiques.length }} boutique(s) couvertes</p>
                        </div>

                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Validation</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ levelsCount }} niveau(x)</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ pendingCount }} dossier(s) chargés</p>
                        </div>

                        <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4">
                            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700">Résultats</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ allOrders.length }} / {{ total }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">dossier(s) chargés</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ total }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">demande(s) à traiter</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">En attente</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600">{{ pendingCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">chargés jusqu'ici</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Boutiques</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ boutiques.length }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">dans le périmètre filtré</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Montant chargé</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ formatAmount(visibleAmountTotal) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">sur les lignes affichées</p>
                </div>
            </section>

            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div v-if="boutiques.length" class="flex items-center gap-3 rounded-2xl border bg-card px-4 py-3 shadow-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-muted text-muted-foreground">
                        <Building2 class="h-5 w-5" />
                    </div>
                    <div class="relative min-w-[220px]">
                        <select v-model="boutique_id" class="h-11 w-full appearance-none rounded-xl border border-input bg-background px-4 pr-10 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20">
                            <option value="">Toutes les boutiques</option>
                            <option v-for="b in boutiques" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link :href="route('disbursement-validations.history')" class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted">
                        Historique
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

            <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                <EmptyState v-if="allOrders.length === 0" title="Aucune demande en attente" description="Toutes les demandes de décaissement ont été traitées." :icon="Clock" />
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="border-b bg-muted/30">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Référence</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Titre</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Demandeur</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Nature</th>
                                <th class="px-4 py-3 text-right font-semibold text-foreground">Montant</th>
                                <th class="px-4 py-3 text-left font-semibold text-foreground">Soumis le</th>
                                <th class="px-4 py-3 text-right font-semibold text-foreground">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="order in allOrders" :key="order.id" class="transition-colors hover:bg-muted/20">
                                <td class="px-4 py-3 font-mono text-xs text-foreground/80">{{ order.reference }}</td>
                                <td class="px-4 py-3 font-medium text-foreground">
                                    {{ order.title }}
                                    <p v-if="order.boutique" class="text-xs text-foreground/65">{{ order.boutique.name }}</p>
                                </td>
                                <td class="px-4 py-3 text-foreground/80">{{ order.user?.name ?? '—' }}</td>
                                <td class="px-4 py-3 text-foreground/80">{{ order.nature_operation?.name ?? '—' }}</td>
                                <td class="px-4 py-3 text-right font-medium tabular-nums text-foreground">{{ formatAmount(order.amount) }}</td>
                                <td class="px-4 py-3 text-foreground/80">{{ order.submitted_at ? formatDate(order.submitted_at) : '—' }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('disbursement-validations.show', { disbursement_request: order.uuid })" class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90">
                                        <Eye class="h-3.5 w-3.5" /> Examiner
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Sentinel pour l'infinite scroll -->
                <div ref="sentinel" class="h-1" />

                <!-- Indicateur de chargement -->
                <div v-if="isLoadingMore" class="flex items-center justify-center gap-2 border-t py-4 text-sm text-muted-foreground">
                    <Loader2 class="h-4 w-4 animate-spin" />
                    Chargement en cours…
                </div>

                <!-- Message fin de liste -->
                <div v-else-if="allOrders.length > 0 && !hasMore" class="border-t px-4 py-3 text-center text-xs text-muted-foreground">
                    Tous les {{ total }} dossier(s) ont été chargés.
                </div>
            </div>
        </div>
    </AppLayout>
</template>
