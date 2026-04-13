<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Article, type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Package, Pencil, Plus, Search, ShieldCheck, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    articles: Article[];
    categories: { id: number; name: string; full_name: string; parent_id?: number | null }[];
    units: string[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Articles', href: '/admin/articles' },
];

// ─── Search ───────────────────────────────────────────────────────────────────
const search = ref('');
const filtered = computed(() =>
    props.articles.filter(
        (a) =>
            a.name.toLowerCase().includes(search.value.toLowerCase()) ||
            (a.reference ?? '').toLowerCase().includes(search.value.toLowerCase()) ||
            (a.category?.full_name ?? '').toLowerCase().includes(search.value.toLowerCase()),
    ),
);

// ─── Formatters ───────────────────────────────────────────────────────────────
const formatPrice = (v?: string | number | null) =>
    v == null ? '—' : new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

// ─── Catégories groupées ──────────────────────────────────────────────────────
const groupedCategories = computed(() => {
    const roots = props.categories.filter((c) => !c.parent_id);
    const result: { id: number; label: string }[] = [];
    for (const root of roots) {
        result.push({ id: root.id, label: root.name });
        const children = props.categories.filter((c) => c.parent_id === root.id);
        for (const child of children) {
            result.push({ id: child.id, label: '  › ' + child.name });
        }
    }
    return result;
});

const activeCount = computed(() => props.articles.filter((article) => article.is_active).length);
const pricedCount = computed(() => props.articles.filter((article) => article.unit_price != null && article.unit_price !== '').length);
const categorizedCount = computed(() => props.articles.filter((article) => article.category_id != null).length);

// ─── Modal ────────────────────────────────────────────────────────────────────
const showModal = ref(false);
const editingArticle = ref<Article | null>(null);

const form = useForm({
    category_id: '' as number | string,
    name: '',
    reference: '',
    description: '',
    unit: 'pièce',
    unit_price: '' as number | string,
    is_active: true,
});

const openCreate = () => {
    editingArticle.value = null;
    form.reset();
    form.unit = 'pièce';
    form.is_active = true;
    showModal.value = true;
};

const openEdit = (a: Article) => {
    editingArticle.value = a;
    form.category_id = a.category_id ?? '';
    form.name = a.name;
    form.reference = a.reference ?? '';
    form.description = a.description ?? '';
    form.unit = a.unit;
    form.unit_price = a.unit_price ?? '';
    form.is_active = a.is_active;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (editingArticle.value) {
        form.put(route('admin.articles.update', editingArticle.value.id), {
            onSuccess: closeModal,
        });
    } else {
        form.post(route('admin.articles.store'), {
            onSuccess: closeModal,
        });
    }
};

// ─── Suppression ─────────────────────────────────────────────────────────────
const deleting = ref<number | null>(null);
const destroy = (id: number) => {
    if (!confirm('Supprimer cet article ?')) return;
    deleting.value = id;
    router.delete(route('admin.articles.destroy', id), {
        onFinish: () => (deleting.value = null),
    });
};
</script>

