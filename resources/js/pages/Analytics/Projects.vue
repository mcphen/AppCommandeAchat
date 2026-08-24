<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, BarChart3, Building2, CalendarRange, FileText, Filter, HardHat, ReceiptText, TrendingUp, Users } from 'lucide-vue-next';
import { BarElement, CategoryScale, Chart as ChartJS, Legend, LinearScale, LineElement, PointElement, Tooltip } from 'chart.js';
import { Bar, Line } from 'vue-chartjs';
import { computed, ref } from 'vue';

ChartJS.register(BarElement, CategoryScale, LinearScale, Tooltip, Legend, LineElement, PointElement);

interface ProjectMetric {
    id: number;
    code: string;
    name: string;
    total: number;
    orders_count: number;
    average: number;
    share: number;
}
interface PeriodPoint { period: string; total: number; orders_count: number }
interface Tranche { key: string; label: string; total: number; orders_count: number }
interface SupplierMetric { name: string; total: number; orders_count: number }
interface OrderRow {
    id: number;
    title: string;
    order_number?: string;
    order_date?: string;
    created_at: string;
    amount: number;
    amount_ttc?: number;
    project_name: string;
}

const props = defineProps<{
    filters: { date_from: string; date_to: string; group_by: 'monthly' | 'quarterly' | 'annual'; project_id: string };
    projectOptions: { id: number; code: string; name: string }[];
    summary: { total: number; orders_count: number; projects_count: number; average: number };
    projects: ProjectMetric[];
    evolution: PeriodPoint[];
    tranches: Tranche[];
    topSuppliers: SupplierMetric[];
    orders: PaginatedData<OrderRow>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Analytique', href: '/analytics' },
    { title: 'Analyse des chantiers', href: '/analytics/projects' },
];

const localFilters = ref({ ...props.filters });
const applyFilters = () => router.get(route('analytics.projects'), localFilters.value, { preserveState: true, preserveScroll: true, replace: true });
const resetFilters = () => router.get(route('analytics.projects'), {}, { preserveState: true, replace: true });

const formatAmount = (amount: number) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(amount);
const formatAmountShort = (amount: number) => amount >= 1_000_000_000 ? (amount / 1_000_000_000).toFixed(1) + ' Md' : amount >= 1_000_000 ? (amount / 1_000_000).toFixed(1) + ' M' : amount >= 1_000 ? (amount / 1_000).toFixed(0) + ' K' : amount.toFixed(0);
const formatDate = (date: string) => new Date(date).toLocaleDateString('fr-FR');

const chartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { callbacks: { label: (ctx: any) => ' ' + formatAmount(ctx.raw) } } },
    scales: {
        x: { grid: { display: false }, border: { display: false }, ticks: { color: '#64748b' } },
        y: { grid: { color: '#e2e8f0' }, border: { display: false }, ticks: { color: '#64748b', callback: (value: any) => formatAmountShort(value) } },
    },
};
const horizontalOptions = { ...chartOptions, indexAxis: 'y' as const };
const evolutionData = computed(() => ({
    labels: props.evolution.map(item => item.period.replace('-Q', ' T')),
    datasets: [{ label: 'Montant approuvé', data: props.evolution.map(item => item.total), borderColor: '#6366f1', backgroundColor: '#6366f122', fill: true, tension: 0.35, pointRadius: 4 }],
}));
const trancheData = computed(() => ({
    labels: props.tranches.map(item => item.label),
    datasets: [{ label: 'Montant', data: props.tranches.map(item => item.total), backgroundColor: ['#dbeafe', '#93c5fd', '#60a5fa', '#2563eb'], borderRadius: 7 }],
}));
const projectData = computed(() => ({
    labels: props.projects.slice(0, 10).map(item => item.name),
    datasets: [{ label: 'Montant', data: props.projects.slice(0, 10).map(item => item.total), backgroundColor: '#10b981cc', borderRadius: 7 }],
}));
</script>

