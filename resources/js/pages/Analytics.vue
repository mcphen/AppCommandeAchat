<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle, ArrowRight, BarChart2, Clock, ShoppingBag,
    HardHat, TrendingUp, Users, CheckCircle2, XCircle, Timer, PackageCheck,
    Download, Truck,
} from 'lucide-vue-next';
import {
    BarElement, CategoryScale, Chart as ChartJS, Legend,
    LinearScale, Tooltip, LineElement, PointElement, Filler,
} from 'chart.js';
import { Bar, Line } from 'vue-chartjs';
import { computed, ref } from 'vue';

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend, LineElement, PointElement, Filler);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Analytique', href: '/analytics' },
];

interface BlockedOrder {
    id: number;
    title: string;
    amount: number;
    submitted_at: string;
    days_waiting: number;
    user_name: string;
}

interface TopItem {
    name: string;
    total: number;
    orders_count: number;
}

interface MonthlyByBoutique {
    labels: string[];
    datasets: {
        label: string;
        data: number[];
        borderColor: string;
        backgroundColor: string;
        tension: number;
        fill: boolean;
        pointRadius: number;
        pointHoverRadius: number;
    }[];
}

interface PeriodSeries {
    labels: string[];
    data: number[];
}

interface DelayEntry {
    name: string;
    avg_hours: number;
    avg_days: number;
    total: number;
}

interface ValidatorDelay extends DelayEntry {
    approved: number;
    rejected: number;
}

interface LeadTimeEntry {
    name: string;
    avg_days: number;
    orders_count: number;
}

interface RejectionRate {
    name: string;
    total: number;
    rejected: number;
    approved: number;
    rate: number;
}

const props = defineProps<{
    blockedOrders: BlockedOrder[];
    topFournisseurs: TopItem[];
    topProjects: (TopItem & { id: number; code: string; share: number })[];
    purchasesByProject: { monthly: MonthlyByBoutique; quarterly: MonthlyByBoutique; annual: MonthlyByBoutique };
    approvedByPeriod: { monthly: PeriodSeries; quarterly: PeriodSeries; annual: PeriodSeries };
    deliveredByPeriod: { monthly: PeriodSeries; quarterly: PeriodSeries };
    fournisseurLeadTimes: LeadTimeEntry[];
    validationDelays: { byLevel: DelayEntry[]; byValidator: ValidatorDelay[] };
    rejectionRates: RejectionRate[];
}>();

const formatAmount = (amount: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(amount);

const formatAmountShort = (amount: number) => {
    if (amount >= 1_000_000_000) return (amount / 1_000_000_000).toFixed(1) + ' Md';
    if (amount >= 1_000_000)     return (amount / 1_000_000).toFixed(1) + ' M';
    if (amount >= 1_000)         return (amount / 1_000).toFixed(0) + ' K';
    return amount.toFixed(0);
};

const formatDate = (iso: string) =>
    new Date(iso).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

// ---- Top fournisseurs (horizontal bar) ----
const topFournisseursData = computed(() => ({
    labels: props.topFournisseurs.map(f => f.name),
    datasets: [{
        label: 'Montant approuvé',
        data: props.topFournisseurs.map(f => f.total),
        backgroundColor: ['#6366f1cc', '#8b5cf6cc', '#a78bfacc', '#c4b5fdcc', '#ddd6fecc'],
        borderRadius: 6,
        borderSkipped: false,
    }],
}));

// ---- Top chantiers (horizontal bar) ----
const topProjectsData = computed(() => ({
    labels: props.topProjects.map(c => c.name),
    datasets: [{
        label: 'Montant approuvé',
        data: props.topProjects.map(c => c.total),
        backgroundColor: ['#10b981cc', '#34d399cc', '#6ee7b7cc', '#a7f3d0cc', '#d1fae5cc'],
        borderRadius: 6,
        borderSkipped: false,
    }],
}));

const horizontalBarOptions = {
    indexAxis: 'y' as const,
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            padding: 10,
            cornerRadius: 8,
            callbacks: {
                label: (ctx: any) => ' ' + formatAmountShort(ctx.raw) + ' XOF',
            },
        },
    },
    scales: {
        x: { grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { callback: (v: any) => formatAmountShort(v) } },
        y: { grid: { display: false }, border: { display: false } },
    },
};

