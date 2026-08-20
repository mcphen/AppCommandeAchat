<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Circuit } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { GitBranch, Layers, Pencil, Plus, Power, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Administration', href: '#' },
    { title: 'Circuits', href: '/admin/circuits' },
];

defineProps<{
    circuits: Circuit[];
}>();

const deleteCircuit = async (circuit: Circuit) => {
    const result = await Swal.fire({
        title: 'Supprimer ce circuit ?',
        text: `Le circuit "${circuit.name}" sera supprime s'il n'est plus utilise.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });

    if (result.isConfirmed) {
        router.delete(route('admin.circuits.destroy', circuit.id));
    }
};
</script>

<template>
    <Head title="Circuits" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-foreground sm:text-2xl">Circuits de validation</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{{ circuits.length }} circuit{{ circuits.length !== 1 ? 's' : '' }} configure{{ circuits.length !== 1 ? 's' : '' }}</p>
                </div>
                <Link
                    :href="route('admin.circuits.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Nouveau circuit
                </Link>
            </div>

            <div v-if="circuits.length > 0" class="grid gap-4 lg:grid-cols-2">
                <div
                    v-for="circuit in circuits"
                    :key="circuit.id"
                    class="rounded-2xl border bg-card p-5 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <div class="flex items-center gap-3">
                                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-blue-50">
                                    <GitBranch class="h-5 w-5 text-blue-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-base font-semibold text-foreground">{{ circuit.name }}</p>
                                    <p class="text-sm text-muted-foreground">{{ circuit.code }}</p>
                                </div>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <span
                                    class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="circuit.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-700'"
                                >
                                    <Power class="h-3.5 w-3.5" />
                                    {{ circuit.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-foreground">
                                    <Layers class="h-3.5 w-3.5" />
                                    {{ circuit.validation_levels_count }} niveau{{ circuit.validation_levels_count !== 1 ? 'x' : '' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            <Link
                                :href="route('admin.circuits.edit', circuit.id)"
                                class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                            >
                                <Pencil class="h-4 w-4" />
                            </Link>
                            <button
                                @click="deleteCircuit(circuit)"
                                class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-600"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <div class="mt-4 text-sm text-muted-foreground">
                        {{ circuit.purchase_orders_count }} commande{{ circuit.purchase_orders_count !== 1 ? 's' : '' }} rattachee{{ circuit.purchase_orders_count !== 1 ? 's' : '' }}
                    </div>
                </div>
            </div>

            <EmptyState
                v-else
                :icon="GitBranch"
                icon-bg="bg-blue-50"
                icon-color="text-blue-500"
                title="Aucun circuit"
                description="Créez un circuit (Achat, Prestation de service...) avant de lui configurer des niveaux de validation."
                :action-href="route('admin.circuits.create')"
                action-label="Créer le premier circuit"
            />
        </div>
    </AppLayout>
</template>
