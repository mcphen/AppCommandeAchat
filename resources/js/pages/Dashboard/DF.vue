<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type DemandeAutorisationPaiement, type Entreprise, type NiveauValidation } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Clock, CheckCircle2, TrendingUp, AlertTriangle, Download } from 'lucide-vue-next';
import { computed, ref } from 'vue';

type BudgetSociete = {
    id: number;
    nom: string;
    code: string;
    budget_id: number | null;
    montant_total: number;
    montant_consomme: number;
    montant_disponible: number;
    pourcentage: number;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Tableau de bord', href: '/dashboard' }];

const props = defineProps<{
    stats: Record<string, number>;
    niveau?: NiveauValidation | null;
    budgetsSocietes?: BudgetSociete[];
    dapsEnAttente?: DemandeAutorisationPaiement[];
    depensesMensuelles?: Record<string, number>;
    entreprises?: Array<Pick<Entreprise, 'id' | 'nom'>>;
    filters?: {
        periode?: string;
        entreprise_id?: string;
        date_debut?: string;
        date_fin?: string;
    };
}>();

const localFilters = ref({
    periode: props.filters?.periode ?? 'this_month',
    entreprise_id: props.filters?.entreprise_id ?? '',
    date_debut: props.filters?.date_debut ?? '',
    date_fin: props.filters?.date_fin ?? '',
});

function applyFilters() {
    router.get(route('dashboard'), {
        periode: localFilters.value.periode,
        entreprise_id: localFilters.value.entreprise_id || undefined,
        date_debut: localFilters.value.periode === 'custom' ? (localFilters.value.date_debut || undefined) : undefined,
        date_fin: localFilters.value.periode === 'custom' ? (localFilters.value.date_fin || undefined) : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    localFilters.value = {
        periode: 'this_month',
        entreprise_id: '',
        date_debut: '',
        date_fin: '',
    };
    applyFilters();
}

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    params.set('periode', localFilters.value.periode);
    if (localFilters.value.entreprise_id) params.set('entreprise_id', localFilters.value.entreprise_id);
    if (localFilters.value.periode === 'custom' && localFilters.value.date_debut) params.set('date_debut', localFilters.value.date_debut);
    if (localFilters.value.periode === 'custom' && localFilters.value.date_fin) params.set('date_fin', localFilters.value.date_fin);
    const qs = params.toString();
    return route('dashboard.df.export') + (qs ? `?${qs}` : '');
});

function formatMontant(v: number) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        maximumFractionDigits: 0,
    }).format(v);
}

function budgetColor(pct: number): string {
    if (pct >= 80) return 'bg-red-500';
    if (pct >= 60) return 'bg-amber-400';
    return 'bg-emerald-500';
}

function budgetBorder(pct: number): string {
    if (pct >= 80) return 'border-red-200 bg-red-50';
    if (pct >= 60) return 'border-amber-200 bg-amber-50';
    return 'border-emerald-200 bg-emerald-50/40';
}

function budgetTextColor(pct: number): string {
    if (pct >= 80) return 'text-red-700';
    if (pct >= 60) return 'text-amber-700';
    return 'text-emerald-700';
}

const totalBudget = computed(() => props.budgetsSocietes?.reduce((s, b) => s + b.montant_total, 0) ?? 0);
const totalConsomme = computed(() => props.budgetsSocietes?.reduce((s, b) => s + b.montant_consomme, 0) ?? 0);
const totalPct = computed(() => (totalBudget.value > 0 ? Math.round((totalConsomme.value / totalBudget.value) * 100) : 0));

