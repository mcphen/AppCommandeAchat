<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Category } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, FolderOpen } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    category?: Category;
    parents: { id: number; name: string }[];
}>();

const isEdit = computed(() => !!props.category);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Catégories', href: '/admin/categories' },
    { title: isEdit.value ? 'Modifier' : 'Nouvelle catégorie', href: '#' },
];

const form = useForm({
    name:          props.category?.name ?? '',
    parent_id:     props.category?.parent_id ?? '',
    description:   props.category?.description ?? '',
    is_active:     props.category?.is_active ?? true,
    account_code:  props.category?.account_code ?? '',
    account_label: props.category?.account_label ?? '',
});

const submit = () => {
    if (isEdit.value) {
        form.put(route('admin.categories.update', props.category!.id));
    } else {
        form.post(route('admin.categories.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Modifier la catégorie' : 'Nouvelle catégorie'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6 max-w-2xl">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('admin.categories.index')" class="rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">{{ isEdit ? 'Modifier la catégorie' : 'Nouvelle catégorie' }}</h1>
                    <p class="text-sm text-muted-foreground">Maximum 2 niveaux : catégorie parente → sous-catégorie</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-5">
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-5">

                    <!-- Icône -->
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 mx-auto">
                        <FolderOpen class="h-6 w-6 text-blue-600" />
                    </div>

                    <!-- Nom -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="name">Nom <span class="text-red-500">*</span></label>
                        <input
                            id="name" v-model="form.name" type="text"
                            placeholder="Ex : Informatique"
                            class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                            :class="{ 'border-red-400': form.errors.name }"
                        />
                        <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                    </div>

                    <!-- Parent -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="parent_id">Catégorie parente</label>
                        <select
                            id="parent_id" v-model="form.parent_id"
                            class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                        >
                            <option value="">— Aucune (catégorie racine) —</option>
                            <option v-for="p in parents" :key="p.id" :value="p.id">{{ p.name }}</option>
                        </select>
                        <p v-if="form.errors.parent_id" class="text-xs text-red-500">{{ form.errors.parent_id }}</p>
                    </div>

                    <!-- Description -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="description">Description</label>
                        <textarea
                            id="description" v-model="form.description" rows="3"
                            placeholder="Description optionnelle…"
                            class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-none"
                        />
                    </div>

                    <!-- Mapping comptable OHADA -->
                    <div class="rounded-xl border border-dashed border-blue-200 bg-blue-50/50 p-4 flex flex-col gap-3">
                        <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide">Mapping comptable OHADA / SYSCOHADA</p>
                        <div class="grid grid-cols-2 gap-3">
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground" for="account_code">Code compte <span class="text-muted-foreground font-normal">(ex: 60400)</span></label>
                                <input
                                    id="account_code" v-model="form.account_code" type="text"
                                    placeholder="60400"
                                    maxlength="10"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                    :class="{ 'border-red-400': form.errors.account_code }"
                                />
                                <p v-if="form.errors.account_code" class="text-xs text-red-500">{{ form.errors.account_code }}</p>
                            </div>
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground" for="account_label">Libellé du compte</label>
                                <input
                                    id="account_label" v-model="form.account_label" type="text"
                                    placeholder="Achats de matériels"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                    :class="{ 'border-red-400': form.errors.account_label }"
                                />
                                <p v-if="form.errors.account_label" class="text-xs text-red-500">{{ form.errors.account_label }}</p>
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">Utilisé pour générer automatiquement les écritures comptables lors de la confirmation d'un BC. Si vide, le compte 60500 (Autres achats) sera utilisé par défaut.</p>
                    </div>

                    <!-- Actif -->
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
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('admin.categories.index')" class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors">
                        Annuler
                    </Link>
                    <button
                        type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:opacity-70"
                    >
                        {{ isEdit ? 'Enregistrer' : 'Créer la catégorie' }}
                    </button>
                </div>
            </form>

        </div>
    </AppLayout>
</template>
