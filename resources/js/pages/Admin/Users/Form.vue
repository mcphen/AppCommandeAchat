<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type Entreprise, type Role, type User } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2 } from 'lucide-vue-next';

const props = defineProps<{
    user?: User;
    roles: Role[];
    entreprises: Entreprise[];
}>();

const isEdit = !!props.user;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Utilisateurs', href: '/admin/users' },
    { title: isEdit ? 'Modifier' : 'Créer', href: '#' },
];

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    password: '',
    password_confirmation: '',
    role_id: props.user?.role_id ?? '',
    entreprise_id: props.user?.entreprise_id ?? '',
    fonction: props.user?.fonction ?? '',
});

function submit() {
    if (isEdit) {
        form.put(route('admin.users.update', props.user!.id));
    } else {
        form.post(route('admin.users.store'));
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="isEdit ? 'Modifier l\'utilisateur' : 'Créer un utilisateur'" />

        <div class="flex w-full flex-col gap-6 p-6">
            <PageHeader
                :title="`${isEdit ? 'Modifier' : 'Créer'} un utilisateur`"
                eyebrow="Administration"
            >
                <template #actions>
                    <a :href="route('admin.users.index')"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-2 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">
                        <ArrowLeft class="h-4 w-4" />
                        Retour
                    </a>
                </template>
            </PageHeader>

            <form @submit.prevent="submit" class="rounded-xl border bg-card p-6 shadow-sm flex flex-col gap-5">
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Nom complet <span class="text-red-500">*</span></label>
                    <input v-model="form.name" type="text" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                    <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Email <span class="text-red-500">*</span></label>
                    <input v-model="form.email" type="email" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                    <p v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">{{ isEdit ? 'Nouveau mot de passe' : 'Mot de passe *' }}</label>
                        <input v-model="form.password" type="password" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                        <p v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</p>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Confirmer</label>
                        <input v-model="form.password_confirmation" type="password" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                    </div>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Rôle <span class="text-red-500">*</span></label>
                    <select v-model="form.role_id" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40">
                        <option value="">— Sélectionner —</option>
                        <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Entreprise</label>
                    <select v-model="form.entreprise_id" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40">
                        <option value="">— Aucune —</option>
                        <option v-for="e in entreprises" :key="e.id" :value="e.id">{{ e.nom }}</option>
                    </select>
                </div>

                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Fonction / Poste</label>
                    <input v-model="form.fonction" type="text" placeholder="Ex: Responsable achats" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                </div>

                <div class="flex items-center justify-end gap-3 pt-2">
                    <a :href="route('admin.users.index')" class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors">Annuler</a>
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