const moisLabels = computed(() => {
    const result: { key: string; label: string }[] = [];
    for (let i = 11; i >= 0; i--) {
        const d = new Date();
        d.setDate(1);
        d.setMonth(d.getMonth() - i);
        const key = d.toISOString().substring(0, 7);
        result.push({ key, label: d.toLocaleDateString('fr-FR', { month: 'short', year: '2-digit' }) });
    }
    return result;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Dashboard DF" />

        <div class="flex flex-col gap-6 p-6">
            <PageHeader
                title="Tableau de bord DF"
                subtitle="Vue consolidée budgétaire et validations à signer"
                eyebrow="Pilotage Direction Financière"
            >
                <template #actions>
                    <Link :href="route('validations-dap.index')"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">
                        DAP en attente
                    </Link>
                    <Link :href="route('validations-dap.toutes')"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">
                        Toutes les DAP
                    </Link>
                </template>
            </PageHeader>

            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Periode</label>
                        <select
                            v-model="localFilters.periode"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40"
                        >
                            <option value="this_month">Ce mois</option>
                            <option value="last_30_days">30 derniers jours</option>
                            <option value="year_to_date">Depuis debut d'annee</option>
                            <option value="custom">Personnalisee</option>
                        </select>
                    </div>
                    <div v-if="localFilters.periode === 'custom'">
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Date debut</label>
                        <input
                            v-model="localFilters.date_debut"
                            type="date"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40"
                        />
                    </div>
                    <div v-if="localFilters.periode === 'custom'">
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Date fin</label>
                        <input
                            v-model="localFilters.date_fin"
                            type="date"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40"
                        />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Entreprise</label>
                        <select
                            v-model="localFilters.entreprise_id"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40"
                        >
                            <option value="">Toutes</option>
                            <option v-for="e in (entreprises ?? [])" :key="e.id" :value="String(e.id)">{{ e.nom }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2 flex items-end gap-2">
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                            @click="applyFilters"
                        >
                            Filtrer
                        </button>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors"
                            @click="resetFilters"
                        >
                            Reinitialiser
                        </button>
                        <a
                            :href="exportUrl"
                            class="ml-auto inline-flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100 transition-colors"
                        >
                            <Download class="h-4 w-4" />
                            Export Excel
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                <div class="rounded-xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-600">A signer</p>
                    <p class="mt-1 text-3xl font-bold text-amber-700">{{ stats.en_attente }}</p>
                    <Link :href="route('validations-dap.index')" class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-amber-600 hover:underline">
                        Traiter <ArrowRight class="h-3 w-3" />
                    </Link>
                </div>
                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Mes approbations</p>
                    <p class="mt-1 text-3xl font-bold">{{ stats.mes_validees }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ niveau?.nom }}</p>
                </div>
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-blue-600">Paye {{ new Date().getFullYear() }}</p>
                    <p class="mt-1 text-xl font-bold leading-tight text-blue-700">{{ formatMontant(stats.total_paye ?? 0) }}</p>
                </div>
                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Engage en cours</p>
                    <p class="mt-1 text-xl font-bold leading-tight">{{ formatMontant(stats.total_en_cours ?? 0) }}</p>
                </div>
            </div>

            <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
                <div class="flex items-center justify-between border-b px-5 py-4">
                    <div>
                        <p class="text-sm font-semibold">Budgets {{ new Date().getFullYear() }} - Vue groupe</p>
                        <p class="mt-0.5 text-xs text-muted-foreground">Consolide Fortune Capital</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-muted-foreground">Total groupe</p>
                        <p class="text-sm font-bold">{{ formatMontant(totalConsomme) }} <span class="font-normal text-muted-foreground">/ {{ formatMontant(totalBudget) }}</span></p>
                        <p class="mt-0.5 text-xs font-semibold" :class="budgetTextColor(totalPct)">{{ totalPct }}% consomme</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 divide-y divide-border md:grid-cols-2 md:divide-x md:divide-y-0">
                    <div v-for="b in budgetsSocietes" :key="b.id" class="flex flex-col gap-3 p-5">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="rounded-md bg-muted px-2 py-0.5 font-mono text-xs font-bold">{{ b.code }}</span>
                                <span class="text-sm font-semibold">{{ b.nom }}</span>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <AlertTriangle v-if="b.pourcentage >= 80" class="h-3.5 w-3.5 text-red-500" />
                                <AlertTriangle v-else-if="b.pourcentage >= 60" class="h-3.5 w-3.5 text-amber-500" />
                                <span class="text-sm font-bold" :class="budgetTextColor(b.pourcentage)">{{ b.pourcentage }}%</span>
                            </div>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div class="h-full rounded-full transition-all duration-500" :class="budgetColor(b.pourcentage)" :style="{ width: Math.min(b.pourcentage, 100) + '%' }"></div>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-xs">
                            <div class="rounded-lg bg-muted/50 p-2">
                                <p class="mb-0.5 text-muted-foreground">Budget total</p>
                                <p class="font-semibold">{{ formatMontant(b.montant_total) }}</p>
                            </div>
                            <div class="rounded-lg p-2" :class="budgetBorder(b.pourcentage)">
                                <p class="mb-0.5 text-muted-foreground">Consomme</p>
                                <p class="font-semibold" :class="budgetTextColor(b.pourcentage)">{{ formatMontant(b.montant_consomme) }}</p>
                            </div>
                            <div class="rounded-lg bg-muted/50 p-2">
                                <p class="mb-0.5 text-muted-foreground">Disponible</p>
                                <p class="font-semibold">{{ formatMontant(b.montant_disponible) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div v-if="dapsEnAttente?.length" class="overflow-hidden rounded-xl border bg-card shadow-sm">
                <div class="flex items-center gap-2 border-b bg-amber-50/60 px-5 py-4">
                    <Clock class="h-4 w-4 text-amber-500" />
                    <p class="text-sm font-semibold text-amber-800">En attente de votre signature ({{ dapsEnAttente.length }})</p>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-border">
                        <tr v-for="dap in dapsEnAttente" :key="dap.id" class="transition-colors hover:bg-muted/30">
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ dap.reference }}</td>
                            <td class="max-w-[200px] truncate px-4 py-3 font-medium">{{ dap.expression_besoin?.objet }}</td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">{{ dap.expression_besoin?.entreprise?.nom }}</td>
                            <td class="px-4 py-3 text-sm font-semibold">{{ formatMontant(dap.expression_besoin?.montant ?? 0) }}</td>
                            <td class="px-4 py-3">
                                <Link :href="route('validations-dap.show', dap.id)"
                                    class="inline-flex items-center gap-1 rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground transition-colors hover:bg-primary/90">
                                    Valider <ArrowRight class="h-3 w-3" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div v-else class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4">
                <CheckCircle2 class="h-5 w-5 shrink-0 text-emerald-500" />
                <p class="text-sm font-medium text-emerald-700">Aucune DAP en attente de votre signature. Tout est a jour !</p>
            </div>

            <div v-if="depensesMensuelles" class="overflow-hidden rounded-xl border bg-card shadow-sm">
                <div class="flex items-center gap-2 border-b px-5 py-4">
                    <TrendingUp class="h-4 w-4 text-primary" />
                    <p class="text-sm font-semibold">Depenses groupe - 12 derniers mois</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs">
                        <thead>
                            <tr class="border-b bg-muted/30">
                                <th v-for="m in moisLabels" :key="m.key" class="px-3 py-2 text-center font-medium uppercase text-muted-foreground">
                                    {{ m.label }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td v-for="m in moisLabels" :key="m.key" class="px-3 py-3 text-center font-semibold">
                                    <span v-if="depensesMensuelles[m.key]" class="text-foreground">
                                        {{ new Intl.NumberFormat('fr-FR', { notation: 'compact', maximumFractionDigits: 1 }).format(depensesMensuelles[m.key]) }}
                                    </span>
                                    <span v-else class="text-muted-foreground/40">-</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
