<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type Entreprise, type Groupe } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2 } from 'lucide-vue-next';

const props = defineProps<{ entreprise?: Entreprise; groupes: Groupe[] }>();
const isEdit = !!props.entreprise;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Entreprises', href: '/admin/entreprises' },
    { title: isEdit ? 'Modifier' : 'Créer', href: '#' },
];

const form = useForm({
    groupe_id: props.entreprise?.groupe_id ?? '',
    nom: props.entreprise?.nom ?? '',
    code: props.entreprise?.code ?? '',
    adresse: props.entreprise?.adresse ?? '',
    ville: props.entreprise?.ville ?? '',
    is_active: props.entreprise?.is_active ?? true,
});

function submit() {
    if (isEdit) form.put(route('admin.entreprises.update', props.entreprise!.id));
    else form.post(route('admin.entreprises.store'));
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="isEdit ? 'Modifier l\'entreprise' : 'Créer une entreprise'" />
        <div class="flex w-full flex-col gap-6 p-6">
            <PageHeader
                :title="`${isEdit ? 'Modifier' : 'Créer'} une entreprise`"
                eyebrow="Administration"
            >
                <template #actions>
                    <a :href="route('admin.entreprises.index')"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">
                        <ArrowLeft class="h-4 w-4" />
                        Retour
                    </a>
                </template>
            </PageHeader>
            <form @submit.prevent="submit" class="rounded-xl border bg-card p-6 shadow-sm flex flex-col gap-5">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Groupe <span class="text-red-500">*</span></label>
                    <select v-model="form.groupe_id" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40">
                        <option value="">— Sélectionner —</option>
                        <option v-for="g in groupes" :key="g.id" :value="g.id">{{ g.nom }}</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Nom <span class="text-red-500">*</span></label>
                        <input v-model="form.nom" type="text" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Code <span class="text-red-500">*</span></label>
                        <input v-model="form.code" type="text" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" placeholder="ENT001" />
                    </div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Adresse</label>
                    <input v-model="form.adresse" type="text" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Ville</label>
                    <input v-model="form.ville" type="text" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input v-model="form.is_active" type="checkbox" class="rounded" />
                    <span class="text-sm font-medium">Active</span>
                </label>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a :href="route('admin.entreprises.index')" class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors">Annuler</a>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60 transition-colors">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        {{ isEdit ? 'Enregistrer' : 'Créer' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
