<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData, type PurchaseOrder } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Plus, ShoppingCart, Eye, Pencil, Trash2, Send,
    FileText, ChevronLeft, ChevronRight, Search,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Mes commandes', href: '/purchase-orders' },
];

defineProps<{
    orders: PaginatedData<PurchaseOrder>;
}>();

const statusConfig = {
    draft:    { label: 'Brouillon',  classes: 'bg-slate-100 text-slate-700',    dot: 'bg-slate-400' },
    pending:  { label: 'En attente', classes: 'bg-amber-50 text-amber-700',      dot: 'bg-amber-500' },
    approved: { label: 'Approuvée',  classes: 'bg-emerald-50 text-emerald-700',  dot: 'bg-emerald-500' },
    rejected: { label: 'Refusée',    classes: 'bg-red-50 text-red-700',          dot: 'bg-red-500' },
} as const;

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const confirmDelete = async (order: PurchaseOrder) => {
    const result = await Swal.fire({
        title: 'Supprimer cette commande ?',
        text: `"${order.title}" sera définitivement supprimée.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) {
        router.delete(route('purchase-orders.destroy', order.id));
    }
};

const submitOrder = async (order: PurchaseOrder) => {
    const result = await Swal.fire({
        title: 'Soumettre à la validation ?',
        text: `La commande "${order.title}" sera envoyée pour approbation.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Soumettre',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) {
        router.post(route('purchase-orders.submit', order.id));
    }
};
</script>

<template>
    <Head title="Mes commandes" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-xl font-bold text-foreground sm:text-2xl">Mes commandes</h1>
                    <p class="text-sm text-muted-foreground mt-1">{{ orders.total }} commande{{ orders.total !== 1 ? 's' : '' }} au total</p>
                </div>
                <Link
                    :href="route('purchase-orders.create')"
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-primary px-3 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors sm:px-4"
                >
                    <Plus class="h-4 w-4" />
                    <span class="hidden sm:inline">Nouvelle commande</span>
                </Link>
            </div>

            <!-- Table -->
            <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                <template v-if="orders.data.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/30">
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Commande</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell sm:px-6">Montant</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Statut</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:table-cell sm:px-6">Date</th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr
                                    v-for="order in orders.data"
                                    :key="order.id"
                                    class="hover:bg-muted/20 transition-colors"
                                >
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-muted sm:flex">
                                                <FileText class="h-4 w-4 text-muted-foreground" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium text-foreground truncate max-w-[150px] sm:max-w-xs">{{ order.title }}</p>
                                                <p class="text-xs text-muted-foreground mt-0.5 line-clamp-1 max-w-[150px] sm:max-w-xs">{{ order.description }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden px-4 py-4 md:table-cell sm:px-6">
                                        <span class="font-semibold text-foreground">{{ formatAmount(order.amount) }}</span>
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium sm:px-2.5"
                                            :class="statusConfig[order.status]?.classes"
                                        >
                                            <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status]?.dot" />
                                            <span class="hidden sm:inline">{{ statusConfig[order.status]?.label }}</span>
                                        </span>
                                    </td>
                                    <td class="hidden px-4 py-4 text-sm text-muted-foreground lg:table-cell sm:px-6">
                                        {{ formatDate(order.created_at) }}
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center justify-end gap-1">
                                            <Link
                                                :href="route('purchase-orders.show', order.id)"
                                                class="rounded-lg p-2 hover:bg-muted transition-colors text-muted-foreground hover:text-foreground"
                                                title="Voir"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </Link>
                                            <template v-if="order.status === 'draft' || order.status === 'rejected'">
                                                <Link
                                                    :href="route('purchase-orders.edit', order.id)"
                                                    class="rounded-lg p-2 hover:bg-muted transition-colors text-muted-foreground hover:text-foreground"
                                                    title="Modifier"
                                                >
                                                    <Pencil class="h-4 w-4" />
                                                </Link>
                                                <button
                                                    @click="submitOrder(order)"
                                                    class="rounded-lg p-2 hover:bg-emerald-50 transition-colors text-muted-foreground hover:text-emerald-600"
                                                    title="Soumettre"
                                                >
                                                    <Send class="h-4 w-4" />
                                                </button>
                                                <button
                                                    @click="confirmDelete(order)"
                                                    class="rounded-lg p-2 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600"
                                                    title="Supprimer"
                                                >
                                                    <Trash2 class="h-4 w-4" />
                                                </button>
                                            </template>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="orders.last_page > 1" class="flex flex-col items-center gap-3 border-t px-4 py-4 sm:flex-row sm:justify-between sm:px-6">
                        <p class="text-sm text-muted-foreground">
                            {{ orders.from }}–{{ orders.to }} sur {{ orders.total }}
                        </p>
                        <div class="flex items-center gap-1 flex-wrap justify-center">
                            <Link
                                v-for="link in orders.links"
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

                <!-- État vide -->
                <div v-else class="flex flex-col items-center justify-center py-12 px-6 text-center sm:py-16">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                        <ShoppingCart class="h-7 w-7 text-muted-foreground" />
                    </div>
                    <h3 class="font-semibold text-foreground mb-2">Aucune commande</h3>
                    <p class="text-sm text-muted-foreground mb-6">Commencez par créer votre première commande d'achat</p>
                    <Link
                        :href="route('purchase-orders.create')"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors"
                    >
                        <Plus class="h-4 w-4" />
                        Nouvelle commande
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
