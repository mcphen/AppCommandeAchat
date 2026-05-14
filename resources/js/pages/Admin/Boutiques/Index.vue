<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, MapPin, Pencil, Plus, Power, Trash2, Users } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Administration', href: '/admin/users' },
    { title: 'Boutiques', href: '/admin/boutiques' },
];

defineProps<{
    boutiques: (Boutique & { users_count: number; purchase_orders_count: number })[];
}>();

const deleteBoutique = async (boutique: Boutique) => {
    const result = await Swal.fire({
        title: 'Supprimer cette boutique ?',
        text: `La boutique "${boutique.name}" sera supprimee si elle n'est plus utilisee.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });

    if (result.isConfirmed) {
        router.delete(route('admin.boutiques.destroy', boutique.id));
    }
};
</script>

<template>
    <Head title="Boutiques" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-foreground sm:text-2xl">Boutiques</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{{ boutiques.length }} boutique{{ boutiques.length !== 1 ? 's' : '' }} configuree{{ boutiques.length !== 1 ? 's' : '' }}</p>
                </div>
                <Link
                    :href="route('admin.boutiques.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Nouvelle boutique
                </Link>
            </div>

            <div v-if="boutiques.length > 0" class="grid gap-4 lg:grid-cols-2">
                <div
                    v-for="boutique in boutiques"
                    :key="boutique.id"
                    class="rounded-2xl border bg-card p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50">
                                    <Building2 class="h-5 w-5 text-blue-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-base font-semibold text-foreground">{{ boutique.name }}</p>
                                    <p class="text-sm text-muted-foreground">{{ boutique.code }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="boutique.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700'"
                                >
                                    <Power class="h-3.5 w-3.5" />
                                    {{ boutique.is_active ? 'Active' : 'Inactive' }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-foreground">
                                    <Users class="h-3.5 w-3.5" />
                                    {{ boutique.users_count }} utilisateur{{ boutique.users_count !== 1 ? 's' : '' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <Link
                                :href="route('admin.boutiques.edit', boutique.id)"
                                class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            >
                                <Pencil class="h-4 w-4" />
                            </Link>
                            <button
                                @click="deleteBoutique(boutique)"
                                class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-600"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 space-y-2 text-sm">
                        <p v-if="boutique.city || boutique.address" class="flex items-start gap-2 text-muted-foreground">
                            <MapPin class="mt-0.5 h-4 w-4 shrink-0" />
                            <span>
                                {{ boutique.address || 'Adresse non renseignee' }}
                                <template v-if="boutique.city">, {{ boutique.city }}</template>
                            </span>
                        </p>
                        <p class="text-muted-foreground">
                            {{ boutique.purchase_orders_count }} commande{{ boutique.purchase_orders_count !== 1 ? 's' : '' }} rattachee{{ boutique.purchase_orders_count !== 1 ? 's' : '' }}
                        </p>
                    </div>
                </div>
            </div>

            <EmptyState
                v-else
                :icon="Building2"
                icon-bg="bg-blue-50"
                icon-color="text-blue-500"
                title="Aucune boutique"
                description="Créez vos boutiques ou départements pour affecter les demandeurs et suivre les dépenses par entité."
                :action-href="route('admin.boutiques.create')"
                action-label="Créer la première boutique"
            />
        </div>
    </AppLayout>
</template>
