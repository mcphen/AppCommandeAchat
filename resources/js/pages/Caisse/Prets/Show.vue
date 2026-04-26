<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type ModeReglement, type Pret, type ValidationLevel } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft, Banknote, Building2, CalendarDays, CheckCircle2,
    CreditCard, HandCoins, Loader2, Send, Smartphone, TrendingDown, Wallet, XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    pret: Pret;
    modes: ModeReglement[];
    levels: ValidationLevel[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Caisse', href: route('caisse.index') },
    { title: 'Prêts', href: route('caisse.prets.index') },
    { title: `Prêt #${props.pret.id}`, href: '#' },
];

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));
const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

// ── Status config ──────────────────────────────────────────────────────────────
const statutConfig: Record<string, { bg: string; text: string; dot: string }> = {
    draft:    { bg: 'bg-gray-50',    text: 'text-gray-600',    dot: 'bg-gray-400' },
    pending:  { bg: 'bg-yellow-50',  text: 'text-yellow-700',  dot: 'bg-yellow-500' },
    approved: { bg: 'bg-blue-50',    text: 'text-blue-700',    dot: 'bg-blue-500' },
    rejected: { bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500' },
    decaisse: { bg: 'bg-orange-50',  text: 'text-orange-700',  dot: 'bg-orange-500' },
    solde:    { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
};
const statutLabels: Record<string, string> = {
    draft: 'Brouillon', pending: 'En validation', approved: 'Approuvé',
    rejected: 'Rejeté', decaisse: 'Décaissé', solde: 'Soldé',
};

// ── Mode icons ─────────────────────────────────────────────────────────────────
const iconMap: Record<string, any> = { banknote: Banknote, smartphone: Smartphone, 'credit-card': CreditCard, wallet: Wallet };
const palette = [
    { bg: 'bg-emerald-50', text: 'text-emerald-700' },
    { bg: 'bg-blue-50',    text: 'text-blue-700' },
    { bg: 'bg-orange-50',  text: 'text-orange-700' },
    { bg: 'bg-purple-50',  text: 'text-purple-700' },
];
const colorMap: Record<number, { bg: string; text: string }> = {};
props.modes.forEach((m, i) => { colorMap[m.id] = palette[i % palette.length]; });
function modeIcon(m: ModeReglement) { return iconMap[m.icon] ?? Banknote; }

// ── Computed ───────────────────────────────────────────────────────────────────
const capitalRembourse = computed(() =>
    (props.pret.remboursements ?? []).reduce((s, r) => s + Number(r.montant), 0),
);
const montantAccorde = computed(() => Number(props.pret.montant_accorde ?? 0));
const resteARembourser = computed(() => Math.max(0, montantAccorde.value - capitalRembourse.value));
const progressPct = computed(() =>
    montantAccorde.value > 0 ? Math.min(100, (capitalRembourse.value / montantAccorde.value) * 100) : 0,
);

const isDraft    = computed(() => props.pret.statut === 'draft');
const isApproved = computed(() => props.pret.statut === 'approved');
const isDecaisse = computed(() => props.pret.statut === 'decaisse');

// ── Soumission ─────────────────────────────────────────────────────────────────
const submitting = ref(false);
function submitPret() {
    submitting.value = true;
    router.post(route('caisse.prets.submit', props.pret.id), {}, {
        onFinish: () => { submitting.value = false; },
    });
}

// ── Décaissement ───────────────────────────────────────────────────────────────
const today = new Date().toISOString().slice(0, 10);
const decForm = useForm({
    montant_accorde:   montantAccorde.value || (props.pret.montant_demande as number) || ('' as number | ''),
    mode_reglement_id: props.modes[0]?.id ?? null as number | null,
    notes:             '',
});
function submitDecaissement() {
    decForm.post(route('caisse.prets.decaisser', props.pret.id), { preserveScroll: true });
}

// ── Remboursement ──────────────────────────────────────────────────────────────
const rembForm = useForm({
    montant:            '' as number | '',
    mode_reglement_id:  props.modes[0]?.id ?? null as number | null,
    reference:          '',
    notes:              '',
    remboursement_date: today,
});
function submitRemboursement() {
    rembForm.post(route('caisse.remboursements.store', props.pret.id), {
        preserveScroll: true,
        onSuccess: () => { rembForm.reset(); rembForm.remboursement_date = today; rembForm.mode_reglement_id = props.modes[0]?.id ?? null; },
    });
}
</script>

<template>
    <Head :title="`Prêt #${pret.id}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">

            <!-- En-tête -->
            <div class="flex items-start gap-4">
                <Link :href="route('caisse.prets.index')"
                    class="mt-1 flex shrink-0 items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold text-muted-foreground hover:bg-muted">
                    <ArrowLeft class="h-3.5 w-3.5" /> Retour
                </Link>
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="text-2xl font-bold text-foreground">Prêt #{{ pret.id }}</h1>
                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            :class="[statutConfig[pret.statut]?.bg, statutConfig[pret.statut]?.text]">
                            <span class="h-1.5 w-1.5 rounded-full" :class="statutConfig[pret.statut]?.dot" />
                            {{ statutLabels[pret.statut] }}
                        </span>
                    </div>
                    <div v-if="pret.agent" class="mt-1 flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                        <span class="font-semibold text-foreground">{{ pret.agent.prenom }} {{ pret.agent.nom }}</span>
                        <span class="font-mono text-xs font-bold text-blue-700">{{ pret.agent.matricule }}</span>
                        <span v-if="pret.agent.boutique" class="flex items-center gap-1">
                            <Building2 class="h-3.5 w-3.5" /> {{ pret.agent.boutique.name }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Carte montants -->
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <p class="text-xs text-muted-foreground">Montant demandé</p>
                    <p class="mt-1 text-2xl font-bold text-foreground">{{ formatAmount(pret.montant_demande) }}</p>
                </div>
                <div class="rounded-2xl border p-5 shadow-sm"
                    :class="pret.montant_accorde ? 'bg-blue-50' : 'bg-card'">
                    <p class="text-xs" :class="pret.montant_accorde ? 'text-blue-600' : 'text-muted-foreground'">Montant accordé</p>
                    <p class="mt-1 text-2xl font-bold" :class="pret.montant_accorde ? 'text-blue-700' : 'text-muted-foreground'">
                        {{ pret.montant_accorde ? formatAmount(pret.montant_accorde) : '—' }}
                    </p>
                </div>
                <div v-if="isDecaisse" class="rounded-2xl border bg-emerald-50 p-5 shadow-sm">
                    <p class="text-xs text-emerald-600">Reste à rembourser</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700">{{ formatAmount(resteARembourser) }}</p>
                </div>
                <div v-else class="rounded-2xl border bg-card p-5 shadow-sm">
                    <p class="text-xs text-muted-foreground">Motif</p>
                    <p class="mt-1 text-sm text-foreground italic">{{ pret.motif ?? '—' }}</p>
                </div>
            </div>

            <!-- Barre remboursement (décaissé uniquement) -->
            <div v-if="isDecaisse || pret.statut === 'solde'" class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="mb-2 flex items-center justify-between text-sm">
                    <span class="font-medium text-foreground">Remboursement</span>
                    <span class="font-bold text-foreground">{{ Math.round(progressPct) }}%</span>
                </div>
                <div class="h-3 w-full overflow-hidden rounded-full bg-muted">
                    <div class="h-full rounded-full bg-emerald-500 transition-all duration-500"
                        :style="{ width: `${progressPct}%` }" />
                </div>
                <div class="mt-2 flex justify-between text-xs text-muted-foreground">
                    <span>Remboursé : {{ formatAmount(capitalRembourse) }}</span>
                    <span>Total : {{ formatAmount(montantAccorde) }}</span>
                </div>
            </div>

            <!-- Circuit de validation -->
            <div v-if="levels.length" class="rounded-2xl border bg-card p-5 shadow-sm">
                <h2 class="mb-4 font-semibold text-foreground">Circuit de validation</h2>
                <div class="flex flex-wrap gap-2">
                    <div v-for="level in levels" :key="level.id"
                        class="flex items-center gap-2 rounded-xl border px-3 py-2 text-sm">
                        <template v-if="pret.validation_logs?.some(l => l.validation_level?.order === level.order && l.action === 'approved')">
                            <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                            <span class="text-emerald-700 font-medium">{{ level.name }}</span>
                        </template>
                        <template v-else-if="pret.validation_logs?.some(l => l.validation_level?.order === level.order && l.action === 'rejected')">
                            <XCircle class="h-4 w-4 text-red-500" />
                            <span class="text-red-600 font-medium">{{ level.name }}</span>
                        </template>
                        <template v-else-if="pret.current_level_order === level.order">
                            <span class="h-2 w-2 rounded-full bg-yellow-400 animate-pulse" />
                            <span class="text-yellow-700 font-medium">{{ level.name }}</span>
                        </template>
                        <template v-else>
                            <span class="h-2 w-2 rounded-full bg-muted-foreground/30" />
                            <span class="text-muted-foreground">{{ level.name }}</span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Action : Soumettre (draft) -->
            <div v-if="isDraft" class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5">
                <p class="text-sm font-medium text-yellow-800 mb-3">Cette demande est en brouillon. Soumettez-la pour lancer la validation.</p>
                <button @click="submitPret" :disabled="submitting"
                    class="flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                    <Loader2 v-if="submitting" class="h-4 w-4 animate-spin" />
                    <Send v-else class="h-4 w-4" />
                    Soumettre au circuit de validation
                </button>
            </div>

            <!-- Décaissement + Remboursement en grille -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <!-- Formulaire décaissement (statut approved) -->
                <div v-if="isApproved" class="rounded-2xl border bg-card shadow-sm">
                    <div class="border-b px-6 py-4">
                        <h2 class="font-semibold text-foreground flex items-center gap-2">
                            <HandCoins class="h-4 w-4 text-primary" /> Décaisser le prêt
                        </h2>
                    </div>
                    <form @submit.prevent="submitDecaissement" class="flex flex-col gap-4 px-6 py-5">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Montant accordé <span class="text-red-500">*</span></label>
                            <input v-model="decForm.montant_accorde" type="number" step="1" min="1"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                                :class="decForm.errors.montant_accorde ? 'border-red-400' : ''" />
                            <p v-if="decForm.errors.montant_accorde" class="mt-1 text-xs text-red-500">{{ decForm.errors.montant_accorde }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Mode de décaissement</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button v-for="m in modes" :key="m.id" type="button" @click="decForm.mode_reglement_id = m.id"
                                    class="flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-medium transition-all"
                                    :class="decForm.mode_reglement_id === m.id
                                        ? `${colorMap[m.id]?.bg ?? 'bg-blue-50'} ${colorMap[m.id]?.text ?? 'text-blue-700'} ring-2 ring-primary/30`
                                        : 'hover:bg-muted'">
                                    <component :is="modeIcon(m)" class="h-4 w-4" /> {{ m.name }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Notes <span class="text-muted-foreground">(optionnel)</span></label>
                            <textarea v-model="decForm.notes" rows="2"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none" />
                        </div>
                        <button type="submit" :disabled="decForm.processing"
                            class="flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                            <Loader2 v-if="decForm.processing" class="h-4 w-4 animate-spin" />
                            <HandCoins v-else class="h-4 w-4" />
                            Décaisser
                        </button>
                    </form>
                </div>

                <!-- Formulaire remboursement (statut decaisse) -->
                <div v-if="isDecaisse" class="rounded-2xl border bg-card shadow-sm">
                    <div class="border-b px-6 py-4">
                        <h2 class="font-semibold text-foreground flex items-center gap-2">
                            <Wallet class="h-4 w-4 text-emerald-600" /> Enregistrer un remboursement
                        </h2>
                    </div>
                    <form @submit.prevent="submitRemboursement" class="flex flex-col gap-4 px-6 py-5">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">
                                Montant <span class="text-red-500">*</span>
                                <span class="ml-1 text-xs text-muted-foreground">(max {{ formatAmount(resteARembourser) }})</span>
                            </label>
                            <input v-model="rembForm.montant" type="number" step="1" min="1" :max="resteARembourser"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                                :class="rembForm.errors.montant ? 'border-red-400' : ''" />
                            <p v-if="rembForm.errors.montant" class="mt-1 text-xs text-red-500">{{ rembForm.errors.montant }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Mode de règlement</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button v-for="m in modes" :key="m.id" type="button" @click="rembForm.mode_reglement_id = m.id"
                                    class="flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-medium transition-all"
                                    :class="rembForm.mode_reglement_id === m.id
                                        ? `${colorMap[m.id]?.bg ?? 'bg-blue-50'} ${colorMap[m.id]?.text ?? 'text-blue-700'} ring-2 ring-primary/30`
                                        : 'hover:bg-muted'">
                                    <component :is="modeIcon(m)" class="h-4 w-4" /> {{ m.name }}
                                </button>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Référence</label>
                                <input v-model="rembForm.reference" type="text" placeholder="Optionnel"
                                    class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Date</label>
                                <input v-model="rembForm.remboursement_date" type="date"
                                    class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                            </div>
                        </div>
                        <button type="submit" :disabled="rembForm.processing"
                            class="flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
                            <Loader2 v-if="rembForm.processing" class="h-4 w-4 animate-spin" />
                            <TrendingDown v-else class="h-4 w-4" />
                            Enregistrer le remboursement
                        </button>
                    </form>
                </div>

                <!-- Historique remboursements -->
                <div v-if="(pret.remboursements ?? []).length || isDecaisse || pret.statut === 'solde'" class="rounded-2xl border bg-card shadow-sm">
                    <div class="border-b px-6 py-4 flex items-center justify-between">
                        <h2 class="font-semibold text-foreground">Remboursements</h2>
                        <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-bold text-muted-foreground">
                            {{ (pret.remboursements ?? []).length }}
                        </span>
                    </div>
                    <div v-if="!(pret.remboursements ?? []).length" class="px-6 py-8 text-center text-sm text-muted-foreground">
                        Aucun remboursement enregistré.
                    </div>
                    <div v-else class="divide-y max-h-72 overflow-y-auto">
                        <div v-for="r in pret.remboursements" :key="r.id" class="flex items-center gap-3 px-5 py-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-50">
                                <TrendingDown class="h-4 w-4 text-emerald-600" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="font-semibold text-emerald-700">{{ formatAmount(r.montant) }}</span>
                                    <span v-if="r.mode_reglement" class="text-xs text-muted-foreground">{{ r.mode_reglement.name }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-muted-foreground mt-0.5">
                                    <CalendarDays class="h-3 w-3" />
                                    {{ formatDate(r.remboursement_date) }}
                                    <span v-if="r.recorder">· {{ r.recorder.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historique des validations -->
            <div v-if="(pret.validation_logs ?? []).length" class="rounded-2xl border bg-card shadow-sm">
                <div class="border-b px-6 py-4">
                    <h2 class="font-semibold text-foreground">Historique des validations</h2>
                </div>
                <div class="divide-y">
                    <div v-for="log in pret.validation_logs" :key="log.id" class="flex items-start gap-3 px-5 py-4">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                            :class="log.action === 'approved' ? 'bg-emerald-50' : 'bg-red-50'">
                            <CheckCircle2 v-if="log.action === 'approved'" class="h-4 w-4 text-emerald-600" />
                            <XCircle v-else class="h-4 w-4 text-red-500" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-semibold text-foreground">{{ log.validation_level?.name }}</span>
                                <span class="text-xs font-medium" :class="log.action === 'approved' ? 'text-emerald-600' : 'text-red-500'">
                                    {{ log.action === 'approved' ? 'Approuvé' : 'Rejeté' }}
                                </span>
                                <span class="text-xs text-muted-foreground">par {{ log.user?.name }}</span>
                                <span v-if="log.delegated_by" class="text-xs text-muted-foreground italic">(via {{ log.delegated_by.name }})</span>
                            </div>
                            <p v-if="log.comment" class="mt-1 text-xs text-muted-foreground italic">{{ log.comment }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ formatDate(log.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
