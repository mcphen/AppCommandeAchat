<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import {
    type BreadcrumbItem, type SessionArbitrage, type SessionArbitrageDap,
    type SharedData
} from '@/types';
import { Head, useForm, usePage, router } from '@inertiajs/vue3';
import {
    ArrowLeft, Play, CheckCircle2, XCircle, Loader2,
    Users, Scale, AlertTriangle, Trophy, RotateCcw
} from 'lucide-vue-next';
import { computed, reactive, ref } from 'vue';

const props = defineProps<{
    session: SessionArbitrage;
    isAdmin: boolean;
    monVote: Record<number, { rang: number; commentaire: string }>;
    votantsIds: number[];
    quorumAtteint: boolean;
    nbVotants: number;
    quorumRequis: number;
    nbMembres: number;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Arbitrage', href: '/arbitrage/sessions' },
    { title: props.session.reference, href: '#' },
];

const page = usePage<SharedData>();
const userId = computed(() => page.props.auth.user?.id);

const estMembre = computed(() =>
    props.session.comite?.membres?.some(m => m.user_id === userId.value && m.is_active) ?? false
);

const peutVoter = computed(() =>
    props.session.statut === 'en_vote' && (estMembre.value || props.isAdmin)
);

const aDejaVote = computed(() => props.votantsIds.includes(userId.value!));

// --- Formulaire de vote ---
const nDaps = computed(() => props.session.session_daps?.length ?? 0);

interface VoteItem { dap_id: number; rang: number; commentaire: string }
const votes = reactive<VoteItem[]>(
    (props.session.session_daps ?? []).map(item => ({
        dap_id:      item.dap_id,
        rang:        props.monVote[item.dap_id]?.rang ?? 0,
        commentaire: props.monVote[item.dap_id]?.commentaire ?? '',
    }))
);

const voteForm = useForm({ votes: [] as VoteItem[] });

const rangsUtilises = computed(() => votes.map(v => v.rang).filter(r => r > 0));
const rangsDupliques = computed(() => {
    const counts: Record<number, number> = {};
    rangsUtilises.value.forEach(r => { counts[r] = (counts[r] ?? 0) + 1; });
    return Object.entries(counts).filter(([, c]) => c > 1).map(([r]) => Number(r));
});
const voteValide = computed(() =>
    rangsUtilises.value.length === nDaps.value &&
    rangsDupliques.value.length === 0 &&
    Math.min(...rangsUtilises.value) === 1 &&
    Math.max(...rangsUtilises.value) === nDaps.value
);

function soumettrVote() {
    voteForm.votes = [...votes];
    voteForm.post(route('arbitrage.sessions.voter', props.session.id));
}

// --- Actions admin ---
function ouvrirVote() {
    router.post(route('arbitrage.sessions.ouvrir-vote', props.session.id));
}

function finaliser() {
    router.post(route('arbitrage.sessions.finaliser', props.session.id));
}

function fmt(v: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
}

// --- Helpers ---
const statutDap: Record<string, { label: string; class: string }> = {
    en_attente: { label: 'En attente', class: 'bg-slate-100 text-slate-600' },
    selectionne: { label: 'Sélectionné', class: 'bg-emerald-100 text-emerald-700' },
    reporte:     { label: 'Reporté',     class: 'bg-amber-100 text-amber-700' },
};

const statutSession: Record<string, { label: string; class: string }> = {
    brouillon: { label: 'Brouillon',     class: 'bg-slate-100 text-slate-600' },
    en_vote:   { label: 'Vote en cours', class: 'bg-amber-100 text-amber-700' },
    cloturee:  { label: 'Clôturée',      class: 'bg-emerald-100 text-emerald-700' },
    annulee:   { label: 'Annulée',       class: 'bg-red-100 text-red-600' },
};

const quorumPct = computed(() =>
    props.nbMembres > 0 ? Math.round((props.nbVotants / props.nbMembres) * 100) : 0
);

