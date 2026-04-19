<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type BudgetAnnuel, type ExpressionBesoin } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Paperclip, CheckCircle2, XCircle, Loader2, ArrowLeft } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    expression: ExpressionBesoin;
    budgets: BudgetAnnuel[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Comptabilité', href: '/compta' },
    { title: props.expression.reference, href: '#' },
];

const validerForm = useForm({
    budget_annuel_id: '',
    notes: '',
});

const rejeterForm = useForm({
    motif_rejet: '',
});

const showRejetModal = ref(false);

function formatMontant(v: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
}

function valider() {
    validerForm.post(route('compta.valider', props.expression.id));
}

function rejeter() {
    rejeterForm.post(route('compta.rejeter', props.expression.id), {
        onSuccess: () => { showRejetModal.value = false; },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`Traiter ${expression.reference}`" />

        <div class="flex w-full flex-col gap-6 p-6">
            <!-- Header -->
            <PageHeader
                :title="expression.objet"
                :subtitle="`Soumis par ${expression.user?.name ?? '-'} (${expression.entreprise?.nom ?? '-'}) · ${new Date(expression.created_at).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' })}`"
                :eyebrow="expression.reference"
            >
                <template #actions>
                    <a :href="route('compta.index')"
                        class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">
                        <ArrowLeft class="h-4 w-4" />
                        Retour
                    </a>
                </template>
            </PageHeader>

            <!-- Détail EB -->
            <div class="rounded-xl border bg-card p-6 shadow-sm grid grid-cols-2 gap-5">
                <div>
                    <p class="text-xs text-muted-foreground mb-1">Montant demandé</p>
                    <p class="text-2xl font-bold">{{ formatMontant(expression.montant) }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground mb-1">Bénéficiaire</p>
                    <p class="font-medium">{{ expression.beneficiaire ?? '—' }}</p>
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
            <div v-if="expression.attachments?.length" class="rounded-xl border bg-card p-5 shadow-sm">
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

            <!-- Formulaire de validation -->
            <form @submit.prevent="valider" class="rounded-xl border bg-card p-6 shadow-sm flex flex-col gap-5">
                <h2 class="font-semibold text-base">Transformer en DAP</h2>

                <!-- Rattachement budget -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Rattacher à un budget annuel (optionnel)</label>
                    <select v-model="validerForm.budget_annuel_id"
                        class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40 transition">
                        <option value="">— Aucun rattachement —</option>
                        <option v-for="b in budgets" :key="b.id" :value="b.id">
                            {{ b.libelle }} ({{ b.annee }}) — Disponible :
                            {{ formatMontant(b.montant_disponible ?? (b.montant_total - b.montant_consomme)) }}
                        </option>
                    </select>
                </div>

                <!-- Notes compta -->
                <div class="flex flex-col gap-1.5">
                    <label class="text-sm font-medium">Notes comptables</label>
                    <textarea v-model="validerForm.notes" rows="3"
                        class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40 transition resize-none"
                        placeholder="Observations, numéro d'imputation, etc."></textarea>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" :disabled="validerForm.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-5 py-2 text-sm font-medium text-white shadow hover:bg-emerald-700 disabled:opacity-60 transition-colors">
                        <Loader2 v-if="validerForm.processing" class="h-4 w-4 animate-spin" />
                        <CheckCircle2 v-else class="h-4 w-4" />
                        Valider et créer la DAP
                    </button>
                    <button type="button" @click="showRejetModal = true"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-5 py-2 text-sm font-medium text-red-600 hover:bg-red-100 transition-colors">
                        <XCircle class="h-4 w-4" />
                        Rejeter
                    </button>
                </div>
            </form>
        </div>

        <!-- Modal rejet -->
        <Teleport to="body">
            <div v-if="showRejetModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
                <div class="w-full max-w-md rounded-2xl bg-background p-6 shadow-xl">
                    <h3 class="text-lg font-semibold mb-4">Motif de rejet</h3>
                    <textarea v-model="rejeterForm.motif_rejet" rows="4"
                        class="w-full rounded-lg border bg-muted/30 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-red-400/40 transition resize-none"
                        placeholder="Expliquez la raison du rejet à l'employé..."></textarea>
                    <p v-if="rejeterForm.errors.motif_rejet" class="text-xs text-red-500 mt-1">{{ rejeterForm.errors.motif_rejet }}</p>
                    <div class="flex gap-3 mt-4">
                        <button @click="showRejetModal = false"
                            class="flex-1 rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors">
                            Annuler
                        </button>
                        <button @click="rejeter" :disabled="rejeterForm.processing"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60 transition-colors">
                            <Loader2 v-if="rejeterForm.processing" class="h-4 w-4 animate-spin" />
                            Confirmer le rejet
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
