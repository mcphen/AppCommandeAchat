<script setup lang="ts">
import AdminChecklist from '@/components/AdminChecklist.vue';
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type Budget, type DisbursementRequest, type PurchaseOrder, type SharedData, type ValidationLevel } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ShoppingCart, Clock, CheckCircle2, XCircle, FileText,
    TrendingUp, Users, Settings, ArrowRight, Eye,
    Store, Layers, Wallet, Ban, Building2, PiggyBank, AlertTriangle, UserCheck, History, BarChart3, Banknote,
} from 'lucide-vue-next';
import { ArcElement, BarElement, CategoryScale, Chart as ChartJS, Legend, LinearScale, Tooltip } from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';
import { computed, ref } from 'vue';

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

interface DDBoutiqueStat {
    id: number;
    name: string;
    code: string;
    city?: string | null;
    dd_total: number;
    dd_pending: number;
    dd_approved: number;
    dd_budget_approved: number | null;
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
    recentDisbursements?: DisbursementRequest[];
    totalUsers?: number;
    totalLevels?: number;
    totalBoutiques?: number;
    boutiqueStats?: BoutiqueStat[];
    ddBoutiqueStats?: DDBoutiqueStat[];
    boutique?: Boutique | null;
    validationLevel?: ValidationLevel | null;
    monthlyData?: {
        labels: string[];
        pending: number[];
        approved: number[];
        rejected: number[];
        draft: number[];
    };
    ddMonthlyData?: {
        labels: string[];
        pending: number[];
        approved: number[];
        rejected: number[];
    };
    alertBudgets?: Budget[];
    activeDelegations?: ActiveDelegation[];
    pipeline?: Array<{
        level_order: number;
        level_name: string;
        pending: number;
        amount: number;
    }>;
    checklist?: Checklist | null;
}>();

// Hub tab for admin
const activeModule = ref<'bc' | 'dd'>('bc');

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

// --- BC Donut chart ---
const bcDonutData = computed(() => {
    const s = props.stats;
    if (role.value === 'validateur') {
        return {
            labels: ['Approuvées', 'Refusées'],
            datasets: [{ data: [s.my_approved ?? 0, s.my_rejected ?? 0], backgroundColor: ['#10b981', '#ef4444'], borderWidth: 0, hoverOffset: 6 }],
        };
    }
    return {
        labels: ['Brouillon', 'En attente', 'Approuvées', 'Refusées'],
        datasets: [{ data: [s.draft ?? 0, s.pending ?? 0, s.approved ?? 0, s.rejected ?? 0], backgroundColor: ['#94a3b8', '#f59e0b', '#10b981', '#ef4444'], borderWidth: 0, hoverOffset: 6 }],
    };
});

// --- DD Donut chart ---
const ddDonutData = computed(() => ({
    labels: ['En attente', 'Approuvées', 'Refusées'],
    datasets: [{
        data: [props.stats.dd_pending ?? 0, props.stats.dd_approved ?? 0, props.stats.dd_rejected ?? 0],
        backgroundColor: ['#f59e0b', '#10b981', '#ef4444'],
        borderWidth: 0,
        hoverOffset: 6,
    }],
}));

const donutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' as const, labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8, font: { size: 12 } } },
        tooltip: { padding: 10, cornerRadius: 8 },
    },
    cutout: '68%',
};

// --- BC Bar chart ---
const bcBarData = computed(() => ({
    labels: props.monthlyData?.labels ?? [],
    datasets: [
        { label: 'En attente', data: props.monthlyData?.pending ?? [], backgroundColor: '#fbbf24', borderRadius: 6, borderSkipped: false },
        { label: 'Approuvées', data: props.monthlyData?.approved ?? [], backgroundColor: '#10b981', borderRadius: 6, borderSkipped: false },
        { label: 'Refusées',   data: props.monthlyData?.rejected ?? [], backgroundColor: '#ef4444', borderRadius: 6, borderSkipped: false },
    ],
}));

// --- DD Bar chart ---
const ddBarData = computed(() => ({
    labels: props.ddMonthlyData?.labels ?? [],
    datasets: [
        { label: 'En attente', data: props.ddMonthlyData?.pending ?? [], backgroundColor: '#fbbf24', borderRadius: 6, borderSkipped: false },
        { label: 'Approuvées', data: props.ddMonthlyData?.approved ?? [], backgroundColor: '#10b981', borderRadius: 6, borderSkipped: false },
        { label: 'Refusées',   data: props.ddMonthlyData?.rejected ?? [], backgroundColor: '#ef4444', borderRadius: 6, borderSkipped: false },
    ],
}));

const barOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: 'bottom' as const, labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8, font: { size: 12 } } },
        tooltip: { padding: 10, cornerRadius: 8 },
    },
    scales: {
        x: { stacked: false, grid: { display: false }, border: { display: false } },
        y: { stacked: false, grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { stepSize: 1 } },
    },
};

