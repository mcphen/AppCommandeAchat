<script setup lang="ts">
import AdminChecklist from '@/components/AdminChecklist.vue';
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type Budget, type PurchaseOrder, type SharedData, type ValidationLevel } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ShoppingCart, Clock, CheckCircle2, XCircle, FileText,
    TrendingUp, Users, Settings, ArrowRight, Eye,
    Store, Layers, Wallet, Ban, Building2, PiggyBank, AlertTriangle, UserCheck,
} from 'lucide-vue-next';
import { ArcElement, BarElement, CategoryScale, Chart as ChartJS, Legend, LinearScale, Tooltip } from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';
import { computed } from 'vue';

ChartJS.register(ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Tableau de bord', href: '/dashboard' }];

const page = usePage<SharedData>();
const role = computed(() => page.props.auth.user?.role?.slug);

interface BoutiqueStat {
    id: number;
    name: string;
    code: string;
    city?: string | null;
    orders_total: number;
    orders_approved: number;
    orders_pending: number;
    budget_approved: number | null;
}

interface ActiveDelegation {
    id: number;
    delegator: { id: number; name: string };
    validationLevel: { id: number; name: string; order: number };
    ends_at: string;
}

interface ChecklistStep {
    key: string;
    label: string;
    detail: string;
    done: boolean;
    href: string;
    cta: string;
}

interface Checklist {
    steps: ChecklistStep[];
    completed: number;
    total: number;
    all_done: boolean;
}

const props = defineProps<{
    stats: Record<string, number>;
    recentOrders?: PurchaseOrder[];
    totalUsers?: number;
    totalLevels?: number;
    totalBoutiques?: number;
    boutiqueStats?: BoutiqueStat[];
    boutique?: Boutique | null;
    validationLevel?: ValidationLevel | null;
    monthlyData?: {
        labels: string[];
        pending: number[];
        approved: number[];
        rejected: number[];
        draft: number[];
    };
    alertBudgets?: Budget[];
    activeDelegations?: ActiveDelegation[];
    checklist?: Checklist | null;
}>();

const statusConfig = {
    draft:    { label: 'Brouillon',  bg: 'bg-slate-100',   text: 'text-slate-600',   dot: 'bg-slate-400' },
    pending:  { label: 'En attente', bg: 'bg-amber-50',    text: 'text-amber-700',   dot: 'bg-amber-400' },
    approved: { label: 'Approuvée',  bg: 'bg-emerald-50',  text: 'text-emerald-700', dot: 'bg-emerald-500' },
    rejected: { label: 'Refusée',    bg: 'bg-red-50',      text: 'text-red-700',     dot: 'bg-red-500' },
} as const;

const formatAmount = (amount: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(amount));

const formatAmountShort = (amount: number) => {
    if (amount >= 1_000_000_000) return (amount / 1_000_000_000).toFixed(1) + ' Md';
    if (amount >= 1_000_000)     return (amount / 1_000_000).toFixed(1) + ' M';
    if (amount >= 1_000)         return (amount / 1_000).toFixed(0) + ' K';
    return amount.toString();
};

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

// --- Donut chart ---
const donutData = computed(() => {
    const s = props.stats;
    if (role.value === 'validateur') {
        return {
            labels: ['Approuvées', 'Refusées'],
            datasets: [{
                data: [s.my_approved ?? 0, s.my_rejected ?? 0],
                backgroundColor: ['#10b981', '#ef4444'],
                borderWidth: 0,
                hoverOffset: 6,
            }],
        };
    }
    return {
        labels: ['Brouillon', 'En attente', 'Approuvées', 'Refusées'],
        datasets: [{
            data: [s.draft ?? 0, s.pending ?? 0, s.approved ?? 0, s.rejected ?? 0],
            backgroundColor: ['#94a3b8', '#f59e0b', '#10b981', '#ef4444'],
            borderWidth: 0,
            hoverOffset: 6,
        }],
    };
});

const donutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8, font: { size: 12 } },
        },
        tooltip: { padding: 10, cornerRadius: 8 },
    },
    cutout: '68%',
};

