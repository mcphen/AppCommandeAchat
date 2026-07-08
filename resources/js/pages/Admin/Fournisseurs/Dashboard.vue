<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { BarElement, CategoryScale, Chart as ChartJS, Filler, Legend, LinearScale, LineElement, PointElement, Tooltip } from 'chart.js';
import { AlertTriangle, ArrowLeft, PieChart, ShieldAlert, TrendingUp, Truck, Wallet } from 'lucide-vue-next';
import { computed } from 'vue';
import { Bar, Line } from 'vue-chartjs';

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend, LineElement, PointElement, Filler);

interface TopBudgetItem {
    id: number;
    name: string;
    code: string;
    total: number;
    percent: number;
}

interface MonthlyPoint {
    month: string;
    label: string;
    total: number;
}

interface RiskSupplier {
    id: number;
    name: string;
    code: string;
    order_lines_count: number;
    total: number;
}

const props = defineProps<{
    totalBudget: number;
    concentrationTop5: number;
    topBudget: TopBudgetItem[];
    monthlyEvolution: MonthlyPoint[];
    riskSuppliers: RiskSupplier[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Fournisseurs', href: '/admin/fournisseurs' },
    { title: 'Analyse budgetaire', href: '/admin/fournisseurs/dashboard' },
];

const formatAmount = (v: number) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);

const formatAmountShort = (v: number) => {
    if (v >= 1_000_000_000) return (v / 1_000_000_000).toFixed(1) + ' Md';
    if (v >= 1_000_000) return (v / 1_000_000).toFixed(1) + ' M';
    if (v >= 1_000) return (v / 1_000).toFixed(0) + ' K';
    return v.toFixed(0);
};

const topBudgetData = computed(() => ({
    labels: props.topBudget.map((f) => f.name),
    datasets: [
        {
            label: 'Montant valide',
            data: props.topBudget.map((f) => f.total),
            backgroundColor: ['#6366f1cc', '#8b5cf6cc', '#a78bfacc', '#c4b5fdcc', '#ddd6fecc', '#e0e7ffcc'],
            borderRadius: 6,
            borderSkipped: false,
        },
    ],
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
            callbacks: { label: (ctx: any) => ' ' + formatAmountShort(ctx.raw) + ' XOF' },
        },
    },
    scales: {
        x: { grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { callback: (v: any) => formatAmountShort(v) } },
        y: { grid: { display: false }, border: { display: false } },
    },
};

const monthlyData = computed(() => ({
    labels: props.monthlyEvolution.map((m) => m.label),
    datasets: [
        {
            label: 'Achats valides',
            data: props.monthlyEvolution.map((m) => m.total),
            borderColor: '#6366f1',
            backgroundColor: '#6366f133',
            tension: 0.35,
            fill: true,
            pointRadius: 3,
            pointHoverRadius: 5,
        },
    ],
}));

const lineOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { display: false },
        tooltip: {
            padding: 10,
            cornerRadius: 8,
            callbacks: { label: (ctx: any) => ' ' + formatAmountShort(ctx.raw) + ' XOF' },
        },
    },
    scales: {
        x: { grid: { display: false }, border: { display: false } },
        y: { grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { callback: (v: any) => formatAmountShort(v) } },
    },
};

const hasMonthlyData = computed(() => props.monthlyEvolution.some((m) => m.total > 0));
</script>

