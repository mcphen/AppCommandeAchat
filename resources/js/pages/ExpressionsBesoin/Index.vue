<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type ExpressionBesoin, type PaginatedData, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Eye, Plus, FileText, Clock, CheckCircle2, XCircle, Search, FilterX } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Expressions de besoin', href: '/expressions-besoin' },
];

const props = defineProps<{
    expressions: PaginatedData<ExpressionBesoin>;
    filters?: {
        search?: string;
        statut?: string;
        user_id?: string;
    };
    demandeurs?: Array<{ id: number; name: string }>;
}>();

const page = usePage();
const isAdmin = computed(() => (page.props as unknown as SharedData).auth.user?.role?.slug === 'admin');
const isValidateur = computed(() => (page.props as unknown as SharedData).auth.user?.role?.slug === 'validateur');
const canCreate = computed(() => !isValidateur.value);
const canSeeDemandeur = computed(() => isAdmin.value || isValidateur.value);

const localFilters = ref({
    search: props.filters?.search ?? '',
    statut: props.filters?.statut ?? '',
    user_id: props.filters?.user_id ?? '',
});

const statutConfig: Record<string, { label: string; class: string; icon: any }> = {
    en_attente: { label: 'En attente', class: 'bg-amber-100 text-amber-700', icon: Clock },
    validee:    { label: 'Validée',    class: 'bg-emerald-100 text-emerald-700', icon: CheckCircle2 },
    rejetee:    { label: 'Rejetée',   class: 'bg-red-100 text-red-700', icon: XCircle },
};

function formatMontant(v: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
}

function applyFilters() {
    router.get(route('expressions-besoin.index'), {
        search: localFilters.value.search || undefined,
        statut: localFilters.value.statut || undefined,
        user_id: canSeeDemandeur.value ? (localFilters.value.user_id || undefined) : undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    localFilters.value = { search: '', statut: '', user_id: '' };
    applyFilters();
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Expressions de besoin" />

        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <PageHeader
                title="Expressions de besoin"
                :subtitle="`${expressions.total} expression(s) au total`"
                eyebrow="Pilotage des demandes"
            >
                <template #actions>
                    <Link v-if="canCreate" :href="route('expressions-besoin.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 transition-colors">
                        <Plus class="h-4 w-4" />
                        Nouvelle expression
                    </Link>
                </template>
            </PageHeader>

            <div class="rounded-xl border bg-card p-4 shadow-sm">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-4">
                    <div class="md:col-span-2">
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Recherche</label>
                        <div class="relative">
                            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <input
                                v-model="localFilters.search"
                                type="text"
                                class="w-full rounded-lg border bg-background py-2 pl-9 pr-3 text-sm outline-none focus:ring-2 focus:ring-primary/40"
                                placeholder="Référence, objet, bénéficiaire"
                                @keyup.enter="applyFilters"
                            />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Statut</label>
                        <select
                            v-model="localFilters.statut"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40"
                        >
                            <option value="">Tous</option>
                            <option value="en_attente">En attente</option>
                            <option value="validee">Validée</option>
                            <option value="rejetee">Rejetée</option>
                        </select>
                    </div>

                    <div v-if="canSeeDemandeur">
                        <label class="mb-1 block text-xs font-medium text-muted-foreground">Demandeur</label>
                        <select
                            v-model="localFilters.user_id"
                            class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40"
                        >
                            <option value="">Tous</option>
                            <option v-for="u in (demandeurs ?? [])" :key="u.id" :value="String(u.id)">{{ u.name }}</option>
                        </select>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-2">
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
                        <FilterX class="h-4 w-4" />
                        Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Référence</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Objet</th>
                            <th v-if="canSeeDemandeur" class="px-4 py-3 text-left font-medium text-muted-foreground">Demandeur</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Montant</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Statut</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-if="expressions.data.length === 0">
                            <td :colspan="canSeeDemandeur ? 7 : 6" class="px-4 py-12 text-center text-muted-foreground">
                                <FileText class="mx-auto h-10 w-10 mb-3 opacity-30" />
                                <p>Aucune expression de besoin</p>
                                <Link :href="route('expressions-besoin.create')" class="mt-2 inline-block text-primary hover:underline text-sm">
                                    Créer votre première expression
                                </Link>
                            </td>
                        </tr>
                        <tr v-for="eb in expressions.data" :key="eb.id" class="hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ eb.reference }}</td>
                            <td class="px-4 py-3 font-medium">{{ eb.objet }}</td>
                            <td v-if="canSeeDemandeur" class="px-4 py-3 text-muted-foreground">{{ eb.user?.name }}</td>
                            <td class="px-4 py-3 font-semibold">{{ formatMontant(eb.montant) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="statutConfig[eb.statut]?.class">
                                    <component :is="statutConfig[eb.statut]?.icon" class="h-3 w-3" />
                                    {{ statutConfig[eb.statut]?.label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground text-xs">
                                {{ new Date(eb.created_at).toLocaleDateString('fr-FR') }}
                            </td>
                            <td class="px-4 py-3">
                                <Link :href="route('expressions-besoin.show', { expressionsBesoin: eb.id })"
                                    class="inline-flex items-center gap-1 rounded-md border px-2.5 py-1 text-xs font-medium hover:bg-muted transition-colors">
                                    <Eye class="h-3.5 w-3.5" />
                                    Voir
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div v-if="expressions.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in expressions.links" :key="link.label">
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
