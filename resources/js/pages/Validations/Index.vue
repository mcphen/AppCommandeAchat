<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData, type PurchaseOrder, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { CheckSquare, FileText, Eye, Clock, Calendar } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Validations', href: '/validations' },
];

defineProps<{
    orders: PaginatedData<PurchaseOrder>;
}>();

const page = usePage<SharedData>();
const user = page.props.auth.user;

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
</script>

<template>
    <Head title="Validations" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-xl font-bold text-foreground sm:text-2xl">Validations</h1>
                    <p class="text-sm text-muted-foreground mt-1 truncate">
                        <template v-if="user?.role?.slug === 'admin'">Toutes les commandes en attente</template>
                        <template v-else>Niveau — <span class="font-medium text-foreground">{{ user?.validation_level?.name }}</span></template>
                    </p>
                </div>
                <div v-if="orders.total > 0" class="flex shrink-0 items-center gap-2 rounded-xl bg-amber-50 border border-amber-200 px-3 py-2 sm:px-4">
                    <Clock class="h-4 w-4 text-amber-600" />
                    <span class="text-sm font-semibold text-amber-700">{{ orders.total }}</span>
                    <span class="hidden text-sm font-semibold text-amber-700 sm:inline">en attente</span>
                </div>
            </div>

            <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                <template v-if="orders.data.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/30">
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Commande</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:table-cell sm:px-6">Demandeur</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Montant</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell sm:px-6">Soumise le</th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Action</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="order in orders.data" :key="order.id" class="hover:bg-muted/20 transition-colors">
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="hidden h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-amber-50 sm:flex">
                                                <FileText class="h-4 w-4 text-amber-600" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-semibold text-foreground truncate max-w-[140px] sm:max-w-xs">{{ order.title }}</p>
                                                <p class="text-xs text-muted-foreground mt-0.5 line-clamp-1 max-w-[140px] sm:max-w-xs">{{ order.description }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="hidden px-4 py-4 sm:table-cell sm:px-6">
                                        <div class="flex items-center gap-2">
                                            <div class="flex h-7 w-7 items-center justify-center rounded-full bg-muted text-xs font-bold text-muted-foreground">
                                                {{ order.user?.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <span class="text-sm text-foreground truncate max-w-[100px]">{{ order.user?.name }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <span class="font-bold text-foreground text-xs sm:text-sm">{{ formatAmount(order.amount) }}</span>
                                    </td>
                                    <td class="hidden px-4 py-4 md:table-cell sm:px-6">
                                        <div class="flex items-center gap-1.5 text-sm text-muted-foreground">
                                            <Calendar class="h-3.5 w-3.5 shrink-0" />
                                            {{ order.submitted_at ? formatDate(order.submitted_at) : '—' }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-right sm:px-6">
                                        <Link
                                            :href="route('validations.show', order.id)"
                                            class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 transition-colors sm:gap-2 sm:px-4"
                                        >
                                            <Eye class="h-4 w-4" />
                                            <span class="hidden sm:inline">Examiner</span>
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="orders.last_page > 1" class="flex flex-col items-center gap-3 border-t px-4 py-4 sm:flex-row sm:justify-between sm:px-6">
                        <p class="text-sm text-muted-foreground">{{ orders.from }}–{{ orders.to }} sur {{ orders.total }}</p>
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

                <div v-else class="flex flex-col items-center justify-center py-12 text-center sm:py-16">
                    <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50">
                        <CheckSquare class="h-7 w-7 text-emerald-500" />
                    </div>
                    <h3 class="font-semibold text-foreground mb-2">Aucune commande en attente</h3>
                    <p class="text-sm text-muted-foreground">Toutes les commandes à votre niveau ont été traitées.</p>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
