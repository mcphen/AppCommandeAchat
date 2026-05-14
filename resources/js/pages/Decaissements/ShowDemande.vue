<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type DisbursementRequest, type ModeReglement } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft, Banknote, Building2, CalendarDays, CheckCircle2,
    CreditCard, FileDown, Loader2, Smartphone, Tag, User, Wallet,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ demande: DisbursementRequest; modes: ModeReglement[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Décaissements DD', href: route('caissier.demandes.index') },
    { title: props.demande.reference, href: '#' },
];

// ─── Helpers ──────────────────────────────────────────────────────────────────
const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric' });

const iconMap: Record<string, any> = {
    'banknote':    Banknote,
    'smartphone':  Smartphone,
    'credit-card': CreditCard,
    'wallet':      Wallet,
};
const colorMap: Record<number, { bg: string; text: string }> = {};
const palette = [
    { bg: 'bg-emerald-50', text: 'text-emerald-700' },
    { bg: 'bg-blue-50',    text: 'text-blue-700' },
    { bg: 'bg-orange-50',  text: 'text-orange-700' },
    { bg: 'bg-purple-50',  text: 'text-purple-700' },
    { bg: 'bg-pink-50',    text: 'text-pink-700' },
];
props.modes.forEach((m, i) => { colorMap[m.id] = palette[i % palette.length]; });

function modeIcon(mode: ModeReglement) { return iconMap[mode.icon] ?? Banknote; }
function modeBg(mode: ModeReglement)   { return colorMap[mode.id]?.bg   ?? 'bg-gray-50'; }
function modeText(mode: ModeReglement) { return colorMap[mode.id]?.text ?? 'text-gray-700'; }

// ─── État ─────────────────────────────────────────────────────────────────────
const isPaid = computed(() => props.demande.payment_status === 'paid');
const today  = new Date().toISOString().slice(0, 10);

// Pré-remplir avec les infos du décaissement auto si elles existent
const existingDecaissement = props.demande.decaissement;

const form = useForm({
    mode_reglement_id: existingDecaissement?.mode_reglement_id ?? (props.modes[0]?.id ?? null) as number | null,
    reference:         existingDecaissement?.reference ?? '',
    notes:             existingDecaissement?.notes ?? '',
    decaissement_date: existingDecaissement?.decaissement_date?.slice(0, 10) ?? today,
});

function submit() {
    form.post(route('caissier.demandes.store', props.demande.uuid), {
        preserveScroll: true,
    });
}

const statusLabels: Record<string, { label: string; cls: string }> = {
    approved: { label: 'Approuvé',  cls: 'bg-emerald-50 text-emerald-700' },
    rejected: { label: 'Refusé',    cls: 'bg-red-50 text-red-700' },
    pending:  { label: 'En attente',cls: 'bg-yellow-50 text-yellow-700' },
};
</script>

