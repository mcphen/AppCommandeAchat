<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BudgetAnnuel, type BreadcrumbItem, type Entreprise } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2 } from 'lucide-vue-next';

const props = defineProps<{ budget?: BudgetAnnuel; entreprises: Entreprise[] }>();
const isEdit = !!props.budget;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Budgets annuels', href: '/admin/budgets-annuels' },
    { title: isEdit ? 'Modifier' : 'Créer', href: '#' },
];

const form = useForm({
    entreprise_id: props.budget?.entreprise_id ?? '',
    annee: props.budget?.annee ?? new Date().getFullYear(),
    libelle: props.budget?.libelle ?? '',
    montant_total: props.budget?.montant_total ?? '',
    is_active: props.budget?.is_active ?? true,
});

function submit() {
    if (isEdit) form.put(route('admin.budgets-annuels.update', props.budget!.id));
    else form.post(route('admin.budgets-annuels.store'));
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="isEdit ? 'Modifier le budget' : 'Créer un budget'" />
        <div class="flex w-full flex-col gap-6 p-6">
            <PageHeader
                :title="`${isEdit ? 'Modifier' : 'Créer'} un budget annuel`"
                eyebrow="Administration financière"
            >
                <template #actions>
                    <a :href="route('admin.budgets-annuels.index')"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">
                        <ArrowLeft class="h-4 w-4" />
                        Retour
                    </a>
                </template>
            </PageHeader>
            <form @submit.prevent="submit" class="rounded-xl border bg-card p-6 shadow-sm flex flex-col gap-5">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Entreprise <span class="text-red-500">*</span></label>
                    <select v-model="form.entreprise_id" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40">
                        <option value="">— Sélectionner —</option>
                        <option v-for="e in entreprises" :key="e.id" :value="e.id">{{ e.nom }}</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Année <span class="text-red-500">*</span></label>
                        <input v-model="form.annee" type="number" min="2020" max="2099" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Montant total (FCFA) <span class="text-red-500">*</span></label>
                        <input v-model="form.montant_total" type="number" min="0" step="1000" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                    </div>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Libellé <span class="text-red-500">*</span></label>
                    <input v-model="form.libelle" type="text" placeholder="Ex: Budget opérationnel 2026" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                </div>
                <label class="flex items-center gap-2 cursor-pointer">
                    <input v-model="form.is_active" type="checkbox" class="rounded" />
                    <span class="text-sm font-medium">Actif</span>
                </label>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <a :href="route('admin.budgets-annuels.index')" class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors">Annuler</a>
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
