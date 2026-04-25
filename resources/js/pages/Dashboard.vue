<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type DemandeAutorisationPaiement, type Entreprise, type ExpressionBesoin, type NiveauValidation, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { FileText, Clock, CheckCircle2, XCircle, ArrowRight, TrendingUp, AlertTriangle, ShieldAlert, Gauge, Users, BookOpen } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Tableau de bord', href: '/dashboard' }];

type BudgetSociete = {
    id: number; nom: string; code: string; budget_id: number | null;
    montant_total: number; montant_consomme: number; montant_disponible: number; pourcentage: number;
};

type DapBloquee = {
    id: number;
    reference: string;
    objet?: string;
    demandeur?: string;
    entreprise?: string;
    jours_retard: number;
};

const props = defineProps<{
    stats: Record<string, number>;
    recentDaps?: DemandeAutorisationPaiement[];
    recentEb?: ExpressionBesoin[];
    niveau?: NiveauValidation | null;
    budgetsSocietes?: BudgetSociete[];
    dapsBloquees?: DapBloquee[];
    entreprises?: Array<Pick<Entreprise, 'id' | 'nom'>>;
    filters?: {
        periode?: string;
        entreprise_id?: string;
        date_debut?: string;
        date_fin?: string;
    };
}>();

const page = usePage<SharedData>();
const role = computed(() => page.props.auth.user?.role?.slug);