// --- Bar chart ---
const barData = computed(() => ({
    labels: props.monthlyData?.labels ?? [],
    datasets: [
        {
            label: 'En attente',
            data: props.monthlyData?.pending ?? [],
            backgroundColor: '#fbbf24',
            borderRadius: 6,
            borderSkipped: false,
        },
        {
            label: 'Approuvées',
            data: props.monthlyData?.approved ?? [],
            backgroundColor: '#10b981',
            borderRadius: 6,
            borderSkipped: false,
        },
        {
            label: 'Refusées',
            data: props.monthlyData?.rejected ?? [],
            backgroundColor: '#ef4444',
            borderRadius: 6,
            borderSkipped: false,
        },
    ],
}));

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8, font: { size: 12 } },
        },
        tooltip: { padding: 10, cornerRadius: 8 },
    },
    scales: {
        x: { stacked: false, grid: { display: false }, border: { display: false } },
        y: { stacked: false, grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { stepSize: 1 } },
    },
};

const hasDonutData = computed(() => {
    const d = donutData.value.datasets[0].data as number[];
    return d.some(v => v > 0);
});

const hasBarData = computed(() =>
    props.monthlyData?.pending.some(v => v > 0) ||
    props.monthlyData?.approved.some(v => v > 0) ||
    props.monthlyData?.rejected.some(v => v > 0)
);
</script>

