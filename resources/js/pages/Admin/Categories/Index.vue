<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Category } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ChevronRight, FolderOpen, FolderTree, Layers, Pencil, Plus, Trash2, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ categories: Category[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Catégories', href: '/admin/categories' },
];

// ── Stats ────────────────────────────────────────────────────────────────────
const rootCount     = computed(() => props.categories.filter(c => !c.parent_id).length);
const childCount    = computed(() => props.categories.filter(c => !!c.parent_id).length);
const activeCount   = computed(() => props.categories.filter(c => c.is_active).length);

// ── Tree helpers ─────────────────────────────────────────────────────────────
const roots    = (cats: Category[]) => cats.filter(c => !c.parent_id);
const children = (cats: Category[], parentId: number) => cats.filter(c => c.parent_id === parentId);

// ── Parents list for modal select ────────────────────────────────────────────
const parentOptions = computed(() => props.categories.filter(c => !c.parent_id));

// ── Delete ───────────────────────────────────────────────────────────────────
const deleting = ref<number | null>(null);
const destroy = (id: number) => {
    if (!confirm('Supprimer cette catégorie ?')) return;
    deleting.value = id;
    router.delete(route('admin.categories.destroy', id), {
        onFinish: () => (deleting.value = null),
    });
};

// ── Modal création ───────────────────────────────────────────────────────────
const showModal = ref(false);

const form = useForm({
    name:          '',
    parent_id:     '' as number | '',
    description:   '',
    is_active:     true,
    account_code:  '',
    account_label: '',
});

