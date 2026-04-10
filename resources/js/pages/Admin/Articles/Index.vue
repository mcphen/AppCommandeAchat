<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Article, type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Package, Pencil, Plus, Search, Trash2, X } from 'lucide-vue-next';
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
    props.articles.filter(a =>
        a.name.toLowerCase().includes(search.value.toLowerCase()) ||
        (a.reference ?? '').toLowerCase().includes(search.value.toLowerCase()) ||
        (a.category?.full_name ?? '').toLowerCase().includes(search.value.toLowerCase())
    )
);

// ─── Formatters ───────────────────────────────────────────────────────────────
const formatPrice = (v?: string | number | null) =>
    v == null ? '—' : new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

// ─── Catégories groupées ──────────────────────────────────────────────────────
const groupedCategories = computed(() => {
    const roots = props.categories.filter(c => !c.parent_id);
    const result: { id: number; label: string }[] = [];
    for (const root of roots) {
        result.push({ id: root.id, label: root.name });
        const children = props.categories.filter(c => c.parent_id === root.id);
        for (const child of children) {
            result.push({ id: child.id, label: '  › ' + child.name });
        }
    }
    return result;
});

// ─── Modal ────────────────────────────────────────────────────────────────────
const showModal = ref(false);
const editingArticle = ref<Article | null>(null);

