<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type PaginatedData, type SharedData, type User, type ValidationLevel, type ValidationLog } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { CheckCircle2, ChevronDown, ClipboardList, Download, FileSpreadsheet, FileText, Filter, RotateCcw, X, XCircle } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Audit & Historique', href: '/audit' },
];

const props = defineProps<{
    logs: PaginatedData<ValidationLog>;
    stats: { total: number; approved: number; rejected: number; orders_touched: number };
    boutiques: Pick<Boutique, 'id' | 'name'>[];
    levels: Pick<ValidationLevel, 'id' | 'name' | 'order'>[];
    validators: Pick<User, 'id' | 'name'>[];
    filters: {
        action?: string;
        boutique_id?: string;
        level_id?: string;
        validator_id?: string;
        date_from?: string;
        date_to?: string;
        search?: string;
    };
}>();

const page = usePage<SharedData>();
const isAdmin = computed(() => page.props.auth.user?.role?.slug === 'admin');

const showFilters = ref(
    !!(props.filters.action || props.filters.boutique_id || props.filters.level_id || props.filters.validator_id || props.filters.date_from || props.filters.date_to)
);
const showExportMenu = ref(false);

const localFilters = ref({
    search:       props.filters.search ?? '',
    action:       props.filters.action ?? '',
    boutique_id:  props.filters.boutique_id ?? '',
    level_id:     props.filters.level_id ?? '',
    validator_id: props.filters.validator_id ?? '',
    date_from:    props.filters.date_from ?? '',
    date_to:      props.filters.date_to ?? '',
});

const hasActiveFilters = computed(() => Object.values(localFilters.value).some(v => v !== ''));