function formatMontant(v: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
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

const totalBudget    = computed(() => props.budgetsSocietes?.reduce((s, b) => s + b.montant_total, 0) ?? 0);
const totalConsomme  = computed(() => props.budgetsSocietes?.reduce((s, b) => s + b.montant_consomme, 0) ?? 0);
const totalPct       = computed(() => totalBudget.value > 0 ? Math.round(totalConsomme.value / totalBudget.value * 100) : 0);

const ebStatutConfig: Record<string, { label: string; class: string; icon: any }> = {
    en_attente: { label: 'En attente', class: 'bg-amber-100 text-amber-700', icon: Clock },
    validee:    { label: 'Validée',    class: 'bg-emerald-100 text-emerald-700', icon: CheckCircle2 },
    rejetee:    { label: 'Rejetée',   class: 'bg-red-100 text-red-700', icon: XCircle },
};

const dapStatutConfig: Record<string, { label: string; class: string }> = {
    en_cours: { label: 'En cours', class: 'bg-amber-100 text-amber-700' },
    validee:  { label: 'Validée', class: 'bg-emerald-100 text-emerald-700' },
    rejetee:  { label: 'Rejetée', class: 'bg-red-100 text-red-700' },
    payee:    { label: 'Payée', class: 'bg-blue-100 text-blue-700' },
};

const isAdmin = computed(() => role.value === 'admin');

const adminFilters = ref({
    periode: props.filters?.periode ?? 'this_month',
    entreprise_id: props.filters?.entreprise_id ?? '',
    date_debut: props.filters?.date_debut ?? '',
    date_fin: props.filters?.date_fin ?? '',
});

function applyAdminFilters() {
    router.get(route('dashboard'), {
        periode: adminFilters.value.periode,
        entreprise_id: adminFilters.value.entreprise_id || undefined,
        date_debut: adminFilters.value.periode === 'custom' ? (adminFilters.value.date_debut || undefined) : undefined,
        date_fin: adminFilters.value.periode === 'custom' ? (adminFilters.value.date_fin || undefined) : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetAdminFilters() {
    adminFilters.value = {
        periode: 'this_month',
        entreprise_id: '',
        date_debut: '',
        date_fin: '',
    };
    applyAdminFilters();
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Tableau de bord" />

        <div class="flex flex-col gap-6 p-6">
            <PageHeader
                title="Tableau de bord"
                subtitle="Vue consolidée des flux achats, validations et paiements"
                eyebrow="Pilotage opérationnel"
            >
                <template #actions>
                    <Link v-if="isAdmin" :href="route('admin.users.index')"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">
                        <Users class="h-4 w-4" />
                        Utilisateurs
                    </Link>
                    <Link v-if="isAdmin" :href="route('admin.niveaux-validation.index')"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">
                        <Gauge class="h-4 w-4" />
                        Seuils
                    </Link>
                </template>
            </PageHeader>

            <template v-if="isAdmin">
                <div class="rounded-xl border bg-card p-4 shadow-sm">
                    <div class="grid grid-cols-1 gap-3 md:grid-cols-6">
                        <div>
                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Période</label>
                            <select
                                v-model="adminFilters.periode"
                                class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40"
                            >
                                <option value="this_month">Ce mois</option>
                                <option value="last_30_days">30 derniers jours</option>
                                <option value="year_to_date">Depuis début d'année</option>
                                <option value="custom">Personnalisée</option>
                            </select>
                        </div>
                        <div v-if="adminFilters.periode === 'custom'">
                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Date début</label>
                            <input
                                v-model="adminFilters.date_debut"
                                type="date"
                                class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40"
                            />
                        </div>
                        <div v-if="adminFilters.periode === 'custom'">
                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Date fin</label>
                            <input
                                v-model="adminFilters.date_fin"
                                type="date"
                                class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium text-muted-foreground">Entreprise</label>
                            <select
                                v-model="adminFilters.entreprise_id"
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
                                @click="applyAdminFilters"
                            >
                                Filtrer
                            </button>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors"
                                @click="resetAdminFilters"
                            >
                                Réinitialiser
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-xl border bg-card p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">Engagé période</p>
                        <p class="mt-1 text-xl font-bold">{{ formatMontant(stats.montant_engage_mois ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wider text-blue-600">Payé période</p>
                        <p class="mt-1 text-xl font-bold text-blue-700">{{ formatMontant(stats.montant_paye_mois ?? 0) }}</p>
                    </div>
                    <div class="rounded-xl border border-red-200 bg-red-50 p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wider text-red-600">EB rejetées période</p>
                        <p class="mt-1 text-3xl font-bold text-red-700">{{ stats.eb_rejetees_30j ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl border bg-card p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wider text-muted-foreground">DAP bloquées</p>
                        <p class="mt-1 text-3xl font-bold">{{ stats.daps_bloquees ?? 0 }}</p>
                    </div>
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
                        <p class="text-xs font-medium uppercase tracking-wider text-emerald-600">Transformation EB → payée</p>
                        <p class="mt-1 text-3xl font-bold text-emerald-700">{{ stats.taux_transformation ?? 0 }}%</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                    <div class="rounded-xl border bg-card p-5 shadow-sm lg:col-span-2">
                        <div class="mb-3 flex items-center justify-between">
                            <p class="text-sm font-semibold">Alertes prioritaires</p>
                            <ShieldAlert class="h-4 w-4 text-amber-500" />
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="rounded-lg border border-red-200 bg-red-50 px-3 py-2">
                                <span class="font-semibold text-red-700">{{ stats.budgets_alertes ?? 0 }}</span>
                                <span class="text-red-700"> budget(s) au-dessus de 80% de consommation</span>
                            </div>
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2">
                                <span class="font-semibold text-amber-700">{{ stats.daps_bloquees ?? 0 }}</span>
                                <span class="text-amber-700"> DAP en cours depuis plus de 3 jours</span>
                            </div>
                            <div class="rounded-lg border border-muted bg-muted/30 px-3 py-2">
                                <span class="font-semibold">{{ stats.eb_en_attente ?? 0 }}</span>
                                <span class="text-muted-foreground"> EB attendent encore la validation comptable</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border bg-card p-5 shadow-sm">
                        <p class="mb-3 text-sm font-semibold">Actions rapides</p>
                        <div class="flex items-center gap-2 overflow-x-auto pb-1">
                            <Link
                                :href="route('admin.users.create')"
                                class="inline-flex shrink-0 items-center justify-center rounded-md bg-sky-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-sky-700"
                            >
                                Nouvel utilisateur
                            </Link>
                            <Link
                                :href="route('admin.validateurs.index')"
                                class="inline-flex shrink-0 items-center justify-center rounded-md bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-emerald-700"
                            >
                                Assigner validateur
                            </Link>
                            <Link
                                :href="route('admin.budgets-annuels.index')"
                                class="inline-flex shrink-0 items-center justify-center rounded-md bg-amber-500 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-amber-600"
                            >
                                Suivre budgets
                            </Link>
                            <Link
                                :href="route('validations-dap.index')"
                                class="inline-flex shrink-0 items-center justify-center rounded-md bg-violet-600 px-3 py-2 text-xs font-semibold text-white transition-colors hover:bg-violet-700"
                            >
                                Traiter DAP
                            </Link>
                        </div>
                    </div>
                </div>

                <div v-if="dapsBloquees?.length" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b flex items-center justify-between">
                        <p class="font-semibold text-sm">Goulots d'étranglement — DAP bloquées</p>
                        <Link :href="route('validations-dap.index')" class="text-xs text-primary hover:underline inline-flex items-center gap-1">
                            Ouvrir la file <ArrowRight class="h-3 w-3" />
                        </Link>
                    </div>
                    <table class="w-full text-sm">
                        <tbody class="divide-y divide-border">
                            <tr v-for="dap in dapsBloquees" :key="dap.id" class="hover:bg-muted/30 transition-colors">
                                <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ dap.reference }}</td>
                                <td class="px-4 py-3 font-medium truncate max-w-xs">{{ dap.objet ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">{{ dap.demandeur ?? '—' }}</td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">{{ dap.entreprise ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700">
                                        {{ dap.jours_retard }}j
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <Link :href="route('validations-dap.show', dap.id)" class="text-primary hover:underline text-xs">Voir</Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <!-- Vue DF déplacée vers Dashboard/DF.vue -->

            <!-- Stats admin/compta/validateur -->
            <div v-if="role !== 'employe'" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div v-if="stats.eb_en_attente !== undefined" class="rounded-xl border bg-card p-5 shadow-sm">
                    <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">EB en attente</p>
                    <p class="text-3xl font-bold mt-1">{{ stats.eb_en_attente }}</p>
                    <Link :href="route('compta.index')" class="text-xs text-primary hover:underline mt-2 inline-flex items-center gap-1">
                        Traiter <ArrowRight class="h-3 w-3" />
                    </Link>
                </div>
                <div v-if="stats.dap_en_cours !== undefined" class="rounded-xl border bg-card p-5 shadow-sm">
                    <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">DAP en cours</p>
                    <p class="text-3xl font-bold mt-1">{{ stats.dap_en_cours }}</p>
                </div>
                <div v-if="stats.dap_validees !== undefined" class="rounded-xl border bg-emerald-50 border-emerald-200 p-5 shadow-sm">
                    <p class="text-xs text-emerald-600 font-medium uppercase tracking-wider">Validées</p>
                    <p class="text-3xl font-bold text-emerald-700 mt-1">{{ stats.dap_validees }}</p>
                </div>
                <div v-if="stats.dap_payees !== undefined" class="rounded-xl border bg-blue-50 border-blue-200 p-5 shadow-sm">
                    <p class="text-xs text-blue-600 font-medium uppercase tracking-wider">Payées</p>
                    <p class="text-3xl font-bold text-blue-700 mt-1">{{ stats.dap_payees }}</p>
                </div>
                <div v-if="stats.en_attente !== undefined && stats.mes_validees !== undefined" class="rounded-xl border bg-card p-5 shadow-sm">
                    <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">À valider</p>
                    <p class="text-3xl font-bold mt-1">{{ stats.en_attente }}</p>
                    <p class="text-xs text-muted-foreground mt-1">{{ niveau?.nom }}</p>
                </div>
                <div v-if="stats.mes_validees !== undefined" class="rounded-xl border bg-emerald-50 border-emerald-200 p-5 shadow-sm">
                    <p class="text-xs text-emerald-600 font-medium uppercase tracking-wider">Mes approbations</p>
                    <p class="text-3xl font-bold text-emerald-700 mt-1">{{ stats.mes_validees }}</p>
                </div>
                <div v-if="stats.mes_rejetees !== undefined" class="rounded-xl border bg-red-50 border-red-200 p-5 shadow-sm">
                    <p class="text-xs text-red-500 font-medium uppercase tracking-wider">Mes rejets</p>
                    <p class="text-3xl font-bold text-red-600 mt-1">{{ stats.mes_rejetees }}</p>
                </div>
            </div>

            <!-- Stats employé -->
            <div v-else class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="rounded-xl border bg-card p-5 shadow-sm">
                    <p class="text-xs text-muted-foreground font-medium uppercase tracking-wider">Total</p>
                    <p class="text-3xl font-bold mt-1">{{ stats.total ?? 0 }}</p>
                </div>
                <div class="rounded-xl border bg-amber-50 border-amber-200 p-5 shadow-sm">
                    <p class="text-xs text-amber-600 font-medium uppercase tracking-wider">En attente</p>
                    <p class="text-3xl font-bold text-amber-700 mt-1">{{ stats.en_attente ?? 0 }}</p>
                </div>
                <div class="rounded-xl border bg-emerald-50 border-emerald-200 p-5 shadow-sm">
                    <p class="text-xs text-emerald-600 font-medium uppercase tracking-wider">Validées</p>
                    <p class="text-3xl font-bold text-emerald-700 mt-1">{{ stats.validees ?? 0 }}</p>
                </div>
                <div class="rounded-xl border bg-red-50 border-red-200 p-5 shadow-sm">
                    <p class="text-xs text-red-500 font-medium uppercase tracking-wider">Rejetées</p>
                    <p class="text-3xl font-bold text-red-600 mt-1">{{ stats.rejetees ?? 0 }}</p>
                </div>
            </div>

            <!-- DAP récentes -->
            <div v-if="recentDaps?.length" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b flex items-center justify-between">
                    <p class="font-semibold text-sm">DAP récentes</p>
                    <Link :href="route('validations-dap.index')" class="text-xs text-primary hover:underline inline-flex items-center gap-1">
                        Voir tout <ArrowRight class="h-3 w-3" />
                    </Link>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-border">
                        <tr v-for="dap in recentDaps" :key="dap.id" class="hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ dap.reference }}</td>
                            <td class="px-4 py-3 font-medium truncate max-w-xs">{{ dap.expression_besoin?.objet }}</td>
                            <td class="px-4 py-3 text-muted-foreground text-xs">{{ dap.expression_besoin?.entreprise?.nom }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="dapStatutConfig[dap.statut]?.class">
                                    {{ dapStatutConfig[dap.statut]?.label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <Link :href="route('validations-dap.show', dap.id)" class="text-primary hover:underline text-xs">Voir</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- EB récentes -->
            <div v-if="recentEb?.length" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b flex items-center justify-between">
                    <p class="font-semibold text-sm">Expressions de besoin récentes</p>
                    <Link :href="role === 'employe' ? route('expressions-besoin.index') : route('compta.index')"
                        class="text-xs text-primary hover:underline inline-flex items-center gap-1">
                        Voir tout <ArrowRight class="h-3 w-3" />
                    </Link>
                </div>
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-border">
                        <tr v-for="eb in recentEb" :key="eb.id" class="hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ eb.reference }}</td>
                            <td class="px-4 py-3 font-medium truncate max-w-xs">{{ eb.objet }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="ebStatutConfig[eb.statut]?.class">
                                    <component :is="ebStatutConfig[eb.statut]?.icon" class="h-3 w-3" />
                                    {{ ebStatutConfig[eb.statut]?.label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <Link :href="role === 'employe' ? route('expressions-besoin.show', eb.id) : route('compta.show', eb.id)"
                                    class="text-primary hover:underline text-xs">Voir</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- CTA vide employé -->
            <div v-if="role === 'employe' && (stats.total === 0)"
                class="rounded-xl border-2 border-dashed border-border p-10 text-center">
                <FileText class="mx-auto h-12 w-12 text-muted-foreground/40 mb-4" />
                <p class="font-semibold text-lg">Bienvenue !</p>
                <p class="text-sm text-muted-foreground mt-1">Soumettez votre première expression de besoin.</p>
                <Link :href="route('expressions-besoin.create')"
                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors">
                    <FileText class="h-4 w-4" />
                    Nouvelle expression
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
