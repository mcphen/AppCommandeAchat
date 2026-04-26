<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type Agent, type BreadcrumbItem, type CompteEpargne, type Pret } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowUpRight, Banknote, Building2, CalendarDays, CheckCircle2,
    HandCoins, TrendingDown, TrendingUp, UserCircle, Wallet, XCircle,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    agent: Agent;
    compte: CompteEpargne | null;
    prets: Pret[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Mon compte', href: '#' },
];

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));
const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const soldeDispo = computed(() => {
    if (!props.compte) return 0;
    return Math.max(0, Number(props.compte.solde_epargne) - Number(props.compte.solde_bloque));
});

const pretActif = computed(() => props.prets.find(p => p.statut === 'decaisse'));

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
</script>

<template>
    <Head title="Mon compte épargne" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">

            <!-- En-tête agent -->
            <div class="flex items-center gap-4">
                <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-full bg-teal-100">
                    <UserCircle class="h-8 w-8 text-teal-600" />
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">{{ agent.prenom }} {{ agent.nom }}</h1>
                    <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                        <span class="font-mono font-bold text-blue-700">{{ agent.matricule }}</span>
                        <span v-if="agent.boutique" class="flex items-center gap-1">
                            <Building2 class="h-3.5 w-3.5" /> {{ agent.boutique.name }}
                        </span>
                        <span v-if="agent.telephone">{{ agent.telephone }}</span>
                        <span class="text-xs">Membre depuis {{ formatDate(agent.date_adhesion) }}</span>
                    </div>
                </div>
            </div>

            <!-- Compte inexistant -->
            <div v-if="!compte" class="rounded-2xl border bg-muted/20 p-8 text-center">
                <Wallet class="mx-auto h-10 w-10 text-muted-foreground opacity-40" />
                <p class="mt-3 font-medium text-muted-foreground">Aucun compte épargne ouvert.</p>
                <p class="mt-1 text-sm text-muted-foreground">Contactez la caisse pour ouvrir votre compte.</p>
            </div>

            <template v-else>
                <!-- Cartes soldes -->
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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
                    <div class="flex items-start gap-3">
                        <HandCoins class="mt-0.5 h-5 w-5 shrink-0 text-orange-600" />
                        <div class="flex-1">
                            <p class="font-semibold text-orange-800">Prêt en cours</p>
                            <div class="mt-2 grid grid-cols-2 gap-3 text-sm sm:grid-cols-3">
                                <div>
                                    <p class="text-xs text-orange-600">Montant accordé</p>
                                    <p class="font-bold text-orange-800">{{ formatAmount(pretActif.montant_accorde ?? pretActif.montant_demande) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-orange-600">Remboursé</p>
                                    <p class="font-bold text-orange-800">
                                        {{ formatAmount((pretActif.remboursements ?? []).reduce((s, r) => s + Number(r.montant), 0)) }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs text-orange-600">Reste</p>
                                    <p class="font-bold text-orange-800">
                                        {{ formatAmount(Math.max(0, Number(pretActif.montant_accorde) - (pretActif.remboursements ?? []).reduce((s, r) => s + Number(r.montant), 0))) }}
                                    </p>
                                </div>
                            </div>
                            <!-- Barre progression -->
                            <div class="mt-3">
                                <div class="h-2 w-full overflow-hidden rounded-full bg-orange-200">
                                    <div class="h-full rounded-full bg-orange-500 transition-all"
                                        :style="{ width: `${Math.min(100, ((pretActif.remboursements ?? []).reduce((s, r) => s + Number(r.montant), 0) / Number(pretActif.montant_accorde)) * 100)}%` }" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historique transactions -->
                <div class="rounded-2xl border bg-card shadow-sm">
                    <div class="border-b px-6 py-4 flex items-center justify-between">
                        <h2 class="font-semibold text-foreground">Mes transactions</h2>
                        <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-bold text-muted-foreground">
                            {{ (compte.transactions ?? []).length }}
                        </span>
                    </div>
                    <div v-if="!(compte.transactions ?? []).length" class="flex flex-col items-center gap-2 px-6 py-10 text-center text-muted-foreground">
                        <Banknote class="h-8 w-8 opacity-30" />
                        <p class="text-sm">Aucune transaction</p>
                    </div>
                    <div v-else class="divide-y max-h-80 overflow-y-auto">
                        <div v-for="tx in compte.transactions" :key="tx.id"
                            class="flex items-center gap-3 px-5 py-3.5">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                                :class="tx.type === 'depot' ? 'bg-emerald-50' : 'bg-orange-50'">
                                <TrendingUp v-if="tx.type === 'depot'" class="h-4 w-4 text-emerald-600" />
                                <TrendingDown v-else class="h-4 w-4 text-orange-600" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-sm font-semibold"
                                        :class="tx.type === 'depot' ? 'text-emerald-700' : 'text-orange-700'">
                                        {{ tx.type === 'depot' ? '+' : '-' }}{{ formatAmount(tx.montant) }}
                                    </span>
                                    <span v-if="tx.mode_reglement" class="text-xs text-muted-foreground">{{ tx.mode_reglement.name }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs text-muted-foreground mt-0.5">
                                    <CalendarDays class="h-3 w-3" />
                                    {{ formatDate(tx.transaction_date) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historique prêts -->
                <div v-if="prets.length" class="rounded-2xl border bg-card shadow-sm">
                    <div class="border-b px-6 py-4">
                        <h2 class="font-semibold text-foreground">Mes prêts</h2>
                    </div>
                    <div class="divide-y">
                        <div v-for="pret in prets" :key="pret.id"
                            class="flex items-center gap-4 px-5 py-4">
                            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                :class="statutConfig[pret.statut]?.bg">
                                <HandCoins class="h-4 w-4" :class="statutConfig[pret.statut]?.text" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="font-semibold text-foreground">{{ formatAmount(pret.montant_demande) }}</span>
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold"
                                        :class="[statutConfig[pret.statut]?.bg, statutConfig[pret.statut]?.text]">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statutConfig[pret.statut]?.dot" />
                                        {{ statutLabels[pret.statut] }}
                                    </span>
                                </div>
                                <p v-if="pret.motif" class="mt-0.5 text-xs text-muted-foreground italic truncate">{{ pret.motif }}</p>
                                <div class="mt-0.5 flex items-center gap-2 text-xs text-muted-foreground">
                                    <CalendarDays class="h-3 w-3" />
                                    {{ formatDate(pret.created_at) }}
                                </div>
                            </div>
                            <!-- Progression si décaissé -->
                            <div v-if="pret.statut === 'decaisse' && pret.montant_accorde" class="hidden shrink-0 sm:block text-right">
                                <p class="text-xs text-muted-foreground">
                                    {{ formatAmount((pret.remboursements ?? []).reduce((s, r) => s + Number(r.montant), 0)) }}
                                    / {{ formatAmount(pret.montant_accorde) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </template>

        </div>
    </AppLayout>
</template>
