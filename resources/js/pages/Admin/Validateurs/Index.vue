<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type NiveauValidation, type User, type Validateur } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Trash2, Loader2, UserCheck } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Validateurs', href: '/admin/validateurs' },
];

defineProps<{
    validateurs: Validateur[];
    niveaux: NiveauValidation[];
    users: User[];
}>();

const form = useForm({
    user_id: '',
    niveau_validation_id: '',
});

function submit() {
    form.post(route('admin.validateurs.store'), { onSuccess: () => form.reset() });
}

function destroy(v: Validateur) {
    Swal.fire({ title: 'Retirer ce validateur ?', text: v.user?.name, icon: 'warning', showCancelButton: true, confirmButtonText: 'Retirer', cancelButtonText: 'Annuler', confirmButtonColor: '#ef4444' })
        .then(r => { if (r.isConfirmed) router.delete(route('admin.validateurs.destroy', v.id)); });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Validateurs" />
        <div class="flex w-full flex-col gap-6 p-6">
            <PageHeader title="Validateurs" eyebrow="Gouvernance" />

            <!-- Formulaire d'assignation -->
            <form @submit.prevent="submit" class="rounded-xl border bg-card p-5 shadow-sm flex flex-col gap-4">
                <h2 class="font-semibold text-sm">Assigner un validateur</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Utilisateur</label>
                        <select v-model="form.user_id" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="">— Sélectionner —</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Niveau</label>
                        <select v-model="form.niveau_validation_id" class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="">— Sélectionner —</option>
                            <option v-for="n in niveaux" :key="n.id" :value="n.id">{{ n.nom }}</option>
                        </select>
                    </div>
                </div>
                <div class="flex justify-end">
                    <button type="submit" :disabled="form.processing || !form.user_id || !form.niveau_validation_id"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60 transition-colors">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <UserCheck v-else class="h-4 w-4" />
                        Assigner
                    </button>
                </div>
            </form>

            <!-- Liste des validateurs -->
            <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Utilisateur</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Niveau</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-if="validateurs.length === 0">
                            <td colspan="4" class="px-4 py-10 text-center text-muted-foreground text-sm">Aucun validateur assigné</td>
                        </tr>
                        <tr v-for="v in validateurs" :key="v.id" class="hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ v.user?.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ v.user?.email }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full bg-emerald-100 text-emerald-700 px-2.5 py-1 text-xs font-medium">
                                    {{ v.niveau_validation?.nom }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <button @click="destroy(v)" class="rounded-md border border-red-200 p-1.5 text-red-500 hover:bg-red-50 transition-colors">
                                    <Trash2 class="h-3.5 w-3.5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
