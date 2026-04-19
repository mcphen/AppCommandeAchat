<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type DemandeAutorisationPaiement, type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { CheckCircle2, XCircle, Loader2, Paperclip, CreditCard, Download, ArrowLeft } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ dap: DemandeAutorisationPaiement }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Validations DAP', href: '/validations-dap' },
    { title: props.dap.reference, href: '#' },
];

const page = usePage<SharedData>();
const isAdmin = computed(() => page.props.auth.user?.role?.slug === 'admin');
const canPay  = computed(() => props.dap.statut === 'validee' && !props.dap.paiement && isAdmin.value);

const approuverForm = useForm({ commentaire: '' });
const rejeterForm   = useForm({ commentaire: '' });
const paiementForm  = useForm({
    montant: props.dap.expression_besoin?.montant ?? '',
    date_paiement: new Date().toISOString().split('T')[0],
    mode_paiement: 'virement',
    reference: '',
    banque: '',
    notes: '',
});

const showRejetModal   = ref(false);
const showPaiementForm = ref(false);

function formatMontant(v: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
}

const statutDap: Record<string, { label: string; class: string }> = {
    en_cours: { label: 'En cours de validation', class: 'bg-amber-100 text-amber-700' },
    validee:  { label: 'Entièrement validée', class: 'bg-emerald-100 text-emerald-700' },
    rejetee:  { label: 'Rejetée', class: 'bg-red-100 text-red-700' },
    payee:    { label: 'Payée', class: 'bg-blue-100 text-blue-700' },
};

const canValidate = computed(() => {
    const niveauUser = page.props.auth.user?.niveau_validation;
    if (!niveauUser || props.dap.statut !== 'en_cours') return false;
    return !props.dap.validations?.some(v => v.niveau_validation_id === niveauUser.id);
});

function approuver() {
    approuverForm.post(route('validations-dap.approuver', props.dap.id));
}

function rejeter() {
    rejeterForm.post(route('validations-dap.rejeter', props.dap.id), {
        onSuccess: () => { showRejetModal.value = false; },
    });
}

