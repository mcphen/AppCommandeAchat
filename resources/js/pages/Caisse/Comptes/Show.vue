<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type Agent, type BreadcrumbItem, type CompteEpargne, type ModeReglement, type Pret } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft, ArrowUpRight, Banknote, Building2, CalendarDays,
    CheckCircle2, CreditCard, HandCoins, Loader2, Smartphone, TrendingDown, TrendingUp, Wallet,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    agent: Agent;
    compte: CompteEpargne | null;
    prets: Pret[];
    modes: ModeReglement[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Caisse', href: route('caisse.index') },
    { title: `${props.agent.prenom} ${props.agent.nom}`, href: '#' },
];

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));
const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const iconMap: Record<string, any> = { banknote: Banknote, smartphone: Smartphone, 'credit-card': CreditCard, wallet: Wallet };
const palette = [
    { bg: 'bg-emerald-50', text: 'text-emerald-700' },
    { bg: 'bg-blue-50', text: 'text-blue-700' },
    { bg: 'bg-orange-50', text: 'text-orange-700' },
    { bg: 'bg-purple-50', text: 'text-purple-700' },
];
const colorMap: Record<number, { bg: string; text: string }> = {};
props.modes.forEach((m, i) => { colorMap[m.id] = palette[i % palette.length]; });
function modeIcon(m: ModeReglement) { return iconMap[m.icon] ?? Banknote; }
function modeBg(m: ModeReglement) { return colorMap[m.id]?.bg ?? 'bg-gray-50'; }
function modeText(m: ModeReglement) { return colorMap[m.id]?.text ?? 'text-gray-700'; }

// ── Soldes ────────────────────────────────────────────────────────────────────
const soldeDispo = computed(() => {
    if (!props.compte) return 0;
    return Math.max(0, Number(props.compte.solde_epargne) - Number(props.compte.solde_bloque));
});

const pretActif = computed(() => props.prets.find(p => p.statut === 'decaisse'));

// ── Formulaire transaction ─────────────────────────────────────────────────────
const today = new Date().toISOString().slice(0, 10);
const txForm = useForm({
    type:              'depot' as 'depot' | 'retrait',
    montant:           0,
    mode_reglement_id: props.modes[0]?.id ?? null as number | null,
    reference:         '',
    notes:             '',
    transaction_date:  today,
});

function submitTx() {
    txForm.post(route('caisse.transactions.store', props.agent.id), {
        preserveScroll: true,
        onSuccess: () => { txForm.reset(); txForm.transaction_date = today; txForm.mode_reglement_id = props.modes[0]?.id ?? null; },
    });
}

const maxRetrait = computed(() => soldeDispo.value);
</script>

