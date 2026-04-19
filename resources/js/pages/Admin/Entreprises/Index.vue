<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type Entreprise } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, CheckCircle2, XCircle } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Entreprises', href: '/admin/entreprises' },
];

defineProps<{ entreprises: Entreprise[] }>();

function destroy(e: Entreprise) {
    Swal.fire({ title: 'Supprimer cette entreprise ?', text: e.nom, icon: 'warning', showCancelButton: true, confirmButtonText: 'Supprimer', cancelButtonText: 'Annuler', confirmButtonColor: '#ef4444' })
        .then(r => { if (r.isConfirmed) router.delete(route('admin.entreprises.destroy', e.id)); });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Entreprises" />
        <div class="flex flex-col gap-6 p-6">
            <PageHeader title="Entreprises" eyebrow="Administration">
                <template #actions>
                    <Link :href="route('admin.entreprises.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors">
                        <Plus class="h-4 w-4" /> Ajouter
                    </Link>
                </template>
            </PageHeader>
            <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Nom</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Code</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Groupe</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Utilisateurs</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Statut</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="e in entreprises" :key="e.id" class="hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ e.nom }}</td>
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ e.code }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ e.groupe?.nom }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ e.users_count ?? 0 }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="e.is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-muted text-muted-foreground'">
                                    <component :is="e.is_active ? CheckCircle2 : XCircle" class="h-3 w-3" />
                                    {{ e.is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <Link :href="route('admin.entreprises.edit', e.id)" class="rounded-md border p-1.5 hover:bg-muted transition-colors">
                                        <Pencil class="h-3.5 w-3.5" />
                                    </Link>
                                    <button @click="destroy(e)" class="rounded-md border border-red-200 p-1.5 text-red-500 hover:bg-red-50 transition-colors">
                                        <Trash2 class="h-3.5 w-3.5" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
