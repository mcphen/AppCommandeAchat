<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type PaginatedData, type Paiement } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { CreditCard } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Paiements', href: '/paiements' },
];

defineProps<{
    paiements: PaginatedData<Paiement & {
        dap?: {
            id: number;
            reference: string;
            expression_besoin?: {
                objet?: string | null;
                entreprise?: { nom?: string | null } | null;
                user?: { name?: string | null } | null;
            } | null;
        } | null;
    }>;
}>();

function formatMontant(v: number | string) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'XOF',
        maximumFractionDigits: 0,
    }).format(Number(v));
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('fr-FR');
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Paiements" />

        <div class="flex flex-col gap-6 p-6">
            <PageHeader
                title="Paiements enregistres"
                :subtitle="`${paiements.total} paiement(s)`"
                eyebrow="Trésorerie"
            />

            <div class="overflow-hidden rounded-xl border bg-card shadow-sm">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">DAP</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Objet</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Entreprise</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Saisi par</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Montant</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Date</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Mode</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-border">
                        <tr v-if="paiements.data.length === 0">
                            <td colspan="8" class="px-4 py-16 text-center text-muted-foreground">
                                <CreditCard class="mx-auto mb-3 h-10 w-10 opacity-30" />
                                <p>Aucun paiement enregistre.</p>
                            </td>
                        </tr>

                        <tr
                            v-for="paiement in paiements.data"
                            :key="paiement.id"
                            class="transition-colors hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground">
                                {{ paiement.dap?.reference ?? '-' }}
                            </td>
                            <td class="max-w-xs truncate px-4 py-3 font-medium">
                                {{ paiement.dap?.expression_besoin?.objet ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ paiement.dap?.expression_besoin?.entreprise?.nom ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ paiement.saisie_par?.name ?? '-' }}
                            </td>
                            <td class="px-4 py-3 font-semibold">{{ formatMontant(paiement.montant) }}</td>
                            <td class="px-4 py-3 text-xs text-muted-foreground">
                                {{ formatDate(paiement.date_paiement) }}
                            </td>
                            <td class="px-4 py-3 capitalize text-muted-foreground">
                                {{ paiement.mode_paiement.replace('_', ' ') }}
                            </td>
                            <td class="px-4 py-3">
                                <Link
                                    :href="route('validations-dap.show', paiement.dap_id)"
                                    class="inline-flex items-center gap-1 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground transition-colors hover:bg-primary/90"
                                >
                                    Voir DAP
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="paiements.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in paiements.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        class="rounded-lg border px-3 py-1.5 text-sm transition-colors"
                        :class="
                            link.active
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border hover:bg-muted'
                        "
                        v-html="link.label"
                    />
                    <span v-else class="px-3 py-1.5 text-sm text-muted-foreground" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