<template>
    <Head title="Tableau de bord" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- Titre -->
            <div>
                <h1 class="text-xl font-bold text-foreground sm:text-2xl">
                    Bonjour, {{ page.props.auth.user?.name?.split(' ')[0] }} 👋
                </h1>
                <p class="text-sm text-muted-foreground mt-1">
                    <template v-if="role === 'admin'">Vue d'ensemble de toutes les commandes</template>
                    <template v-else-if="role === 'validateur'">Commandes en attente de votre validation</template>
                    <template v-else>Suivi de vos commandes d'achat</template>
                </p>
            </div>

            <!-- Banner intérim (validateur avec délégations actives reçues) -->
            <div v-if="activeDelegations && activeDelegations.length > 0"
                 class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                <div class="flex items-start gap-3">
                    <UserCheck class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600" />
                    <div class="text-sm text-indigo-800">
                        <p class="font-semibold mb-1">Vous validez par intérim pour :</p>
                        <ul class="space-y-0.5">
                            <li v-for="d in activeDelegations" :key="d.id">
                                <span class="font-medium">{{ d.delegator.name }}</span>
                                — niveau <span class="font-medium">{{ d.validationLevel.name }}</span>
                                jusqu'au {{ new Date(d.ends_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) }}
                            </li>
                        </ul>
                    </div>
                    <Link :href="route('delegations.index')" class="ml-auto shrink-0 text-xs font-medium text-indigo-600 hover:underline">
                        Gérer
                    </Link>
                </div>
            </div>

            <!-- ===== ADMIN ===== -->
            <template v-if="role === 'admin'">

                <!-- Checklist d'activation -->
                <AdminChecklist v-if="checklist" :checklist="checklist" />

                <!-- Stat cards (counts) -->
                <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">Total</span>
                            <div class="rounded-lg bg-blue-50 p-2"><FileText class="h-4 w-4 text-blue-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.total }}</p>
                        <p class="text-xs text-muted-foreground mt-1">commandes</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">En attente</span>
                            <div class="rounded-lg bg-amber-50 p-2"><Clock class="h-4 w-4 text-amber-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.pending }}</p>
                        <p class="text-xs text-muted-foreground mt-1">en circuit</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">Approuvées</span>
                            <div class="rounded-lg bg-emerald-50 p-2"><CheckCircle2 class="h-4 w-4 text-emerald-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.approved }}</p>
                        <p class="text-xs text-emerald-600 font-medium mt-1">{{ formatAmountShort(stats.budget_approved ?? 0) }} FCFA</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">Refusées</span>
                            <div class="rounded-lg bg-red-50 p-2"><XCircle class="h-4 w-4 text-red-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.rejected }}</p>
                        <p class="text-xs text-muted-foreground mt-1">commandes</p>
                    </div>
                </div>

                <!-- Budget cards -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                    <div class="rounded-2xl border bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-emerald-800">Budget validé</span>
                            <div class="rounded-xl bg-emerald-100 p-2.5"><Wallet class="h-5 w-5 text-emerald-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-emerald-700 sm:text-3xl">
                            {{ formatAmount(stats.budget_approved ?? 0) }}
                        </p>
                        <p class="text-xs text-emerald-600 mt-1.5">{{ stats.approved }} commandes approuvées</p>
                    </div>
                    <div class="rounded-2xl border bg-gradient-to-br from-amber-50 to-amber-100/50 p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-amber-800">Budget en attente</span>
                            <div class="rounded-xl bg-amber-100 p-2.5"><Clock class="h-5 w-5 text-amber-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-amber-700 sm:text-3xl">
                            {{ formatAmount(stats.budget_pending ?? 0) }}
                        </p>
                        <p class="text-xs text-amber-600 mt-1.5">{{ stats.pending }} commandes en circuit</p>
                    </div>
                </div>

                <!-- Graphes -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="rounded-2xl border bg-card p-5 shadow-sm">
                        <h3 class="font-semibold text-foreground text-sm mb-4">Répartition des statuts</h3>
                        <div v-if="hasDonutData" class="h-56">
                            <Doughnut :data="donutData" :options="donutOptions" />
                        </div>
                        <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">
                            Aucune donnée
                        </div>
                    </div>
                    <div class="rounded-2xl border bg-card p-5 shadow-sm lg:col-span-2">
                        <h3 class="font-semibold text-foreground text-sm mb-4">Évolution sur 6 mois</h3>
                        <div v-if="hasBarData" class="h-56">
                            <Bar :data="barData" :options="barOptions" />
                        </div>
                        <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">
                            Aucune activité sur cette période
                        </div>
                    </div>
                </div>

                <!-- Performance par boutique -->
                <div v-if="boutiqueStats && boutiqueStats.length > 0" class="rounded-2xl border bg-card shadow-sm">
                    <div class="flex items-center justify-between border-b px-4 py-4 sm:px-6">
                        <h2 class="font-semibold text-foreground flex items-center gap-2">
                            <Store class="h-4 w-4 text-muted-foreground" />
                            Performance par boutique
                        </h2>
                        <Link :href="route('admin.boutiques.index')" class="text-sm text-primary hover:underline flex items-center gap-1">
                            Gérer <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                    <div class="divide-y">
                        <div
                            v-for="b in boutiqueStats"
                            :key="b.id"
                            class="grid grid-cols-12 items-center gap-3 px-4 py-3 hover:bg-muted/30 transition-colors sm:px-6"
                        >
                            <!-- Boutique info -->
                            <div class="col-span-5 flex items-center gap-3 min-w-0">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                                    <Building2 class="h-4 w-4 text-blue-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ b.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ b.code }}<template v-if="b.city"> · {{ b.city }}</template></p>
                                </div>
                            </div>
                            <!-- Counts -->
                            <div class="col-span-4 flex items-center gap-2 text-xs">
                                <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 font-medium text-blue-700">
                                    {{ b.orders_total }} total
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 font-medium text-amber-700">
                                    {{ b.orders_pending }} att.
                                </span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 font-medium text-emerald-700">
                                    {{ b.orders_approved }} OK
                                </span>
                            </div>
                            <!-- Budget approuvé -->
                            <div class="col-span-3 text-right">
                                <p class="text-sm font-semibold text-emerald-600">{{ formatAmountShort(b.budget_approved ?? 0) }}</p>
                                <p class="text-xs text-muted-foreground">validé</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Budgets en alerte -->
                <div v-if="alertBudgets && alertBudgets.length > 0" class="rounded-2xl border bg-card shadow-sm">
                    <div class="flex items-center justify-between border-b px-4 py-4 sm:px-6">
                        <h2 class="font-semibold text-foreground flex items-center gap-2">
                            <PiggyBank class="h-4 w-4 text-muted-foreground" />
                            Suivi budgétaire
                        </h2>
                        <Link :href="route('admin.budgets.index')" class="text-sm text-primary hover:underline flex items-center gap-1">
                            Tout voir <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                    <div class="divide-y">
                        <div v-for="b in alertBudgets" :key="b.id" class="px-4 py-3 sm:px-6 hover:bg-muted/20 transition-colors">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <div class="flex items-center gap-2 min-w-0 flex-1">
                                    <AlertTriangle v-if="b.consumption?.is_exceeded" class="h-3.5 w-3.5 shrink-0 text-red-500" />
                                    <AlertTriangle v-else-if="b.consumption?.is_warning" class="h-3.5 w-3.5 shrink-0 text-amber-500" />
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-foreground truncate">
                                            {{ b.boutique?.name ?? 'Toutes boutiques' }}
                                            <span class="text-muted-foreground font-normal"> · {{ b.category?.name ?? 'Toutes catégories' }}</span>
                                        </p>
                                        <p class="text-xs text-muted-foreground">{{ b.period }}</p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="text-sm font-bold" :class="b.consumption?.is_exceeded ? 'text-red-600' : b.consumption?.is_warning ? 'text-amber-600' : 'text-foreground'">
                                        {{ b.consumption?.percent_engaged ?? 0 }}%
                                    </span>
                                    <p class="text-xs text-muted-foreground">engagé</p>
                                </div>
                            </div>
                            <div class="h-1.5 w-full rounded-full bg-muted overflow-hidden">
                                <div class="h-full rounded-full transition-all"
                                    :class="b.consumption?.is_exceeded ? 'bg-red-500' : b.consumption?.is_warning ? 'bg-amber-400' : 'bg-emerald-500'"
                                    :style="{ width: Math.min(100, b.consumption?.percent_engaged ?? 0) + '%' }" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Admin info row -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
                    <Link :href="route('admin.users.index')" class="rounded-2xl border bg-card p-4 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between group sm:p-5">
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-violet-50 p-2.5"><Users class="h-4 w-4 text-violet-600 sm:h-5 sm:w-5" /></div>
                            <div>
                                <p class="font-semibold text-foreground text-sm sm:text-base">{{ totalUsers }} utilisateurs</p>
                                <p class="text-xs text-muted-foreground">Gérer les comptes</p>
                            </div>
                        </div>
                        <ArrowRight class="h-4 w-4 text-muted-foreground group-hover:translate-x-1 transition-transform shrink-0" />
                    </Link>
                    <Link :href="route('admin.validation-levels.index')" class="rounded-2xl border bg-card p-4 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between group sm:p-5">
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-indigo-50 p-2.5"><Settings class="h-4 w-4 text-indigo-600 sm:h-5 sm:w-5" /></div>
                            <div>
                                <p class="font-semibold text-foreground text-sm sm:text-base">{{ totalLevels }} niveaux</p>
                                <p class="text-xs text-muted-foreground">Circuit de validation</p>
                            </div>
                        </div>
                        <ArrowRight class="h-4 w-4 text-muted-foreground group-hover:translate-x-1 transition-transform shrink-0" />
                    </Link>
                    <Link :href="route('admin.boutiques.index')" class="rounded-2xl border bg-card p-4 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between group sm:p-5">
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-blue-50 p-2.5"><Store class="h-4 w-4 text-blue-600 sm:h-5 sm:w-5" /></div>
                            <div>
                                <p class="font-semibold text-foreground text-sm sm:text-base">{{ totalBoutiques }} boutiques</p>
                                <p class="text-xs text-muted-foreground">Gérer les boutiques</p>
                            </div>
                        </div>
                        <ArrowRight class="h-4 w-4 text-muted-foreground group-hover:translate-x-1 transition-transform shrink-0" />
                    </Link>
                </div>
            </template>

            <!-- ===== VALIDATEUR ===== -->
            <template v-else-if="role === 'validateur'">

                <!-- Bandeau niveau -->
                <div v-if="validationLevel" class="rounded-2xl border border-indigo-200 bg-gradient-to-r from-indigo-50 to-indigo-100/50 p-4 flex items-center gap-4 sm:p-5">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-indigo-100">
                        <Layers class="h-6 w-6 text-indigo-600" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500 mb-0.5">Votre niveau de validation</p>
                        <p class="text-base font-bold text-indigo-800">
                            Niveau {{ validationLevel.order }}
                            <template v-if="totalLevels"> / {{ totalLevels }}</template>
                            — {{ validationLevel.name }}
                        </p>
                        <p v-if="validationLevel.description" class="text-xs text-indigo-600 mt-0.5">{{ validationLevel.description }}</p>
                    </div>
                </div>

                <!-- Stat cards -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-muted-foreground">À valider</span>
                            <div class="rounded-lg bg-amber-50 p-2"><Clock class="h-4 w-4 text-amber-600" /></div>
                        </div>
                        <p class="text-3xl font-bold text-foreground">{{ stats.pending }}</p>
                        <p class="text-xs text-muted-foreground mt-1">à mon niveau</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-muted-foreground">Approuvées par moi</span>
                            <div class="rounded-lg bg-emerald-50 p-2"><CheckCircle2 class="h-4 w-4 text-emerald-600" /></div>
                        </div>
                        <p class="text-3xl font-bold text-foreground">{{ stats.my_approved }}</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-muted-foreground">Refusées par moi</span>
                            <div class="rounded-lg bg-red-50 p-2"><XCircle class="h-4 w-4 text-red-600" /></div>
                        </div>
                        <p class="text-3xl font-bold text-foreground">{{ stats.my_rejected }}</p>
                    </div>
                </div>

                <!-- Donut validateur -->
                <div v-if="hasDonutData" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border bg-card p-5 shadow-sm">
                        <h3 class="font-semibold text-foreground text-sm mb-4">Mes décisions</h3>
                        <div class="h-56">
                            <Doughnut :data="donutData" :options="donutOptions" />
                        </div>
                    </div>
                    <div v-if="stats.pending > 0" class="rounded-2xl border border-amber-200 bg-amber-50 p-5 flex flex-col justify-center items-center text-center gap-3">
                        <div class="h-12 w-12 rounded-full bg-amber-100 flex items-center justify-center">
                            <Clock class="h-6 w-6 text-amber-600" />
                        </div>
                        <p class="text-3xl font-bold text-amber-700">{{ stats.pending }}</p>
                        <p class="text-sm font-medium text-amber-800">commande{{ stats.pending > 1 ? 's' : '' }} en attente de votre validation</p>
                        <Link :href="route('validations.index')" class="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-600 transition-colors">
                            Voir les validations <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                </div>
                <div v-else-if="stats.pending > 0" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="h-2 w-2 rounded-full bg-amber-500 animate-pulse shrink-0" />
                        <p class="text-sm font-medium text-amber-800 truncate">
                            {{ stats.pending }} commande{{ stats.pending > 1 ? 's' : '' }} en attente
                        </p>
                    </div>
                    <Link :href="route('validations.index')" class="text-sm font-semibold text-amber-700 flex items-center gap-1 hover:gap-2 transition-all shrink-0">
                        Voir <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
            </template>

            <!-- ===== DEMANDEUR ===== -->
            <template v-else>

                <!-- Boutique card -->
                <div v-if="boutique" class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                            <Store class="h-5 w-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Votre boutique</p>
                            <p class="text-base font-semibold text-foreground">{{ boutique.name }}</p>
                            <p class="text-xs text-muted-foreground">
                                {{ boutique.code }}<template v-if="boutique.city"> · {{ boutique.city }}</template>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Stat cards -->
                <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">Total</span>
                            <div class="rounded-lg bg-blue-50 p-2"><ShoppingCart class="h-4 w-4 text-blue-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.total }}</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">En attente</span>
                            <div class="rounded-lg bg-amber-50 p-2"><Clock class="h-4 w-4 text-amber-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.pending }}</p>
                        <p class="text-xs text-amber-600 font-medium mt-1">{{ formatAmountShort(stats.budget_pending ?? 0) }} FCFA</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">Approuvées</span>
                            <div class="rounded-lg bg-emerald-50 p-2"><CheckCircle2 class="h-4 w-4 text-emerald-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.approved }}</p>
                        <p class="text-xs text-emerald-600 font-medium mt-1">{{ formatAmountShort(stats.budget_approved ?? 0) }} FCFA</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">Refusées</span>
                            <div class="rounded-lg bg-red-50 p-2"><XCircle class="h-4 w-4 text-red-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.rejected }}</p>
                    </div>
                </div>

                <!-- Budget cards -->
                <div v-if="stats.total > 0" class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                    <div class="rounded-2xl border bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-emerald-800">Budget validé</span>
                            <div class="rounded-xl bg-emerald-100 p-2.5"><Wallet class="h-5 w-5 text-emerald-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-emerald-700">{{ formatAmount(stats.budget_approved ?? 0) }}</p>
                        <p class="text-xs text-emerald-600 mt-1.5">{{ stats.approved }} commandes approuvées</p>
                    </div>
                    <div class="rounded-2xl border bg-gradient-to-br from-amber-50 to-amber-100/50 p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-amber-800">Budget en circuit</span>
                            <div class="rounded-xl bg-amber-100 p-2.5"><Clock class="h-5 w-5 text-amber-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-amber-700">{{ formatAmount(stats.budget_pending ?? 0) }}</p>
                        <p class="text-xs text-amber-600 mt-1.5">{{ stats.pending }} commandes en attente</p>
                    </div>
                </div>

                <!-- Graphes demandeur -->
                <div v-if="stats.total > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border bg-card p-5 shadow-sm">
                        <h3 class="font-semibold text-foreground text-sm mb-4">Répartition de mes commandes</h3>
                        <div v-if="hasDonutData" class="h-56">
                            <Doughnut :data="donutData" :options="donutOptions" />
                        </div>
                        <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">Aucune donnée</div>
                    </div>
                    <div class="rounded-2xl border bg-card p-5 shadow-sm">
                        <h3 class="font-semibold text-foreground text-sm mb-4">Activité sur 6 mois</h3>
                        <div v-if="hasBarData" class="h-56">
                            <Bar :data="barData" :options="barOptions" />
                        </div>
                        <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">Aucune activité sur cette période</div>
                    </div>
                </div>
            </template>

            <!-- ===== Commandes récentes (admin + validateur + demandeur) ===== -->
            <div v-if="recentOrders && recentOrders.length > 0" class="rounded-2xl border bg-card shadow-sm">
                <div class="flex items-center justify-between border-b px-4 py-4 sm:px-6">
                    <h2 class="font-semibold text-foreground flex items-center gap-2">
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                        <template v-if="role === 'validateur'">Commandes à valider</template>
                        <template v-else>Commandes récentes</template>
                    </h2>
                    <Link
                        :href="role === 'admin' ? route('validations.index') : role === 'validateur' ? route('validations.index') : route('purchase-orders.index')"
                        class="text-sm text-primary hover:underline flex items-center gap-1"
                    >
                        Voir tout <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
                <div class="divide-y">
                    <div
                        v-for="order in recentOrders"
                        :key="order.id"
                        class="flex items-center justify-between px-4 py-3 hover:bg-muted/30 transition-colors gap-3 sm:px-6 sm:py-4"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="hidden h-9 w-9 items-center justify-center rounded-xl bg-muted shrink-0 sm:flex">
                                <FileText class="h-4 w-4 text-muted-foreground" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-sm text-foreground truncate">{{ order.title }}</p>
                                <p class="text-xs text-muted-foreground mt-0.5 flex items-center gap-1.5 flex-wrap">
                                    <template v-if="role === 'admin'">
                                        <span>{{ order.user?.name }}</span>
                                        <span class="text-muted-foreground/40">·</span>
                                    </template>
                                    <template v-if="order.boutique">
                                        <span class="inline-flex items-center gap-1">
                                            <Store class="h-3 w-3" />{{ order.boutique.name }}
                                        </span>
                                        <span class="text-muted-foreground/40">·</span>
                                    </template>
                                    <span>{{ formatDate(order.created_at) }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 sm:gap-3">
                            <p class="hidden text-sm font-semibold text-foreground sm:block">{{ formatAmount(order.amount) }}</p>
                            <!-- Badge statut + niveau pour pending -->
                            <template v-if="order.status === 'pending' && order.current_level_order">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400" />
                                    <span class="hidden sm:inline">Niv. {{ order.current_level_order }}</span>
                                    <template v-if="totalLevels">
                                        <span class="hidden sm:inline text-amber-400">/{{ totalLevels }}</span>
                                    </template>
                                    <span class="sm:hidden">Att. {{ order.current_level_order }}<template v-if="totalLevels">/{{ totalLevels }}</template></span>
                                </span>
                            </template>
                            <template v-else>
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium sm:px-2.5"
                                    :class="[statusConfig[order.status]?.bg, statusConfig[order.status]?.text]"
                                >
                                    <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status]?.dot" />
                                    <span class="hidden sm:inline">{{ statusConfig[order.status]?.label }}</span>
                                </span>
                            </template>
                            <Link
                                :href="role === 'admin' || role === 'validateur'
                                    ? route('validations.show', order.id)
                                    : route('purchase-orders.show', order.id)"
                                class="rounded-lg p-1.5 hover:bg-muted transition-colors"
                            >
                                <Eye class="h-4 w-4 text-muted-foreground" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CTA vide (demandeur) -->
            <EmptyState
                v-else-if="role === 'demandeur' && stats.total === 0"
                :icon="ShoppingCart"
                icon-bg="bg-primary/10"
                icon-color="text-primary"
                title="Aucune commande pour l'instant"
                description="Les commandes importées depuis Sage100 apparaîtront ici dès qu'elles seront synchronisées."
            />

        </div>
    </AppLayout>
</template>