// Membres qui ont voté
const membresVotants = computed(() =>
    props.session.comite?.membres?.filter(m => props.votantsIds.includes(m.user_id)) ?? []
);
const membresEnAttente = computed(() =>
    props.session.comite?.membres?.filter(m => !props.votantsIds.includes(m.user_id) && m.is_active) ?? []
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="session.reference" />

        <div class="flex flex-col gap-6 p-6">
            <!-- Header -->
            <PageHeader
                :title="session.titre"
                :subtitle="`${session.comite?.nom} · ${session.comite?.entreprise?.nom ?? 'Toutes entreprises'}`"
                :eyebrow="session.reference"
            >
                <template #actions>
                    <a :href="route('arbitrage.sessions.index')"
                        class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">
                        <ArrowLeft class="h-3.5 w-3.5" /> Retour
                    </a>
                    <span class="inline-flex items-center rounded-xl px-3 py-1.5 text-sm font-medium"
                        :class="statutSession[session.statut]?.class">
                        {{ statutSession[session.statut]?.label }}
                    </span>
                </template>
            </PageHeader>

            <div class="grid grid-cols-4 gap-6 items-start">

                <!-- Colonne principale -->
                <div class="col-span-4 lg:col-span-3 flex flex-col gap-6">

                    <!-- Résumé trésorerie -->
                    <div v-if="session.tresorerie_disponible" class="rounded-xl border bg-card p-5 shadow-sm">
                        <div class="grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-muted-foreground mb-1">Trésorerie disponible</p>
                                <p class="text-xl font-bold">{{ fmt(session.tresorerie_disponible) }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground mb-1">Total DAPs sélectionnées</p>
                                <p class="text-xl font-bold text-emerald-600">
                                    {{ fmt(session.session_daps?.filter(d => d.statut === 'selectionne').reduce((s, d) => s + (d.dap?.expression_besoin?.montant ?? 0), 0) ?? 0) }}
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-muted-foreground mb-1">Contrôle dépassement</p>
                                <p class="text-sm font-medium flex items-center gap-1.5">
                                    <CheckCircle2 v-if="session.bloquer_depassement" class="h-4 w-4 text-emerald-500" />
                                    <XCircle v-else class="h-4 w-4 text-muted-foreground" />
                                    {{ session.bloquer_depassement ? 'Activé' : 'Désactivé' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- ===== RÉSULTATS FINAUX (session clôturée) ===== -->
                    <div v-if="session.statut === 'cloturee'" class="rounded-xl border bg-card shadow-sm overflow-hidden">
                        <div class="flex items-center gap-2 border-b bg-emerald-50 px-5 py-3">
                            <Trophy class="h-4 w-4 text-emerald-600" />
                            <p class="font-semibold text-emerald-700">Ordre de priorité final</p>
                        </div>
                        <div class="divide-y">
                            <div v-for="item in (session.session_daps ?? []).sort((a,b) => (a.ordre_final ?? 99) - (b.ordre_final ?? 99))"
                                :key="item.id"
                                class="flex items-center gap-4 px-5 py-4"
                                :class="item.statut === 'selectionne' ? 'bg-white' : 'bg-muted/20 opacity-60'">

                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-sm font-bold"
                                    :class="item.statut === 'selectionne' ? 'bg-emerald-100 text-emerald-700' : 'bg-muted text-muted-foreground'">
                                    {{ item.ordre_final }}
                                </div>

                                <div class="flex-1 min-w-0">
                                    <p class="font-medium truncate">{{ item.dap?.expression_besoin?.objet }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        {{ item.dap?.reference }} · {{ item.dap?.expression_besoin?.user?.name }}
                                        · Score moyen : {{ item.score_moyen?.toFixed(2) ?? '—' }}
                                    </p>
                                </div>

                                <div class="text-right shrink-0">
                                    <p class="font-bold">{{ fmt(item.dap?.expression_besoin?.montant ?? 0) }}</p>
                                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium mt-0.5"
                                        :class="statutDap[item.statut]?.class">
                                        {{ statutDap[item.statut]?.label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ===== INTERFACE DE VOTE (session en_vote, pour membre/admin) ===== -->
                    <div v-else-if="peutVoter && session.statut === 'en_vote'">
                        <div class="rounded-xl border bg-card shadow-sm overflow-hidden">
                            <div class="flex items-center justify-between border-b bg-amber-50 px-5 py-3">
                                <div class="flex items-center gap-2">
                                    <Scale class="h-4 w-4 text-amber-600" />
                                    <p class="font-semibold text-amber-700">
                                        {{ aDejaVote ? 'Modifier votre vote' : 'Votre vote' }}
                                    </p>
                                </div>
                                <p class="text-xs text-amber-600">Attribuez un rang unique à chaque DAP (1 = priorité maximale)</p>
                            </div>

                            <div class="divide-y">
                                <div v-for="(item, idx) in (session.session_daps ?? [])" :key="item.dap_id"
                                    class="flex items-start gap-4 px-5 py-4">

                                    <div class="flex flex-col items-center gap-1 shrink-0">
                                        <label class="text-xs text-muted-foreground font-medium">Rang</label>
                                        <input type="number" v-model.number="votes[idx].rang"
                                            :min="1" :max="nDaps"
                                            class="w-16 rounded-lg border px-2 py-1.5 text-center text-sm font-bold outline-none focus:ring-2 transition-colors"
                                            :class="{
                                                'border-red-300 bg-red-50 focus:ring-red-400/40': rangsDupliques.includes(votes[idx].rang),
                                                'border-emerald-300 bg-emerald-50 focus:ring-emerald-400/40': votes[idx].rang > 0 && !rangsDupliques.includes(votes[idx].rang),
                                                'focus:ring-primary/40': votes[idx].rang === 0,
                                            }" />
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        <p class="font-medium text-sm">{{ item.dap?.expression_besoin?.objet }}</p>
                                        <p class="text-xs text-muted-foreground mt-0.5">
                                            <span class="font-mono">{{ item.dap?.reference }}</span>
                                            · {{ item.dap?.expression_besoin?.entreprise?.nom }}
                                            · {{ item.dap?.expression_besoin?.user?.name }}
                                        </p>
                                        <p class="text-sm font-bold mt-1">{{ fmt(item.dap?.expression_besoin?.montant ?? 0) }}</p>
                                        <input v-model="votes[idx].commentaire" type="text"
                                            placeholder="Commentaire sur cette DAP (optionnel)..."
                                            class="mt-2 w-full rounded-lg border bg-background px-2 py-1.5 text-xs outline-none focus:ring-2 focus:ring-primary/40" />
                                    </div>
                                </div>
                            </div>

                            <div class="border-t bg-muted/20 px-5 py-4 flex items-center justify-between gap-4">
                                <div class="text-xs text-muted-foreground">
                                    <span v-if="!voteValide" class="text-amber-600">
                                        Chaque rang de 1 à {{ nDaps }} doit être utilisé exactement une fois.
                                        <span v-if="rangsDupliques.length"> · Rangs dupliqués : {{ rangsDupliques.join(', ') }}</span>
                                    </span>
                                    <span v-else class="text-emerald-600 flex items-center gap-1">
                                        <CheckCircle2 class="h-3.5 w-3.5" /> Vote valide, prêt à soumettre.
                                    </span>
                                </div>
                                <button @click="soumettrVote" :disabled="voteForm.processing || !voteValide"
                                    class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60 transition-colors">
                                    <Loader2 v-if="voteForm.processing" class="h-4 w-4 animate-spin" />
                                    <CheckCircle2 v-else class="h-4 w-4" />
                                    {{ aDejaVote ? 'Mettre à jour mon vote' : 'Soumettre mon vote' }}
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ===== LISTE DAPs (brouillon ou en_vote lecture seule) ===== -->
                    <div v-else class="rounded-xl border bg-card shadow-sm overflow-hidden">
                        <div class="border-b px-5 py-3 flex items-center gap-2">
                            <p class="font-semibold">DAPs soumises à arbitrage</p>
                            <span class="text-xs text-muted-foreground">({{ session.session_daps?.length ?? 0 }})</span>
                        </div>
                        <div class="divide-y">
                            <div v-for="item in (session.session_daps ?? [])" :key="item.id"
                                class="flex items-center gap-4 px-5 py-4 hover:bg-muted/20 transition-colors">
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium truncate">{{ item.dap?.expression_besoin?.objet }}</p>
                                    <p class="text-xs text-muted-foreground mt-0.5">
                                        <span class="font-mono">{{ item.dap?.reference }}</span>
                                        · {{ item.dap?.expression_besoin?.user?.name }}
                                        · {{ item.dap?.expression_besoin?.entreprise?.nom }}
                                        · {{ new Date(item.dap?.created_at ?? '').toLocaleDateString('fr-FR') }}
                                    </p>
                                </div>
                                <p class="shrink-0 font-bold">{{ fmt(item.dap?.expression_besoin?.montant ?? 0) }}</p>
                            </div>
                            <p v-if="!session.session_daps?.length" class="px-5 py-4 text-sm text-muted-foreground">Aucune DAP.</p>
                        </div>
                    </div>

                    <!-- Votes détaillés (admin en phase vote) -->
                    <div v-if="isAdmin && session.statut === 'en_vote' && nbVotants > 0"
                        class="rounded-xl border bg-card shadow-sm overflow-hidden">
                        <div class="border-b px-5 py-3">
                            <p class="font-semibold text-sm">Scores calculés (en temps réel)</p>
                            <p class="text-xs text-muted-foreground">Moyenne des rangs attribués — plus le score est bas, plus la priorité est haute.</p>
                        </div>
                        <div class="divide-y">
                            <div v-for="item in (session.session_daps ?? []).slice().sort((a, b) => (a.score_moyen ?? 9999) - (b.score_moyen ?? 9999))"
                                :key="item.id"
                                class="flex items-center gap-4 px-5 py-3">
                                <div class="shrink-0 w-16 text-center">
                                    <span class="text-lg font-bold text-primary">{{ item.score_moyen?.toFixed(2) ?? '—' }}</span>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm truncate">{{ item.dap?.expression_besoin?.objet }}</p>
                                    <p class="text-xs text-muted-foreground font-mono">{{ item.dap?.reference }}</p>
                                </div>
                                <p class="shrink-0 font-bold text-sm">{{ fmt(item.dap?.expression_besoin?.montant ?? 0) }}</p>
                            </div>
                        </div>
                    </div>

                </div><!-- fin col principale -->

                <!-- Sidebar droite -->
                <div class="col-span-4 lg:col-span-1 flex flex-col gap-4">

                    <!-- Actions admin -->
                    <div v-if="isAdmin" class="rounded-xl border bg-card p-5 shadow-sm flex flex-col gap-3">
                        <h3 class="font-semibold text-sm">Actions</h3>

                        <!-- Brouillon → Ouvrir les votes -->
                        <div v-if="session.statut === 'brouillon'" class="flex flex-col gap-2">
                            <p class="text-xs text-muted-foreground">Les membres du comité ne peuvent pas encore voter.</p>
                            <button @click="ouvrirVote"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-amber-500 px-4 py-2 text-sm font-medium text-white hover:bg-amber-600 transition-colors">
                                <Play class="h-4 w-4" />
                                Ouvrir les votes
                            </button>
                        </div>

                        <!-- En vote → Finaliser -->
                        <div v-else-if="session.statut === 'en_vote'" class="flex flex-col gap-3">
                            <div v-if="!quorumAtteint" class="flex items-start gap-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2">
                                <AlertTriangle class="h-4 w-4 text-amber-600 shrink-0 mt-0.5" />
                                <p class="text-xs text-amber-700">
                                    Quorum non atteint ({{ nbVotants }}/{{ quorumRequis }} requis).
                                    Vous pouvez quand même finaliser manuellement.
                                </p>
                            </div>
                            <button @click="finaliser"
                                class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 transition-colors">
                                <CheckCircle2 class="h-4 w-4" />
                                Finaliser la session
                            </button>
                        </div>

                        <!-- Clôturée -->
                        <div v-else-if="session.statut === 'cloturee'" class="text-sm text-muted-foreground">
                            Session clôturée le {{ session.date_cloture ? new Date(session.date_cloture).toLocaleDateString('fr-FR') : '—' }}
                            par <strong>{{ session.finalise_par_user?.name ?? '—' }}</strong>.
                        </div>
                    </div>

                    <!-- Progression des votes -->
                    <div class="rounded-xl border bg-card p-5 shadow-sm flex flex-col gap-3">
                        <h3 class="font-semibold text-sm">Participation</h3>

                        <div>
                            <div class="flex justify-between text-xs text-muted-foreground mb-1.5">
                                <span>{{ nbVotants }} / {{ nbMembres }} membres</span>
                                <span>{{ quorumPct }}%</span>
                            </div>
                            <div class="h-2 w-full rounded-full bg-muted overflow-hidden">
                                <div class="h-full rounded-full transition-all duration-500"
                                    :class="quorumAtteint ? 'bg-emerald-500' : 'bg-amber-400'"
                                    :style="{ width: `${Math.min(quorumPct, 100)}%` }" />
                            </div>
                            <p class="text-xs mt-1.5" :class="quorumAtteint ? 'text-emerald-600' : 'text-muted-foreground'">
                                Quorum : {{ quorumRequis }} vote(s) requis
                                <span v-if="quorumAtteint"> · ✓ Atteint</span>
                            </p>
                        </div>

                        <!-- Membres ayant voté -->
                        <div v-if="membresVotants.length > 0" class="flex flex-col gap-1">
                            <p class="text-xs font-medium text-muted-foreground">Ont voté</p>
                            <div v-for="m in membresVotants" :key="m.id"
                                class="flex items-center gap-2 rounded-md bg-emerald-50 px-2 py-1">
                                <CheckCircle2 class="h-3 w-3 text-emerald-500 shrink-0" />
                                <span class="text-xs font-medium truncate">{{ m.user?.name }}</span>
                            </div>
                        </div>

                        <!-- Membres en attente -->
                        <div v-if="membresEnAttente.length > 0 && session.statut === 'en_vote'" class="flex flex-col gap-1">
                            <p class="text-xs font-medium text-muted-foreground">En attente</p>
                            <div v-for="m in membresEnAttente" :key="m.id"
                                class="flex items-center gap-2 rounded-md bg-muted/40 px-2 py-1">
                                <RotateCcw class="h-3 w-3 text-muted-foreground shrink-0" />
                                <span class="text-xs text-muted-foreground truncate">{{ m.user?.name }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Membres du comité -->
                    <div class="rounded-xl border bg-card p-5 shadow-sm flex flex-col gap-3">
                        <h3 class="font-semibold text-sm">Comité</h3>
                        <div class="flex flex-col gap-1.5">
                            <div v-for="m in (session.comite?.membres ?? [])" :key="m.id"
                                class="flex items-center justify-between text-sm">
                                <span class="font-medium truncate">{{ m.user?.name }}</span>
                                <span class="shrink-0 text-xs text-muted-foreground">
                                    {{ m.role_membre === 'president' ? '👑' : m.role_membre === 'secretaire' ? '📝' : '' }}
                                    {{ m.role_membre }}
                                </span>
                            </div>
                        </div>
                    </div>

                </div><!-- fin sidebar -->
            </div><!-- fin grid -->
        </div>
    </AppLayout>
</template>