const hasBcDonutData = computed(() => (bcDonutData.value.datasets[0].data as number[]).some(v => v > 0));
const hasBcBarData   = computed(() => props.monthlyData?.pending.some(v => v > 0) || props.monthlyData?.approved.some(v => v > 0) || props.monthlyData?.rejected.some(v => v > 0));
const hasDdDonutData = computed(() => ddDonutData.value.datasets[0].data.some(v => v > 0));
const hasDdBarData   = computed(() => props.ddMonthlyData?.pending.some(v => v > 0) || props.ddMonthlyData?.approved.some(v => v > 0) || props.ddMonthlyData?.rejected.some(v => v > 0));
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
                    <template v-if="role === 'admin'">Vue d'ensemble de l'activité du groupe</template>
                    <template v-else-if="role === 'validateur'">Commandes en attente de votre validation</template>
                    <template v-else-if="role === 'caissier'">Paiements en attente de traitement</template>
                    <template v-else>Suivi de vos commandes d'achat</template>
                </p>
            </div>

            <!-- Banner intérim (validateur) -->
            <div v-if="activeDelegations && activeDelegations.length > 0" class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
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
                    <Link :href="route('delegations.index')" class="ml-auto shrink-0 text-xs font-medium text-indigo-600 hover:underline">Gérer</Link>
                </div>
            </div>

            <!-- ===== ADMIN ===== -->
            <template v-if="role === 'admin'">

                <!-- Checklist -->
                <AdminChecklist v-if="checklist" :checklist="checklist" />

                <!-- HUB — deux modules -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <!-- Module Bons de commande -->
                    <button
                        class="group relative overflow-hidden rounded-2xl border-2 p-5 text-left shadow-sm transition-all duration-200 hover:shadow-md"
                        :class="activeModule === 'bc' ? 'border-primary bg-primary/5 shadow-md' : 'border-border bg-card hover:border-primary/30'"
                        @click="activeModule = 'bc'"
                    >
                        <div v-if="activeModule === 'bc'" class="absolute right-0 top-0 h-1 w-full rounded-t-2xl bg-primary" />
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl" :class="activeModule === 'bc' ? 'bg-primary/10 text-primary' : 'bg-blue-50 text-blue-600'">
                                <ShoppingCart class="h-7 w-7" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold uppercase tracking-wide" :class="activeModule === 'bc' ? 'text-primary' : 'text-muted-foreground'">Module</p>
                                <h2 class="mt-0.5 text-lg font-bold text-foreground">Bons de commande</h2>
                                <p class="mt-2 text-sm text-muted-foreground">Circuit de validation des achats</p>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-2">
                            <div class="rounded-xl bg-muted/40 p-2.5 text-center">
                                <p class="text-xl font-bold text-foreground">{{ stats.total }}</p>
                                <p class="text-xs text-muted-foreground">Total</p>
                            </div>
                            <div class="rounded-xl bg-amber-50 p-2.5 text-center">
                                <p class="text-xl font-bold text-amber-700">{{ stats.pending }}</p>
                                <p class="text-xs text-amber-600">En attente</p>
                            </div>
                            <div class="rounded-xl bg-emerald-50 p-2.5 text-center">
                                <p class="text-xl font-bold text-emerald-700">{{ stats.approved }}</p>
                                <p class="text-xs text-emerald-600">Approuvées</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-1 text-xs font-semibold" :class="activeModule === 'bc' ? 'text-primary' : 'text-muted-foreground'">
                            {{ formatAmountShort(stats.budget_approved ?? 0) }} FCFA validés
                            <ArrowRight class="ml-auto h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </div>
                    </button>

                    <!-- Module Décaissements DD -->
                    <button
                        class="group relative overflow-hidden rounded-2xl border-2 p-5 text-left shadow-sm transition-all duration-200 hover:shadow-md"
                        :class="activeModule === 'dd' ? 'border-indigo-500 bg-indigo-50/50 shadow-md' : 'border-border bg-card hover:border-indigo-300'"
                        @click="activeModule = 'dd'"
                    >
                        <div v-if="activeModule === 'dd'" class="absolute right-0 top-0 h-1 w-full rounded-t-2xl bg-indigo-500" />
                        <div class="flex items-start gap-4">
                            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl" :class="activeModule === 'dd' ? 'bg-indigo-100 text-indigo-600' : 'bg-indigo-50 text-indigo-500'">
                                <Banknote class="h-7 w-7" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-xs font-semibold uppercase tracking-wide" :class="activeModule === 'dd' ? 'text-indigo-600' : 'text-muted-foreground'">Module</p>
                                <h2 class="mt-0.5 text-lg font-bold text-foreground">Décaissements DD</h2>
                                <p class="mt-2 text-sm text-muted-foreground">Demandes de décaissement direct</p>
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-2">
                            <div class="rounded-xl bg-muted/40 p-2.5 text-center">
                                <p class="text-xl font-bold text-foreground">{{ stats.dd_total ?? 0 }}</p>
                                <p class="text-xs text-muted-foreground">Total</p>
                            </div>
                            <div class="rounded-xl bg-amber-50 p-2.5 text-center">
                                <p class="text-xl font-bold text-amber-700">{{ stats.dd_pending ?? 0 }}</p>
                                <p class="text-xs text-amber-600">En attente</p>
                            </div>
                            <div class="rounded-xl bg-emerald-50 p-2.5 text-center">
                                <p class="text-xl font-bold text-emerald-700">{{ stats.dd_approved ?? 0 }}</p>
                                <p class="text-xs text-emerald-600">Approuvées</p>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center gap-1 text-xs font-semibold" :class="activeModule === 'dd' ? 'text-indigo-600' : 'text-muted-foreground'">
                            {{ formatAmountShort(stats.dd_budget_approved ?? 0) }} FCFA validés
                            <ArrowRight class="ml-auto h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </div>
                    </button>
                </div>

                <!-- Séparateur avec label module actif -->
                <div class="flex items-center gap-3">
                    <div class="h-px flex-1 bg-border" />
                    <span class="inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-xs font-semibold"
                        :class="activeModule === 'bc' ? 'border-primary/30 bg-primary/5 text-primary' : 'border-indigo-200 bg-indigo-50 text-indigo-700'">
                        <ShoppingCart v-if="activeModule === 'bc'" class="h-3.5 w-3.5" />
                        <Banknote v-else class="h-3.5 w-3.5" />
                        {{ activeModule === 'bc' ? 'Bons de commande — analytique' : 'Décaissements DD — analytique' }}
                    </span>
                    <div class="h-px flex-1 bg-border" />
                </div>

                <!-- ===== ONGLET BC ===== -->
                <template v-if="activeModule === 'bc'">

                    <!-- Budget cards -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                        <div class="rounded-2xl border bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-emerald-800">Budget validé</span>
                                <div class="rounded-xl bg-emerald-100 p-2.5"><Wallet class="h-5 w-5 text-emerald-600" /></div>
                            </div>
                            <p class="text-2xl font-bold text-emerald-700 sm:text-3xl">{{ formatAmount(stats.budget_approved ?? 0) }}</p>
                            <p class="text-xs text-emerald-600 mt-1.5">{{ stats.approved }} commandes approuvées</p>
                        </div>
                        <div class="rounded-2xl border bg-gradient-to-br from-amber-50 to-amber-100/50 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-amber-800">Budget en attente</span>
                                <div class="rounded-xl bg-amber-100 p-2.5"><Clock class="h-5 w-5 text-amber-600" /></div>
                            </div>
                            <p class="text-2xl font-bold text-amber-700 sm:text-3xl">{{ formatAmount(stats.budget_pending ?? 0) }}</p>
                            <p class="text-xs text-amber-600 mt-1.5">{{ stats.pending }} commandes en circuit</p>
                        </div>
                    </div>

                    <!-- Graphes BC -->
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                        <div class="rounded-2xl border bg-card p-5 shadow-sm">
                            <h3 class="font-semibold text-foreground text-sm mb-4">Répartition des statuts</h3>
                            <div v-if="hasBcDonutData" class="h-56"><Doughnut :data="bcDonutData" :options="donutOptions" /></div>
                            <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">Aucune donnée</div>
                        </div>
                        <div class="rounded-2xl border bg-card p-5 shadow-sm lg:col-span-2">
                            <h3 class="font-semibold text-foreground text-sm mb-4">Évolution sur 6 mois</h3>
                            <div v-if="hasBcBarData" class="h-56"><Bar :data="bcBarData" :options="barOptions" /></div>
                            <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">Aucune activité sur cette période</div>
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
                            <div v-for="b in boutiqueStats" :key="b.id" class="grid grid-cols-12 items-center gap-3 px-4 py-3 hover:bg-muted/30 transition-colors sm:px-6">
                                <div class="col-span-5 flex items-center gap-3 min-w-0">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                                        <Building2 class="h-4 w-4 text-blue-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-foreground truncate">{{ b.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ b.code }}<template v-if="b.city"> · {{ b.city }}</template></p>
                                    </div>
                                </div>
                                <div class="col-span-4 flex items-center gap-2 text-xs">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2 py-0.5 font-medium text-blue-700">{{ b.orders_total }} total</span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 font-medium text-amber-700">{{ b.orders_pending }} att.</span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 font-medium text-emerald-700">{{ b.orders_approved }} OK</span>
                                </div>
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
                            <Link :href="route('admin.budgets.index')" class="text-sm text-primary hover:underline flex items-center gap-1">Tout voir <ArrowRight class="h-3.5 w-3.5" /></Link>
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

                    <!-- Commandes récentes -->
                    <div v-if="recentOrders && recentOrders.length > 0" class="rounded-2xl border bg-card shadow-sm">
                        <div class="flex items-center justify-between border-b px-4 py-4 sm:px-6">
                            <h2 class="font-semibold text-foreground flex items-center gap-2">
                                <TrendingUp class="h-4 w-4 text-muted-foreground" />
                                Commandes récentes
                            </h2>
                            <Link :href="route('validations.index')" class="text-sm text-primary hover:underline flex items-center gap-1">Voir tout <ArrowRight class="h-3.5 w-3.5" /></Link>
                        </div>
                        <div class="divide-y">
                            <div v-for="order in recentOrders" :key="order.id" class="flex items-center justify-between px-4 py-3 hover:bg-muted/30 transition-colors gap-3 sm:px-6 sm:py-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="hidden h-9 w-9 items-center justify-center rounded-xl bg-muted shrink-0 sm:flex">
                                        <FileText class="h-4 w-4 text-muted-foreground" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-sm text-foreground truncate">{{ order.title }}</p>
                                        <p class="text-xs text-muted-foreground mt-0.5 flex items-center gap-1.5">
                                            <span>{{ order.user?.name }}</span>
                                            <span v-if="order.boutique" class="text-muted-foreground/40">·</span>
                                            <span v-if="order.boutique" class="inline-flex items-center gap-1"><Store class="h-3 w-3" />{{ order.boutique.name }}</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <p class="hidden text-sm font-semibold text-foreground sm:block">{{ formatAmount(order.amount) }}</p>
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium"
                                        :class="[statusConfig[order.status as keyof typeof statusConfig]?.bg, statusConfig[order.status as keyof typeof statusConfig]?.text]">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status as keyof typeof statusConfig]?.dot" />
                                        {{ statusConfig[order.status as keyof typeof statusConfig]?.label }}
                                    </span>
                                    <Link :href="route('validations.show', order.uuid)" class="rounded-lg p-1.5 hover:bg-muted transition-colors">
                                        <Eye class="h-4 w-4 text-muted-foreground" />
                                    </Link>
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

                <!-- ===== ONGLET DD ===== -->
                <template v-else>

                    <!-- Budget cards DD -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                        <div class="rounded-2xl border bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-emerald-800">Montant validé</span>
                                <div class="rounded-xl bg-emerald-100 p-2.5"><Wallet class="h-5 w-5 text-emerald-600" /></div>
                            </div>
                            <p class="text-2xl font-bold text-emerald-700 sm:text-3xl">{{ formatAmount(stats.dd_budget_approved ?? 0) }}</p>
                            <p class="text-xs text-emerald-600 mt-1.5">{{ stats.dd_approved ?? 0 }} demandes approuvées</p>
                        </div>
                        <div class="rounded-2xl border bg-gradient-to-br from-amber-50 to-amber-100/50 p-5 shadow-sm">
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-sm font-medium text-amber-800">Montant en attente</span>
                                <div class="rounded-xl bg-amber-100 p-2.5"><Clock class="h-5 w-5 text-amber-600" /></div>
                            </div>
                            <p class="text-2xl font-bold text-amber-700 sm:text-3xl">{{ formatAmount(stats.dd_budget_pending ?? 0) }}</p>
                            <p class="text-xs text-amber-600 mt-1.5">{{ stats.dd_pending ?? 0 }} demandes en circuit</p>
                        </div>
                    </div>

                    <!-- Graphes DD -->
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                        <div class="rounded-2xl border bg-card p-5 shadow-sm">
                            <h3 class="font-semibold text-foreground text-sm mb-4">Répartition des statuts</h3>
                            <div v-if="hasDdDonutData" class="h-56"><Doughnut :data="ddDonutData" :options="donutOptions" /></div>
                            <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">Aucune donnée</div>
                        </div>
                        <div class="rounded-2xl border bg-card p-5 shadow-sm lg:col-span-2">
                            <h3 class="font-semibold text-foreground text-sm mb-4">Évolution sur 6 mois</h3>
                            <div v-if="hasDdBarData" class="h-56"><Bar :data="ddBarData" :options="barOptions" /></div>
                            <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">Aucune activité sur cette période</div>
                        </div>
                    </div>

                    <!-- Performance par boutique DD -->
                    <div v-if="ddBoutiqueStats && ddBoutiqueStats.length > 0" class="rounded-2xl border bg-card shadow-sm">
                        <div class="flex items-center justify-between border-b px-4 py-4 sm:px-6">
                            <h2 class="font-semibold text-foreground flex items-center gap-2">
                                <Store class="h-4 w-4 text-muted-foreground" />
                                Décaissements par boutique
                            </h2>
                            <Link :href="route('disbursement-requests.index')" class="text-sm text-indigo-600 hover:underline flex items-center gap-1">
                                Voir tout <ArrowRight class="h-3.5 w-3.5" />
                            </Link>
                        </div>
                        <div class="divide-y">
                            <div v-for="b in ddBoutiqueStats" :key="b.id" class="grid grid-cols-12 items-center gap-3 px-4 py-3 hover:bg-muted/30 transition-colors sm:px-6">
                                <div class="col-span-5 flex items-center gap-3 min-w-0">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50">
                                        <Building2 class="h-4 w-4 text-indigo-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-foreground truncate">{{ b.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ b.code }}<template v-if="b.city"> · {{ b.city }}</template></p>
                                    </div>
                                </div>
                                <div class="col-span-4 flex items-center gap-2 text-xs">
                                    <span class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 font-medium text-indigo-700">{{ b.dd_total }} total</span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 font-medium text-amber-700">{{ b.dd_pending }} att.</span>
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 font-medium text-emerald-700">{{ b.dd_approved }} OK</span>
                                </div>
                                <div class="col-span-3 text-right">
                                    <p class="text-sm font-semibold text-emerald-600">{{ formatAmountShort(b.dd_budget_approved ?? 0) }}</p>
                                    <p class="text-xs text-muted-foreground">validé</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Demandes récentes DD -->
                    <div v-if="recentDisbursements && recentDisbursements.length > 0" class="rounded-2xl border bg-card shadow-sm">
                        <div class="flex items-center justify-between border-b px-4 py-4 sm:px-6">
                            <h2 class="font-semibold text-foreground flex items-center gap-2">
                                <TrendingUp class="h-4 w-4 text-muted-foreground" />
                                Demandes récentes
                            </h2>
                            <Link :href="route('disbursement-requests.index')" class="text-sm text-indigo-600 hover:underline flex items-center gap-1">Voir tout <ArrowRight class="h-3.5 w-3.5" /></Link>
                        </div>
                        <div class="divide-y">
                            <div v-for="dd in recentDisbursements" :key="dd.id" class="flex items-center justify-between px-4 py-3 hover:bg-muted/30 transition-colors gap-3 sm:px-6 sm:py-4">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="hidden h-9 w-9 items-center justify-center rounded-xl bg-indigo-50 shrink-0 sm:flex">
                                        <Banknote class="h-4 w-4 text-indigo-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-medium text-sm text-foreground truncate">{{ dd.title }}</p>
                                        <p class="text-xs text-muted-foreground mt-0.5 flex items-center gap-1.5">
                                            <span>{{ dd.user?.name }}</span>
                                            <span v-if="dd.boutique" class="text-muted-foreground/40">·</span>
                                            <span v-if="dd.boutique" class="inline-flex items-center gap-1"><Store class="h-3 w-3" />{{ dd.boutique.name }}</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <p class="hidden text-sm font-semibold text-foreground sm:block">{{ formatAmount(dd.amount) }}</p>
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium"
                                        :class="[statusConfig[dd.status as keyof typeof statusConfig]?.bg, statusConfig[dd.status as keyof typeof statusConfig]?.text]">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[dd.status as keyof typeof statusConfig]?.dot" />
                                        {{ statusConfig[dd.status as keyof typeof statusConfig]?.label }}
                                    </span>
                                    <Link :href="route('disbursement-requests.show', { disbursement_request: dd.uuid })" class="rounded-lg p-1.5 hover:bg-muted transition-colors">
                                        <Eye class="h-4 w-4 text-muted-foreground" />
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Raccourcis DD -->
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
                        <Link :href="route('disbursement-requests.index')" class="rounded-2xl border bg-card p-4 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between group sm:p-5">
                            <div class="flex items-center gap-3">
                                <div class="rounded-xl bg-indigo-50 p-2.5"><FileText class="h-4 w-4 text-indigo-600 sm:h-5 sm:w-5" /></div>
                                <div>
                                    <p class="font-semibold text-foreground text-sm sm:text-base">{{ stats.dd_total ?? 0 }} demandes</p>
                                    <p class="text-xs text-muted-foreground">Toutes les demandes</p>
                                </div>
                            </div>
                            <ArrowRight class="h-4 w-4 text-muted-foreground group-hover:translate-x-1 transition-transform shrink-0" />
                        </Link>
                        <Link :href="route('disbursement-validations.index')" class="rounded-2xl border bg-card p-4 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between group sm:p-5">
                            <div class="flex items-center gap-3">
                                <div class="rounded-xl bg-amber-50 p-2.5"><Clock class="h-4 w-4 text-amber-600 sm:h-5 sm:w-5" /></div>
                                <div>
                                    <p class="font-semibold text-foreground text-sm sm:text-base">{{ stats.dd_pending ?? 0 }} en attente</p>
                                    <p class="text-xs text-muted-foreground">File de validation DD</p>
                                </div>
                            </div>
                            <ArrowRight class="h-4 w-4 text-muted-foreground group-hover:translate-x-1 transition-transform shrink-0" />
                        </Link>
                        <Link :href="route('disbursement-validations.history')" class="rounded-2xl border bg-card p-4 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between group sm:p-5">
                            <div class="flex items-center gap-3">
                                <div class="rounded-xl bg-emerald-50 p-2.5"><History class="h-4 w-4 text-emerald-600 sm:h-5 sm:w-5" /></div>
                                <div>
                                    <p class="font-semibold text-foreground text-sm sm:text-base">{{ stats.dd_approved ?? 0 }} approuvées</p>
                                    <p class="text-xs text-muted-foreground">Historique des décisions</p>
                                </div>
                            </div>
                            <ArrowRight class="h-4 w-4 text-muted-foreground group-hover:translate-x-1 transition-transform shrink-0" />
                        </Link>
                    </div>

                </template>

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
                    <Link :href="route('validations.history')" class="ml-auto shrink-0 hidden items-center gap-1.5 rounded-xl border border-indigo-200 bg-white/60 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-white transition-colors sm:flex">
                        <History class="h-3.5 w-3.5" />
                        Historique
                    </Link>
                </div>

                <!-- Stat cards -->
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">À valider</span>
                            <div class="rounded-lg bg-amber-50 p-2"><Clock class="h-4 w-4 text-amber-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.pending }}</p>
                        <p class="text-xs text-amber-600 font-medium mt-1">{{ formatAmountShort(stats.budget_pending ?? 0) }} FCFA</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">J'ai approuvé</span>
                            <div class="rounded-lg bg-emerald-50 p-2"><CheckCircle2 class="h-4 w-4 text-emerald-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.my_approved }}</p>
                        <p class="text-xs text-emerald-600 font-medium mt-1">{{ formatAmountShort(stats.my_budget_approved ?? 0) }} FCFA</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">J'ai refusé</span>
                            <div class="rounded-lg bg-red-50 p-2"><XCircle class="h-4 w-4 text-red-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.my_rejected }}</p>
                    </div>
                    <div class="rounded-2xl border bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-emerald-800 sm:text-sm">Validées (global)</span>
                            <div class="rounded-lg bg-emerald-100 p-2"><Wallet class="h-4 w-4 text-emerald-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-emerald-700 sm:text-3xl">{{ stats.global_approved }}</p>
                        <p class="text-xs text-emerald-600 font-medium mt-1">{{ formatAmountShort(stats.global_budget_approved ?? 0) }} FCFA</p>
                    </div>
                </div>

                <!-- Pipeline -->
                <div v-if="pipeline && pipeline.length > 1" class="rounded-2xl border bg-card shadow-sm">
                    <div class="flex items-center justify-between border-b px-4 py-4 sm:px-6">
                        <h2 class="font-semibold text-foreground flex items-center gap-2">
                            <BarChart3 class="h-4 w-4 text-muted-foreground" />
                            Circuit de validation — en cours
                        </h2>
                        <span class="text-sm text-muted-foreground">{{ stats.global_pending }} commandes en circuit</span>
                    </div>
                    <div class="divide-y">
                        <div v-for="step in pipeline" :key="step.level_order" class="flex items-center gap-4 px-4 py-3 sm:px-6" :class="step.level_order === validationLevel?.order ? 'bg-amber-50/60' : ''">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-bold" :class="step.level_order === validationLevel?.order ? 'bg-amber-100 text-amber-700' : 'bg-muted text-muted-foreground'">
                                {{ step.level_order }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-foreground truncate">
                                    {{ step.level_name }}
                                    <span v-if="step.level_order === validationLevel?.order" class="ml-1.5 text-xs font-semibold text-amber-600">(vous)</span>
                                </p>
                                <p class="text-xs text-muted-foreground">{{ formatAmount(step.amount) }} en attente</p>
                            </div>
                            <div class="hidden w-32 flex-col gap-1 sm:flex">
                                <div class="h-2 w-full rounded-full bg-muted overflow-hidden">
                                    <div class="h-full rounded-full transition-all" :class="step.level_order === validationLevel?.order ? 'bg-amber-400' : 'bg-slate-300'" :style="{ width: stats.global_pending > 0 ? Math.round((step.pending / stats.global_pending) * 100) + '%' : '0%' }" />
                                </div>
                            </div>
                            <div class="shrink-0 text-right">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-sm font-bold" :class="step.pending > 0 ? (step.level_order === validationLevel?.order ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600') : 'bg-emerald-50 text-emerald-600'">
                                    {{ step.pending }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Donut + stats globales -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border bg-card p-5 shadow-sm">
                        <h3 class="font-semibold text-foreground text-sm mb-4">Mes décisions</h3>
                        <div v-if="hasBcDonutData" class="h-56"><Doughnut :data="bcDonutData" :options="donutOptions" /></div>
                        <div v-else class="flex h-56 flex-col items-center justify-center gap-3 text-sm text-muted-foreground">
                            <CheckCircle2 class="h-8 w-8 text-emerald-200" />
                            Aucune décision encore
                        </div>
                    </div>
                    <div class="rounded-2xl border bg-card p-5 shadow-sm flex flex-col gap-4">
                        <h3 class="font-semibold text-foreground text-sm">Vue globale du circuit</h3>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="rounded-xl bg-emerald-50 p-3 text-center">
                                <p class="text-2xl font-bold text-emerald-700">{{ stats.global_approved }}</p>
                                <p class="text-xs text-emerald-600 font-medium mt-0.5">Approuvées</p>
                            </div>
                            <div class="rounded-xl bg-red-50 p-3 text-center">
                                <p class="text-2xl font-bold text-red-700">{{ stats.global_rejected }}</p>
                                <p class="text-xs text-red-600 font-medium mt-0.5">Rejetées</p>
                            </div>
                            <div class="col-span-2 rounded-xl bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-3">
                                <p class="text-xs text-emerald-600 font-semibold uppercase tracking-wide mb-1">Budget validé total</p>
                                <p class="text-lg font-bold text-emerald-700 truncate">{{ formatAmount(stats.global_budget_approved ?? 0) }}</p>
                            </div>
                        </div>
                        <Link :href="route('validations.history')" class="mt-auto inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted">
                            <History class="h-4 w-4" />
                            Voir l'historique complet
                            <ArrowRight class="ml-auto h-3.5 w-3.5 text-muted-foreground" />
                        </Link>
                    </div>
                </div>

                <!-- CTA commandes en attente -->
                <div v-if="stats.pending > 0" class="rounded-2xl border border-amber-200 bg-amber-50 p-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="h-2 w-2 rounded-full bg-amber-500 animate-pulse shrink-0" />
                        <p class="text-sm font-medium text-amber-800">
                            <span class="font-bold">{{ stats.pending }}</span> commande{{ stats.pending > 1 ? 's' : '' }} en attente de votre validation
                            <span v-if="stats.budget_pending" class="text-amber-600"> · {{ formatAmount(stats.budget_pending) }}</span>
                        </p>
                    </div>
                    <Link :href="route('validations.index')" class="text-sm font-semibold text-amber-700 flex items-center gap-1 hover:gap-2 transition-all shrink-0">
                        Valider <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <!-- CTA DD en attente -->
                <div v-if="(stats.dd_pending_my_level ?? 0) > 0" class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <Banknote class="h-4 w-4 text-indigo-600 shrink-0" />
                        <p class="text-sm font-medium text-indigo-800">
                            <span class="font-bold">{{ stats.dd_pending_my_level }}</span> demande{{ stats.dd_pending_my_level > 1 ? 's' : '' }} de décaissement à valider à votre niveau
                        </p>
                    </div>
                    <Link :href="route('disbursement-validations.index')" class="text-sm font-semibold text-indigo-700 flex items-center gap-1 hover:gap-2 transition-all shrink-0">
                        Valider <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </div>

                <!-- Commandes récentes validateur -->
                <div v-if="recentOrders && recentOrders.length > 0" class="rounded-2xl border bg-card shadow-sm">
                    <div class="flex items-center justify-between border-b px-4 py-4 sm:px-6">
                        <h2 class="font-semibold text-foreground flex items-center gap-2">
                            <TrendingUp class="h-4 w-4 text-muted-foreground" />
                            Commandes à valider
                        </h2>
                        <Link :href="route('validations.index')" class="text-sm text-primary hover:underline flex items-center gap-1">Voir tout <ArrowRight class="h-3.5 w-3.5" /></Link>
                    </div>
                    <div class="divide-y">
                        <div v-for="order in recentOrders" :key="order.id" class="flex items-center justify-between px-4 py-3 hover:bg-muted/30 transition-colors gap-3 sm:px-6 sm:py-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="hidden h-9 w-9 items-center justify-center rounded-xl bg-muted shrink-0 sm:flex">
                                    <FileText class="h-4 w-4 text-muted-foreground" />
                                </div>
                                <div class="min-w-0">
                                    <p class="font-medium text-sm text-foreground truncate">{{ order.title }}</p>
                                    <p class="text-xs text-muted-foreground mt-0.5 flex items-center gap-1.5">
                                        <template v-if="order.boutique"><Store class="h-3 w-3" />{{ order.boutique.name }}</template>
                                        <span class="text-muted-foreground/40">·</span>
                                        {{ formatDate(order.created_at) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <p class="hidden text-sm font-semibold text-foreground sm:block">{{ formatAmount(order.amount) }}</p>
                                <span v-if="order.status === 'pending' && order.current_level_order" class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-amber-400" />
                                    Niv. {{ order.current_level_order }}<template v-if="totalLevels">/{{ totalLevels }}</template>
                                </span>
                                <Link :href="route('validations.show', order.uuid)" class="rounded-lg p-1.5 hover:bg-muted transition-colors">
                                    <Eye class="h-4 w-4 text-muted-foreground" />
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>

            </template>

            <!-- ===== CAISSIER ===== -->
            <template v-else-if="role === 'caissier'">

                <div v-if="boutique" class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                    <div class="flex items-center gap-4">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50"><Store class="h-5 w-5 text-blue-600" /></div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Votre boutique</p>
                            <p class="text-base font-semibold text-foreground">{{ boutique.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ boutique.code }}<template v-if="boutique.city"> · {{ boutique.city }}</template></p>
                        </div>
                        <div v-if="(stats.total_to_pay ?? 0) + (stats.dd_to_pay ?? 0) > 0" class="shrink-0 text-right">
                            <p class="text-xs text-muted-foreground">Total à décaisser</p>
                            <p class="text-lg font-bold text-amber-600">{{ formatAmount((stats.total_to_pay ?? 0) + (stats.dd_to_pay ?? 0)) }}</p>
                        </div>
                        <div v-else class="shrink-0 text-right">
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                                <CheckCircle2 class="h-3.5 w-3.5" /> Tout réglé
                            </span>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                    <Link :href="route('decaissements.index')" class="group flex items-center gap-4 rounded-2xl border-2 p-5 shadow-sm transition-all hover:shadow-md" :class="(stats.unpaid ?? 0) > 0 ? 'border-orange-200 bg-gradient-to-br from-orange-50 to-amber-50' : 'border-border bg-card'">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl" :class="(stats.unpaid ?? 0) > 0 ? 'bg-orange-100' : 'bg-muted'">
                            <ShoppingCart class="h-6 w-6" :class="(stats.unpaid ?? 0) > 0 ? 'text-orange-600' : 'text-muted-foreground'" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wide" :class="(stats.unpaid ?? 0) > 0 ? 'text-orange-600' : 'text-muted-foreground'">Bons de commande</p>
                            <p class="text-3xl font-bold" :class="(stats.unpaid ?? 0) > 0 ? 'text-orange-700' : 'text-foreground'">{{ stats.unpaid ?? 0 }}</p>
                            <p class="text-sm font-medium" :class="(stats.unpaid ?? 0) > 0 ? 'text-orange-600' : 'text-muted-foreground'">
                                <template v-if="(stats.unpaid ?? 0) > 0">{{ formatAmount(stats.total_to_pay ?? 0) }} à régler</template>
                                <template v-else>Tout est réglé ✓</template>
                            </p>
                        </div>
                        <ArrowRight class="h-5 w-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-1" />
                    </Link>

                    <Link :href="route('caissier.demandes.index')" class="group flex items-center gap-4 rounded-2xl border-2 p-5 shadow-sm transition-all hover:shadow-md" :class="(stats.dd_unpaid ?? 0) > 0 ? 'border-indigo-200 bg-gradient-to-br from-indigo-50 to-indigo-100/50' : 'border-border bg-card'">
                        <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl" :class="(stats.dd_unpaid ?? 0) > 0 ? 'bg-indigo-100' : 'bg-muted'">
                            <Banknote class="h-6 w-6" :class="(stats.dd_unpaid ?? 0) > 0 ? 'text-indigo-600' : 'text-muted-foreground'" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-semibold uppercase tracking-wide" :class="(stats.dd_unpaid ?? 0) > 0 ? 'text-indigo-600' : 'text-muted-foreground'">Décaissements</p>
                            <p class="text-3xl font-bold" :class="(stats.dd_unpaid ?? 0) > 0 ? 'text-indigo-700' : 'text-foreground'">{{ stats.dd_unpaid ?? 0 }}</p>
                            <p class="text-sm font-medium" :class="(stats.dd_unpaid ?? 0) > 0 ? 'text-indigo-600' : 'text-muted-foreground'">
                                <template v-if="(stats.dd_unpaid ?? 0) > 0">{{ formatAmount(stats.dd_to_pay ?? 0) }} à décaisser</template>
                                <template v-else>Aucun en attente ✓</template>
                            </p>
                        </div>
                        <ArrowRight class="h-5 w-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-1" />
                    </Link>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                    <div class="rounded-2xl border bg-card p-4 shadow-sm">
                        <div class="mb-2 flex items-center justify-between"><span class="text-xs text-muted-foreground">BC approuvés</span><FileText class="h-4 w-4 text-muted-foreground" /></div>
                        <p class="text-2xl font-bold text-foreground">{{ stats.approved_total ?? 0 }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">total</p>
                    </div>
                    <div class="rounded-2xl border bg-emerald-50 p-4 shadow-sm">
                        <div class="mb-2 flex items-center justify-between"><span class="text-xs text-emerald-700">BC payés</span><CheckCircle2 class="h-4 w-4 text-emerald-600" /></div>
                        <p class="text-2xl font-bold text-emerald-700">{{ stats.paid ?? 0 }}</p>
                        <p class="mt-0.5 text-xs text-emerald-600">{{ formatAmountShort(stats.total_paid_bc ?? 0) }} FCFA</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm">
                        <div class="mb-2 flex items-center justify-between"><span class="text-xs text-muted-foreground">DD approuvées</span><Banknote class="h-4 w-4 text-muted-foreground" /></div>
                        <p class="text-2xl font-bold text-foreground">{{ stats.dd_total ?? 0 }}</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">total</p>
                    </div>
                    <div class="rounded-2xl border bg-emerald-50 p-4 shadow-sm">
                        <div class="mb-2 flex items-center justify-between"><span class="text-xs text-emerald-700">DD payées</span><CheckCircle2 class="h-4 w-4 text-emerald-600" /></div>
                        <p class="text-2xl font-bold text-emerald-700">{{ stats.dd_paid ?? 0 }}</p>
                        <p class="mt-0.5 text-xs text-emerald-600">{{ formatAmountShort(stats.dd_paid_amount ?? 0) }} FCFA</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                    <div class="rounded-2xl border bg-card shadow-sm">
                        <div class="flex items-center justify-between border-b px-4 py-4 sm:px-5">
                            <h2 class="flex items-center gap-2 text-sm font-semibold text-foreground"><ShoppingCart class="h-4 w-4 text-muted-foreground" />Bons à régler</h2>
                            <Link :href="route('decaissements.index')" class="flex items-center gap-1 text-xs text-primary hover:underline">Tout voir <ArrowRight class="h-3 w-3" /></Link>
                        </div>
                        <template v-if="recentOrders && recentOrders.length > 0">
                            <div class="divide-y">
                                <Link v-for="order in recentOrders" :key="order.id" :href="route('purchase-orders.show', order.uuid)" class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/30 sm:px-5">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-50"><ShoppingCart class="h-4 w-4 text-orange-600" /></div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-foreground">{{ order.title }}</p>
                                        <p class="text-xs text-muted-foreground">{{ order.reference }}<template v-if="order.fournisseur"> · {{ order.fournisseur.name }}</template></p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-sm font-semibold text-orange-600">{{ formatAmountShort(Number(order.amount)) }}</p>
                                        <p class="text-xs text-muted-foreground">FCFA</p>
                                    </div>
                                </Link>
                            </div>
                        </template>
                        <template v-else>
                            <div class="flex flex-col items-center gap-2 py-10 text-center"><CheckCircle2 class="h-8 w-8 text-emerald-200" /><p class="text-sm text-muted-foreground">Tout est à jour</p></div>
                        </template>
                    </div>

                    <div class="rounded-2xl border bg-card shadow-sm">
                        <div class="flex items-center justify-between border-b px-4 py-4 sm:px-5">
                            <h2 class="flex items-center gap-2 text-sm font-semibold text-foreground"><Banknote class="h-4 w-4 text-muted-foreground" />Décaissements à traiter</h2>
                            <Link :href="route('caissier.demandes.index')" class="flex items-center gap-1 text-xs text-primary hover:underline">Tout voir <ArrowRight class="h-3 w-3" /></Link>
                        </div>
                        <template v-if="recentDisbursements && recentDisbursements.length > 0">
                            <div class="divide-y">
                                <Link v-for="dd in recentDisbursements" :key="dd.id" :href="route('disbursement-requests.show', dd.uuid)" class="flex items-center gap-3 px-4 py-3 transition-colors hover:bg-muted/30 sm:px-5">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50"><Banknote class="h-4 w-4 text-indigo-600" /></div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium text-foreground">{{ dd.title }}</p>
                                        <p class="text-xs text-muted-foreground">{{ dd.reference }}<template v-if="dd.nature_operation"> · {{ dd.nature_operation.name }}</template></p>
                                    </div>
                                    <div class="shrink-0 text-right">
                                        <p class="text-sm font-semibold text-indigo-600">{{ formatAmountShort(Number(dd.amount)) }}</p>
                                        <p class="text-xs text-muted-foreground">FCFA</p>
                                    </div>
                                </Link>
                            </div>
                        </template>
                        <template v-else>
                            <div class="flex flex-col items-center gap-2 py-10 text-center"><CheckCircle2 class="h-8 w-8 text-emerald-200" /><p class="text-sm text-muted-foreground">Aucun en attente</p></div>
                        </template>
                    </div>
                </div>

            </template>

            <!-- ===== DEMANDEUR ===== -->
            <template v-else>

                <div v-if="boutique" class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50"><Store class="h-5 w-5 text-blue-600" /></div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Votre boutique</p>
                            <p class="text-base font-semibold text-foreground">{{ boutique.name }}</p>
                            <p class="text-xs text-muted-foreground">{{ boutique.code }}<template v-if="boutique.city"> · {{ boutique.city }}</template></p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3"><span class="text-xs font-medium text-muted-foreground sm:text-sm">Total</span><div class="rounded-lg bg-blue-50 p-2"><ShoppingCart class="h-4 w-4 text-blue-600" /></div></div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.total }}</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3"><span class="text-xs font-medium text-muted-foreground sm:text-sm">En attente</span><div class="rounded-lg bg-amber-50 p-2"><Clock class="h-4 w-4 text-amber-600" /></div></div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.pending }}</p>
                        <p class="text-xs text-amber-600 font-medium mt-1">{{ formatAmountShort(stats.budget_pending ?? 0) }} FCFA</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3"><span class="text-xs font-medium text-muted-foreground sm:text-sm">Approuvées</span><div class="rounded-lg bg-emerald-50 p-2"><CheckCircle2 class="h-4 w-4 text-emerald-600" /></div></div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.approved }}</p>
                        <p class="text-xs text-emerald-600 font-medium mt-1">{{ formatAmountShort(stats.budget_approved ?? 0) }} FCFA</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3"><span class="text-xs font-medium text-muted-foreground sm:text-sm">Refusées</span><div class="rounded-lg bg-red-50 p-2"><XCircle class="h-4 w-4 text-red-600" /></div></div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.rejected }}</p>
                    </div>
                </div>

                <div v-if="stats.total > 0" class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                    <div class="rounded-2xl border bg-gradient-to-br from-emerald-50 to-emerald-100/50 p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-3"><span class="text-sm font-medium text-emerald-800">Budget validé</span><div class="rounded-xl bg-emerald-100 p-2.5"><Wallet class="h-5 w-5 text-emerald-600" /></div></div>
                        <p class="text-2xl font-bold text-emerald-700">{{ formatAmount(stats.budget_approved ?? 0) }}</p>
                        <p class="text-xs text-emerald-600 mt-1.5">{{ stats.approved }} commandes approuvées</p>
                    </div>
                    <div class="rounded-2xl border bg-gradient-to-br from-amber-50 to-amber-100/50 p-5 shadow-sm">
                        <div class="flex items-center justify-between mb-3"><span class="text-sm font-medium text-amber-800">Budget en circuit</span><div class="rounded-xl bg-amber-100 p-2.5"><Clock class="h-5 w-5 text-amber-600" /></div></div>
                        <p class="text-2xl font-bold text-amber-700">{{ formatAmount(stats.budget_pending ?? 0) }}</p>
                        <p class="text-xs text-amber-600 mt-1.5">{{ stats.pending }} commandes en attente</p>
                    </div>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                    <div class="mb-3 flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-indigo-50"><Banknote class="h-4 w-4 text-indigo-600" /></div>
                        <h3 class="font-semibold text-foreground">Demandes de décaissement</h3>
                        <Link :href="route('disbursement-requests.index')" class="ml-auto text-xs text-primary hover:underline flex items-center gap-1">Voir tout <ArrowRight class="h-3 w-3" /></Link>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div class="rounded-xl border bg-muted/30 p-3 text-center">
                            <p class="text-xl font-bold text-foreground">{{ stats.dd_total ?? 0 }}</p>
                            <p class="text-xs text-muted-foreground mt-0.5">Total</p>
                        </div>
                        <div class="rounded-xl border bg-amber-50 p-3 text-center">
                            <p class="text-xl font-bold text-amber-700">{{ stats.dd_pending ?? 0 }}</p>
                            <p class="text-xs text-amber-600 mt-0.5">En circuit</p>
                        </div>
                        <div class="rounded-xl border bg-emerald-50 p-3 text-center">
                            <p class="text-xl font-bold text-emerald-700">{{ stats.dd_approved ?? 0 }}</p>
                            <p class="text-xs text-emerald-600 mt-0.5">Approuvées</p>
                        </div>
                    </div>
                    <div v-if="(stats.dd_budget_approved ?? 0) > 0" class="mt-3 rounded-xl bg-indigo-50 px-4 py-2.5 text-sm">
                        <span class="text-indigo-600 font-medium">{{ formatAmount(stats.dd_budget_approved ?? 0) }}</span>
                        <span class="text-indigo-400 ml-1.5">décaissés approuvés</span>
                    </div>
                </div>

                <div v-if="stats.total > 0" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div class="rounded-2xl border bg-card p-5 shadow-sm">
                        <h3 class="font-semibold text-foreground text-sm mb-4">Répartition de mes commandes</h3>
                        <div v-if="hasBcDonutData" class="h-56"><Doughnut :data="bcDonutData" :options="donutOptions" /></div>
                        <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">Aucune donnée</div>
                    </div>
                    <div class="rounded-2xl border bg-card p-5 shadow-sm">
                        <h3 class="font-semibold text-foreground text-sm mb-4">Activité sur 6 mois</h3>
                        <div v-if="hasBcBarData" class="h-56"><Bar :data="bcBarData" :options="barOptions" /></div>
                        <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">Aucune activité sur cette période</div>
                    </div>
                </div>

                <!-- Commandes récentes demandeur -->
                <div v-if="recentOrders && recentOrders.length > 0" class="rounded-2xl border bg-card shadow-sm">
                    <div class="flex items-center justify-between border-b px-4 py-4 sm:px-6">
                        <h2 class="font-semibold text-foreground flex items-center gap-2"><TrendingUp class="h-4 w-4 text-muted-foreground" />Commandes récentes</h2>
                        <Link :href="route('purchase-orders.index')" class="text-sm text-primary hover:underline flex items-center gap-1">Voir tout <ArrowRight class="h-3.5 w-3.5" /></Link>
                    </div>
                    <div class="divide-y">
                        <div v-for="order in recentOrders" :key="order.id" class="flex items-center justify-between px-4 py-3 hover:bg-muted/30 transition-colors gap-3 sm:px-6 sm:py-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="hidden h-9 w-9 items-center justify-center rounded-xl bg-muted shrink-0 sm:flex"><FileText class="h-4 w-4 text-muted-foreground" /></div>
                                <div class="min-w-0">
                                    <p class="font-medium text-sm text-foreground truncate">{{ order.title }}</p>
                                    <p class="text-xs text-muted-foreground mt-0.5">{{ formatDate(order.created_at) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <p class="hidden text-sm font-semibold text-foreground sm:block">{{ formatAmount(order.amount) }}</p>
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium"
                                    :class="[statusConfig[order.status as keyof typeof statusConfig]?.bg, statusConfig[order.status as keyof typeof statusConfig]?.text]">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status as keyof typeof statusConfig]?.dot" />
                                    {{ statusConfig[order.status as keyof typeof statusConfig]?.label }}
                                </span>
                                <Link :href="route('purchase-orders.show', order.uuid)" class="rounded-lg p-1.5 hover:bg-muted transition-colors"><Eye class="h-4 w-4 text-muted-foreground" /></Link>
                            </div>
                        </div>
                    </div>
                </div>

                <EmptyState
                    v-else-if="stats.total === 0"
                    :icon="ShoppingCart"
                    icon-bg="bg-primary/10"
                    icon-color="text-primary"
                    title="Aucune commande pour l'instant"
                    description="Créez votre première demande d'achat, sélectionnez vos articles et soumettez-la pour validation."
                    :action-href="route('purchase-orders.create')"
                    action-label="Créer ma première commande"
                />

            </template>

        </div>
    </AppLayout>
</template>