<template>
    <Head title="Articles" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">Administration catalogue</p>
                    <h1 class="mt-1 text-2xl font-bold text-foreground">Articles</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Gere le catalogue des articles, leurs categories, references et prix indicatifs.</p>
                </div>

                <div
                    class="inline-flex items-center gap-2 self-start rounded-xl border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary"
                >
                    <ShieldCheck class="h-4 w-4" />
                    Administration
                </div>
            </div>

            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <Package class="h-8 w-8" />
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Referentiel articles</p>
                            <h2 class="mt-1 text-xl font-semibold text-foreground">Catalogue central des achats</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Structurez les articles proposes aux demandeurs avec des unites claires et un classement propre.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:w-[560px]">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Catalogue</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ articles.length }} article(s)</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ categories.length }} categorie(s)</p>
                        </div>

                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Actifs</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ activeCount }} article(s)</p>
                            <p class="mt-1 text-xs text-muted-foreground">disponibles dans les demandes</p>
                        </div>

                        <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950/30">
                            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">Prix saisis</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ pricedCount }} reference(s)</p>
                            <p class="mt-1 text-xs text-muted-foreground">avec prix indicatif</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ articles.length }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">article(s) au catalogue</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Actifs</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-300">{{ activeCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">visibles a la saisie</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Classes</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ categorizedCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">rattaches a une categorie</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Resultats</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ filtered.length }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">apres recherche</p>
                </div>
            </section>

            <!-- Recherche -->
            <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                <div v-if="articles.length > 0" class="relative lg:max-w-xl lg:flex-1">
                    <Search class="pointer-events-none absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input
                        v-model="search"
                        type="text"
                        placeholder="Rechercher par nom, référence, catégorie…"
                        class="h-11 w-full rounded-xl border border-input bg-background pl-10 pr-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                    />
                </div>
                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 self-start rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Nouvel article
                </button>
            </div>

            <!-- Vide -->
            <EmptyState
                v-if="articles.length === 0"
                :icon="Package"
                icon-bg="bg-orange-50 dark:bg-orange-950/30"
                icon-color="text-orange-500 dark:text-orange-300"
                title="Aucun article dans le catalogue"
                description="Constituez votre catalogue d'articles pour que les demandeurs puissent les sélectionner lors de la création de commandes."
            />

            <!-- Table -->
            <section v-else class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="flex flex-col gap-2 border-b px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-foreground">Catalogue des articles</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ filtered.length }} resultat(s) affiche(s) sur {{ articles.length }} article(s).
                        </p>
                    </div>

                    <div class="inline-flex items-center gap-2 rounded-xl border bg-muted/20 px-3 py-2 text-xs font-medium text-muted-foreground">
                        <CheckCircle2 class="h-4 w-4" />
                        {{ props.units.length }} unite(s) disponibles
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/20">
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Article</th>
                                <th
                                    class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground md:table-cell"
                                >
                                    Catégorie
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground sm:table-cell"
                                >
                                    Unité
                                </th>
                                <th
                                    class="hidden px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground sm:table-cell"
                                >
                                    Prix réf.
                                </th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-muted-foreground">Statut</th>
                                <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border/60">
                            <tr v-if="filtered.length === 0">
                                <td colspan="6" class="px-5 py-8 text-center text-sm text-muted-foreground">Aucun résultat pour "{{ search }}"</td>
                            </tr>
                            <tr v-for="a in filtered" :key="a.id" class="transition-colors hover:bg-muted/20">
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                            <Package class="h-4 w-4" />
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-semibold text-foreground">{{ a.name }}</p>
                                            <p class="mt-1 font-mono text-xs text-muted-foreground">{{ a.reference || 'Sans reference' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="hidden px-4 py-4 md:table-cell">
                                    <span
                                        v-if="a.category"
                                        class="inline-flex items-center rounded-full border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300"
                                    >
                                        {{ a.category.full_name }}
                                    </span>
                                    <span v-else class="text-xs text-muted-foreground">—</span>
                                </td>
                                <td class="hidden px-4 py-4 text-muted-foreground sm:table-cell">{{ a.unit }}</td>
                                <td class="hidden px-4 py-4 text-right font-medium text-foreground sm:table-cell">{{ formatPrice(a.unit_price) }}</td>
                                <td class="px-4 py-4 text-center">
                                    <span
                                        :class="
                                            a.is_active
                                                ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300'
                                                : 'bg-muted text-muted-foreground'
                                        "
                                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                                    >
                                        {{ a.is_active ? 'Actif' : 'Inactif' }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            @click="openEdit(a)"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300 dark:hover:bg-amber-950/50"
                                        >
                                            <Pencil class="h-3.5 w-3.5" />
                                            Modifier
                                        </button>
                                        <button
                                            @click="destroy(a.id!)"
                                            :disabled="deleting === a.id"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 transition-colors hover:bg-red-100 disabled:opacity-50 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300 dark:hover:bg-red-950/50"
                                        >
                                            <Trash2 class="h-3.5 w-3.5" />
                                            Supprimer
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>

    <!-- ─── Modal Créer / Modifier ─────────────────────────────────────────── -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal" />

                <!-- Panel -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                >
                    <div v-if="showModal" class="relative flex max-h-[90vh] w-full max-w-2xl flex-col rounded-2xl bg-card shadow-2xl">
                        <!-- En-tête -->
                        <div class="flex shrink-0 items-center justify-between border-b px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10">
                                    <Package class="h-5 w-5 text-primary" />
                                </div>
                                <h2 class="text-base font-semibold text-foreground">
                                    {{ editingArticle ? "Modifier l'article" : 'Nouvel article' }}
                                </h2>
                            </div>
                            <button @click="closeModal" class="rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-muted">
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Corps -->
                        <form @submit.prevent="submit" class="flex-1 overflow-y-auto p-6">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <!-- Nom -->
                                <div class="flex flex-col gap-1.5 sm:col-span-2">
                                    <label class="text-sm font-medium text-foreground" for="modal_name">
                                        Nom <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="modal_name"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="Ex : Ordinateur portable Dell"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                        :class="{ 'border-red-400': form.errors.name }"
                                    />
                                    <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                                </div>

                                <!-- Référence -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground" for="modal_reference">Référence</label>
                                    <input
                                        id="modal_reference"
                                        v-model="form.reference"
                                        type="text"
                                        placeholder="Ex : INF-001"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 font-mono text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                        :class="{ 'border-red-400': form.errors.reference }"
                                    />
                                    <p v-if="form.errors.reference" class="text-xs text-red-500">{{ form.errors.reference }}</p>
                                </div>

                                <!-- Catégorie -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground" for="modal_category">Catégorie</label>
                                    <select
                                        id="modal_category"
                                        v-model="form.category_id"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    >
                                        <option value="">— Sans catégorie —</option>
                                        <option v-for="c in groupedCategories" :key="c.id" :value="c.id">{{ c.label }}</option>
                                    </select>
                                </div>

                                <!-- Unité -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground" for="modal_unit">
                                        Unité <span class="text-red-500">*</span>
                                    </label>
                                    <select
                                        id="modal_unit"
                                        v-model="form.unit"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    >
                                        <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
                                    </select>
                                </div>

                                <!-- Prix unitaire -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground" for="modal_price"> Prix unitaire de référence (FCFA) </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground">FCFA</span>
                                        <input
                                            id="modal_price"
                                            v-model="form.unit_price"
                                            type="number"
                                            min="0"
                                            step="1"
                                            placeholder="0"
                                            class="h-10 w-full rounded-xl border border-input bg-background pl-16 pr-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                        />
                                    </div>
                                    <p class="text-xs text-muted-foreground">Valeur indicative, modifiable sur chaque ligne de commande.</p>
                                </div>

                                <!-- Description -->
                                <div class="flex flex-col gap-1.5 sm:col-span-2">
                                    <label class="text-sm font-medium text-foreground" for="modal_description">Description</label>
                                    <textarea
                                        id="modal_description"
                                        v-model="form.description"
                                        rows="3"
                                        placeholder="Caractéristiques, spécifications techniques…"
                                        class="w-full resize-none rounded-xl border border-input bg-background px-4 py-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    />
                                </div>

                                <!-- Actif -->
                                <div class="flex items-center gap-3 pt-1 sm:col-span-2">
                                    <button
                                        type="button"
                                        @click="form.is_active = !form.is_active"
                                        :class="form.is_active ? 'bg-primary' : 'bg-muted'"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                    >
                                        <span
                                            :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                                            class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                                        />
                                    </button>
                                    <span class="text-sm font-medium text-foreground">Article actif</span>
                                </div>
                            </div>
                        </form>

                        <!-- Pied -->
                        <div class="flex shrink-0 items-center justify-end gap-3 border-t px-6 py-4">
                            <button
                                type="button"
                                @click="closeModal"
                                class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                            >
                                Annuler
                            </button>
                            <button
                                type="button"
                                @click="submit"
                                :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-70"
                            >
                                {{ editingArticle ? 'Enregistrer' : "Créer l'article" }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