// ---- Achats par chantier et par période (line) ----
const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'bottom' as const,
            labels: { padding: 16, usePointStyle: true, pointStyleWidth: 8, font: { size: 12 } },
        },
        tooltip: {
            padding: 10,
            cornerRadius: 8,
            callbacks: {
                label: (ctx: any) => ' ' + ctx.dataset.label + ' : ' + formatAmountShort(ctx.raw) + ' XOF',
            },
        },
    },
    scales: {
        x: { grid: { display: false }, border: { display: false } },
        y: { grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { callback: (v: any) => formatAmountShort(v) } },
    },
};

const projectPeriod = ref<'monthly' | 'quarterly' | 'annual'>('monthly');
const projectSeries = computed(() => props.purchasesByProject[projectPeriod.value]);
const hasLineData = computed(() =>
    projectSeries.value.datasets.some(d => d.data.some(v => v > 0))
);

// ---- Montant engagé par période ----
const approvedPeriod = ref<'monthly' | 'quarterly' | 'annual'>('monthly');
const approvedSeries = computed(() => props.approvedByPeriod[approvedPeriod.value]);
const approvedTotal = computed(() => approvedSeries.value.data.reduce((sum, value) => sum + value, 0));
const hasApprovedData = computed(() => approvedSeries.value.data.some(value => value > 0));
const approvedBarData = computed(() => ({
    labels: approvedSeries.value.labels,
    datasets: [{
        label: 'BC approuvés',
        data: approvedSeries.value.data,
        backgroundColor: '#10b981cc',
        borderRadius: 6,
        borderSkipped: false,
    }],
}));
// ---- Montant livré par période (mensuel / trimestriel) ----
const deliveredPeriod = ref<'monthly' | 'quarterly'>('monthly');

const deliveredSeries = computed(() => props.deliveredByPeriod[deliveredPeriod.value]);

const deliveredTotal = computed(() =>
    deliveredSeries.value.data.reduce((sum, v) => sum + v, 0)
);

const hasDeliveredData = computed(() => deliveredSeries.value.data.some(v => v > 0));

const deliveredBarData = computed(() => ({
    labels: deliveredSeries.value.labels,
    datasets: [{
        label: 'Montant livré',
        data: deliveredSeries.value.data,
        backgroundColor: '#0ea5e9cc',
        borderRadius: 6,
        borderSkipped: false,
    }],
}));

const exportDeliveredUrl = computed(() =>
    route('analytics.export-delivered', { period: deliveredPeriod.value })
);

const deliveredBarOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            padding: 10,
            cornerRadius: 8,
            callbacks: {
                label: (ctx: any) => ' ' + formatAmountShort(ctx.raw) + ' XOF',
            },
        },
    },
    scales: {
        x: { grid: { display: false }, border: { display: false } },
        y: { grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { callback: (v: any) => formatAmountShort(v) } },
    },
};

// ---- Délai moyen par niveau (bar) ----
const delayByLevelData = computed(() => ({
    labels: props.validationDelays.byLevel.map(l => l.name),
    datasets: [{
        label: 'Délai moyen (heures)',
        data: props.validationDelays.byLevel.map(l => l.avg_hours),
        backgroundColor: '#f59e0bcc',
        borderRadius: 6,
        borderSkipped: false,
    }],
}));

const delayBarOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            padding: 10,
            cornerRadius: 8,
            callbacks: {
                label: (ctx: any) => ` ${ctx.raw}h (${(ctx.raw / 24).toFixed(1)} jours)`,
            },
        },
    },
    scales: {
        x: { grid: { display: false }, border: { display: false } },
        y: { grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { callback: (v: any) => v + 'h' } },
    },
};

// ---- Taux de refus ----
const maxRejectionRate = computed(() =>
    props.rejectionRates.length ? Math.max(...props.rejectionRates.map(r => r.rate), 1) : 100
);

