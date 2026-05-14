<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Agent, type Boutique, type BreadcrumbItem, type PaginatedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowRight, Banknote, Building2, Download, HandCoins, RotateCcw, Search, SlidersHorizontal, TrendingUp, UserCircle, Users, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Caisse Épargne', href: '#' },
];

const props = defineProps<{
    agents: PaginatedData<Agent>;
    stats: { total_agents: number; total_epargne: number; prets_en_cours: number; encours_prets: number; transactions_jour: number };
    boutiques: Boutique[];
    filters: { boutique_id: string; search: string };
}>();

const page = usePage();
const isAdmin = computed(() => (page.props as any).auth?.user?.role?.slug === 'admin');

const localFilters = ref({ boutique_id: props.filters.boutique_id, search: props.filters.search });

let t: ReturnType<typeof setTimeout>;
watch(() => localFilters.value.search, () => { clearTimeout(t); t = setTimeout(apply, 350); });
watch(() => localFilters.value.boutique_id, apply);

function apply() { router.get(route('caisse.index'), localFilters.value, { preserveState: true, replace: true }); }
function clear() { localFilters.value = { boutique_id: '', search: '' }; }
const hasFilters = computed(() => localFilters.value.boutique_id || localFilters.value.search);

function exportUrl() {
    const params = new URLSearchParams();
    Object.entries(localFilters.value).forEach(([key, value]) => {
        if (value) params.set(key, String(value));
    });

    const query = params.toString();
    return query ? `${route('caisse.export')}?${query}` : route('caisse.export');
}

const formatAmount = (v: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(v);
</script>

<template>
    <Head title="Caisse Épargne" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Caisse Épargne</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Gestion des comptes épargne des agents</p>
                </div>
                <div class="flex items-center gap-2">
                    <a
                        :href="exportUrl()"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                    >
                        <Download class="h-4 w-4" />
                        Export Excel
                    </a>
                    <Link :href="route('caisse.prets.index')"
                        class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90">
                        <HandCoins class="h-4 w-4" /> Voir les prêts
                    </Link>
                </div>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-100">
                            <Users class="h-5 w-5 text-teal-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-foreground">{{ stats.total_agents }}</p>
                            <p class="text-xs text-muted-foreground">Agents</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100">
                            <Banknote class="h-5 w-5 text-emerald-600" />
                        </div>
                        <div>
                            <p class="text-lg font-bold text-foreground leading-tight">{{ formatAmount(stats.total_epargne) }}</p>
                            <p class="text-xs text-muted-foreground">Total épargne</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100">
                            <HandCoins class="h-5 w-5 text-orange-600" />
                        </div>
                        <div>
                            <p class="text-lg font-bold text-foreground leading-tight">{{ formatAmount(stats.encours_prets) }}</p>
                            <p class="text-xs text-muted-foreground">Prêts en cours ({{ stats.prets_en_cours }})</p>
                        </div>
                    </div>
                </div>
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100">
                            <TrendingUp class="h-5 w-5 text-blue-600" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-foreground">{{ stats.transactions_jour }}</p>
                            <p class="text-xs text-muted-foreground">Transactions auj.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="flex flex-wrap items-center gap-3">
                <div class="relative flex-1 min-w-48">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input v-model="localFilters.search" type="text" placeholder="Nom, prénom, matricule…"
                        class="w-full rounded-xl border border-input bg-background py-2 pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                </div>
                <div class="flex items-center gap-2">
                    <SlidersHorizontal class="h-4 w-4 text-muted-foreground" />
                    <select v-if="isAdmin" v-model="localFilters.boutique_id"
                        class="rounded-xl border border-input bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <option value="">Toutes les boutiques</option>
                        <option v-for="b in boutiques" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <button v-if="hasFilters" @click="clear"
                        class="flex items-center gap-1.5 rounded-xl border px-3 py-2 text-sm text-muted-foreground hover:bg-muted">
                        <X class="h-3.5 w-3.5" /> Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Liste agents -->
            <div v-if="agents.data.length" class="flex flex-col gap-3">
                <div v-for="agent in agents.data" :key="agent.id"
                    class="flex items-center gap-4 rounded-2xl border bg-card p-5 shadow-sm transition-shadow hover:shadow-md">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-teal-100">
                        <UserCircle class="h-6 w-6 text-teal-600" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-bold text-foreground">{{ agent.prenom }} {{ agent.nom }}</span>
                            <span class="font-mono text-xs font-semibold text-blue-700">{{ agent.matricule }}</span>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                            <span v-if="agent.boutique" class="flex items-center gap-1">
                                <Building2 class="h-3 w-3" /> {{ agent.boutique.name }}
                            </span>
                            <span v-if="agent.telephone">{{ agent.telephone }}</span>
                        </div>
                    </div>
                    <div class="hidden shrink-0 text-right sm:block">
                        <p class="font-bold text-foreground">{{ agent.compte ? new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(agent.compte.solde_epargne)) : '—' }}</p>
                        <p class="text-xs text-muted-foreground">Épargne</p>
                        <p v-if="agent.compte && Number(agent.compte.solde_bloque) > 0" class="text-xs text-orange-600">
                            Bloqué : {{ new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(agent.compte.solde_bloque)) }}
                        </p>
                    </div>
                    <Link :href="route('caisse.agents.show', { agent: agent.id })"
                        class="ml-2 flex shrink-0 items-center gap-1.5 rounded-xl bg-teal-50 px-3 py-2 text-xs font-semibold text-teal-700 transition-colors hover:bg-teal-100">
                        Gérer <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
            </div>

            <EmptyState v-else-if="hasFilters" :icon="SlidersHorizontal" icon-bg="bg-slate-100" icon-color="text-slate-400"
                title="Aucun résultat" description="Modifiez les filtres.">
                <button @click="clear" class="inline-flex items-center gap-2 rounded-xl border px-5 py-2.5 text-sm font-semibold hover:bg-muted">
                    <RotateCcw class="h-4 w-4" /> Effacer
                </button>
            </EmptyState>
            <EmptyState v-else :icon="UserCircle" icon-bg="bg-teal-50" icon-color="text-teal-500"
                title="Aucun agent" description="Créez des agents depuis l'administration." />

            <div v-if="agents.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in agents.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="rounded-lg px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted text-muted-foreground'"
                        v-html="link.label" />
                    <span v-else class="rounded-lg px-3 py-1.5 text-sm text-muted-foreground opacity-40" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
