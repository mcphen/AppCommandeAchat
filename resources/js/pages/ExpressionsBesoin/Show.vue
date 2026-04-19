<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type ExpressionBesoin } from '@/types';
import { Head } from '@inertiajs/vue3';
import { Paperclip, CheckCircle2, XCircle, Clock, ArrowRight, ArrowLeft } from 'lucide-vue-next';

const props = defineProps<{ expression: ExpressionBesoin }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Expressions de besoin', href: '/expressions-besoin' },
    { title: props.expression.reference, href: '#' },
];

const statutConfig: Record<string, { label: string; class: string; icon: any }> = {
    en_attente: { label: 'En attente de validation comptable', class: 'bg-amber-50 border-amber-200 text-amber-700', icon: Clock },
    validee:    { label: 'Validée — DAP créée',               class: 'bg-emerald-50 border-emerald-200 text-emerald-700', icon: CheckCircle2 },
    rejetee:    { label: 'Rejetée',                           class: 'bg-red-50 border-red-200 text-red-700', icon: XCircle },
};

function formatMontant(v: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="expression.reference" />

        <div class="flex w-full flex-col gap-6 p-6">
            <!-- Header -->
            <PageHeader
                :title="expression.objet"
                :subtitle="`Soumis par ${expression.user?.name ?? '-'} · ${new Date(expression.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}`"
                :eyebrow="expression.reference"
            >
                <template #actions>
                    <a :href="route('expressions-besoin.index')"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">
                        <ArrowLeft class="h-4 w-4" />
                        Retour
                    </a>
                    <span class="inline-flex items-center gap-2 rounded-xl border px-3 py-1.5 text-sm font-medium"
                        :class="statutConfig[expression.statut]?.class">
                        <component :is="statutConfig[expression.statut]?.icon" class="h-4 w-4" />
                        {{ statutConfig[expression.statut]?.label }}
                    </span>
                </template>
            </PageHeader>

            <!-- Motif rejet -->
            <div v-if="expression.statut === 'rejetee' && expression.motif_rejet"
                class="rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-semibold text-red-700 mb-1">Motif du rejet</p>
                <p class="text-sm text-red-600">{{ expression.motif_rejet }}</p>
            </div>

            <!-- Infos principales -->
            <div class="rounded-xl border bg-card p-6 shadow-sm grid grid-cols-2 gap-6">
                <div>
                    <p class="text-xs text-muted-foreground mb-1">Montant demandé</p>
                    <p class="text-2xl font-bold text-foreground">{{ formatMontant(expression.montant) }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground mb-1">Bénéficiaire</p>
                    <p class="font-medium">{{ expression.beneficiaire ?? '—' }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-xs text-muted-foreground mb-1">Entreprise</p>
                    <p class="font-medium">{{ expression.entreprise?.nom }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-xs text-muted-foreground mb-1">Description</p>
                    <p class="text-sm whitespace-pre-wrap">{{ expression.description }}</p>
                </div>
                <div v-if="expression.justification" class="col-span-2">
                    <p class="text-xs text-muted-foreground mb-1">Justification</p>
                    <p class="text-sm whitespace-pre-wrap text-muted-foreground">{{ expression.justification }}</p>
                </div>
            </div>

            <!-- Pièces jointes -->
            <div v-if="expression.attachments && expression.attachments.length > 0" class="rounded-xl border bg-card p-5 shadow-sm">
                <p class="text-sm font-semibold mb-3">Pièces jointes</p>
                <div class="flex flex-col gap-2">
                    <a v-for="att in expression.attachments" :key="att.id"
                        :href="route('eb-attachments.download', att.id)"
                        class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm hover:bg-muted transition-colors">
                        <Paperclip class="h-4 w-4 text-muted-foreground" />
                        {{ att.nom_fichier }}
                    </a>
                </div>
            </div>

            <!-- DAP liée -->
            <div v-if="expression.dap" class="rounded-xl border bg-card p-5 shadow-sm">
                <p class="text-sm font-semibold mb-3">Demande d'autorisation de paiement créée</p>
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-mono text-sm text-muted-foreground">{{ expression.dap.reference }}</p>
                        <div class="mt-2 flex gap-2">
                            <span v-for="v in expression.dap.validations" :key="v.id"
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="v.statut === 'approuve' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'">
                                {{ v.niveau_validation?.nom }}
                                <component :is="v.statut === 'approuve' ? CheckCircle2 : XCircle" class="h-3 w-3" />
                            </span>
                        </div>
                    </div>
                    <a :href="route('validations-dap.show', expression.dap.id)"
                        class="inline-flex items-center gap-1 rounded-lg border px-3 py-1.5 text-sm font-medium hover:bg-muted transition-colors">
                        Voir la DAP <ArrowRight class="h-3.5 w-3.5" />
                    </a>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