const rateColor = (rate: number) => {
    if (rate >= 50) return 'bg-red-500';
    if (rate >= 25) return 'bg-amber-400';
    return 'bg-emerald-500';
};

// ---- Délai de livraison par fournisseur ----
const leadTimeBadgeClass = (days: number) => {
    if (days >= 15) return 'bg-red-50 text-red-700';
    if (days >= 7) return 'bg-amber-50 text-amber-700';
    return 'bg-emerald-50 text-emerald-700';
};
</script>

<template>
    <Head title="Analytique" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">

            <!-- Header -->
            <div class="flex items-center gap-3">
                <div class="rounded-xl bg-indigo-50 p-2.5">
                    <BarChart2 class="h-5 w-5 text-indigo-600" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-foreground sm:text-2xl">Tableau de bord analytique</h1>
                    <p class="text-sm text-muted-foreground mt-0.5">Indicateurs avancés de performance et de pilotage</p>
                </div>
            </div>

            <!-- ===================== -->
            <!-- 1. COMMANDES BLOQUÉES -->
            <!-- ===================== -->
            <section>
                <div class="mb-3 flex items-center gap-2">
                    <AlertTriangle class="h-4 w-4 text-amber-500" />
                    <h2 class="font-semibold text-foreground">Commandes bloquées</h2>
                    <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
                        {{ blockedOrders.length }} en attente &gt; 3 jours
                    </span>
                </div>

                <div v-if="blockedOrders.length === 0"
                     class="rounded-2xl border bg-card p-8 text-center text-sm text-muted-foreground shadow-sm">
                    <CheckCircle2 class="mx-auto mb-2 h-8 w-8 text-emerald-400" />
                    Aucune commande bloquée — tout est à jour.
                </div>

                <div v-else class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Commande</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:table-cell">Demandeur</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Montant</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Attente</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="order in blockedOrders" :key="order.id"
                                class="transition-colors hover:bg-muted/30">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-foreground line-clamp-1">{{ order.title }}</p>
                                    <p class="text-xs text-muted-foreground mt-0.5">Soumis le {{ formatDate(order.submitted_at) }}</p>
                                </td>
                                <td class="hidden px-4 py-3 text-muted-foreground sm:table-cell">{{ order.user_name }}</td>
                                <td class="px-4 py-3 text-right font-semibold text-foreground">
                                    {{ formatAmountShort(order.amount) }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold"
                                          :class="order.days_waiting >= 7 ? 'bg-red-50 text-red-700' : 'bg-amber-50 text-amber-700'">
                                        <Clock class="h-3 w-3" />
                                        {{ order.days_waiting }}j
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="route('validations.show', order.id)"
                                          class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-2.5 py-1.5 text-xs font-medium text-indigo-700 transition hover:bg-indigo-100">
                                        Voir <ArrowRight class="h-3 w-3" />
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- ================================= -->
            <!-- 2. TOP 5 FOURNISSEURS + CATÉGORIES -->
            <!-- ================================= -->
            <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">

                <!-- Top fournisseurs -->
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-2">
                        <div class="rounded-lg bg-indigo-50 p-1.5"><ShoppingBag class="h-4 w-4 text-indigo-600" /></div>
                        <h2 class="font-semibold text-foreground">Top 5 fournisseurs</h2>
                        <span class="ml-auto text-xs text-muted-foreground">par montant approuvé</span>
                    </div>
                    <div v-if="topFournisseurs.length === 0" class="flex h-32 items-center justify-center text-sm text-muted-foreground">
                        Pas encore de données
                    </div>
                    <div v-else class="h-44">
                        <Bar :data="topFournisseursData" :options="horizontalBarOptions" />
                    </div>
                    <ul v-if="topFournisseurs.length > 0" class="mt-4 divide-y divide-border">
                        <li v-for="(f, i) in topFournisseurs" :key="f.name"
                            class="flex items-center justify-between py-2 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700">{{ i + 1 }}</span>
                                <span class="font-medium text-foreground">{{ f.name }}</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-muted-foreground">{{ f.orders_count }} cmd</span>
                                <span class="font-semibold text-foreground">{{ formatAmountShort(f.total) }}</span>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Top chantiers -->
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-2">
                        <div class="rounded-lg bg-emerald-50 p-1.5"><HardHat class="h-4 w-4 text-emerald-600" /></div>
                        <h2 class="font-semibold text-foreground">Top 5 chantiers</h2>
                        <Link :href="route('analytics.projects')" class="ml-auto inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold text-foreground transition-colors hover:bg-muted">
                            Analyser <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </div>
                    <div v-if="topProjects.length === 0" class="flex h-32 items-center justify-center text-sm text-muted-foreground">
                        Pas encore de données
                    </div>
                    <div v-else class="h-44">
                        <Bar :data="topProjectsData" :options="horizontalBarOptions" />
                    </div>
                    <ul v-if="topProjects.length > 0" class="mt-4 divide-y divide-border">
                        <li v-for="(c, i) in topProjects" :key="c.name"
                            class="flex items-center justify-between py-2 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-700">{{ i + 1 }}</span>
                                <span class="font-medium text-foreground">{{ c.name }}</span><span class="text-xs text-muted-foreground">{{ c.share }} %</span>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="text-xs text-muted-foreground">{{ c.orders_count }} cmd</span>
                                <span class="font-semibold text-foreground">{{ formatAmountShort(c.total) }}</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- ================================== -->
            <!-- 3. DÉPENSES MENSUELLES PAR BOUTIQUE -->
            <!-- ================================== -->
            <section>
                <div class="mb-3 flex items-center gap-2">
                    <TrendingUp class="h-4 w-4 text-indigo-500" />
                    <h2 class="font-semibold text-foreground">Achats par chantier</h2>
                    <div class="ml-auto inline-flex rounded-lg border bg-muted/40 p-0.5 text-xs font-medium">
                        <button v-for="period in (['monthly', 'quarterly', 'annual'] as const)" :key="period" class="rounded-md px-3 py-1.5 transition-colors" :class="projectPeriod === period ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'" @click="projectPeriod = period">
                            {{ period === 'monthly' ? 'Mensuel' : period === 'quarterly' ? 'Trimestriel' : 'Annuel' }}
                        </button>
                    </div>
                </div>
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div v-if="!hasLineData" class="flex h-40 items-center justify-center text-sm text-muted-foreground">
                        Pas encore de données
                    </div>
                    <div v-else class="h-64">
                        <Line :data="projectSeries" :options="lineOptions" />
                    </div>
                </div>
            </section>

            <!-- ============================== -->
            <section>
                <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div class="flex items-center gap-2">
                        <ShoppingBag class="h-4 w-4 text-emerald-500" />
                        <h2 class="font-semibold text-foreground">Montant des bons de commande approuvés</h2>
                        <span class="text-xs text-muted-foreground">engagements d’achat</span>
                    </div>
                    <div class="inline-flex self-start rounded-lg border bg-muted/40 p-0.5 text-xs font-medium sm:ml-auto">
                        <button v-for="period in (['monthly', 'quarterly', 'annual'] as const)" :key="period" class="rounded-md px-3 py-1.5 transition-colors" :class="approvedPeriod === period ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'" @click="approvedPeriod = period">{{ period === 'monthly' ? 'Mensuel' : period === 'quarterly' ? 'Trimestriel' : 'Annuel' }}</button>
                    </div>
                </div>
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-baseline gap-2"><span class="text-2xl font-bold text-foreground">{{ formatAmount(approvedTotal) }}</span><span class="text-xs text-muted-foreground">total affiché sur la période</span></div>
                    <div v-if="!hasApprovedData" class="flex h-40 items-center justify-center text-sm text-muted-foreground">Aucun bon de commande approuvé sur cette période</div>
                    <div v-else class="h-56"><Bar :data="approvedBarData" :options="deliveredBarOptions" /></div>
                </div>
            </section>
            <!-- 3bis. MONTANT LIVRÉ PAR PÉRIODE -->
            <!-- ============================== -->
            <section>
                <div class="mb-3 flex items-center gap-2">
                    <PackageCheck class="h-4 w-4 text-sky-500" />
                    <h2 class="font-semibold text-foreground">Montant des commandes livrées</h2>
                    <span class="ml-1 text-xs text-muted-foreground">réceptions complètes et partielles</span>
                    <div class="ml-auto flex items-center gap-2">
                        <div class="inline-flex rounded-lg border bg-muted/40 p-0.5 text-xs font-medium">
                            <button
                                @click="deliveredPeriod = 'monthly'"
                                class="rounded-md px-3 py-1.5 transition-colors"
                                :class="deliveredPeriod === 'monthly' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                            >
                                Mensuel
                            </button>
                            <button
                                @click="deliveredPeriod = 'quarterly'"
                                class="rounded-md px-3 py-1.5 transition-colors"
                                :class="deliveredPeriod === 'quarterly' ? 'bg-card text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground'"
                            >
                                Trimestriel
                            </button>
                        </div>
                        <a
                            :href="exportDeliveredUrl"
                            class="inline-flex items-center gap-1.5 rounded-lg border bg-card px-3 py-1.5 text-xs font-medium text-foreground transition-colors hover:bg-muted"
                        >
                            <Download class="h-3.5 w-3.5" />
                            Exporter
                        </a>
                    </div>
                </div>
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-baseline gap-2">
                        <span class="text-2xl font-bold text-foreground">{{ formatAmount(deliveredTotal) }}</span>
                        <span class="text-xs text-muted-foreground">
                            total livré — {{ deliveredPeriod === 'monthly' ? '12 derniers mois' : '8 derniers trimestres' }}
                        </span>
                    </div>
                    <div v-if="!hasDeliveredData" class="flex h-40 items-center justify-center text-sm text-muted-foreground">
                        Pas encore de livraisons enregistrées sur cette période
                    </div>
                    <div v-else class="h-56">
                        <Bar :data="deliveredBarData" :options="deliveredBarOptions" />
                    </div>
                </div>
            </section>

            <!-- ==================================== -->
            <!-- 3ter. DÉLAI DE LIVRAISON PAR FOURNISSEUR -->
            <!-- ==================================== -->
            <section>
                <div class="mb-3 flex items-center gap-2">
                    <Truck class="h-4 w-4 text-orange-500" />
                    <h2 class="font-semibold text-foreground">Délai moyen de livraison par fournisseur</h2>
                    <span class="ml-auto text-xs text-muted-foreground">confirmation → réception complète</span>
                </div>
                <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                    <div v-if="fournisseurLeadTimes.length === 0" class="p-8 text-center text-sm text-muted-foreground">
                        Pas encore de commandes entièrement réceptionnées
                    </div>
                    <ul v-else class="divide-y divide-border">
                        <li v-for="lt in fournisseurLeadTimes" :key="lt.name"
                            class="flex items-center justify-between px-5 py-3 text-sm">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-foreground">{{ lt.name }}</span>
                                <span class="text-xs text-muted-foreground">{{ lt.orders_count }} cmd</span>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="leadTimeBadgeClass(lt.avg_days)">
                                {{ lt.avg_days }}j moy.
                            </span>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- ========================== -->
            <!-- 4. DÉLAIS DE VALIDATION    -->
            <!-- ========================== -->
            <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">

                <!-- Par niveau -->
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-2">
                        <div class="rounded-lg bg-amber-50 p-1.5"><Timer class="h-4 w-4 text-amber-600" /></div>
                        <h2 class="font-semibold text-foreground">Délai moyen par niveau</h2>
                    </div>
                    <div v-if="validationDelays.byLevel.length === 0"
                         class="flex h-32 items-center justify-center text-sm text-muted-foreground">
                        Aucune validation enregistrée
                    </div>
                    <template v-else>
                        <div class="h-40 mb-4">
                            <Bar :data="delayByLevelData" :options="delayBarOptions" />
                        </div>
                        <ul class="divide-y divide-border">
                            <li v-for="l in validationDelays.byLevel" :key="l.name"
                                class="flex items-center justify-between py-2.5 text-sm">
                                <span class="font-medium text-foreground">{{ l.name }}</span>
                                <div class="flex items-center gap-3 text-right">
                                    <span class="text-xs text-muted-foreground">{{ l.total }} validations</span>
                                    <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                        {{ l.avg_days }}j moy.
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </template>
                </div>

                <!-- Par validateur -->
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-2">
                        <div class="rounded-lg bg-sky-50 p-1.5"><Users class="h-4 w-4 text-sky-600" /></div>
                        <h2 class="font-semibold text-foreground">Délai moyen par validateur</h2>
                        <span class="ml-auto text-xs text-muted-foreground">du plus rapide au plus lent</span>
                    </div>
                    <div v-if="validationDelays.byValidator.length === 0"
                         class="flex h-32 items-center justify-center text-sm text-muted-foreground">
                        Aucune validation enregistrée
                    </div>
                    <ul v-else class="divide-y divide-border">
                        <li v-for="v in validationDelays.byValidator" :key="v.name"
                            class="py-3 text-sm">
                            <div class="flex items-center justify-between mb-1.5">
                                <span class="font-medium text-foreground">{{ v.name }}</span>
                                <span class="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-semibold text-sky-700">
                                    {{ v.avg_days }}j moy.
                                </span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-muted-foreground">
                                <span class="flex items-center gap-1 text-emerald-600">
                                    <CheckCircle2 class="h-3 w-3" /> {{ v.approved }} approuvées
                                </span>
                                <span class="flex items-center gap-1 text-red-500">
                                    <XCircle class="h-3 w-3" /> {{ v.rejected }} refusées
                                </span>
                                <span class="ml-auto">{{ v.total }} total</span>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>

            <!-- ========================= -->
            <!-- 5. TAUX DE REFUS PAR USER -->
            <!-- ========================= -->
            <section>
                <div class="mb-3 flex items-center gap-2">
                    <XCircle class="h-4 w-4 text-red-500" />
                    <h2 class="font-semibold text-foreground">Taux de refus par demandeur</h2>
                    <span class="ml-auto text-xs text-muted-foreground">commandes finalisées (approuvées + refusées)</span>
                </div>
                <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                    <div v-if="rejectionRates.length === 0"
                         class="p-8 text-center text-sm text-muted-foreground">
                        Pas encore de données suffisantes
                    </div>
                    <table v-else class="w-full text-sm">
                        <thead class="border-b bg-muted/40">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Demandeur</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-muted-foreground">Total</th>
                                <th class="hidden px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:table-cell">
                                    <CheckCircle2 class="inline h-3.5 w-3.5 text-emerald-500 mr-1" />Approu.
                                </th>
                                <th class="hidden px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:table-cell">
                                    <XCircle class="inline h-3.5 w-3.5 text-red-500 mr-1" />Refusées
                                </th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Taux refus</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="r in rejectionRates" :key="r.name"
                                class="transition-colors hover:bg-muted/30">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-slate-100 text-xs font-bold text-slate-600 uppercase">
                                            {{ r.name.charAt(0) }}
                                        </div>
                                        <span class="font-medium text-foreground">{{ r.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center text-muted-foreground">{{ r.total }}</td>
                                <td class="hidden px-4 py-3 text-center text-emerald-600 sm:table-cell">{{ r.approved }}</td>
                                <td class="hidden px-4 py-3 text-center text-red-500 sm:table-cell">{{ r.rejected }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <div class="hidden w-24 overflow-hidden rounded-full bg-muted h-1.5 sm:block">
                                            <div
                                                class="h-full rounded-full transition-all"
                                                :class="rateColor(r.rate)"
                                                :style="{ width: Math.min(r.rate, 100) + '%' }"
                                            />
                                        </div>
                                        <span class="font-semibold tabular-nums"
                                              :class="r.rate >= 50 ? 'text-red-600' : r.rate >= 25 ? 'text-amber-600' : 'text-emerald-600'">
                                            {{ r.rate }}%
                                        </span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </AppLayout>
</template>
