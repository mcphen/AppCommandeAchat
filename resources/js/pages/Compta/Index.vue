<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type ExpressionBesoin, type PaginatedData } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Eye, ClipboardList, AlertCircle } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Comptabilité', href: '/compta' },
];

defineProps<{
    expressions: PaginatedData<ExpressionBesoin>;
}>();

function formatMontant(v: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Comptabilité — Expressions en attente" />

        <div class="flex flex-col gap-6 p-6">
            <PageHeader
                title="Expressions de besoin à traiter"
                :subtitle="`${expressions.total} en attente de validation comptable`"
                eyebrow="Comptabilité"
            />

            <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Référence</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Objet</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Demandeur</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Entreprise</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Montant</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-if="expressions.data.length === 0">
                            <td colspan="7" class="px-4 py-16 text-center text-muted-foreground">
                                <ClipboardList class="mx-auto h-10 w-10 mb-3 opacity-30" />
                                <p>Aucune expression de besoin en attente</p>
                            </td>
                        </tr>
                        <tr v-for="eb in expressions.data" :key="eb.id" class="hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs text-muted-foreground">{{ eb.reference }}</td>
                            <td class="px-4 py-3 font-medium max-w-xs truncate">{{ eb.objet }}</td>
                            <td class="px-4 py-3">{{ eb.user?.name }}</td>
                            <td class="px-4 py-3 text-muted-foreground">{{ eb.entreprise?.nom }}</td>
                            <td class="px-4 py-3 font-semibold">{{ formatMontant(eb.montant) }}</td>
                            <td class="px-4 py-3 text-muted-foreground text-xs">
                                {{ new Date(eb.created_at).toLocaleDateString('fr-FR') }}
                            </td>
                            <td class="px-4 py-3">
                                <Link :href="route('compta.show', eb.id)"
                                    class="inline-flex items-center gap-1 rounded-md bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 transition-colors">
                                    <Eye class="h-3.5 w-3.5" />
                                    Traiter
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="expressions.last_page > 1" class="flex items-center justify-center gap-1">
                <template v-for="link in expressions.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url"
                        class="px-3 py-1.5 rounded-lg text-sm border transition-colors"
                        :class="link.active ? 'bg-primary text-primary-foreground border-primary' : 'hover:bg-muted border-border'"
                        v-html="link.label" />
                    <span v-else class="px-3 py-1.5 text-sm text-muted-foreground" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
