<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type DemandeAutorisationPaiement, type NiveauValidation, type PaginatedData } from '@/types';
import { Head, Link, usePage, router } from '@inertiajs/vue3';
import { Eye, CheckSquare, Search, FilterX, Download } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import { type SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Validations DAP', href: '/validations-dap' },
];

const props = defineProps<{
    daps: PaginatedData<DemandeAutorisationPaiement>;
    niveau: NiveauValidation | null;
    mode?: 'pending' | 'all';
    filters?: { search?: string; statut?: string; user_id?: string; entreprise_id?: string };
    demandeurs?: Array<{ id: number; name: string }>;
    entreprises?: Array<{ id: number; nom: string }>;
}>();

const page = usePage<SharedData>();
const isDf = computed(() => page.props.auth.user?.role?.slug === 'validateur');

const localFilters = ref({
    search:        props.filters?.search        ?? '',
    statut:        props.filters?.statut        ?? '',
    user_id:       props.filters?.user_id       ?? '',
    entreprise_id: props.filters?.entreprise_id ?? '',
});

function applyFilters() {
    router.get(route('validations-dap.toutes'), {
        search:        localFilters.value.search        || undefined,
        statut:        localFilters.value.statut        || undefined,
        user_id:       localFilters.value.user_id       || undefined,
        entreprise_id: localFilters.value.entreprise_id || undefined,
    }, { preserveState: true, preserveScroll: true, replace: true });
}

function resetFilters() {
    localFilters.value = { search: '', statut: '', user_id: '', entreprise_id: '' };
    applyFilters();
}

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (localFilters.value.search)        params.set('search',        localFilters.value.search);
    if (localFilters.value.statut)        params.set('statut',        localFilters.value.statut);
    if (localFilters.value.user_id)       params.set('user_id',       localFilters.value.user_id);
    if (localFilters.value.entreprise_id) params.set('entreprise_id', localFilters.value.entreprise_id);
    const qs = params.toString();
    return route('validations-dap.export') + (qs ? `?${qs}` : '');
});

const statutConfig: Record<string, { label: string; class: string }> = {
    en_cours: { label: 'En cours', class: 'bg-amber-100 text-amber-700' },
    validee:  { label: 'Validée',  class: 'bg-emerald-100 text-emerald-700' },
    rejetee:  { label: 'Rejetée', class: 'bg-red-100 text-red-700' },
    payee:    { label: 'Payée',    class: 'bg-blue-100 text-blue-700' },
};

function formatMontant(v: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Validations DAP" />

        <div class="flex flex-col gap-6 p-6">
            <PageHeader
                title="Demandes d'autorisation de paiement"
                :subtitle="mode === 'all' ? `${daps.total} DAP (toutes)` : `${daps.total} DAP en attente de votre validation${niveau ? ` (${niveau.nom})` : ''}`"
                eyebrow="Circuit de validation"
            />

            <!-- Filtres (mode all uniquement) -->
            <div v-if="mode === 'all'" class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
                    <div class="lg:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Recherche</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <input v-model="localFilters.search" type="text"
                                class="w-full rounded-lg border bg-background py-2 pl-9 pr-3 text-sm outline-none focus:ring-2 focus:ring-primary/40"
                                placeholder="Référence, objet, bénéficiaire"
                                @keyup.enter="applyFilters" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Statut</label>
                        <select v-model="localFilters.statut"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="">Tous</option>
                            <option value="en_cours">En cours</option>
                            <option value="validee">Validée</option>
                            <option value="rejetee">Rejetée</option>
                            <option value="payee">Payée</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Demandeur</label>
                        <select v-model="localFilters.user_id"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="">Tous</option>
                            <option v-for="u in (demandeurs ?? [])" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Entreprise</label>
                        <select v-model="localFilters.entreprise_id"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="">Toutes</option>
                            <option v-for="e in (entreprises ?? [])" :key="e.id" :value="String(e.id)">{{ e.nom }}</option>
                        </select>
                    </div>
                </div>
                <div class="mt-3 flex items-center gap-2">
                    <button type="button"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors"
                        @click="applyFilters">Filtrer</button>
                    <button type="button"
                        class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors"
                        @click="resetFilters">
                        <FilterX class="h-4 w-4" />
                        Réinitialiser
                    </button>
                    <a :href="exportUrl"
                        class="ml-auto inline-flex items-center gap-2 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700 hover:bg-emerald-100 transition-colors">
                        <Download class="h-4 w-4" />
                        Exporter Excel
                    </a>
                </div>
            </div>

            <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Référence DAP</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Objet</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Demandeur</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Entreprise</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Montant</th>
                            <th v-if="mode === 'all'" class="px-4 py-3 text-left font-medium text-muted-foreground">Statut</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Validations</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-if="daps.data.length === 0">
                            <td :colspan="mode === 'all' ? 8 : 7" class="px-4 py-16 text-center text-muted-foreground">
                                <CheckSquare class="mx-auto h-10 w-10 mb-3 opacity-30" />
                                <p>{{ mode === 'all' ? 'Aucune DAP enregistrée' : 'Aucune DAP en attente de votre validation' }}</p>
                            </td>
                        </tr>
                        <tr v-for="dap in daps.data" :key="dap.id" class="hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ dap.reference }}</td>
                            <td class="px-4 py-3 font-medium max-w-xs truncate">{{ dap.expression_besoin?.objet }}</td>
                            <td class="px-4 py-3">{{ dap.expression_besoin?.user?.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ dap.expression_besoin?.entreprise?.nom }}</td>
                            <td class="px-4 py-3 font-semibold">{{ formatMontant(dap.expression_besoin?.montant ?? 0) }}</td>
                            <td v-if="mode === 'all'" class="px-4 py-3">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="statutConfig[dap.statut]?.class">
                                    {{ statutConfig[dap.statut]?.label }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-1 flex-wrap">
                                    <span v-for="v in dap.validations" :key="v.id"
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="v.statut === 'approuve' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                                        {{ v.niveau_validation?.nom }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <Link :href="route('validations-dap.show', dap.id)"
                                    class="inline-flex items-center gap-1 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 transition-colors">
                                    <Eye class="h-3.5 w-3.5" />
                                    Valider
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="daps.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in daps.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-sm border transition-colors"
                        :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'hover:bg-muted border-border'"
                        v-html="link.label" />
                    <span v-else class="px-3 py-1.5 text-sm text-muted-foreground" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
