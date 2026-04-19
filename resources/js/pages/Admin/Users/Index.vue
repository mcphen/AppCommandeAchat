<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type PaginatedData, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Utilisateurs', href: '/admin/users' },
];

defineProps<{ users: PaginatedData<User> }>();

function destroy(user: User) {
    Swal.fire({ title: 'Supprimer cet utilisateur ?', text: user.name, icon: 'warning', showCancelButton: true, confirmButtonText: 'Supprimer', cancelButtonText: 'Annuler', confirmButtonColor: '#ef4444' })
        .then(r => { if (r.isConfirmed) router.delete(route('admin.users.destroy', user.id)); });
}

const roleColors: Record<string, string> = {
    admin:      'bg-violet-100 text-violet-700',
    employe:    'bg-sky-100 text-sky-700',
    validateur: 'bg-emerald-100 text-emerald-700',
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Utilisateurs" />
        <div class="flex flex-col gap-6 p-6">
            <PageHeader
                title="Utilisateurs"
                :subtitle="`${users.total} utilisateur(s)`"
                eyebrow="Administration"
            >
                <template #actions>
                    <Link :href="route('admin.users.create')"
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
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Email</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Rôle</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Entreprise</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Fonction</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="user in users.data" :key="user.id" class="hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ user.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ user.email }}</td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium" :class="roleColors[user.role?.slug ?? '']">
                                    {{ user.role?.name }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">{{ user.entreprise?.nom ?? '—' }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ user.fonction ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <Link :href="route('admin.users.edit', user.id)"
                                        class="rounded-md border p-1.5 hover:bg-muted transition-colors">
                                        <Pencil class="h-3.5 w-3.5" />
                                    </Link>
                                    <button @click="destroy(user)"
                                        class="rounded-md border border-red-200 p-1.5 text-red-500 hover:bg-red-50 transition-colors">
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
