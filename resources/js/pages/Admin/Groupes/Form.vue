<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type Groupe } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2 } from 'lucide-vue-next';

const props = defineProps<{ groupe?: Groupe }>();
const isEdit = !!props.groupe;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Groupes', href: '/admin/groupes' },
    { title: isEdit ? 'Modifier' : 'Créer', href: '#' },
];

const form = useForm({
    nom: props.groupe?.nom ?? '',
    code: props.groupe?.code ?? '',
    is_active: props.groupe?.is_active ?? true,
});

function submit() {
    if (isEdit) form.put(route('admin.groupes.update', props.groupe!.id));
    else form.post(route('admin.groupes.store'));
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="isEdit ? 'Modifier le groupe' : 'Créer un groupe'" />
        <div class="flex w-full flex-col gap-6 p-6">
            <PageHeader
                :title="`${isEdit ? 'Modifier' : 'Créer'} un groupe`"
                eyebrow="Administration"
            >
                <template #actions>
                    <a :href="route('admin.groupes.index')"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">
                        <ArrowLeft class="h-4 w-4" />
                        Retour
                    </a>
                </template>
            </PageHeader>
            <form @submit.prevent="submit" class="rounded-xl border bg-card p-6 shadow-sm flex flex-col gap-5">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Nom <span class="text-red-500">*</span></label>
                    <input v-model="form.nom" type="text" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                    <p v-if="form.errors.nom" class="text-xs text-red-500">{{ form.errors.nom }}</p>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Code <span class="text-red-500">*</span></label>
                    <input v-model="form.code" type="text" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" placeholder="GRP001" />
                    <p v-if="form.errors.code" class="text-xs text-red-500">{{ form.errors.code }}</p>
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input v-model="form.is_active" type="checkbox" class="rounded" />
                    <span class="text-sm font-medium">Actif</span>
                </label>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a :href="route('admin.groupes.index')" class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors">Annuler</a>
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
