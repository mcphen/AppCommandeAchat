<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type PaginatedData, type Pret } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Building2, CheckSquare, HandCoins, RotateCcw, SlidersHorizontal, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Validations Prêts', href: '#' },
];

const props = defineProps<{
    prets: PaginatedData<Pret>;
    boutiques: Boutique[];
    levelsCount: number;
    filters: { boutique_id: string };
}>();

const localFilters = ref({ boutique_id: props.filters.boutique_id });
watch(() => localFilters.value.boutique_id, apply);

function apply() { router.get(route('pret-validations.index'), localFilters.value, { preserveState: true, replace: true }); }
function clear() { localFilters.value = { boutique_id: '' }; }
const hasFilters = computed(() => localFilters.value.boutique_id);

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

function levelProgress(pret: Pret): string {
    if (!pret.current_level_order || !props.levelsCount) return '';
    return `Niveau ${pret.current_level_order}/${props.levelsCount}`;
}
</script>

<template>
    <Head title="Validations Prêts" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Validations Prêts</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Demandes en attente de votre validation</p>
                </div>
                <span v-if="prets.total" class="rounded-full bg-primary px-3 py-1 text-sm font-bold text-primary-foreground">
                    {{ prets.total }}
                </span>
            </div>

            <!-- Filtres -->
            <div class="flex flex-wrap items-center gap-3">
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

            <!-- Liste -->
            <div v-if="prets.data.length" class="flex flex-col gap-3">
                <div v-for="pret in prets.data" :key="pret.id"
                    class="flex items-center gap-4 rounded-2xl border bg-card p-5 shadow-sm transition-shadow hover:shadow-md">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-yellow-50">
                        <HandCoins class="h-5 w-5 text-yellow-600" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-bold text-foreground">
                                {{ pret.agent ? `${pret.agent.prenom} ${pret.agent.nom}` : '—' }}
                            </span>
                            <span class="font-mono text-xs font-semibold text-blue-700" v-if="pret.agent?.matricule">
                                {{ pret.agent.matricule }}
                            </span>
                            <span v-if="levelProgress(pret)"
                                class="inline-flex items-center rounded-full bg-yellow-50 px-2.5 py-0.5 text-xs font-semibold text-yellow-700">
                                {{ levelProgress(pret) }}
                            </span>
                        </div>
                        <div class="mt-1 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                            <span v-if="pret.agent?.boutique" class="flex items-center gap-1">
                                <Building2 class="h-3 w-3" /> {{ pret.agent.boutique.name }}
                            </span>
                            <span v-if="pret.motif" class="italic truncate max-w-[200px]">{{ pret.motif }}</span>
                        </div>
                    </div>
                    <div class="hidden shrink-0 text-right sm:block">
                        <p class="font-bold text-foreground">{{ formatAmount(pret.montant_demande) }}</p>
                        <p v-if="pret.submitted_at" class="text-xs text-muted-foreground">
                            {{ new Date(pret.submitted_at).toLocaleDateString('fr-FR') }}
                        </p>
                    </div>
                    <Link :href="route('pret-validations.show', pret.id)"
                        class="ml-2 flex shrink-0 items-center gap-1.5 rounded-xl bg-primary px-3 py-2 text-xs font-semibold text-primary-foreground hover:bg-primary/90">
                        Valider <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
            </div>

            <EmptyState v-else-if="hasFilters" :icon="SlidersHorizontal" icon-bg="bg-slate-100" icon-color="text-slate-400"
                title="Aucun résultat" description="Modifiez les filtres.">
                <button @click="clear" class="inline-flex items-center gap-2 rounded-xl border px-5 py-2.5 text-sm font-semibold hover:bg-muted">
                    <RotateCcw class="h-4 w-4" /> Effacer
                </button>
            </EmptyState>
            <EmptyState v-else :icon="CheckSquare" icon-bg="bg-emerald-50" icon-color="text-emerald-500"
                title="Aucune demande en attente" description="Toutes les demandes de prêt ont été traitées." />

            <div v-if="prets.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in prets.links" :key="link.label">
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
