<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PurchaseOrder, type SharedData, type ValidationLog } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ShoppingCart, Clock, CheckCircle2, XCircle, FileText,
    TrendingUp, Users, Settings, ArrowRight, Eye,
} from 'lucide-vue-next';
import { ArcElement, BarElement, CategoryScale, Chart as ChartJS, Legend, LinearScale, Tooltip } from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';
import { computed } from 'vue';

ChartJS.register(ArcElement, BarElement, CategoryScale, LinearScale, Tooltip, Legend);

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Tableau de bord', href: '/dashboard' }];

const page = usePage<SharedData>();
const role = computed(() => page.props.auth.user?.role?.slug);

const props = defineProps<{
    stats: Record<string, number>;
    recentOrders?: PurchaseOrder[];
    myValidations?: (ValidationLog & { purchase_order: PurchaseOrder })[];
    totalUsers?: number;
    totalLevels?: number;
    monthlyData?: {
        labels: string[];
        pending: number[];
        approved: number[];
        rejected: number[];
        draft: number[];
    };
}>();

const statusConfig = {
    draft:    { label: 'Brouillon',  bg: 'bg-slate-100',   text: 'text-slate-600',   dot: 'bg-slate-400' },
    pending:  { label: 'En attente', bg: 'bg-amber-50',    text: 'text-amber-700',   dot: 'bg-amber-400' },
    approved: { label: 'Approuvée',  bg: 'bg-emerald-50',  text: 'text-emerald-700', dot: 'bg-emerald-500' },
    rejected: { label: 'Refusée',    bg: 'bg-red-50',      text: 'text-red-700',     dot: 'bg-red-500' },
} as const;

const formatAmount = (amount: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(amount));

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

// --- Donut chart : répartition des statuts ---
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

