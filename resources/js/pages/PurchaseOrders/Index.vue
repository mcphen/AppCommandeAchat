<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type PaginatedData, type PurchaseOrder, type SharedData, type User, type ValidationLevel } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Building2, ChevronDown, Download, Eye, FileSpreadsheet, FileText, Filter, Pencil, Plus, RotateCcw, Send, ShoppingCart, Trash2, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Commandes', href: '/purchase-orders' },
];

const props = defineProps<{
    orders: PaginatedData<PurchaseOrder>;
    boutiques: Boutique[];
    demandeurs: Pick<User, 'id' | 'name'>[];
    levels: Pick<ValidationLevel, 'id' | 'name' | 'order'>[];
    levelsCount: number;
    filters: {
        boutique_id?: string;
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
    !!(props.filters.status || props.filters.user_id || props.filters.date_from || props.filters.date_to || props.filters.amount_min || props.filters.amount_max || props.filters.level_order)
);
const showExportMenu = ref(false);

const localFilters = ref({
    search:      props.filters.search ?? '',
    boutique_id: props.filters.boutique_id ?? '',
    status:      props.filters.status ?? '',
    user_id:     props.filters.user_id ?? '',
    date_from:   props.filters.date_from ?? '',
    date_to:     props.filters.date_to ?? '',
    amount_min:  props.filters.amount_min ?? '',
    amount_max:  props.filters.amount_max ?? '',
    level_order: props.filters.level_order ?? '',
});

const hasActiveFilters = computed(() =>
    Object.values(localFilters.value).some(v => v !== '')
);

const statusConfig = {
    draft:    { label: 'Brouillon',   classes: 'bg-slate-100 text-slate-700',   dot: 'bg-slate-400' },
    pending:  { label: 'En attente',  classes: 'bg-amber-50 text-amber-700',    dot: 'bg-amber-500' },
    approved: { label: 'Approuvee',   classes: 'bg-emerald-50 text-emerald-700', dot: 'bg-emerald-500' },
    rejected: { label: 'Refusee',     classes: 'bg-red-50 text-red-700',        dot: 'bg-red-500' },
} as const;

const formatAmount = (value: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(value));

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const progressLabel = (order: PurchaseOrder) => {
    if (order.status === 'approved') return `Circuit termine (${props.levelsCount}/${props.levelsCount})`;
    if (order.status === 'rejected') return 'Validation interrompue';
    if (order.status === 'pending' && order.current_level_order) {
        const done = Math.max(order.current_level_order - 1, 0);
        return `${done}/${props.levelsCount} valide${done > 1 ? 's' : ''} · niveau ${order.current_level_order}`;
    }
    return 'Non soumise';
};

const validatorsSummary = (order: PurchaseOrder): string => {
    if (!order.validation_logs?.length) return '';
    return order.validation_logs
        .filter(log => log.action === 'approved')
        .map(log => {
            const initials = log.user?.name?.split(' ').map(n => n[0]).join('').toUpperCase() ?? '?';
            const level = log.validation_level?.name ?? `N${log.validation_level_id}`;
            return `${level}: ${initials}.`;
        })
        .join(' · ');
};

const rejectedBy = (order: PurchaseOrder) => {
    if (order.status !== 'rejected') return null;
    return order.validation_logs?.find(log => log.action === 'rejected') ?? null;
};

const applyFilters = () => {
    const params: Record<string, string> = {};
    Object.entries(localFilters.value).forEach(([k, v]) => { if (v) params[k] = v; });
    router.get(route('purchase-orders.index'), params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    localFilters.value = { search: '', boutique_id: '', status: '', user_id: '', date_from: '', date_to: '', amount_min: '', amount_max: '', level_order: '' };
    router.get(route('purchase-orders.index'), {}, { preserveState: true, preserveScroll: true, replace: true });
};

const exportUrl = (format: string) => {
    const params = new URLSearchParams();
    Object.entries(localFilters.value).forEach(([k, v]) => { if (v) params.append(k, v); });
    const qs = params.toString();
    return route('purchase-orders.export', { format }) + (qs ? '?' + qs : '');
};

const confirmDelete = async (order: PurchaseOrder) => {
    const result = await Swal.fire({
        title: 'Supprimer cette commande ?',
        text: `"${order.title}" sera definitivement supprimee.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) router.delete(route('purchase-orders.destroy', order.id));
};

const submitOrder = async (order: PurchaseOrder) => {
    const result = await Swal.fire({
        title: 'Soumettre a la validation ?',
        text: `La commande "${order.title}" sera envoyee pour approbation.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Soumettre',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) router.post(route('purchase-orders.submit', order.id));
};
</script>

<template>
    <Head :title="isAdmin ? 'Commandes du groupe' : 'Mes commandes'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-xl font-bold text-foreground sm:text-2xl">{{ isAdmin ? 'Commandes du groupe' : 'Mes commandes' }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{{ orders.total }} commande{{ orders.total !== 1 ? 's' : '' }} au total</p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <!-- Export menu -->
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
                            <div
                                v-if="showExportMenu"
                                class="absolute right-0 top-full z-20 mt-2 w-48 overflow-hidden rounded-xl border bg-card shadow-lg"
                            >
                                <a
                                    :href="exportUrl('csv')"
                                    class="flex items-center gap-3 px-4 py-3 text-sm text-foreground transition-colors hover:bg-muted"
                                    @click="showExportMenu = false"
                                >
                                    <FileText class="h-4 w-4 text-muted-foreground" />
                                    Export CSV
                                </a>
                                <a
                                    :href="exportUrl('excel')"
                                    class="flex items-center gap-3 px-4 py-3 text-sm text-foreground transition-colors hover:bg-muted"
                                    @click="showExportMenu = false"
                                >
                                    <FileSpreadsheet class="h-4 w-4 text-emerald-600" />
                                    Export Excel
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

                    <Link
                        :href="route('purchase-orders.create')"
                        class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-primary px-3 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 sm:px-4"
                    >
                        <Plus class="h-4 w-4" />
                        <span class="hidden sm:inline">Nouvelle commande</span>
                    </Link>
                </div>
            </div>

            <!-- Filtres -->
            <div class="rounded-2xl border bg-card shadow-sm">
                <!-- Barre de recherche + toggle filtres -->
                <div class="flex items-center gap-3 p-4">
                    <div class="relative flex-1">
                        <input
                            v-model="localFilters.search"
                            type="text"
                            placeholder="Rechercher par titre ou description..."
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

                <!-- Filtres avancés -->
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
                            <!-- Boutique -->
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

                            <!-- Statut -->
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
                                    <option value="approved">Approuvee</option>
                                    <option value="rejected">Refusee</option>
                                </select>
                            </div>

                            <!-- Niveau de validation (visible seulement si statut = pending) -->
                            <Transition
                                enter-active-class="transition duration-150 ease-out"
                                enter-from-class="opacity-0 -translate-x-2"
                                enter-to-class="opacity-100 translate-x-0"
                                leave-active-class="transition duration-100 ease-in"
                                leave-from-class="opacity-100 translate-x-0"
                                leave-to-class="opacity-0 -translate-x-2"
                            >
                                <div v-if="localFilters.status === 'pending'">
                                    <label class="mb-1.5 block text-xs font-medium text-amber-600">Niveau de validation</label>
                                    <select
                                        v-model="localFilters.level_order"
                                        class="h-10 w-full rounded-xl border border-amber-300 bg-amber-50 px-3 text-sm text-foreground transition-colors focus:border-amber-500 focus:outline-none focus:ring-2 focus:ring-amber-300/40"
                                    >
                                        <option value="">Tous les niveaux</option>
                                        <option v-for="level in levels" :key="level.id" :value="String(level.order)">
                                            Niveau {{ level.order }} — {{ level.name }}
                                        </option>
                                    </select>
                                </div>
                            </Transition>

                            <!-- Demandeur (admin only) -->
                            <div v-if="isAdmin">
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Demandeur</label>
                                <select
                                    v-model="localFilters.user_id"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                >
                                    <option value="">Tous</option>
                                    <option v-for="d in demandeurs" :key="d.id" :value="String(d.id)">{{ d.name }}</option>
                                </select>
                            </div>

                            <!-- Date de -->
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Date creation (de)</label>
                                <input
                                    v-model="localFilters.date_from"
                                    type="date"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                />
                            </div>

                            <!-- Date à -->
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Date creation (a)</label>
                                <input
                                    v-model="localFilters.date_to"
                                    type="date"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                />
                            </div>

                            <!-- Montant min -->
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

                            <!-- Montant max -->
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

                        <div class="mt-3 flex items-center justify-between">
                            <p v-if="hasActiveFilters" class="text-xs text-muted-foreground">
                                {{ Object.values(localFilters).filter(v => v).length }} filtre{{ Object.values(localFilters).filter(v => v).length > 1 ? 's' : '' }} actif{{ Object.values(localFilters).filter(v => v).length > 1 ? 's' : '' }}
                            </p>
                            <span v-else />
                            <button
                                v-if="hasActiveFilters"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                @click="resetFilters"
                            >
                                <RotateCcw class="h-3.5 w-3.5" />
                                Reinitialiser les filtres
                            </button>
                        </div>
                    </div>
                </Transition>

                <!-- Tags filtres actifs (hors search) -->
                <div
                    v-if="!showFilters && (filters.boutique_id || filters.status || filters.user_id || filters.date_from || filters.date_to || filters.amount_min || filters.amount_max)"
                    class="flex flex-wrap gap-2 border-t px-4 py-3"
                >
                    <span v-if="filters.boutique_id" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Boutique filtrée
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.boutique_id = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.status" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Statut: {{ statusConfig[filters.status as keyof typeof statusConfig]?.label ?? filters.status }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.status = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.date_from || filters.date_to" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Periode: {{ filters.date_from || '...' }} → {{ filters.date_to || '...' }}
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.date_from = ''; localFilters.date_to = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                    <span v-if="filters.amount_min || filters.amount_max" class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2.5 py-1 text-xs font-medium text-primary">
                        Montant: {{ filters.amount_min || '0' }} → {{ filters.amount_max || '∞' }} XOF
                        <button class="ml-1 rounded-full hover:bg-primary/20" @click="localFilters.amount_min = ''; localFilters.amount_max = ''; applyFilters()"><X class="h-3 w-3" /></button>
                    </span>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <template v-if="orders.data.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/30">
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Commande</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell sm:px-6">Boutique</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:table-cell sm:px-6">Montant</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Statut</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:table-cell sm:px-6">Date</th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="order in orders.data" :key="order.id" class="transition-colors hover:bg-muted/20">
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-muted sm:flex">
                                                <FileText class="h-4 w-4 text-muted-foreground" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="max-w-[150px] truncate font-medium text-foreground sm:max-w-xs">{{ order.title }}</p>
                                                <p v-if="isAdmin && order.user" class="mt-0.5 text-xs text-muted-foreground">{{ order.user.name }}</p>
                                                <p class="mt-0.5 text-xs text-muted-foreground">{{ progressLabel(order) }}</p>
                                                <p v-if="validatorsSummary(order)" class="mt-0.5 text-xs font-medium text-emerald-600">
                                                    {{ validatorsSummary(order) }}
                                                </p>
                                                <p v-if="rejectedBy(order)" class="mt-0.5 text-xs font-medium text-red-500">
                                                    Refuse par {{ rejectedBy(order)?.user?.name }} ({{ rejectedBy(order)?.validation_level?.name }})
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden px-4 py-4 md:table-cell sm:px-6">
                                        <div class="flex items-center gap-2">
                                            <Building2 class="h-4 w-4 text-muted-foreground" />
                                            <span class="truncate text-sm text-foreground">{{ order.boutique?.name ?? 'Non renseignee' }}</span>
                                        </div>
                                    </td>
                                    <td class="hidden px-4 py-4 lg:table-cell sm:px-6">
                                        <span class="font-semibold text-foreground">{{ formatAmount(order.amount) }}</span>
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium sm:px-2.5" :class="statusConfig[order.status]?.classes">
                                            <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status]?.dot" />
                                            <span class="hidden sm:inline">{{ statusConfig[order.status]?.label }}</span>
                                        </span>
                                    </td>
                                    <td class="hidden px-4 py-4 text-sm text-muted-foreground xl:table-cell sm:px-6">
                                        {{ formatDate(order.created_at) }}
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center justify-end gap-1">
                                            <Link
                                                :href="route('purchase-orders.show', order.id)"
                                                class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                                title="Voir"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Link>
                                            <a
                                                :href="route('purchase-orders.pdf', order.id)"
                                                target="_blank"
                                                class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-indigo-50 hover:text-indigo-600"
                                                title="Telecharger PDF"
                                            >
                                                <Download class="h-4 w-4" />
                                            </a>
                                            <template v-if="order.status === 'draft' || order.status === 'rejected'">
                                                <Link
                                                    :href="route('purchase-orders.edit', order.id)"
                                                    class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                                    title="Modifier"
                                                >
                                                    <Pencil class="h-4 w-4" />
                                                </Link>
                                                <button
                                                    class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-emerald-50 hover:text-emerald-600"
                                                    title="Soumettre"
                                                    @click="submitOrder(order)"
                                                >
                                                    <Send class="h-4 w-4" />
                                                </button>
                                                <button
                                                    class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-600"
                                                    title="Supprimer"
                                                    @click="confirmDelete(order)"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="orders.last_page > 1" class="flex flex-col items-center gap-3 border-t px-4 py-4 sm:flex-row sm:justify-between sm:px-6">
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
                </template>

                <EmptyState
                    v-else-if="hasActiveFilters"
                    :icon="Filter"
                    icon-bg="bg-slate-100"
                    icon-color="text-slate-400"
                    title="Aucun résultat"
                    description="Aucune commande ne correspond aux filtres appliqués. Modifiez ou réinitialisez les filtres."
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
                <EmptyState
                    v-else
                    :icon="ShoppingCart"
                    icon-bg="bg-primary/10"
                    icon-color="text-primary"
                    title="Aucune commande pour l'instant"
                    description="Créez votre première demande d'achat et suivez son avancement jusqu'à l'approbation et la réception."
                    :action-href="route('purchase-orders.create')"
                    action-label="Créer une commande"
                    :bordered="false"
                />
            </div>
        </div>
    </AppLayout>
</template>
