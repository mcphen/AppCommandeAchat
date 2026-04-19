<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type BudgetAnnuel, type PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2 } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Budgets annuels', href: '/admin/budgets-annuels' },
];

defineProps<{ budgets: PaginatedData<BudgetAnnuel> }>();

function destroy(b: BudgetAnnuel) {
    Swal.fire({ title: 'Supprimer ce budget ?', text: b.libelle, icon: 'warning', showCancelButton: true, confirmButtonText: 'Supprimer', cancelButtonText: 'Annuler', confirmButtonColor: '#ef4444' })
        .then(r => { if (r.isConfirmed) router.delete(route('admin.budgets-annuels.destroy', b.id)); });
}

function formatMontant(v: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Budgets annuels" />
        <div class="flex flex-col gap-6 p-6">
            <PageHeader title="Budgets annuels" eyebrow="Administration financière">
                <template #actions>
                    <Link :href="route('admin.budgets-annuels.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors">
                        <Plus class="h-4 w-4" /> Ajouter
                    </Link>
                </template>
            </PageHeader>

            <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Libellé</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Entreprise</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Année</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Total</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Consommé</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Disponible</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="b in budgets.data" :key="b.id" class="hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-3 font-medium">{{ b.libelle }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ b.entreprise?.nom }}</td>
                            <td class="px-4 py-3">{{ b.annee }}</td>
                            <td class="px-4 py-3 font-semibold">{{ formatMontant(b.montant_total) }}</td>
                            <td class="px-4 py-3 text-amber-600">{{ formatMontant(b.montant_consomme) }}</td>
                            <td class="px-4 py-3">
                                <span :class="(b.montant_total - b.montant_consomme) < 0 ? 'text-red-500 font-semibold' : 'text-emerald-600 font-semibold'">
                                    {{ formatMontant(b.montant_total - b.montant_consomme) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <Link :href="route('admin.budgets-annuels.edit', b.id)" class="rounded-md border p-1.5 hover:bg-muted transition-colors">
                                        <Pencil class="h-3.5 w-3.5" />
                                    </Link>
                                    <button @click="destroy(b)" class="rounded-md border border-red-200 p-1.5 text-red-500 hover:bg-red-50 transition-colors">
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