const openModal = () => {
    form.reset();
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    form.post(route('admin.categories.store'), {
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <Head title="Catégories" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">

            <!-- ── Header banner ─────────────────────────────────────────── -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Catégories</h1>
                    <p class="text-sm text-muted-foreground">Organisez vos articles et associez les codes comptables OHADA/SYSCOHADA.</p>
                </div>
                <div class="inline-flex items-center gap-2 self-start rounded-xl border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary">
                    <FolderTree class="h-4 w-4" />
                    Administration
                </div>
            </div>

            <!-- ── Section info ──────────────────────────────────────────── -->
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <FolderTree class="h-8 w-8" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Référentiel catégories</p>
                            <h2 class="mt-1 text-xl font-semibold text-foreground">Arborescence à 2 niveaux</h2>
                            <p class="mt-1 text-sm text-muted-foreground">Catégories parentes et sous-catégories pour classer vos articles et générer les écritures comptables.</p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:w-[480px]">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">
                                <FolderTree class="h-4 w-4" />
                                Total
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ categories.length }} catégorie{{ categories.length > 1 ? 's' : '' }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 dark:border-indigo-900 dark:bg-indigo-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300">
                                <FolderOpen class="h-4 w-4" />
                                Racines
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ rootCount }} parente{{ rootCount > 1 ? 's' : '' }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                <Layers class="h-4 w-4" />
                                Actives
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ activeCount }} active{{ activeCount > 1 ? 's' : '' }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── Bouton action ─────────────────────────────────────────── -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    @click="openModal"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Nouvelle catégorie
                </button>
            </div>

            <!-- ── Stats cards ───────────────────────────────────────────── -->
            <section v-if="categories.length > 0" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ categories.length }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">catégorie{{ categories.length > 1 ? 's' : '' }}</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Racines</p>
                    <p class="mt-2 text-2xl font-bold text-blue-600 dark:text-blue-300">{{ rootCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">catégories parentes</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Sous-catégories</p>
                    <p class="mt-2 text-2xl font-bold text-indigo-600 dark:text-indigo-300">{{ childCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">niveaux enfants</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Actives</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-300">{{ activeCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">utilisées dans les BCs</p>
                </div>
            </section>

            <!-- ── Vide ──────────────────────────────────────────────────── -->
            <EmptyState
                v-if="categories.length === 0"
                :icon="FolderTree"
                icon-bg="bg-amber-50 dark:bg-amber-950/30"
                icon-color="text-amber-500 dark:text-amber-300"
                title="Aucune catégorie"
                description="Organisez vos articles par catégories pour faciliter les recherches et associer des codes comptables OHADA."
            >
                <button
                    @click="openModal"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Créer une catégorie
                </button>
            </EmptyState>

            <!-- ── Liste arborescente ─────────────────────────────────────── -->
            <div v-else class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                <template v-for="root in roots(categories)" :key="root.id">

                    <!-- Catégorie racine -->
                    <div class="flex items-center justify-between px-5 py-4 border-b last:border-b-0 hover:bg-muted/30 transition-colors bg-muted/10">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-950/30 shrink-0">
                                <FolderOpen class="h-4 w-4 text-blue-600 dark:text-blue-300" />
                            </div>
                            <div>
                                <p class="font-semibold text-foreground text-sm">{{ root.name }}</p>
                                <p v-if="root.description" class="text-xs text-muted-foreground">{{ root.description }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap justify-end">
                            <span v-if="root.account_code" class="rounded-lg bg-blue-50 text-blue-700 border border-blue-100 dark:bg-blue-950/30 dark:text-blue-300 dark:border-blue-900 px-2 py-0.5 text-xs font-mono font-medium">{{ root.account_code }}</span>
                            <span class="text-xs text-muted-foreground">{{ root.articles_count ?? 0 }} art.</span>
                            <span :class="root.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-900' : 'bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400'" class="rounded-full border px-2.5 py-0.5 text-xs font-medium">
                                {{ root.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                            <a :href="route('admin.categories.edit', root.id)" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300 dark:hover:bg-amber-950/50">
                                <Pencil class="h-3.5 w-3.5" />
                                Modifier
                            </a>
                            <button @click="destroy(root.id)" :disabled="deleting === root.id" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 transition-colors hover:bg-red-100 disabled:opacity-50 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300 dark:hover:bg-red-950/50">
                                <Trash2 class="h-3.5 w-3.5" />
                                Supprimer
                            </button>
                        </div>
                    </div>

                    <!-- Sous-catégories -->
                    <div
                        v-for="child in children(categories, root.id)" :key="child.id"
                        class="flex items-center justify-between px-5 py-3 border-b last:border-b-0 hover:bg-muted/20 transition-colors pl-14"
                    >
                        <div class="flex items-center gap-3">
                            <ChevronRight class="h-3.5 w-3.5 text-muted-foreground/50 shrink-0 -ml-5" />
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-950/30 shrink-0">
                                <FolderOpen class="h-3.5 w-3.5 text-indigo-500 dark:text-indigo-300" />
                            </div>
                            <div>
                                <p class="font-medium text-foreground text-sm">{{ child.name }}</p>
                                <p v-if="child.description" class="text-xs text-muted-foreground">{{ child.description }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 flex-wrap justify-end">
                            <span v-if="child.account_code" class="rounded-lg bg-blue-50 text-blue-700 border border-blue-100 dark:bg-blue-950/30 dark:text-blue-300 dark:border-blue-900 px-2 py-0.5 text-xs font-mono font-medium">{{ child.account_code }}</span>
                            <span class="text-xs text-muted-foreground">{{ child.articles_count ?? 0 }} art.</span>
                            <span :class="child.is_active ? 'bg-emerald-50 text-emerald-700 border-emerald-100 dark:bg-emerald-950/30 dark:text-emerald-300 dark:border-emerald-900' : 'bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400'" class="rounded-full border px-2.5 py-0.5 text-xs font-medium">
                                {{ child.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                            <a :href="route('admin.categories.edit', child.id)" class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300 dark:hover:bg-amber-950/50">
                                <Pencil class="h-3.5 w-3.5" />
                                Modifier
                            </a>
                            <button @click="destroy(child.id)" :disabled="deleting === child.id" class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2.5 py-1.5 text-xs font-medium text-red-700 transition-colors hover:bg-red-100 disabled:opacity-50 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300 dark:hover:bg-red-950/50">
                                <Trash2 class="h-3.5 w-3.5" />
                                Supprimer
                            </button>
                        </div>
                    </div>

                </template>
            </div>

        </div>
    </AppLayout>

    <!-- ── Modal Nouvelle catégorie ──────────────────────────────────────── -->
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
                <!-- Overlay -->
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
                    <div v-if="showModal" class="relative z-10 w-full max-w-lg rounded-2xl border bg-card shadow-2xl overflow-hidden">

                        <!-- Header modal -->
                        <div class="flex items-center justify-between border-b bg-muted/30 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 dark:bg-blue-950/30">
                                    <FolderOpen class="h-5 w-5 text-blue-600 dark:text-blue-300" />
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-foreground">Nouvelle catégorie</h2>
                                    <p class="text-xs text-muted-foreground">Maximum 2 niveaux</p>
                                </div>
                            </div>
                            <button @click="closeModal" class="rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground">
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Corps du formulaire -->
                        <form @submit.prevent="submit" class="flex flex-col gap-4 p-6">

                            <!-- Nom -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground" for="modal-name">
                                    Nom <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="modal-name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Ex : Informatique"
                                    autofocus
                                    class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    :class="{ 'border-red-400': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                            </div>

                            <!-- Catégorie parente -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground" for="modal-parent">Catégorie parente</label>
                                <select
                                    id="modal-parent"
                                    v-model="form.parent_id"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                >
                                    <option value="">— Aucune (catégorie racine) —</option>
                                    <option v-for="p in parentOptions" :key="p.id" :value="p.id">{{ p.name }}</option>
                                </select>
                                <p v-if="form.errors.parent_id" class="text-xs text-red-500">{{ form.errors.parent_id }}</p>
                            </div>

                            <!-- Description -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground" for="modal-desc">Description</label>
                                <textarea
                                    id="modal-desc"
                                    v-model="form.description"
                                    rows="2"
                                    placeholder="Description optionnelle…"
                                    class="w-full resize-none rounded-xl border border-input bg-background px-4 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                />
                            </div>

                            <!-- Mapping OHADA -->
                            <div class="rounded-xl border border-dashed border-blue-200 bg-blue-50/50 dark:border-blue-900 dark:bg-blue-950/20 p-4 flex flex-col gap-3">
                                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Mapping comptable OHADA</p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-medium text-foreground" for="modal-code">Code compte</label>
                                        <input
                                            id="modal-code"
                                            v-model="form.account_code"
                                            type="text"
                                            placeholder="60400"
                                            maxlength="10"
                                            class="h-9 w-full rounded-xl border border-input bg-background px-3 text-sm font-mono transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                            :class="{ 'border-red-400': form.errors.account_code }"
                                        />
                                    </div>
                                    <div class="flex flex-col gap-1.5">
                                        <label class="text-xs font-medium text-foreground" for="modal-label">Libellé</label>
                                        <input
                                            id="modal-label"
                                            v-model="form.account_label"
                                            type="text"
                                            placeholder="Achats de matériels"
                                            class="h-9 w-full rounded-xl border border-input bg-background px-3 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Toggle actif -->
                            <div class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="form.is_active = !form.is_active"
                                    :class="form.is_active ? 'bg-primary' : 'bg-muted'"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors"
                                >
                                    <span :class="form.is_active ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform" />
                                </button>
                                <span class="text-sm font-medium text-foreground">Catégorie active</span>
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-3 border-t border-border/50 pt-4">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-70"
                                >
                                    <Plus class="h-4 w-4" />
                                    Créer la catégorie
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