const approvalRate = computed(() =>
    props.stats.total > 0 ? Math.round((props.stats.approved / props.stats.total) * 100) : 0
);

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const applyFilters = () => {
    const params: Record<string, string> = {};
    Object.entries(localFilters.value).forEach(([k, v]) => { if (v) params[k] = v; });
    router.get(route('audit.index'), params, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.value = { search: '', action: '', boutique_id: '', level_id: '', validator_id: '', date_from: '', date_to: '' };
    router.get(route('audit.index'), {}, { preserveState: true, preserveScroll: true, replace: true });
};

const exportUrl = (format: string) => {
    const params = new URLSearchParams();
    Object.entries(localFilters.value).forEach(([k, v]) => { if (v) params.append(k, v); });
    const qs = params.toString();
    return route('audit.export', { format }) + (qs ? '?' + qs : '');
};
</script>

<template>
    <Head title="Audit & Historique" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100">
                            <ClipboardList class="h-5 w-5 text-indigo-600" />
                        </div>
                        <div>
                            <h1 class="text-xl font-bold text-foreground sm:text-2xl">Audit & Historique</h1>
                            <p class="text-sm text-muted-foreground">{{ logs.total }} action{{ logs.total !== 1 ? 's' : '' }} de validation enregistrée{{ logs.total !== 1 ? 's' : '' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Export -->
                <div class="relative">
                    <div v-if="showExportMenu" class="fixed inset-0 z-10" @click="showExportMenu = false" />
                    <button
                        class="inline-flex items-center gap-2 rounded-xl border px-3 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted sm:px-4"
                        @click="showExportMenu = !showExportMenu"
                    >
                        <Download class="h-4 w-4" />
                        <span class="hidden sm:inline">Exporter</span>
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
                            <a
                                :href="exportUrl('excel')"
                                class="flex items-center gap-3 px-4 py-3 text-sm text-foreground transition-colors hover:bg-muted"
                                @click="showExportMenu = false"
                            >
                                <FileSpreadsheet class="h-4 w-4 text-emerald-600" />
                                Export Excel (.xlsx)
                            </a>
                            <a
                                :href="exportUrl('pdf')"
                                target="_blank"
                                class="flex items-center gap-3 px-4 py-3 text-sm text-foreground transition-colors hover:bg-muted"
                                @click="showExportMenu = false"
                            >
                                <FileText class="h-4 w-4 text-red-500" />
                                Export PDF
                            </a>
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- Stats cards -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-5">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Total actions</p>
                    <p class="mt-1.5 text-2xl font-bold text-foreground">{{ stats.total }}</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-emerald-600">Approuvées</p>
                    <p class="mt-1.5 text-2xl font-bold text-emerald-600">{{ stats.approved }}</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-red-500">Refusées</p>
                    <p class="mt-1.5 text-2xl font-bold text-red-500">{{ stats.rejected }}</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Commandes traitées</p>
                    <p class="mt-1.5 text-2xl font-bold text-foreground">{{ stats.orders_touched }}</p>
                </div>
                <div class="col-span-2 rounded-2xl border bg-card p-4 shadow-sm sm:col-span-1">
                    <p class="text-xs font-medium uppercase tracking-wide text-indigo-600">Taux approbation</p>
                    <p class="mt-1.5 text-2xl font-bold text-indigo-600">{{ approvalRate }}%</p>
                    <div class="mt-2 h-1.5 w-full rounded-full bg-muted">
                        <div
                            class="h-1.5 rounded-full bg-indigo-500 transition-all duration-500"
                            :style="{ width: approvalRate + '%' }"
                        />
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="rounded-2xl border bg-card shadow-sm">
                <div class="flex items-center gap-3 p-4">
                    <div class="relative flex-1">
                        <input
                            v-model="localFilters.search"
                            type="text"
                            placeholder="Rechercher par titre de commande..."
                            class="h-10 w-full rounded-xl border border-input bg-background pl-4 pr-4 text-sm text-foreground placeholder:text-muted-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <button
                        class="inline-flex items-center gap-2 rounded-xl border px-3 py-2.5 text-sm font-medium transition-colors"
                        :class="showFilters ? 'border-primary bg-primary/5 text-primary' : 'text-foreground hover:bg-muted'"
                        @click="showFilters = !showFilters"
                    >
                        <Filter class="h-4 w-4" />
                        <span class="hidden sm:inline">Filtres</span>
                        <span v-if="hasActiveFilters" class="flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[10px] font-bold text-primary-foreground">
                            {{ Object.values(localFilters).filter(v => v).length }}
                        </span>
                    </button>
                    <button
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-3 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                        @click="applyFilters"
                    >
                        Rechercher
                    </button>
                </div>

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-1"
                >
                    <div v-if="showFilters" class="border-t px-4 pb-4 pt-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

                            <!-- Action -->
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Action</label>
                                <select v-model="localFilters.action" class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
                                    <option value="">Toutes</option>
                                    <option value="approved">Approuvée</option>
                                    <option value="rejected">Refusée</option>
                                </select>
                            </div>

                            <!-- Niveau -->
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Niveau de validation</label>
                                <select v-model="localFilters.level_id" class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
                                    <option value="">Tous</option>
                                    <option v-for="level in levels" :key="level.id" :value="String(level.id)">
                                        N{{ level.order }} — {{ level.name }}
                                    </option>
                                </select>
                            </div>

                            <!-- Boutique -->
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Boutique</label>
                                <select v-model="localFilters.boutique_id" class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
                                    <option value="">Toutes</option>
                                    <option v-for="b in boutiques" :key="b.id" :value="String(b.id)">{{ b.name }}</option>
                                </select>
                            </div>

                            <!-- Validateur (admin only) -->
                            <div v-if="isAdmin">
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Validateur</label>
                                <select v-model="localFilters.validator_id" class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
                                    <option value="">Tous</option>
                                    <option v-for="v in validators" :key="v.id" :value="String(v.id)">{{ v.name }}</option>
                                </select>
                            </div>

                            <!-- Période de -->
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Période (du)</label>
                                <input v-model="localFilters.date_from" type="date" class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30" />
                            </div>

                            <!-- Période à -->
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Période (au)</label>
                                <input v-model="localFilters.date_to" type="date" class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30" />
                            </div>
                        </div>

                        <div class="mt-3 flex justify-end">
                            <button
                                v-if="hasActiveFilters"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                @click="resetFilters"
                            >
                                <RotateCcw class="h-3.5 w-3.5" />
                                Réinitialiser les filtres
                            </button>
                        </div>
                    </div>
                </Transition>

                <!-- Tags filtres actifs -->
                <div
                    v-if="!showFilters && (filters.action || filters.boutique_id || filters.level_id || filters.validator_id || filters.date_from || filters.date_to)"
                    class="flex flex-wrap gap-2 border-t px-4 py-3"
                >
                    <span v-if="filters.action" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        {{ filters.action === 'approved' ? 'Approuvées' : 'Refusées' }}
                        <button @click="localFilters.action = ''; applyFilters()"><X class="h-3 w-3 ml-1" /></button>
                    </span>
                    <span v-if="filters.date_from || filters.date_to" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        {{ filters.date_from || '...' }} → {{ filters.date_to || '...' }}
                        <button @click="localFilters.date_from = ''; localFilters.date_to = ''; applyFilters()"><X class="h-3 w-3 ml-1" /></button>
                    </span>
                    <span v-if="filters.boutique_id" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Boutique filtrée
                        <button @click="localFilters.boutique_id = ''; applyFilters()"><X class="h-3 w-3 ml-1" /></button>
                    </span>
                    <span v-if="filters.level_id" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Niveau filtré
                        <button @click="localFilters.level_id = ''; applyFilters()"><X class="h-3 w-3 ml-1" /></button>
                    </span>
                    <span v-if="filters.validator_id" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Validateur filtré
                        <button @click="localFilters.validator_id = ''; applyFilters()"><X class="h-3 w-3 ml-1" /></button>
                    </span>
                </div>
            </div>

            <!-- Table logs -->
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <template v-if="logs.data.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/30">
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Date</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Commande</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell sm:px-6">Boutique</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:table-cell sm:px-6">Demandeur</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Action</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell sm:px-6">Niveau</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:table-cell sm:px-6">Validateur</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:table-cell sm:px-6">Commentaire</th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Lien</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr
                                    v-for="log in logs.data"
                                    :key="log.id"
                                    class="transition-colors hover:bg-muted/20"
                                    :class="log.action === 'approved' ? 'hover:bg-emerald-50/30' : 'hover:bg-red-50/30'"
                                >
                                    <!-- Date -->
                                    <td class="px-4 py-4 text-xs text-muted-foreground sm:px-6">
                                        {{ formatDate(log.created_at) }}
                                    </td>

                                    <!-- Commande -->
                                    <td class="px-4 py-4 sm:px-6">
                                        <p class="max-w-[140px] truncate font-medium text-foreground sm:max-w-[200px]">
                                            {{ log.purchase_order?.title ?? '—' }}
                                        </p>
                                        <p class="mt-0.5 text-xs font-semibold text-muted-foreground">
                                            {{ log.purchase_order?.amount
                                                ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(log.purchase_order.amount))
                                                : '' }}
                                        </p>
                                    </td>

                                    <!-- Boutique -->
                                    <td class="hidden px-4 py-4 text-sm text-foreground md:table-cell sm:px-6">
                                        {{ log.purchase_order?.boutique?.name ?? '—' }}
                                    </td>

                                    <!-- Demandeur -->
                                    <td class="hidden px-4 py-4 text-sm text-foreground lg:table-cell sm:px-6">
                                        {{ log.purchase_order?.user?.name ?? '—' }}
                                    </td>

                                    <!-- Action -->
                                    <td class="px-4 py-4 sm:px-6">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="log.action === 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'"
                                        >
                                            <CheckCircle2 v-if="log.action === 'approved'" class="h-3 w-3" />
                                            <XCircle v-else class="h-3 w-3" />
                                            <span class="hidden sm:inline">{{ log.action === 'approved' ? 'Approuvée' : 'Refusée' }}</span>
                                        </span>
                                    </td>

                                    <!-- Niveau -->
                                    <td class="hidden px-4 py-4 md:table-cell sm:px-6">
                                        <span v-if="log.validation_level" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700">
                                            N{{ log.validation_level.order }} — {{ log.validation_level.name }}
                                        </span>
                                        <span v-else class="text-muted-foreground">—</span>
                                    </td>

                                    <!-- Validateur -->
                                    <td class="hidden px-4 py-4 text-sm text-foreground lg:table-cell sm:px-6">
                                        {{ log.user?.name ?? '—' }}
                                    </td>

                                    <!-- Commentaire -->
                                    <td class="hidden px-4 py-4 xl:table-cell sm:px-6">
                                        <p v-if="log.comment" class="max-w-[180px] truncate text-xs italic text-muted-foreground" :title="log.comment">
                                            "{{ log.comment }}"
                                        </p>
                                        <span v-else class="text-xs text-muted-foreground/40">—</span>
                                    </td>

                                    <!-- Lien -->
                                    <td class="px-4 py-4 text-right sm:px-6">
                                        <Link
                                            v-if="log.purchase_order_id"
                                            :href="route('purchase-orders.show', log.purchase_order_id)"
                                            class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground inline-flex"
                                            title="Voir la commande"
                                        >
                                            <FileText class="h-4 w-4" />
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="logs.last_page > 1" class="flex flex-col items-center gap-3 border-t px-4 py-4 sm:flex-row sm:justify-between sm:px-6">
                        <p class="text-sm text-muted-foreground">{{ logs.from }}–{{ logs.to }} sur {{ logs.total }}</p>
                        <div class="flex flex-wrap items-center justify-center gap-1">
                            <Link
                                v-for="link in logs.links"
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

                <!-- Empty state -->
                <div v-else class="flex flex-col items-center justify-center px-6 py-16 text-center">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                        <ClipboardList class="h-7 w-7 text-muted-foreground" />
                    </div>
                    <h3 class="mb-2 font-semibold text-foreground">Aucune action trouvée</h3>
                    <p class="mb-4 text-sm text-muted-foreground">Aucun log de validation ne correspond aux filtres appliqués.</p>
                    <button
                        v-if="hasActiveFilters"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                        @click="resetFilters"
                    >
                        <RotateCcw class="h-4 w-4" />
                        Réinitialiser les filtres
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
