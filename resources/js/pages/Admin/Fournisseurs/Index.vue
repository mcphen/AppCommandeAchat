<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Fournisseur, type PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, ArrowUpDown, BarChart2, Download, Eye, Pencil, Plus, Search, ShieldAlert, ShieldCheck, Trash2, Truck, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    fournisseurs: PaginatedData<Fournisseur>;
    filters: { search: string; sort: string; direction: 'asc' | 'desc' };
    stats: { total: number; approved: number; pending: number; inactive: number };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Fournisseurs', href: '/admin/fournisseurs' },
];

const deleting = ref<number | null>(null);
const search = ref(props.filters.search ?? '');
const sort = ref(props.filters.sort || 'name');
const direction = ref<'asc' | 'desc'>(props.filters.direction || 'asc');
let searchTimeout: ReturnType<typeof setTimeout> | undefined;

const currencyFmt = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 });
const formatAmount = (v: number | string | null | undefined) => currencyFmt.format(Number(v ?? 0));

const applySearch = () => {
    router.get(
        route('admin.fournisseurs.index'),
        { search: search.value || undefined, sort: sort.value, direction: direction.value },
        { preserveState: true, replace: true },
    );
};

const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(applySearch, 350);
};

const clearSearch = () => {
    search.value = '';
    applySearch();
};

const toggleSort = (column: string) => {
    if (sort.value === column) {
        direction.value = direction.value === 'asc' ? 'desc' : 'asc';
    } else {
        sort.value = column;
        direction.value = column === 'total_achats_valide' ? 'desc' : 'asc';
    }
    applySearch();
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (search.value) params.set('search', search.value);
    params.set('sort', sort.value);
    params.set('direction', direction.value);
    return route('admin.fournisseurs.export') + '?' + params.toString();
});

const destroy = (id: number) => {
    if (!confirm('Supprimer ce fournisseur ?')) return;
    deleting.value = id;
    router.delete(route('admin.fournisseurs.destroy', id), {
        onFinish: () => (deleting.value = null),
    });
};
</script>