<template>
    <Head title="Analyse des chantiers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 text-foreground sm:px-6 sm:py-6">
            <header class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <Link :href="route('analytics.index')" class="mb-3 inline-flex items-center gap-1.5 text-sm font-medium text-muted-foreground hover:text-foreground"><ArrowLeft class="h-4 w-4" /> Retour à l’analytique</Link>
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-indigo-600">Pilotage des engagements</p>
                    <h1 class="mt-1 text-2xl font-bold text-foreground">Analyse des chantiers</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Poids budgétaire, évolution, tranches et commandes approuvées par chantier.</p>
                </div>
                <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-semibold text-indigo-700">{{ filters.date_from }} → {{ filters.date_to }}</div>
            </header>

            <section class="rounded-2xl border bg-card p-4 shadow-sm">
                <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                    <label class="text-xs font-medium text-muted-foreground">Du<input v-model="localFilters.date_from" type="date" class="mt-1 h-10 w-full rounded-xl border bg-background px-3 text-sm text-foreground" /></label>
                    <label class="text-xs font-medium text-muted-foreground">Au<input v-model="localFilters.date_to" type="date" class="mt-1 h-10 w-full rounded-xl border bg-background px-3 text-sm text-foreground" /></label>
                    <label class="text-xs font-medium text-muted-foreground">Chantier<select v-model="localFilters.project_id" class="mt-1 h-10 w-full rounded-xl border bg-background px-3 text-sm text-foreground"><option value="">Tous les chantiers</option><option v-for="project in projectOptions" :key="project.id" :value="String(project.id)">{{ project.name }}</option></select></label>
                    <label class="text-xs font-medium text-muted-foreground">Regroupement<select v-model="localFilters.group_by" class="mt-1 h-10 w-full rounded-xl border bg-background px-3 text-sm text-foreground"><option value="monthly">Mensuel</option><option value="quarterly">Trimestriel</option><option value="annual">Annuel</option></select></label>
                    <div class="flex items-end gap-2"><button class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-xl bg-primary px-4 text-sm font-semibold text-primary-foreground" @click="applyFilters"><Filter class="h-4 w-4" /> Analyser</button><button class="h-10 rounded-xl border px-3 text-sm text-muted-foreground hover:bg-muted" @click="resetFilters">Effacer</button></div>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="card in [
                    { label: 'Montant approuvé', value: formatAmount(summary.total), icon: ReceiptText, color: 'text-indigo-600', bg: 'bg-indigo-50' },
                    { label: 'Bons de commande', value: summary.orders_count, icon: FileText, color: 'text-amber-600', bg: 'bg-amber-50' },
                    { label: 'Chantiers actifs', value: summary.projects_count, icon: HardHat, color: 'text-emerald-600', bg: 'bg-emerald-50' },
                    { label: 'Montant moyen / BC', value: formatAmount(summary.average), icon: TrendingUp, color: 'text-sky-600', bg: 'bg-sky-50' },
                ]" :key="card.label" class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center gap-3"><div class="rounded-xl p-2.5" :class="[card.bg, card.color]"><component :is="card.icon" class="h-5 w-5" /></div><div><p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">{{ card.label }}</p><p class="mt-1 text-xl font-bold text-foreground">{{ card.value }}</p></div></div>
                </div>
            </section>

            <section class="grid gap-5 xl:grid-cols-2">
                <div class="rounded-2xl border bg-card p-5 shadow-sm"><div class="mb-4 flex items-center gap-2"><TrendingUp class="h-4 w-4 text-indigo-500" /><h2 class="font-semibold">Évolution des engagements</h2></div><div v-if="evolution.length" class="h-72"><Line :data="evolutionData" :options="chartOptions" /></div><div v-else class="flex h-72 items-center justify-center text-sm text-muted-foreground">Aucune donnée sur cette période</div></div>
                <div class="rounded-2xl border bg-card p-5 shadow-sm"><div class="mb-4 flex items-center gap-2"><BarChart3 class="h-4 w-4 text-blue-500" /><h2 class="font-semibold">Répartition par tranche de BC</h2></div><div class="h-72"><Bar :data="trancheData" :options="horizontalOptions" /></div></div>
            </section>

            <section class="grid gap-5 xl:grid-cols-2">
                <div class="rounded-2xl border bg-card p-5 shadow-sm"><div class="mb-4 flex items-center gap-2"><HardHat class="h-4 w-4 text-emerald-500" /><h2 class="font-semibold">Poids des chantiers</h2></div><div v-if="projects.length" class="h-72"><Bar :data="projectData" :options="horizontalOptions" /></div><div v-else class="flex h-72 items-center justify-center text-sm text-muted-foreground">Aucun chantier</div></div>
                <div class="overflow-hidden rounded-2xl border bg-card shadow-sm"><div class="border-b px-5 py-4"><h2 class="font-semibold">Classement détaillé</h2></div><div class="max-h-80 overflow-auto divide-y"><div v-for="(project, index) in projects" :key="project.id" class="flex items-center gap-3 px-5 py-3"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-50 text-xs font-bold text-emerald-700">{{ index + 1 }}</span><div class="min-w-0 flex-1"><p class="truncate text-sm font-semibold">{{ project.name }}</p><p class="text-xs text-muted-foreground">{{ project.orders_count }} BC · moyenne {{ formatAmountShort(project.average) }}</p></div><div class="text-right"><p class="text-sm font-bold">{{ formatAmountShort(project.total) }}</p><p class="text-xs text-emerald-600">{{ project.share }} %</p></div></div></div></div>
            </section>

            <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="border-b px-5 py-4"><div class="flex items-center gap-2"><Users class="h-4 w-4 text-violet-500" /><h2 class="font-semibold">Top fournisseurs sur la sélection</h2></div></div>
                <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b bg-muted/20"><th class="px-5 py-3 text-left">Fournisseur</th><th class="px-5 py-3 text-right">BC</th><th class="px-5 py-3 text-right">Montant</th></tr></thead><tbody class="divide-y"><tr v-for="supplier in topSuppliers" :key="supplier.name"><td class="px-5 py-3 font-medium">{{ supplier.name }}</td><td class="px-5 py-3 text-right text-muted-foreground">{{ supplier.orders_count }}</td><td class="px-5 py-3 text-right font-semibold">{{ formatAmount(supplier.total) }}</td></tr><tr v-if="!topSuppliers.length"><td colspan="3" class="px-5 py-10 text-center text-muted-foreground">Aucun fournisseur sur cette période</td></tr></tbody></table></div>
            </section>

            <section class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="border-b px-5 py-4"><div class="flex items-center gap-2"><CalendarRange class="h-4 w-4 text-indigo-500" /><h2 class="font-semibold">Commandes à l’origine des montants</h2><span class="ml-auto text-xs text-muted-foreground">{{ orders.total }} résultat(s)</span></div></div>
                <div class="overflow-x-auto"><table class="w-full text-sm"><thead><tr class="border-b bg-muted/20"><th class="px-5 py-3 text-left">Commande</th><th class="px-5 py-3 text-left">Chantier</th><th class="px-5 py-3 text-left">Date</th><th class="px-5 py-3 text-right">HT</th><th class="px-5 py-3 text-right">TTC</th></tr></thead><tbody class="divide-y"><tr v-for="order in orders.data" :key="order.id" class="hover:bg-muted/20"><td class="px-5 py-3"><Link :href="route('purchase-orders.show', order.id)" class="font-semibold text-primary hover:underline">{{ order.order_number || order.title }}</Link></td><td class="px-5 py-3">{{ order.project_name }}</td><td class="px-5 py-3 text-muted-foreground">{{ formatDate(order.order_date || order.created_at) }}</td><td class="px-5 py-3 text-right font-medium">{{ formatAmount(order.amount) }}</td><td class="px-5 py-3 text-right">{{ order.amount_ttc ? formatAmount(order.amount_ttc) : '—' }}</td></tr></tbody></table></div>
                <div v-if="orders.last_page > 1" class="flex flex-wrap justify-center gap-1 border-t px-5 py-4"><Link v-for="link in orders.links" :key="link.label" :href="link.url || '#'" class="rounded-lg px-3 py-1.5 text-sm" :class="[link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted', !link.url ? 'pointer-events-none opacity-40' : '']"><span v-html="link.label" /></Link></div>
            </section>
        </div>
    </AppLayout>
</template>