<template>
    <Head :title="`Paiement DD — ${demande.reference}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">

            <!-- Retour + titre -->
            <div class="flex items-start gap-4">
                <Link
                    :href="route('caissier.demandes.index')"
                    class="mt-0.5 flex shrink-0 items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold text-muted-foreground transition-colors hover:bg-muted"
                >
                    <ArrowLeft class="h-3.5 w-3.5" /> Retour
                </Link>

                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-foreground">{{ demande.title }}</h1>
                    <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                        <span class="font-mono font-semibold text-indigo-700">{{ demande.reference }}</span>
                        <span v-if="demande.boutique" class="flex items-center gap-1">
                            <Building2 class="h-3.5 w-3.5" /> {{ demande.boutique.name }}
                        </span>
                        <span v-if="demande.nature_operation" class="flex items-center gap-1">
                            <Tag class="h-3.5 w-3.5" /> {{ demande.nature_operation.name }}
                        </span>
                    </div>
                </div>

                <!-- PDF -->
                <a
                    :href="route('disbursement-requests.pdf', demande.uuid)"
                    target="_blank"
                    rel="noopener"
                    class="mt-0.5 flex shrink-0 items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition-colors hover:bg-rose-100"
                >
                    <FileDown class="h-3.5 w-3.5" /> PDF
                </a>
            </div>

            <!-- Carte montant -->
            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-muted-foreground">Montant de la demande</p>
                        <p class="text-3xl font-bold text-foreground">{{ formatAmount(demande.amount) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-muted-foreground">Statut paiement</p>
                        <span
                            class="mt-1 inline-flex items-center gap-1.5 rounded-full px-3 py-1.5 text-sm font-semibold"
                            :class="isPaid ? 'bg-emerald-100 text-emerald-700' : 'bg-orange-100 text-orange-700'"
                        >
                            <CheckCircle2 v-if="isPaid" class="h-4 w-4" />
                            {{ isPaid ? 'Payée' : 'En attente de paiement' }}
                        </span>
                    </div>
                </div>

                <!-- Infos rapides -->
                <div class="mt-5 grid grid-cols-2 gap-3 border-t pt-5 sm:grid-cols-4">
                    <div>
                        <p class="text-xs text-muted-foreground">Demandeur</p>
                        <p class="mt-0.5 flex items-center gap-1 text-sm font-semibold text-foreground">
                            <User class="h-3.5 w-3.5 text-muted-foreground" /> {{ demande.user?.name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Boutique</p>
                        <p class="mt-0.5 flex items-center gap-1 text-sm font-semibold text-foreground">
                            <Building2 class="h-3.5 w-3.5 text-muted-foreground" /> {{ demande.boutique?.name ?? '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs text-muted-foreground">Approuvée le</p>
                        <p class="mt-0.5 flex items-center gap-1 text-sm font-semibold text-foreground">
                            <CalendarDays class="h-3.5 w-3.5 text-muted-foreground" /> {{ formatDate(demande.updated_at) }}
                        </p>
                    </div>
                    <div v-if="demande.decaissement">
                        <p class="text-xs text-muted-foreground">Date de paiement</p>
                        <p class="mt-0.5 flex items-center gap-1 text-sm font-semibold text-foreground">
                            <CalendarDays class="h-3.5 w-3.5 text-muted-foreground" />
                            {{ formatDate(demande.decaissement.decaissement_date) }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Grille -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <!-- Formulaire de paiement -->
                <div class="rounded-2xl border bg-card shadow-sm">
                    <div class="border-b px-6 py-4">
                        <h2 class="flex items-center gap-2 font-semibold text-foreground">
                            <Wallet class="h-4 w-4 text-primary" />
                            {{ isPaid ? 'Détails du paiement' : 'Confirmer le paiement' }}
                        </h2>
                    </div>

                    <!-- Déjà payé -->
                    <div v-if="isPaid" class="px-6 py-5 space-y-4">
                        <div class="flex flex-col items-center gap-3 py-6 text-center">
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
                                <CheckCircle2 class="h-7 w-7 text-emerald-600" />
                            </div>
                            <p class="font-semibold text-foreground">Paiement confirmé</p>
                        </div>
                        <dl class="divide-y rounded-xl border text-sm">
                            <div class="flex items-center justify-between px-4 py-3">
                                <dt class="text-muted-foreground">Mode de règlement</dt>
                                <dd class="font-semibold">{{ demande.decaissement?.mode_reglement?.name ?? '—' }}</dd>
                            </div>
                            <div v-if="demande.decaissement?.reference" class="flex items-center justify-between px-4 py-3">
                                <dt class="text-muted-foreground">Référence</dt>
                                <dd class="font-mono font-semibold">{{ demande.decaissement.reference }}</dd>
                            </div>
                            <div class="flex items-center justify-between px-4 py-3">
                                <dt class="text-muted-foreground">Date</dt>
                                <dd class="font-semibold">{{ formatDate(demande.decaissement?.decaissement_date ?? '') }}</dd>
                            </div>
                            <div v-if="demande.decaissement?.notes" class="px-4 py-3">
                                <dt class="mb-1 text-muted-foreground">Notes</dt>
                                <dd class="text-foreground">{{ demande.decaissement.notes }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Formulaire si pas encore payé -->
                    <form v-else @submit.prevent="submit" class="flex flex-col gap-5 px-6 py-5">
                        <!-- Mode de règlement -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Mode de règlement</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="m in modes"
                                    :key="m.id"
                                    type="button"
                                    @click="form.mode_reglement_id = m.id"
                                    class="flex items-center gap-2 rounded-xl border px-3 py-2.5 text-sm font-medium transition-all"
                                    :class="form.mode_reglement_id === m.id
                                        ? `${modeBg(m)} ${modeText(m)} border-transparent ring-2 ring-primary/30`
                                        : 'hover:bg-muted text-foreground'"
                                >
                                    <component :is="modeIcon(m)" class="h-4 w-4" />
                                    {{ m.name }}
                                </button>
                            </div>
                            <p v-if="form.errors.mode_reglement_id" class="mt-1 text-xs text-red-500">{{ form.errors.mode_reglement_id }}</p>
                        </div>

                        <!-- Référence -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">
                                Référence
                                <span class="ml-1 text-xs text-muted-foreground">(N° chèque, ID transaction…)</span>
                            </label>
                            <input
                                v-model="form.reference"
                                type="text"
                                placeholder="Optionnel"
                                class="w-full rounded-xl border border-input bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                            />
                        </div>

                        <!-- Date -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Date du paiement</label>
                            <input
                                v-model="form.decaissement_date"
                                type="date"
                                class="w-full rounded-xl border border-input bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                                :class="form.errors.decaissement_date ? 'border-red-400' : ''"
                            />
                            <p v-if="form.errors.decaissement_date" class="mt-1 text-xs text-red-500">{{ form.errors.decaissement_date }}</p>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">
                                Notes <span class="ml-1 text-xs text-muted-foreground">(optionnel)</span>
                            </label>
                            <textarea
                                v-model="form.notes"
                                rows="2"
                                placeholder="Observations, remarques…"
                                class="w-full resize-none rounded-xl border border-input bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                            />
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-emerald-700 disabled:opacity-50"
                        >
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <CheckCircle2 v-else class="h-4 w-4" />
                            Confirmer le paiement
                        </button>
                    </form>
                </div>

                <!-- Détails de la demande -->
                <div class="space-y-5">

                    <!-- Infos demande -->
                    <div class="rounded-2xl border bg-card shadow-sm">
                        <div class="border-b px-5 py-4">
                            <h2 class="font-semibold text-foreground">Détails de la demande</h2>
                        </div>
                        <dl class="divide-y px-5 text-sm">
                            <div v-if="demande.description" class="py-3">
                                <dt class="mb-1 text-xs font-medium text-muted-foreground">Justification</dt>
                                <dd class="text-foreground leading-relaxed">{{ demande.description }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-3">
                                <dt class="text-muted-foreground">Soumis le</dt>
                                <dd class="font-semibold">{{ demande.submitted_at ? formatDate(demande.submitted_at) : '—' }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Circuit de validation -->
                    <div v-if="demande.validation_logs && demande.validation_logs.length" class="rounded-2xl border bg-card shadow-sm">
                        <div class="border-b px-5 py-4">
                            <h2 class="font-semibold text-foreground">Circuit de validation</h2>
                        </div>
                        <ul class="divide-y px-5">
                            <li
                                v-for="log in demande.validation_logs"
                                :key="log.id"
                                class="flex items-start gap-3 py-3 text-sm"
                            >
                                <div
                                    class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                    :class="log.action === 'approved' ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700'"
                                >
                                    {{ log.validation_level?.order ?? '?' }}
                                </div>
                                <div class="flex-1">
                                    <p class="font-semibold text-foreground">{{ log.validation_level?.name ?? 'Niveau' }}</p>
                                    <p class="text-xs text-muted-foreground">{{ log.user?.name ?? '—' }} · {{ formatDate(log.created_at) }}</p>
                                    <p v-if="log.comment" class="mt-1 text-xs italic text-muted-foreground">"{{ log.comment }}"</p>
                                </div>
                                <span
                                    class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                    :class="log.action === 'approved' ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700'"
                                >
                                    {{ log.action === 'approved' ? 'Approuvé' : 'Refusé' }}
                                </span>
                            </li>
                        </ul>
                    </div>

                </div>
            </div>

        </div>
    </AppLayout>
</template>