// --- Bar chart : tendance mensuelle ---
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

            <!-- ===== ADMIN ===== -->
            <template v-if="role === 'admin'">
                <!-- Stat cards -->
                <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">Total</span>
                            <div class="rounded-lg bg-blue-50 p-2"><FileText class="h-4 w-4 text-blue-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.total }}</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">En attente</span>
                            <div class="rounded-lg bg-amber-50 p-2"><Clock class="h-4 w-4 text-amber-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.pending }}</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">Approuvées</span>
                            <div class="rounded-lg bg-emerald-50 p-2"><CheckCircle2 class="h-4 w-4 text-emerald-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.approved }}</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">Refusées</span>
                            <div class="rounded-lg bg-red-50 p-2"><XCircle class="h-4 w-4 text-red-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.rejected }}</p>
                    </div>
                </div>

                <!-- Graphes -->
                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <!-- Donut -->
                    <div class="rounded-2xl border bg-card p-5 shadow-sm">
                        <h3 class="font-semibold text-foreground text-sm mb-4">Répartition des statuts</h3>
                        <div v-if="hasDonutData" class="h-56">
                            <Doughnut :data="donutData" :options="donutOptions" />
                        </div>
                        <div v-else class="flex h-56 items-center justify-center text-sm text-muted-foreground">
                            Aucune donnée
                        </div>
                    </div>
                    <!-- Bar -->
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

                <!-- Admin info row -->
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 sm:gap-4">
                    <Link :href="route('admin.users.index')" class="rounded-2xl border bg-card p-4 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between group sm:p-5">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <div class="rounded-xl bg-violet-50 p-2.5 sm:p-3"><Users class="h-4 w-4 text-violet-600 sm:h-5 sm:w-5" /></div>
                            <div>
                                <p class="font-semibold text-foreground text-sm sm:text-base">{{ totalUsers }} utilisateurs</p>
                                <p class="text-xs text-muted-foreground sm:text-sm">Gérer les comptes</p>
                            </div>
                        </div>
                        <ArrowRight class="h-4 w-4 text-muted-foreground group-hover:translate-x-1 transition-transform shrink-0" />
                    </Link>
                    <Link :href="route('admin.validation-levels.index')" class="rounded-2xl border bg-card p-4 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between group sm:p-5">
                        <div class="flex items-center gap-3 sm:gap-4">
                            <div class="rounded-xl bg-indigo-50 p-2.5 sm:p-3"><Settings class="h-4 w-4 text-indigo-600 sm:h-5 sm:w-5" /></div>
                            <div>
                                <p class="font-semibold text-foreground text-sm sm:text-base">{{ totalLevels }} niveaux de validation</p>
                                <p class="text-xs text-muted-foreground sm:text-sm">Configurer le circuit</p>
                            </div>
                        </div>
                        <ArrowRight class="h-4 w-4 text-muted-foreground group-hover:translate-x-1 transition-transform shrink-0" />
                    </Link>
                </div>
            </template>

            <!-- ===== VALIDATEUR ===== -->
            <template v-else-if="role === 'validateur'">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-3 sm:gap-4">
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-sm font-medium text-muted-foreground">À valider</span>
                            <div class="rounded-lg bg-amber-50 p-2"><Clock class="h-4 w-4 text-amber-600" /></div>
                        </div>
                        <p class="text-3xl font-bold text-foreground">{{ stats.pending }}</p>
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
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">Approuvées</span>
                            <div class="rounded-lg bg-emerald-50 p-2"><CheckCircle2 class="h-4 w-4 text-emerald-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.approved }}</p>
                    </div>
                    <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs font-medium text-muted-foreground sm:text-sm">Refusées</span>
                            <div class="rounded-lg bg-red-50 p-2"><XCircle class="h-4 w-4 text-red-600" /></div>
                        </div>
                        <p class="text-2xl font-bold text-foreground sm:text-3xl">{{ stats.rejected }}</p>
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

            <!-- Commandes récentes -->
            <div v-if="recentOrders && recentOrders.length > 0" class="rounded-2xl border bg-card shadow-sm">
                <div class="flex items-center justify-between border-b px-4 py-4 sm:px-6">
                    <h2 class="font-semibold text-foreground flex items-center gap-2">
                        <TrendingUp class="h-4 w-4 text-muted-foreground" />
                        Commandes récentes
                    </h2>
                    <Link
                        :href="role === 'admin' ? route('validations.index') : route('purchase-orders.index')"
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
                            <div class="hidden h-8 w-8 items-center justify-center rounded-xl bg-muted shrink-0 sm:flex sm:h-9 sm:w-9">
                                <FileText class="h-4 w-4 text-muted-foreground" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-medium text-sm text-foreground truncate">{{ order.title }}</p>
                                <p class="text-xs text-muted-foreground mt-0.5">
                                    <template v-if="role === 'admin'">{{ order.user?.name }} · </template>
                                    {{ formatDate(order.created_at) }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0 sm:gap-4">
                            <p class="hidden text-sm font-semibold text-foreground sm:block">{{ formatAmount(order.amount) }}</p>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium sm:px-2.5"
                                :class="[statusConfig[order.status]?.bg, statusConfig[order.status]?.text]"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status]?.dot" />
                                <span class="hidden sm:inline">{{ statusConfig[order.status]?.label }}</span>
                            </span>
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

            <!-- CTA vide -->
            <div
                v-else-if="role === 'demandeur' && stats.total === 0"
                class="rounded-2xl border-2 border-dashed border-border bg-card p-8 text-center sm:p-12"
            >
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10">
                    <ShoppingCart class="h-7 w-7 text-primary" />
                </div>
                <h3 class="font-semibold text-foreground mb-2">Aucune commande pour l'instant</h3>
                <p class="text-sm text-muted-foreground mb-6">Créez votre première commande d'achat</p>
                <Link
                    :href="route('purchase-orders.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors"
                >
                    <ShoppingCart class="h-4 w-4" />
                    Nouvelle commande
                </Link>
            </div>

        </div>
    </AppLayout>
</template>
