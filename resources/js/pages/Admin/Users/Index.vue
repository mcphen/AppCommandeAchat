<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Users, Shield, CheckSquare, ShoppingCart } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Administration', href: '#' },
    { title: 'Utilisateurs', href: '/admin/users' },
];

defineProps<{ users: PaginatedData<User> }>();

const roleConfig = {
    admin:      { label: 'Admin',      classes: 'bg-violet-50 text-violet-700',  icon: Shield },
    demandeur:  { label: 'Demandeur',  classes: 'bg-blue-50 text-blue-700',      icon: ShoppingCart },
    validateur: { label: 'Validateur', classes: 'bg-emerald-50 text-emerald-700', icon: CheckSquare },
} as const;

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const deleteUser = async (user: User) => {
    const result = await Swal.fire({
        title: 'Supprimer cet utilisateur ?',
        text: `L'utilisateur "${user.name}" sera définitivement supprimé.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) {
        router.delete(route('admin.users.destroy', user.id));
    }
};
</script>

<template>
    <Head title="Utilisateurs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-xl font-bold text-foreground sm:text-2xl">Utilisateurs</h1>
                    <p class="text-sm text-muted-foreground mt-1">{{ users.total }} utilisateur{{ users.total !== 1 ? 's' : '' }}</p>
                </div>
                <Link
                    :href="route('admin.users.create')"
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-primary px-3 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors sm:px-4"
                >
                    <Plus class="h-4 w-4" />
                    <span class="hidden sm:inline">Nouvel utilisateur</span>
                </Link>
            </div>

            <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                <template v-if="users.data.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/30">
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Utilisateur</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Rôle</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell sm:px-6">Niveau de validation</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:table-cell sm:px-6">Créé le</th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="user in users.data" :key="user.id" class="hover:bg-muted/20 transition-colors">
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary/20 to-primary/40 text-sm font-bold text-primary sm:h-9 sm:w-9">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-foreground truncate max-w-[120px] sm:max-w-none">{{ user.name }}</p>
                                                <p class="text-xs text-muted-foreground truncate max-w-[120px] sm:max-w-none">{{ user.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <span
                                            v-if="user.role"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium sm:px-2.5"
                                            :class="roleConfig[user.role.slug as keyof typeof roleConfig]?.classes ?? 'bg-muted text-muted-foreground'"
                                        >
                                            {{ user.role.name }}
                                        </span>
                                        <span v-else class="text-muted-foreground text-xs">—</span>
                                    </td>
                                    <td class="hidden px-4 py-4 md:table-cell sm:px-6">
                                        <span v-if="user.validation_level" class="text-sm text-foreground">
                                            {{ user.validation_level.name }}
                                            <span class="text-xs text-muted-foreground">(N{{ user.validation_level.order }})</span>
                                        </span>
                                        <span v-else class="text-muted-foreground text-xs">—</span>
                                    </td>
                                    <td class="hidden px-4 py-4 text-sm text-muted-foreground lg:table-cell sm:px-6">
                                        {{ formatDate(user.created_at) }}
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center justify-end gap-1">
                                            <Link
                                                :href="route('admin.users.edit', user.id)"
                                                class="rounded-lg p-2 hover:bg-muted transition-colors text-muted-foreground hover:text-foreground"
                                            >
                                                <Pencil class="h-4 w-4" />
                                            </Link>
                                            <button
                                                @click="deleteUser(user)"
                                                class="rounded-lg p-2 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="users.last_page > 1" class="flex flex-col items-center gap-3 border-t px-4 py-4 sm:flex-row sm:justify-between sm:px-6">
                        <p class="text-sm text-muted-foreground">{{ users.from }}–{{ users.to }} sur {{ users.total }}</p>
                        <div class="flex items-center gap-1 flex-wrap justify-center">
                            <Link
                                v-for="link in users.links"
                                :key="link.label"
                                :href="link.url ?? '#'"
                                :class="[
                                    'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                                    link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-muted text-muted-foreground',
                                    !link.url ? 'pointer-events-none opacity-40' : '',
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </template>

                <div v-else class="flex flex-col items-center justify-center py-12 sm:py-16">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                        <Users class="h-7 w-7 text-muted-foreground" />
                    </div>
                    <h3 class="font-semibold text-foreground mb-2">Aucun utilisateur</h3>
                    <Link :href="route('admin.users.create')" class="text-sm text-primary hover:underline">Créer le premier utilisateur</Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