const form = useForm({
    category_id: '' as number | string,
    name:        '',
    reference:   '',
    description: '',
    unit:        'pièce',
    unit_price:  '' as number | string,
    is_active:   true,
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
    form.name        = a.name;
    form.reference   = a.reference ?? '';
    form.description = a.description ?? '';
    form.unit        = a.unit;
    form.unit_price  = a.unit_price ?? '';
    form.is_active   = a.is_active;
    showModal.value  = true;
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
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Articles</h1>
                    <p class="text-sm text-muted-foreground mt-1">{{ articles.length }} article{{ articles.length > 1 ? 's' : '' }}</p>
                </div>
                <button @click="openCreate"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors shrink-0">
                    <Plus class="h-4 w-4" /> Nouvel article
                </button>
            </div>

            <!-- Recherche -->
            <div v-if="articles.length > 0" class="relative">
                <Search class="absolute left-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <input v-model="search" type="text" placeholder="Rechercher par nom, référence, catégorie…"
                    class="h-10 w-full rounded-xl border border-input bg-background pl-10 pr-4 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors" />
            </div>

            <!-- Vide -->
            <EmptyState
                v-if="articles.length === 0"
                :icon="Package"
                icon-bg="bg-orange-50"
                icon-color="text-orange-500"
                title="Aucun article dans le catalogue"
                description="Constituez votre catalogue d'articles pour que les demandeurs puissent les sélectionner lors de la création de commandes."
            />

            <!-- Table -->
            <div v-else class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/30">
                            <th class="text-left px-5 py-3 font-semibold text-xs uppercase tracking-wide text-muted-foreground">Article</th>
                            <th class="text-left px-4 py-3 font-semibold text-xs uppercase tracking-wide text-muted-foreground hidden md:table-cell">Catégorie</th>
                            <th class="text-left px-4 py-3 font-semibold text-xs uppercase tracking-wide text-muted-foreground hidden sm:table-cell">Unité</th>
                            <th class="text-right px-4 py-3 font-semibold text-xs uppercase tracking-wide text-muted-foreground hidden sm:table-cell">Prix réf.</th>
                            <th class="text-center px-4 py-3 font-semibold text-xs uppercase tracking-wide text-muted-foreground">Statut</th>
                            <th class="text-right px-5 py-3 font-semibold text-xs uppercase tracking-wide text-muted-foreground">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-if="filtered.length === 0">
                            <td colspan="6" class="px-5 py-8 text-center text-sm text-muted-foreground">Aucun résultat pour "{{ search }}"</td>
                        </tr>
                        <tr v-for="a in filtered" :key="a.id" class="hover:bg-muted/20 transition-colors">
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                        <Package class="h-4 w-4 text-primary" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-foreground">{{ a.name }}</p>
                                        <p v-if="a.reference" class="text-xs text-muted-foreground font-mono">{{ a.reference }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3.5 hidden md:table-cell">
                                <span v-if="a.category" class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700">
                                    {{ a.category.full_name }}
                                </span>
                                <span v-else class="text-xs text-muted-foreground">—</span>
                            </td>
                            <td class="px-4 py-3.5 hidden sm:table-cell text-muted-foreground">{{ a.unit }}</td>
                            <td class="px-4 py-3.5 text-right hidden sm:table-cell font-medium text-foreground">{{ formatPrice(a.unit_price) }}</td>
                            <td class="px-4 py-3.5 text-center">
                                <span :class="a.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                    class="rounded-full px-2.5 py-0.5 text-xs font-medium">
                                    {{ a.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right">
                                <div class="flex items-center justify-end gap-1">
                                    <button @click="openEdit(a)" class="rounded-lg p-1.5 hover:bg-muted transition-colors text-muted-foreground hover:text-foreground">
                                        <Pencil class="h-4 w-4" />
                                    </button>
                                    <button @click="destroy(a.id!)" :disabled="deleting === a.id"
                                        class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600 disabled:opacity-50">
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

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
                    <div v-if="showModal" class="relative w-full max-w-2xl rounded-2xl bg-card shadow-2xl flex flex-col max-h-[90vh]">

                        <!-- En-tête -->
                        <div class="flex items-center justify-between border-b px-6 py-4 shrink-0">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10">
                                    <Package class="h-5 w-5 text-primary" />
                                </div>
                                <h2 class="text-base font-semibold text-foreground">
                                    {{ editingArticle ? 'Modifier l\'article' : 'Nouvel article' }}
                                </h2>
                            </div>
                            <button @click="closeModal" class="rounded-lg p-1.5 hover:bg-muted transition-colors text-muted-foreground">
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Corps -->
                        <form @submit.prevent="submit" class="overflow-y-auto flex-1 p-6">
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                                <!-- Nom -->
                                <div class="flex flex-col gap-1.5 sm:col-span-2">
                                    <label class="text-sm font-medium text-foreground" for="modal_name">
                                        Nom <span class="text-red-500">*</span>
                                    </label>
                                    <input id="modal_name" v-model="form.name" type="text"
                                        placeholder="Ex : Ordinateur portable Dell"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                        :class="{ 'border-red-400': form.errors.name }" />
                                    <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                                </div>

                                <!-- Référence -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground" for="modal_reference">Référence</label>
                                    <input id="modal_reference" v-model="form.reference" type="text"
                                        placeholder="Ex : INF-001"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground font-mono focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                        :class="{ 'border-red-400': form.errors.reference }" />
                                    <p v-if="form.errors.reference" class="text-xs text-red-500">{{ form.errors.reference }}</p>
                                </div>

                                <!-- Catégorie -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground" for="modal_category">Catégorie</label>
                                    <select id="modal_category" v-model="form.category_id"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors">
                                        <option value="">— Sans catégorie —</option>
                                        <option v-for="c in groupedCategories" :key="c.id" :value="c.id">{{ c.label }}</option>
                                    </select>
                                </div>

                                <!-- Unité -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground" for="modal_unit">
                                        Unité <span class="text-red-500">*</span>
                                    </label>
                                    <select id="modal_unit" v-model="form.unit"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors">
                                        <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
                                    </select>
                                </div>

                                <!-- Prix unitaire -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground" for="modal_price">
                                        Prix unitaire de référence (FCFA)
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground text-sm font-medium">FCFA</span>
                                        <input id="modal_price" v-model="form.unit_price" type="number" min="0" step="1" placeholder="0"
                                            class="h-10 w-full rounded-xl border border-input bg-background pl-16 pr-4 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors" />
                                    </div>
                                    <p class="text-xs text-muted-foreground">Valeur indicative, modifiable sur chaque ligne de commande.</p>
                                </div>

                                <!-- Description -->
                                <div class="flex flex-col gap-1.5 sm:col-span-2">
                                    <label class="text-sm font-medium text-foreground" for="modal_description">Description</label>
                                    <textarea id="modal_description" v-model="form.description" rows="3"
                                        placeholder="Caractéristiques, spécifications techniques…"
                                        class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-none" />
                                </div>

                                <!-- Actif -->
                                <div class="flex items-center gap-3 sm:col-span-2 pt-1">
                                    <button type="button" @click="form.is_active = !form.is_active"
                                        :class="form.is_active ? 'bg-primary' : 'bg-muted'"
                                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                                        <span :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                                            class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform" />
                                    </button>
                                    <span class="text-sm font-medium text-foreground">Article actif</span>
                                </div>

                            </div>
                        </form>

                        <!-- Pied -->
                        <div class="flex items-center justify-end gap-3 border-t px-6 py-4 shrink-0">
                            <button type="button" @click="closeModal"
                                class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors">
                                Annuler
                            </button>
                            <button type="button" @click="submit" :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:opacity-70">
                                {{ editingArticle ? 'Enregistrer' : 'Créer l\'article' }}
                            </button>
                        </div>

                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
