<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type Article, type BreadcrumbItem, type Category } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Package } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    article?: Article;
    categories: { id: number; name: string; full_name: string; parent_id?: number | null }[];
    units: string[];
}>();

const isEdit = computed(() => !!props.article);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Articles', href: '/admin/articles' },
    { title: isEdit.value ? 'Modifier' : 'Nouvel article', href: '#' },
];

const form = useForm({
    category_id: props.article?.category_id ?? '',
    name:        props.article?.name ?? '',
    reference:   props.article?.reference ?? '',
    description: props.article?.description ?? '',
    unit:        props.article?.unit ?? 'pièce',
    unit_price:  props.article?.unit_price ?? '',
    is_active:   props.article?.is_active ?? true,
});

const submit = () => {
    if (isEdit.value) {
        form.put(route('admin.articles.update', props.article!.id));
    } else {
        form.post(route('admin.articles.store'));
    }
};

// Grouper les catégories : racines en premier, enfants indentés
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
</script>

<template>
    <Head :title="isEdit ? 'Modifier l\'article' : 'Nouvel article'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6 max-w-2xl">

            <div class="flex items-center gap-4">
                <Link :href="route('admin.articles.index')" class="rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <h1 class="text-2xl font-bold text-foreground">{{ isEdit ? 'Modifier l\'article' : 'Nouvel article' }}</h1>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-5">
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-5">

                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 mx-auto">
                        <Package class="h-6 w-6 text-primary" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        <!-- Nom -->
                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label class="text-sm font-medium text-foreground" for="name">Nom <span class="text-red-500">*</span></label>
                            <input id="name" v-model="form.name" type="text" placeholder="Ex : Ordinateur portable Dell"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.name }" />
                            <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <!-- Référence -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground" for="reference">Référence</label>
                            <input id="reference" v-model="form.reference" type="text" placeholder="Ex : INF-001"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.reference }" />
                            <p v-if="form.errors.reference" class="text-xs text-red-500">{{ form.errors.reference }}</p>
                        </div>

                        <!-- Catégorie -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground" for="category_id">Catégorie</label>
                            <select id="category_id" v-model="form.category_id"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors">
                                <option value="">— Sans catégorie —</option>
                                <option v-for="c in groupedCategories" :key="c.id" :value="c.id">{{ c.label }}</option>
                            </select>
                        </div>

                        <!-- Unité -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground" for="unit">Unité <span class="text-red-500">*</span></label>
                            <select id="unit" v-model="form.unit"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors">
                                <option v-for="u in units" :key="u" :value="u">{{ u }}</option>
                            </select>
                        </div>

                        <!-- Prix unitaire de référence -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground" for="unit_price">Prix unitaire de référence (FCFA)</label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground text-sm font-medium">FCFA</span>
                                <input id="unit_price" v-model="form.unit_price" type="number" min="0" step="1" placeholder="0"
                                    class="h-10 w-full rounded-xl border border-input bg-background pl-16 pr-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors" />
                            </div>
                            <p class="text-xs text-muted-foreground">Valeur indicative, modifiable sur chaque ligne de commande.</p>
                        </div>

                        <!-- Description -->
                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label class="text-sm font-medium text-foreground" for="description">Description</label>
                            <textarea id="description" v-model="form.description" rows="3"
                                placeholder="Caractéristiques, spécifications techniques…"
                                class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-none" />
                        </div>
                    </div>

                    <!-- Actif -->
                    <div class="flex items-center gap-3 pt-1">
                        <button type="button" @click="form.is_active = !form.is_active"
                            :class="form.is_active ? 'bg-primary' : 'bg-muted'"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                            <span :class="form.is_active ? 'translate-x-6' : 'translate-x-1'" class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform" />
                        </button>
                        <span class="text-sm font-medium text-foreground">Article actif</span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('admin.articles.index')" class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors">
                        Annuler
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:opacity-70">
                        {{ isEdit ? 'Enregistrer' : 'Créer l\'article' }}
                    </button>
                </div>
            </form>

        </div>
    </AppLayout>
</template>
