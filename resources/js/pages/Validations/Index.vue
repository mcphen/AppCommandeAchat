<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type PaginatedData, type PurchaseOrder, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Building2, Calendar, CheckSquare, Clock, Eye, FileText } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Validations', href: '/validations' },
];

defineProps<{
    orders: PaginatedData<PurchaseOrder>;
    boutiques: Boutique[];
    levelsCount: number;
    filters: {
        boutique_id?: string;
    };
}>();

const page = usePage<SharedData>();
const user = page.props.auth.user;

const formatAmount = (value: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(value));

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const progressLabel = (order: PurchaseOrder, levelsCount: number) => {
    if (order.status === 'pending' && order.current_level_order) {
        const completedLevels = Math.max(order.current_level_order - 1, 0);
        return `${completedLevels}/${levelsCount} niveau${levelsCount > 1 ? 'x' : ''} valide${completedLevels > 1 ? 's' : ''} - actuellement niveau ${order.current_level_order}`;
    }

    return 'En attente de traitement';
};

const applyBoutiqueFilter = (event: Event) => {
    const value = (event.target as HTMLSelectElement).value;

    router.get(route('validations.index'), { boutique_id: value || undefined }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const resetFilters = () => {
    router.get(route('validations.index'), {}, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};
</script>

<template>
    <Head title="Validations" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-xl font-bold text-foreground sm:text-2xl">Validations</h1>
                    <p class="mt-1 truncate text-sm text-muted-foreground">
                        <template v-if="user?.role?.slug === 'admin'">Toutes les commandes en attente</template>
                        <template v-else>Niveau - <span class="font-medium text-foreground">{{ user?.validation_level?.name }}</span></template>
                    </p>
                </div>
                <div v-if="orders.total > 0" class="flex shrink-0 items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 sm:px-4">
                    <Clock class="h-4 w-4 text-amber-600" />
                    <span class="text-sm font-semibold text-amber-700">{{ orders.total }}</span>
                    <span class="hidden text-sm font-semibold text-amber-700 sm:inline">en attente</span>
                </div>
            </div>

            <div class="rounded-2xl border bg-card p-4 shadow-sm">
                <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                    <div class="w-full max-w-sm">
                        <label class="mb-1.5 block text-sm font-medium text-foreground">Filtrer par boutique</label>
                        <select
                            :value="filters.boutique_id ?? ''"
                            class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-black transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                            @change="applyBoutiqueFilter"
                        >
                            <option value="">Toutes les boutiques</option>
                            <option v-for="boutique in boutiques" :key="boutique.id" :value="boutique.id">{{ boutique.name }}</option>
                        </select>
                    </div>
                    <button
                        v-if="filters.boutique_id"
                        type="button"
                        class="rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                        @click="resetFilters"
                    >
                        Reinitialiser
                    </button>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <template v-if="orders.data.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/30">
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Commande</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell sm:px-6">Demandeur</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:table-cell sm:px-6">Boutique</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Montant</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:table-cell sm:px-6">Soumise le</th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="order in orders.data" :key="order.id" class="transition-colors hover:bg-muted/20">
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 sm:flex">
                                                <FileText class="h-4 w-4 text-amber-600" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="max-w-[140px] truncate font-semibold text-foreground sm:max-w-xs">{{ order.title }}</p>
                                                <p class="mt-0.5 line-clamp-1 max-w-[140px] text-xs text-muted-foreground sm:max-w-xs">{{ order.description }}</p>
                                                <p class="mt-1 text-xs text-muted-foreground">{{ progressLabel(order, levelsCount) }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden px-4 py-4 md:table-cell sm:px-6">
                                        <span class="text-sm text-foreground">{{ order.user?.name }}</span>
                                    </td>
                                    <td class="hidden px-4 py-4 lg:table-cell sm:px-6">
                                        <div class="flex items-center gap-2">
                                            <Building2 class="h-4 w-4 text-muted-foreground" />
                                            <span class="text-sm text-foreground">{{ order.boutique?.name ?? 'Non renseignee' }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <span class="text-xs font-bold text-foreground sm:text-sm">{{ formatAmount(order.amount) }}</span>
                                    </td>
                                    <td class="hidden px-4 py-4 xl:table-cell sm:px-6">
                                        <div class="flex items-center gap-1.5 text-sm text-muted-foreground">
                                            <Calendar class="h-3.5 w-3.5 shrink-0" />
                                            {{ order.submitted_at ? formatDate(order.submitted_at) : '-' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-right sm:px-6">
                                        <Link
                                            :href="route('validations.show', order.id)"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 sm:gap-2 sm:px-4"
                                        >
                                            <Eye class="h-4 w-4" />
                                            <span class="hidden sm:inline">Examiner</span>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="orders.last_page > 1" class="flex flex-col items-center gap-3 border-t px-4 py-4 sm:flex-row sm:justify-between sm:px-6">
                        <p class="text-sm text-muted-foreground">{{ orders.from }}-{{ orders.to }} sur {{ orders.total }}</p>
                        <div class="flex flex-wrap items-center justify-center gap-1">
                            <Link
                                v-for="link in orders.links"
                                :key="link.label"
                                :href="link.url ?? '#'"
                                :class="[
                                    'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                                    link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted',
                                    !link.url ? 'pointer-events-none opacity-40' : '',
                                ]"
                            >
                                <span v-html="link.label" />
                            </Link>
                        </div>
                    </div>
                </template>

                <div v-else class="flex flex-col items-center justify-center py-12 text-center sm:py-16">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50">
                        <CheckSquare class="h-7 w-7 text-emerald-500" />
                    </div>
                    <h3 class="mb-2 font-semibold text-foreground">Aucune commande en attente</h3>
                    <p class="text-sm text-muted-foreground">Aucune commande ne correspond au filtre actuel.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