function payerDap() {
    paiementForm.post(route('paiements.store', props.dap.id), {
        onSuccess: () => { showPaiementForm.value = false; },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="dap.reference" />

        <div class="flex w-full flex-col gap-6 p-6">
            <!-- Header -->
            <PageHeader
                :title="dap.expression_besoin?.objet ?? dap.reference"
                :subtitle="`${dap.expression_besoin?.user?.name ?? '-'} · ${dap.expression_besoin?.entreprise?.nom ?? '-'}`"
                :eyebrow="dap.reference"
            >
                <template #actions>
                    <a :href="route('validations-dap.index')"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">
                        <ArrowLeft class="h-3.5 w-3.5" />
                        Retour
                    </a>
                    <span class="inline-flex items-center rounded-xl px-3 py-1.5 text-sm font-medium"
                        :class="statutDap[dap.statut]?.class">
                        {{ statutDap[dap.statut]?.label }}
                    </span>
                    <a :href="route('dap.pdf', dap.id)" target="_blank"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm font-medium hover:bg-muted transition-colors text-muted-foreground">
                        <Download class="h-3.5 w-3.5" />
                        PDF
                    </a>
                </template>
            </PageHeader>

            <!-- Layout 2 colonnes -->
            <div class="grid grid-cols-4 gap-6 items-start">

            <!-- Colonne principale (col-span-3) -->
            <div class="col-span-4 lg:col-span-3 flex flex-col gap-6">

            <!-- Infos -->
            <div class="rounded-xl border bg-card p-6 shadow-sm grid grid-cols-2 gap-5">
                <div>
                    <p class="text-xs text-muted-foreground mb-1">Montant</p>
                    <p class="text-2xl font-bold">{{ formatMontant(dap.expression_besoin?.montant ?? 0) }}</p>
                </div>
                <div>
                    <p class="text-xs text-muted-foreground mb-1">Budget rattaché</p>
                    <p class="font-medium">{{ dap.budget_annuel?.libelle ?? '—' }}</p>
                </div>
                <div class="col-span-2">
                    <p class="text-xs text-muted-foreground mb-1">Description</p>
                    <p class="text-sm whitespace-pre-wrap">{{ dap.expression_besoin?.description }}</p>
                </div>
                <div v-if="dap.notes" class="col-span-2">
                    <p class="text-xs text-muted-foreground mb-1">Notes comptables</p>
                    <p class="text-sm whitespace-pre-wrap text-muted-foreground">{{ dap.notes }}</p>
                </div>
            </div>

            <!-- Pièces jointes -->
            <div v-if="dap.expression_besoin?.attachments?.length" class="rounded-xl border bg-card p-5 shadow-sm">
                <p class="text-sm font-semibold mb-3">Pièces jointes</p>
                <div class="flex flex-col gap-2">
                    <a v-for="att in dap.expression_besoin.attachments" :key="att.id"
                        :href="route('eb-attachments.download', att.id)"
                        class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm hover:bg-muted transition-colors">
                        <Paperclip class="h-4 w-4 text-muted-foreground" />
                        {{ att.nom_fichier }}
                    </a>
                </div>
            </div>

            <!-- Circuit de validation -->
            <div class="rounded-xl border bg-card p-5 shadow-sm">
                <p class="text-sm font-semibold mb-4">Circuit de validation</p>
                <div class="flex flex-col gap-3">
                    <div v-for="v in dap.validations" :key="v.id"
                        class="flex items-center justify-between rounded-lg border p-3"
                        :class="v.statut === 'approuve' ? 'border-emerald-200 bg-emerald-50' : 'border-red-200 bg-red-50'">
                        <div class="flex items-center gap-3">
                            <component :is="v.statut === 'approuve' ? CheckCircle2 : XCircle" class="h-5 w-5"
                                :class="v.statut === 'approuve' ? 'text-emerald-600' : 'text-red-500'" />
                            <div>
                                <p class="text-sm font-medium">{{ v.niveau_validation?.nom }}</p>
                                <p class="text-xs text-muted-foreground">{{ v.validateur?.name }} · {{ new Date(v.validated_at).toLocaleDateString('fr-FR') }}</p>
                                <p v-if="v.commentaire" class="text-xs italic text-muted-foreground mt-0.5">{{ v.commentaire }}</p>
                            </div>
                        </div>
                    </div>
                    <p v-if="!dap.validations?.length" class="text-sm text-muted-foreground">Aucune validation enregistrée</p>
                </div>
            </div>

            <!-- Paiement -->
            <div v-if="canPay" class="rounded-xl border bg-card p-5 shadow-sm">
                <div v-if="!showPaiementForm" class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold">DAP entièrement validée</p>
                        <p class="text-sm text-muted-foreground mt-0.5">Vous pouvez maintenant enregistrer le paiement.</p>
                    </div>
                    <button @click="showPaiementForm = true"
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 transition-colors">
                        <CreditCard class="h-4 w-4" />
                        Enregistrer le paiement
                    </button>
                </div>

                <form v-else @submit.prevent="payerDap" class="flex flex-col gap-4">
                    <h3 class="font-semibold">Enregistrement du paiement</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium">Montant</label>
                            <input v-model="paiementForm.montant" type="number" step="1"
                                class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium">Date de paiement</label>
                            <input v-model="paiementForm.date_paiement" type="date"
                                class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium">Mode de paiement</label>
                            <select v-model="paiementForm.mode_paiement"
                                class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40">
                                <option value="virement">Virement</option>
                                <option value="cheque">Chèque</option>
                                <option value="espece">Espèces</option>
                                <option value="mobile_money">Mobile Money</option>
                            </select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium">Référence</label>
                            <input v-model="paiementForm.reference" type="text"
                                class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40"
                                placeholder="N° virement / chèque..." />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium">Banque</label>
                            <input v-model="paiementForm.banque" type="text"
                                class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                        </div>
                        <div class="col-span-2 flex flex-col gap-1.5">
                            <label class="text-sm font-medium">Notes</label>
                            <textarea v-model="paiementForm.notes" rows="2"
                                class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40 resize-none" />
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="submit" :disabled="paiementForm.processing"
                            class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-5 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-60 transition-colors">
                            <Loader2 v-if="paiementForm.processing" class="h-4 w-4 animate-spin" />
                            Confirmer le paiement
                        </button>
                        <button type="button" @click="showPaiementForm = false"
                            class="rounded-lg border px-4 py-2 text-sm hover:bg-muted transition-colors">
                            Annuler
                        </button>
                    </div>
                </form>
            </div>

            <!-- Paiement enregistré -->
            <div v-if="dap.paiement" class="rounded-xl border border-blue-200 bg-blue-50 p-5">
                <p class="font-semibold text-blue-700 mb-2">Paiement effectué</p>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div><span class="text-muted-foreground">Montant : </span><strong>{{ formatMontant(dap.paiement.montant) }}</strong></div>
                    <div><span class="text-muted-foreground">Date : </span>{{ new Date(dap.paiement.date_paiement).toLocaleDateString('fr-FR') }}</div>
                    <div><span class="text-muted-foreground">Mode : </span>{{ dap.paiement.mode_paiement }}</div>
                    <div v-if="dap.paiement.reference"><span class="text-muted-foreground">Réf : </span>{{ dap.paiement.reference }}</div>
                </div>
            </div>

            </div><!-- fin col principale -->

            <!-- Sidebar : Votre décision (col-span-1) -->
            <div class="col-span-4 lg:col-span-1 flex flex-col gap-4">

            <!-- Actions validation -->
            <div v-if="canValidate" class="rounded-xl border bg-card p-5 shadow-sm flex flex-col gap-4">
                <h3 class="font-semibold">Votre décision</h3>
                <div>
                    <label class="text-sm font-medium">Commentaire (optionnel)</label>
                    <textarea v-model="approuverForm.commentaire" rows="3"
                        class="mt-1 w-full rounded-lg border bg-muted/30 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40 resize-none transition" />
                </div>
                <div class="flex flex-col gap-2">
                    <button @click="approuver" :disabled="approuverForm.processing"
                        class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60 transition-colors">
                        <Loader2 v-if="approuverForm.processing" class="h-4 w-4 animate-spin" />
                        <CheckCircle2 v-else class="h-4 w-4" />
                        Approuver
                    </button>
                    <button @click="showRejetModal = true"
                        class="inline-flex items-center justify-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-100 transition-colors">
                        <XCircle class="h-4 w-4" />
                        Rejeter
                    </button>
                </div>
            </div>

            </div><!-- fin sidebar -->
            </div><!-- fin grid -->
        </div><!-- fin flex flex-col p-6 -->

        <!-- Modal rejet -->
        <Teleport to="body">
            <div v-if="showRejetModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
                <div class="w-full max-w-md rounded-2xl bg-background p-6 shadow-xl">
                    <h3 class="text-lg font-semibold mb-4">Motif de rejet</h3>
                    <textarea v-model="rejeterForm.commentaire" rows="4"
                        class="w-full rounded-lg border bg-muted/30 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-red-400/40 resize-none"
                        placeholder="Expliquez la raison du rejet..."></textarea>
                    <p v-if="rejeterForm.errors.commentaire" class="text-xs text-red-500 mt-1">{{ rejeterForm.errors.commentaire }}</p>
                    <div class="flex gap-3 mt-4">
                        <button @click="showRejetModal = false"
                            class="flex-1 rounded-lg border px-4 py-2 text-sm hover:bg-muted transition-colors">
                            Annuler
                        </button>
                        <button @click="rejeter" :disabled="rejeterForm.processing"
                            class="flex-1 inline-flex items-center justify-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 disabled:opacity-60 transition-colors">
                            <Loader2 v-if="rejeterForm.processing" class="h-4 w-4 animate-spin" />
                            Confirmer
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </AppLayout>
</template>