<template>
    <Head title="Analyse budgetaire fournisseurs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <Link
                        :href="route('admin.fournisseurs.index')"
                        class="mb-1 inline-flex items-center gap-1 text-xs font-medium text-muted-foreground hover:text-foreground"
                    >
                        <ArrowLeft class="h-3.5 w-3.5" />
                        Retour aux fournisseurs
                    </Link>
                    <h1 class="text-2xl font-bold text-foreground">Analyse budgetaire fournisseurs</h1>
                    <p class="text-sm text-muted-foreground">Concentration des achats, tendance mensuelle et fournisseurs a risque.</p>
                </div>
            </div>

            <section class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        <Wallet class="h-4 w-4" />
                        Budget total valide
                    </div>
                    <p class="mt-3 text-2xl font-bold text-foreground">{{ formatAmount(totalBudget) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">commandes approuvees, toutes periodes</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        <PieChart class="h-4 w-4" />
                        Concentration top 5
                    </div>
                    <p class="mt-3 text-2xl font-bold text-foreground">{{ concentrationTop5 }}%</p>
                    <p class="mt-1 text-xs text-muted-foreground">du budget porte par 5 fournisseurs</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-300">
                        <ShieldAlert class="h-4 w-4" />
                        Fournisseurs a surveiller
                    </div>
                    <p class="mt-3 text-2xl font-bold text-foreground">{{ riskSuppliers.length }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">actifs mais non homologues</p>
                </div>
            </section>

            <section class="grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-2">
                        <div class="rounded-lg bg-indigo-50 p-1.5 dark:bg-indigo-950/30"><Truck class="h-4 w-4 text-indigo-600 dark:text-indigo-300" /></div>
                        <h2 class="font-semibold text-foreground">Top 10 fournisseurs</h2>
                        <span class="ml-auto text-xs text-muted-foreground">par montant valide</span>
                    </div>
                    <div v-if="topBudget.length === 0" class="flex h-32 items-center justify-center text-sm text-muted-foreground">
                        Pas encore de donnees
                    </div>
                    <template v-else>
                        <div class="h-56">
                            <Bar :data="topBudgetData" :options="horizontalBarOptions" />
                        </div>
                        <ul class="mt-4 divide-y divide-border">
                            <li v-for="(f, i) in topBudget" :key="f.id" class="flex items-center justify-between py-2 text-sm">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300">
                                        {{ i + 1 }}
                                    </span>
                                    <span class="truncate font-medium text-foreground">{{ f.name }}</span>
                                </div>
                                <div class="flex shrink-0 items-center gap-3">
                                    <span class="text-xs text-muted-foreground">{{ f.percent }}%</span>
                                    <span class="font-semibold text-foreground">{{ formatAmountShort(f.total) }}</span>
                                </div>
                            </li>
                        </ul>
                    </template>
                </div>

                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-2">
                        <div class="rounded-lg bg-emerald-50 p-1.5 dark:bg-emerald-950/30"><TrendingUp class="h-4 w-4 text-emerald-600 dark:text-emerald-300" /></div>
                        <h2 class="font-semibold text-foreground">Evolution mensuelle</h2>
                        <span class="ml-auto text-xs text-muted-foreground">12 derniers mois - achats valides</span>
                    </div>
                    <div v-if="!hasMonthlyData" class="flex h-32 items-center justify-center text-sm text-muted-foreground">
                        Pas encore de donnees
                    </div>
                    <div v-else class="h-72">
                        <Line :data="monthlyData" :options="lineOptions" />
                    </div>
                </div>
            </section>

            <section>
                <div class="mb-3 flex items-center gap-2">
                    <AlertTriangle class="h-4 w-4 text-amber-500" />
                    <h2 class="font-semibold text-foreground">Fournisseurs a surveiller</h2>
                    <span class="ml-auto text-xs text-muted-foreground">actifs, non homologues, tries par activite</span>
                </div>

                <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                    <div v-if="riskSuppliers.length === 0" class="p-8 text-center text-sm text-muted-foreground">
                        Aucun fournisseur actif en attente d'homologation.
                    </div>
                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/30">
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Fournisseur</th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Lignes de commande</th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Montant valide</th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="f in riskSuppliers" :key="f.id" class="transition-colors hover:bg-muted/20">
                                    <td class="px-4 py-4 sm:px-6">
                                        <p class="font-medium text-foreground">{{ f.name }}</p>
                                        <p class="font-mono text-xs text-muted-foreground">{{ f.code }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-right text-muted-foreground sm:px-6">{{ f.order_lines_count }}</td>
                                    <td class="px-4 py-4 text-right font-semibold text-foreground sm:px-6">{{ formatAmount(f.total) }}</td>
                                    <td class="px-4 py-4 text-right sm:px-6">
                                        <Link
                                            :href="route('admin.fournisseurs.edit', f.id)"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300 dark:hover:bg-amber-950/50"
                                        >
                                            Homologuer
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