<template>
    <Head title="Fournisseurs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Fournisseurs</h1>
                    <p class="text-sm text-muted-foreground">Gerez l'homologation, les contacts et l'historique d'achat de vos partenaires.</p>
                </div>

                <div class="flex shrink-0 items-center gap-2 self-start">
                    <Link
                        :href="route('admin.fournisseurs.dashboard')"
                        class="inline-flex items-center gap-2 rounded-xl border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary transition-colors hover:bg-primary/10"
                    >
                        <BarChart2 class="h-4 w-4" />
                        Analyse budgetaire
                    </Link>
                </div>
            </div>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ stats.total }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">fournisseur{{ stats.total > 1 ? 's' : '' }}</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Homologues</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-300">{{ stats.approved }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">prets pour les commandes</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Non homologues</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-300">{{ stats.pending }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">fournisseurs actifs a valider</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Inactifs</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ stats.inactive }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">hors utilisation courante</p>
                </div>
            </section>

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="relative w-full sm:max-w-sm">
                    <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Rechercher par nom, code, email, ville..."
                        class="w-full rounded-xl border bg-card py-2.5 pl-9 pr-9 text-sm text-foreground shadow-sm outline-none transition-colors focus:border-primary"
                        @input="onSearchInput"
                        @keyup.enter="applySearch"
                    />
                    <button
                        v-if="search"
                        type="button"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                        @click="clearSearch"
                    >
                        <X class="h-4 w-4" />
                    </button>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <a
                        :href="exportUrl"
                        class="inline-flex items-center gap-2 rounded-xl border bg-card px-4 py-2.5 text-sm font-semibold text-foreground shadow-sm transition-colors hover:bg-muted"
                    >
                        <Download class="h-4 w-4" />
                        <span class="hidden sm:inline">Exporter</span>
                    </a>
                    <Link
                        :href="route('admin.fournisseurs.create')"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        Nouveau fournisseur
                    </Link>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <template v-if="fournisseurs.data.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/30">
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">
                                        <button type="button" class="inline-flex items-center gap-1 hover:text-foreground" @click="toggleSort('name')">
                                            Fournisseur
                                            <ArrowUpDown v-if="sort !== 'name'" class="h-3 w-3 opacity-40" />
                                            <ArrowUp v-else-if="direction === 'asc'" class="h-3 w-3" />
                                            <ArrowDown v-else class="h-3 w-3" />
                                        </button>
                                    </th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell sm:px-6">Contact</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:table-cell sm:px-6">
                                        <button type="button" class="inline-flex items-center gap-1 hover:text-foreground" @click="toggleSort('city')">
                                            Ville
                                            <ArrowUpDown v-if="sort !== 'city'" class="h-3 w-3 opacity-40" />
                                            <ArrowUp v-else-if="direction === 'asc'" class="h-3 w-3" />
                                            <ArrowDown v-else class="h-3 w-3" />
                                        </button>
                                    </th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">
                                        <button type="button" class="inline-flex items-center gap-1 hover:text-foreground" @click="toggleSort('is_approved')">
                                            Statut
                                            <ArrowUpDown v-if="sort !== 'is_approved'" class="h-3 w-3 opacity-40" />
                                            <ArrowUp v-else-if="direction === 'asc'" class="h-3 w-3" />
                                            <ArrowDown v-else class="h-3 w-3" />
                                        </button>
                                    </th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">
                                        <button
                                            type="button"
                                            class="ml-auto inline-flex items-center gap-1 hover:text-foreground"
                                            @click="toggleSort('total_achats_valide')"
                                        >
                                            Montant valide
                                            <ArrowUpDown v-if="sort !== 'total_achats_valide'" class="h-3 w-3 opacity-40" />
                                            <ArrowUp v-else-if="direction === 'asc'" class="h-3 w-3" />
                                            <ArrowDown v-else class="h-3 w-3" />
                                        </button>
                                    </th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="f in fournisseurs.data" :key="f.id" class="transition-colors hover:bg-muted/20">
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                                :class="
                                                    f.is_approved
                                                        ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-300'
                                                        : 'bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-300'
                                                "
                                            >
                                                <Truck class="h-4 w-4" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="max-w-[140px] truncate font-medium text-foreground sm:max-w-none">{{ f.name }}</p>
                                                <p class="font-mono text-xs text-muted-foreground">{{ f.code }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden px-4 py-4 md:table-cell sm:px-6">
                                        <p v-if="f.email" class="max-w-[180px] truncate text-sm text-foreground">{{ f.email }}</p>
                                        <p v-if="f.phone" class="text-xs text-muted-foreground">{{ f.phone }}</p>
                                        <span v-if="!f.email && !f.phone" class="text-xs text-muted-foreground">-</span>
                                    </td>
                                    <td class="hidden px-4 py-4 text-sm text-foreground lg:table-cell sm:px-6">
                                        {{ f.city ?? '-' }}
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex flex-col items-start gap-1">
                                            <span
                                                v-if="f.is_approved"
                                                class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300"
                                            >
                                                <ShieldCheck class="h-3 w-3" />
                                                Homologue
                                            </span>
                                            <span
                                                v-else
                                                class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300"
                                            >
                                                <ShieldAlert class="h-3 w-3" />
                                                Non homologue
                                            </span>
                                            <span
                                                class="rounded-full px-2.5 py-1 text-xs font-medium"
                                                :class="
                                                    f.is_active
                                                        ? 'bg-blue-50 text-blue-600 dark:bg-blue-950/30 dark:text-blue-300'
                                                        : 'bg-muted text-muted-foreground'
                                                "
                                            >
                                                {{ f.is_active ? 'Actif' : 'Inactif' }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-right sm:px-6">
                                        <p class="font-semibold text-foreground">{{ formatAmount(f.total_achats_valide) }}</p>
                                        <p class="text-xs text-muted-foreground">
                                            {{ f.order_lines_count ?? 0 }} ligne{{ (f.order_lines_count ?? 0) > 1 ? 's' : '' }}
                                        </p>
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center justify-end gap-1">
                                            <Link
                                                :href="route('admin.fournisseurs.show', f.id)"
                                                class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-950/30"
                                                title="Historique des achats"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Link>
                                            <Link
                                                :href="route('admin.fournisseurs.edit', f.id)"
                                                class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-amber-50 hover:text-amber-600 dark:hover:bg-amber-950/30"
                                            >
                                                <Pencil class="h-4 w-4" />
                                            </Link>
                                            <button
                                                @click="destroy(f.id)"
                                                :disabled="deleting === f.id"
                                                class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-600 disabled:opacity-50 dark:hover:bg-red-950/30"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-if="fournisseurs.last_page > 1"
                        class="flex flex-col items-center gap-3 border-t px-4 py-4 sm:flex-row sm:justify-between sm:px-6"
                    >
                        <p class="text-sm text-muted-foreground">{{ fournisseurs.from }}-{{ fournisseurs.to }} sur {{ fournisseurs.total }}</p>
                        <div class="flex flex-wrap items-center justify-center gap-1">
                            <Link
                                v-for="link in fournisseurs.links"
                                :key="link.label"
                                :href="link.url ?? '#'"
                                preserve-scroll
                                :class="[
                                    'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                                    link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted',
                                    !link.url ? 'pointer-events-none opacity-40' : '',
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </template>

                <EmptyState
                    v-else
                    :icon="Truck"
                    icon-bg="bg-violet-50 dark:bg-violet-950/30"
                    icon-color="text-violet-500 dark:text-violet-300"
                    :title="search ? 'Aucun résultat' : 'Aucun fournisseur'"
                    :description="
                        search
                            ? 'Aucun fournisseur ne correspond a votre recherche.'
                            : 'Ajoutez et approuvez vos fournisseurs pour les associer aux bons de commande et generer les ecritures comptables.'
                    "
                    :action-href="route('admin.fournisseurs.create')"
                    action-label="Ajouter un fournisseur"
                    :bordered="false"
                />
            </div>
        </div>
    </AppLayout>
</template>
