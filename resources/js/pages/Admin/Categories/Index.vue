<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Category } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { FolderOpen, FolderTree, Plus, Pencil, Trash2, ChevronRight } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{ categories: Category[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Catégories', href: '/admin/categories' },
];

const deleting = ref<number | null>(null);

const destroy = (id: number) => {
    if (!confirm('Supprimer cette catégorie ?')) return;
    deleting.value = id;
    router.delete(route('admin.categories.destroy', id), {
        onFinish: () => deleting.value = null,
    });
};

const roots = (cats: Category[]) => cats.filter(c => !c.parent_id);
const children = (cats: Category[], parentId: number) => cats.filter(c => c.parent_id === parentId);
</script>

<template>
    <Head title="Catégories" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Catégories</h1>
                    <p class="text-sm text-muted-foreground mt-1">{{ categories.length }} catégorie{{ categories.length > 1 ? 's' : '' }}</p>
                </div>
                <Link :href="route('admin.categories.create')" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                    <Plus class="h-4 w-4" /> Nouvelle catégorie
                </Link>
            </div>

            <!-- Vide -->
            <EmptyState
                v-if="categories.length === 0"
                :icon="FolderTree"
                icon-bg="bg-amber-50"
                icon-color="text-amber-500"
                title="Aucune catégorie"
                description="Organisez vos articles par catégories pour faciliter les recherches et associer des codes comptables OHADA."
                :action-href="route('admin.categories.create')"
                action-label="Créer une catégorie"
            />

            <!-- Liste arborescente -->
            <div v-else class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                <template v-for="root in roots(categories)" :key="root.id">
                    <!-- Catégorie racine -->
                    <div class="flex items-center justify-between px-5 py-4 border-b last:border-b-0 hover:bg-muted/30 transition-colors bg-muted/10">
                        <div class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50 shrink-0">
                                <FolderOpen class="h-4 w-4 text-blue-600" />
                            </div>
                            <div>
                                <p class="font-semibold text-foreground text-sm">{{ root.name }}</p>
                                <p v-if="root.description" class="text-xs text-muted-foreground">{{ root.description }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span v-if="root.account_code" class="rounded-lg bg-blue-50 text-blue-700 border border-blue-100 px-2 py-0.5 text-xs font-mono font-medium">{{ root.account_code }}</span>
                            <span class="text-xs text-muted-foreground">{{ root.articles_count }} article{{ (root.articles_count ?? 0) > 1 ? 's' : '' }}</span>
                            <span :class="root.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                                {{ root.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                            <Link :href="route('admin.categories.edit', root.id)" class="rounded-lg p-1.5 hover:bg-muted transition-colors text-muted-foreground hover:text-foreground">
                                <Pencil class="h-4 w-4" />
                            </Link>
                            <button @click="destroy(root.id)" :disabled="deleting === root.id" class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600 disabled:opacity-50">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Sous-catégories -->
                    <div v-for="child in children(categories, root.id)" :key="child.id"
                        class="flex items-center justify-between px-5 py-3 border-b last:border-b-0 hover:bg-muted/20 transition-colors pl-14">
                        <div class="flex items-center gap-3">
                            <ChevronRight class="h-3.5 w-3.5 text-muted-foreground/50 shrink-0 -ml-5" />
                            <div class="flex h-7 w-7 items-center justify-center rounded-lg bg-indigo-50 shrink-0">
                                <FolderOpen class="h-3.5 w-3.5 text-indigo-500" />
                            </div>
                            <div>
                                <p class="font-medium text-foreground text-sm">{{ child.name }}</p>
                                <p v-if="child.description" class="text-xs text-muted-foreground">{{ child.description }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <span v-if="child.account_code" class="rounded-lg bg-blue-50 text-blue-700 border border-blue-100 px-2 py-0.5 text-xs font-mono font-medium">{{ child.account_code }}</span>
                            <span class="text-xs text-muted-foreground">{{ child.articles_count }} article{{ (child.articles_count ?? 0) > 1 ? 's' : '' }}</span>
                            <span :class="child.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'" class="rounded-full px-2 py-0.5 text-xs font-medium">
                                {{ child.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                            <Link :href="route('admin.categories.edit', child.id)" class="rounded-lg p-1.5 hover:bg-muted transition-colors text-muted-foreground hover:text-foreground">
                                <Pencil class="h-4 w-4" />
                            </Link>
                            <button @click="destroy(child.id)" :disabled="deleting === child.id" class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600 disabled:opacity-50">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </template>
            </div>

        </div>
    </AppLayout>
</template>