<template>
    <Head :title="`${agent.prenom} ${agent.nom} — Compte épargne`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">

            <!-- En-tête agent -->
            <div class="flex items-start gap-4">
                <Link :href="route('caisse.index')"
                    class="mt-1 flex shrink-0 items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold text-muted-foreground hover:bg-muted">
                    <ArrowLeft class="h-3.5 w-3.5" /> Retour
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">{{ agent.prenom }} {{ agent.nom }}</h1>
                    <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                        <span class="font-mono font-bold text-blue-700">{{ agent.matricule }}</span>
                        <span v-if="agent.boutique" class="flex items-center gap-1">
                            <Building2 class="h-3.5 w-3.5" /> {{ agent.boutique.name }}
                        </span>
                        <span v-if="agent.telephone">{{ agent.telephone }}</span>
                    </div>
                </div>
                <Link :href="route('caisse.prets.create') + '?agent_id=' + agent.id"
                    class="ml-auto flex shrink-0 items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90">
                    <HandCoins class="h-4 w-4" /> Nouveau prêt
                </Link>
            </div>

            <!-- Carte soldes -->
            <div v-if="compte" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border bg-card p-5 shadow-sm">
                    <p class="text-xs text-muted-foreground">Épargne totale</p>
                    <p class="mt-1 text-2xl font-bold text-foreground">{{ formatAmount(compte.solde_epargne) }}</p>
                </div>
                <div class="rounded-2xl border bg-orange-50 p-5 shadow-sm">
                    <p class="text-xs text-orange-600">Bloqué (garantie prêt)</p>
                    <p class="mt-1 text-2xl font-bold text-orange-700">{{ formatAmount(compte.solde_bloque) }}</p>
                </div>
                <div class="rounded-2xl border bg-emerald-50 p-5 shadow-sm">
                    <p class="text-xs text-emerald-600">Disponible</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700">{{ formatAmount(soldeDispo) }}</p>
                </div>
            </div>

            <!-- Prêt actif -->
            <div v-if="pretActif" class="rounded-2xl border border-orange-200 bg-orange-50 p-5">
                <div class="flex items-center justify-between flex-wrap gap-3">
                    <div class="flex items-center gap-3">
                        <HandCoins class="h-5 w-5 text-orange-600" />
                        <div>
                            <p class="font-semibold text-orange-800">Prêt en cours — {{ formatAmount(pretActif.montant_accorde ?? pretActif.montant_demande) }}</p>
                            <p class="text-xs text-orange-600">
                                Reste : {{ formatAmount(Math.max(0, Number(pretActif.montant_accorde) - (pretActif.remboursements ?? []).reduce((s, r) => s + Number(r.montant), 0))) }}
                            </p>
                        </div>
                    </div>
                    <Link :href="route('caisse.prets.show', pretActif.id)"
                        class="flex items-center gap-1.5 rounded-xl border px-3 py-1.5 text-xs font-semibold hover:bg-orange-100 text-orange-800">
                        Gérer <ArrowUpRight class="h-3.5 w-3.5" />
                    </Link>
                </div>
            </div>

            <!-- Grille transaction + historique -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <!-- Formulaire dépôt/retrait -->
                <div v-if="compte?.is_active" class="rounded-2xl border bg-card shadow-sm">
                    <div class="border-b px-6 py-4">
                        <h2 class="font-semibold text-foreground flex items-center gap-2">
                            <Wallet class="h-4 w-4 text-primary" /> Nouvelle transaction
                        </h2>
                    </div>
                    <form @submit.prevent="submitTx" class="flex flex-col gap-4 px-6 py-5">
                        <!-- Type -->
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="txForm.type = 'depot'"
                                class="flex items-center justify-center gap-2 rounded-xl border py-2.5 text-sm font-semibold transition-all"
                                :class="txForm.type === 'depot' ? 'bg-emerald-50 text-emerald-700 border-emerald-200 ring-2 ring-emerald-200' : 'hover:bg-muted'">
                                <TrendingUp class="h-4 w-4" /> Dépôt
                            </button>
                            <button type="button" @click="txForm.type = 'retrait'"
                                class="flex items-center justify-center gap-2 rounded-xl border py-2.5 text-sm font-semibold transition-all"
                                :class="txForm.type === 'retrait' ? 'bg-orange-50 text-orange-700 border-orange-200 ring-2 ring-orange-200' : 'hover:bg-muted'">
                                <TrendingDown class="h-4 w-4" /> Retrait
                            </button>
                        </div>
                        <!-- Montant -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">
                                Montant
                                <span v-if="txForm.type === 'retrait'" class="ml-1 text-xs text-muted-foreground">(max {{ formatAmount(maxRetrait) }})</span>
                            </label>
                            <input v-model="txForm.montant" type="number" step="1" min="1"
                                :max="txForm.type === 'retrait' ? maxRetrait : undefined"
                                class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                                :class="txForm.errors.montant ? 'border-red-400' : ''" />
                            <p v-if="txForm.errors.montant" class="mt-1 text-xs text-red-500">{{ txForm.errors.montant }}</p>
                        </div>
                        <!-- Mode -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Mode de règlement</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button v-for="m in modes" :key="m.id" type="button" @click="txForm.mode_reglement_id = m.id"
                                    class="flex items-center gap-2 rounded-xl border px-3 py-2 text-sm font-medium transition-all"
                                    :class="txForm.mode_reglement_id === m.id ? `${modeBg(m)} ${modeText(m)} border-transparent ring-2 ring-primary/30` : 'hover:bg-muted'">
                                    <component :is="modeIcon(m)" class="h-4 w-4" /> {{ m.name }}
                                </button>
                            </div>
                        </div>
                        <!-- Référence + Date -->
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Référence</label>
                                <input v-model="txForm.reference" type="text" placeholder="Optionnel"
                                    class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Date</label>
                                <input v-model="txForm.transaction_date" type="date"
                                    class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                            </div>
                        </div>
                        <button type="submit" :disabled="txForm.processing"
                            class="flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                            <Loader2 v-if="txForm.processing" class="h-4 w-4 animate-spin" />
                            <Banknote v-else class="h-4 w-4" />
                            Enregistrer
                        </button>
                    </form>
                </div>

                <!-- Historique transactions -->
                <div class="rounded-2xl border bg-card shadow-sm">
                    <div class="border-b px-6 py-4 flex items-center justify-between">
                        <h2 class="font-semibold text-foreground">Historique</h2>
                        <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-bold text-muted-foreground">
                            {{ (compte?.transactions ?? []).length }}
                        </span>
                    </div>
                    <div v-if="(compte?.transactions ?? []).length === 0" class="flex flex-col items-center gap-2 px-6 py-10 text-center text-muted-foreground">
                        <Banknote class="h-8 w-8 opacity-30" />
                        <p class="text-sm">Aucune transaction</p>
                    </div>
                    <div v-else class="divide-y max-h-96 overflow-y-auto">
                        <div v-for="tx in compte!.transactions" :key="tx.id"
                            class="flex items-center gap-3 px-5 py-3.5">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                :class="tx.type === 'depot' ? 'bg-emerald-50' : 'bg-orange-50'">
                                <TrendingUp v-if="tx.type === 'depot'" class="h-4 w-4 text-emerald-600" />
                                <TrendingDown v-else class="h-4 w-4 text-orange-600" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold" :class="tx.type === 'depot' ? 'text-emerald-700' : 'text-orange-700'">
                                        {{ tx.type === 'depot' ? '+' : '-' }}{{ formatAmount(tx.montant) }}
                                    </span>
                                    <span v-if="tx.mode_reglement" class="text-xs text-muted-foreground">{{ tx.mode_reglement.name }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-muted-foreground mt-0.5">
                                    <CalendarDays class="h-3 w-3" />
                                    {{ formatDate(tx.transaction_date) }}
                                    <span v-if="tx.recorder">· {{ tx.recorder.name }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
