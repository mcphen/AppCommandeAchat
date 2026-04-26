<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Agent, type Boutique, type BreadcrumbItem, type PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, Plus, RotateCcw, Search, SlidersHorizontal, UserCircle, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Agents', href: '#' },
];

const props = defineProps<{
    agents: PaginatedData<Agent>;
    boutiques: Boutique[];
    filters: { boutique_id: string; search: string };
}>();

const localFilters = ref({ boutique_id: props.filters.boutique_id, search: props.filters.search });

let t: ReturnType<typeof setTimeout>;
watch(() => localFilters.value.search, () => { clearTimeout(t); t = setTimeout(apply, 350); });
watch(() => localFilters.value.boutique_id, apply);

function apply() { router.get(route('admin.agents.index'), localFilters.value, { preserveState: true, replace: true }); }
function clear() { localFilters.value = { boutique_id: '', search: '' }; }
const hasFilters = computed(() => localFilters.value.boutique_id || localFilters.value.search);

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));
</script>

<template>
    <Head title="Agents" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Agents</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Membres de la caisse d'épargne interne</p>
                </div>
                <Link
                    :href="route('admin.agents.create')"
                    class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" /> Nouvel agent
                </Link>
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
                    <select v-model="localFilters.boutique_id"
                        class="rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                        <option value="">Toutes les boutiques</option>
                        <option v-for="b in boutiques" :key="b.id" :value="b.id">{{ b.name }}</option>
                    </select>
                    <button v-if="hasFilters" @click="clear"
                        class="flex items-center gap-1.5 rounded-xl border px-3 py-2 text-sm text-muted-foreground hover:bg-muted">
                        <X class="h-3.5 w-3.5" /> Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Tableau -->
            <div v-if="agents.data.length" class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/30">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Agent</th>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground">Matricule</th>
                            <th class="px-4 py-3 text-left font-semibold text-muted-foreground hidden sm:table-cell">Boutique</th>
                            <th class="px-4 py-3 text-right font-semibold text-muted-foreground hidden md:table-cell">Épargne</th>
                            <th class="px-4 py-3 text-center font-semibold text-muted-foreground">Statut</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="agent in agents.data" :key="agent.id" class="hover:bg-muted/20 transition-colors">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100">
                                        <UserCircle class="h-5 w-5 text-teal-600" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-foreground">{{ agent.prenom }} {{ agent.nom }}</p>
                                        <p v-if="agent.telephone" class="text-xs text-muted-foreground">{{ agent.telephone }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-blue-700 font-bold">{{ agent.matricule }}</td>
                            <td class="px-4 py-3 hidden sm:table-cell">
                                <span v-if="agent.boutique" class="flex items-center gap-1 text-muted-foreground">
                                    <Building2 class="h-3.5 w-3.5" /> {{ agent.boutique.name }}
                                </span>
                                <span v-else class="text-muted-foreground">—</span>
                            </td>
                            <td class="px-4 py-3 text-right hidden md:table-cell font-semibold">
                                {{ agent.compte ? formatAmount(agent.compte.solde_epargne) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                    :class="agent.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'">
                                    {{ agent.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Link :href="route('admin.agents.edit', agent.id)"
                                    class="rounded-lg border px-3 py-1.5 text-xs font-semibold hover:bg-muted transition-colors">
                                    Modifier
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <EmptyState v-else-if="hasFilters" :icon="SlidersHorizontal" icon-bg="bg-slate-100" icon-color="text-slate-400"
                title="Aucun résultat" description="Modifiez les filtres pour voir d'autres agents.">
                <button @click="clear" class="inline-flex items-center gap-2 rounded-xl border px-5 py-2.5 text-sm font-semibold hover:bg-muted">
                    <RotateCcw class="h-4 w-4" /> Effacer
                </button>
            </EmptyState>
            <EmptyState v-else :icon="UserCircle" icon-bg="bg-teal-50" icon-color="text-teal-500"
                title="Aucun agent enregistré"
                description="Créez le premier agent pour démarrer la caisse d'épargne."
                :action-href="route('admin.agents.create')" action-label="Créer un agent" />

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
