<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type PaginatedData, type Pret } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ArrowRight, Building2, HandCoins, RotateCcw, SlidersHorizontal, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Caisse', href: route('caisse.index') },
    { title: 'Prêts', href: '#' },
];

const props = defineProps<{
    prets: PaginatedData<Pret>;
    boutiques: Boutique[];
    filters: { statut: string; boutique_id: string };
}>();

const page = usePage();
const isAdmin = computed(() => (page.props as any).auth?.user?.role?.slug === 'admin');

const localFilters = ref({ statut: props.filters.statut, boutique_id: props.filters.boutique_id });
watch([() => localFilters.value.statut, () => localFilters.value.boutique_id], apply);

function apply() { router.get(route('caisse.prets.index'), localFilters.value, { preserveState: true, replace: true }); }
function clear() { localFilters.value = { statut: '', boutique_id: '' }; }
const hasFilters = computed(() => localFilters.value.statut || localFilters.value.boutique_id);

const statutConfig: Record<string, { bg: string; text: string; dot: string }> = {
    draft:    { bg: 'bg-gray-50',   text: 'text-gray-600',   dot: 'bg-gray-400' },
    pending:  { bg: 'bg-yellow-50', text: 'text-yellow-700', dot: 'bg-yellow-500' },
    approved: { bg: 'bg-blue-50',   text: 'text-blue-700',   dot: 'bg-blue-500' },
    rejected: { bg: 'bg-red-50',    text: 'text-red-700',    dot: 'bg-red-500' },
    decaisse: { bg: 'bg-orange-50', text: 'text-orange-700', dot: 'bg-orange-500' },
    solde:    { bg: 'bg-emerald-50',text: 'text-emerald-700',dot: 'bg-emerald-500' },
};

const statutLabels: Record<string, string> = {
    draft: 'Brouillon', pending: 'En validation', approved: 'Approuvé',
    rejected: 'Rejeté', decaisse: 'Décaissé', solde: 'Soldé',
};

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));
</script>

<template>
    <Head title="Prêts" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Prêts</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Suivi des demandes et remboursements</p>
                </div>
                <Link :href="route('caisse.prets.create')"
                    class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90">
                    <HandCoins class="h-4 w-4" /> Nouveau prêt
                </Link>
            </div>

            <!-- Filtres -->
            <div class="flex flex-wrap items-center gap-3">
                <SlidersHorizontal class="h-4 w-4 text-muted-foreground" />
                <select v-model="localFilters.statut"
                    class="rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                    <option value="">Tous les statuts</option>
                    <option v-for="(label, key) in statutLabels" :key="key" :value="key">{{ label }}</option>
                </select>
                <select v-if="isAdmin" v-model="localFilters.boutique_id"
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
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl"
                        :class="statutConfig[pret.statut]?.bg">
                        <HandCoins class="h-5 w-5" :class="statutConfig[pret.statut]?.text" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-bold text-foreground">
                                {{ pret.agent ? `${pret.agent.prenom} ${pret.agent.nom}` : '—' }}
                            </span>
                            <span class="font-mono text-xs font-semibold text-blue-700" v-if="pret.agent?.matricule">
                                {{ pret.agent.matricule }}
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                :class="[statutConfig[pret.statut]?.bg, statutConfig[pret.statut]?.text]">
                                <span class="h-1.5 w-1.5 rounded-full" :class="statutConfig[pret.statut]?.dot" />
                                {{ statutLabels[pret.statut] }}
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
                        <p v-if="pret.montant_accorde && pret.montant_accorde !== pret.montant_demande" class="text-xs text-blue-700">
                            Accordé : {{ formatAmount(pret.montant_accorde) }}
                        </p>
                    </div>
                    <Link :href="route('caisse.prets.show', pret.id)"
                        class="ml-2 flex shrink-0 items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold hover:bg-muted">
                        Voir <ArrowRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
            </div>

            <EmptyState v-else-if="hasFilters" :icon="SlidersHorizontal" icon-bg="bg-slate-100" icon-color="text-slate-400"
                title="Aucun résultat" description="Modifiez les filtres.">
                <button @click="clear" class="inline-flex items-center gap-2 rounded-xl border px-5 py-2.5 text-sm font-semibold hover:bg-muted">
                    <RotateCcw class="h-4 w-4" /> Effacer
                </button>
            </EmptyState>
            <EmptyState v-else :icon="HandCoins" icon-bg="bg-orange-50" icon-color="text-orange-500"
                title="Aucun prêt" description="Créez une demande de prêt pour un agent."
                :action-href="route('caisse.prets.create')" action-label="Nouveau prêt" />

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
