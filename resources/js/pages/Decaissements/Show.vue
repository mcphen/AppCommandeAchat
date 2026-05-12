<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type ModeReglement, type PurchaseOrder } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft, Banknote, Building2, CalendarDays, CheckCircle2,
    CircleDollarSign, CreditCard, Download, FileText, Loader2, Smartphone, Wallet,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ order: PurchaseOrder; modes: ModeReglement[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Décaissements', href: route('decaissements.index') },
    { title: props.order.title, href: '#' },
];

// ─── Helpers ──────────────────────────────────────────────────────────────────
const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

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
    { bg: 'bg-teal-50',    text: 'text-teal-700' },
];
props.modes.forEach((m, i) => {
    colorMap[m.id] = palette[i % palette.length];
});

function modeIcon(mode: ModeReglement) {
    return iconMap[mode.icon] ?? Banknote;
}
function modeBg(mode: ModeReglement)   { return colorMap[mode.id]?.bg   ?? 'bg-gray-50'; }
function modeText(mode: ModeReglement) { return colorMap[mode.id]?.text ?? 'text-gray-700'; }

// ─── Calculs ──────────────────────────────────────────────────────────────────
const totalDecaisse = computed(() =>
    (props.order.decaissements ?? []).reduce((s, d) => s + Number(d.montant), 0),
);
const orderAmount   = computed(() => Number(props.order.amount));
const resteADecaisser = computed(() => Math.max(0, orderAmount.value - totalDecaisse.value));
const progressPct   = computed(() =>
    orderAmount.value ? Math.min(100, Math.round((totalDecaisse.value / orderAmount.value) * 100)) : 0,
);
const isPaid = computed(() => props.order.payment_status === 'paid');

// ─── Formulaire ───────────────────────────────────────────────────────────────
const showForm = ref(!isPaid.value);
const today = new Date().toISOString().slice(0, 10);

const form = useForm({
    montant:            resteADecaisser.value,
    mode_reglement_id:  props.modes[0]?.id ?? null as number | null,
    reference:          '',
    notes:              '',
    decaissement_date:  today,
});

function submit() {
    form.post(route('decaissements.store', props.order.uuid), {
        preserveScroll: true,
        onSuccess: () => {
            form.reset();
            form.montant = resteADecaisser.value;
            form.mode_reglement_id = props.modes[0]?.id ?? null;
            form.decaissement_date = today;
            showForm.value = false;
        },
    });
}
</script>

<template>
    <Head :title="`Décaissement — ${order.title}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">

            <!-- Retour + titre -->
            <div class="flex items-start gap-4">
                <Link
                    :href="route('decaissements.index')"
                    class="mt-0.5 flex shrink-0 items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold text-muted-foreground transition-colors hover:bg-muted"
                >
                    <ArrowLeft class="h-3.5 w-3.5" /> Retour
                </Link>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-foreground">{{ order.title }}</h1>
                    <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                        <span v-if="order.order_number" class="font-mono font-semibold text-blue-700">{{ order.order_number }}</span>
                        <span v-if="order.boutique" class="flex items-center gap-1">
                            <Building2 class="h-3.5 w-3.5" /> {{ order.boutique.name }}
                        </span>
                        <span v-if="order.fournisseur" class="flex items-center gap-1">
                            <CircleDollarSign class="h-3.5 w-3.5" /> {{ order.fournisseur.name }}
                        </span>
                    </div>
                </div>
                <!-- Bouton télécharger reçu PDF -->
                <a
                    v-if="(order.decaissements ?? []).length > 0"
                    :href="route('decaissements.pdf', order.uuid)"
                    target="_blank"
                    class="mt-0.5 flex shrink-0 items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition-colors hover:bg-emerald-100"
                >
                    <Download class="h-3.5 w-3.5" /> Reçu PDF
                </a>
            </div>

            <!-- Barre de progression décaissement -->
            <div class="rounded-2xl border bg-card p-6 shadow-sm">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-muted-foreground">Montant total de la commande</p>
                        <p class="text-3xl font-bold text-foreground">{{ formatAmount(order.amount) }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-muted-foreground">Reste à décaisser</p>
                        <p
                            class="text-2xl font-bold"
                            :class="isPaid ? 'text-emerald-600' : 'text-orange-600'"
                        >{{ formatAmount(resteADecaisser) }}</p>
                    </div>
                </div>

                <div class="mt-4">
                    <div class="mb-1 flex items-center justify-between">
                        <span class="text-sm font-medium text-foreground">Progression du décaissement</span>
                        <span
                            class="text-sm font-bold"
                            :class="isPaid ? 'text-emerald-600' : 'text-orange-600'"
                        >{{ progressPct }}%</span>
                    </div>
                    <div class="h-3 w-full overflow-hidden rounded-full bg-muted">
                        <div
                            class="h-full rounded-full transition-all duration-500"
                            :class="isPaid ? 'bg-emerald-500' : 'bg-orange-400'"
                            :style="{ width: `${progressPct}%` }"
                        />
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-muted-foreground">
                        <span>Décaissé : {{ formatAmount(totalDecaisse) }}</span>
                        <span
                            v-if="isPaid"
                            class="flex items-center gap-1 font-semibold text-emerald-600"
                        >
                            <CheckCircle2 class="h-3.5 w-3.5" /> Entièrement décaissé
                        </span>
                        <span v-else>
                            {{ (order.decaissements ?? []).length }} opération(s)
                        </span>
                    </div>
                </div>
            </div>

            <!-- Grille: formulaire + historique -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                <!-- Formulaire d'enregistrement -->
                <div class="rounded-2xl border bg-card shadow-sm">
                    <div class="border-b px-6 py-4">
                        <h2 class="font-semibold text-foreground flex items-center gap-2">
                            <Wallet class="h-4 w-4 text-primary" />
                            Enregistrer un décaissement
                        </h2>
                    </div>

                    <div v-if="isPaid" class="flex flex-col items-center gap-3 px-6 py-10 text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-emerald-100">
                            <CheckCircle2 class="h-7 w-7 text-emerald-600" />
                        </div>
                        <p class="font-semibold text-foreground">Commande entièrement décaissée</p>
                        <p class="text-sm text-muted-foreground">Aucun paiement supplémentaire n'est requis.</p>
                    </div>

                    <form v-else @submit.prevent="submit" class="flex flex-col gap-5 px-6 py-5">
                        <!-- Montant -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">
                                Montant à décaisser
                                <span class="ml-1 text-xs text-muted-foreground">(max {{ formatAmount(resteADecaisser) }})</span>
                            </label>
                            <input
                                v-model="form.montant"
                                type="number"
                                step="1"
                                min="1"
                                :max="resteADecaisser"
                                class="w-full rounded-xl border border-input bg-white dark:bg-zinc-900 px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/40"
                                :class="form.errors.montant ? 'border-red-400' : ''"
                            />
                            <p v-if="form.errors.montant" class="mt-1 text-xs text-red-500">{{ form.errors.montant }}</p>
                        </div>

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

                        <!-- Référence (numéro chèque, ID transaction, etc.) -->
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

                        <!-- Date de décaissement -->
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-foreground">Date de décaissement</label>
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
                            class="flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50"
                        >
                            <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                            <Banknote v-else class="h-4 w-4" />
                            Enregistrer le décaissement
                        </button>
                    </form>
                </div>

                <!-- Historique des décaissements -->
                <div class="rounded-2xl border bg-card shadow-sm">
                    <div class="border-b px-6 py-4">
                        <h2 class="font-semibold text-foreground flex items-center gap-2">
                            <FileText class="h-4 w-4 text-primary" />
                            Historique des décaissements
                            <span class="ml-auto rounded-full bg-muted px-2 py-0.5 text-xs font-bold text-muted-foreground">
                                {{ (order.decaissements ?? []).length }}
                            </span>
                        </h2>
                    </div>

                    <div v-if="(order.decaissements ?? []).length === 0" class="flex flex-col items-center gap-2 px-6 py-10 text-center text-muted-foreground">
                        <Banknote class="h-8 w-8 opacity-30" />
                        <p class="text-sm">Aucun décaissement enregistré</p>
                    </div>

                    <div v-else class="divide-y">
                        <div
                            v-for="(d, i) in order.decaissements"
                            :key="d.id"
                            class="flex items-start gap-4 px-6 py-4"
                        >
                            <!-- Numéro + icône mode -->
                            <div
                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-xs font-bold"
                                :class="d.mode_reglement ? modeBg(d.mode_reglement) : 'bg-gray-50'"
                            >
                                <component
                                    :is="d.mode_reglement ? modeIcon(d.mode_reglement) : Banknote"
                                    class="h-4 w-4"
                                    :class="d.mode_reglement ? modeText(d.mode_reglement) : 'text-gray-500'"
                                />
                            </div>

                            <!-- Détails -->
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-bold text-foreground">{{ formatAmount(d.montant) }}</span>
                                    <span
                                        v-if="d.mode_reglement"
                                        class="rounded-full px-2 py-0.5 text-xs font-semibold"
                                        :class="[modeBg(d.mode_reglement), modeText(d.mode_reglement)]"
                                    >{{ d.mode_reglement.name }}</span>
                                    <span v-if="d.reference" class="font-mono text-xs text-muted-foreground">Réf : {{ d.reference }}</span>
                                </div>
                                <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                    <span class="flex items-center gap-1">
                                        <CalendarDays class="h-3 w-3" />
                                        {{ formatDate(d.decaissement_date) }}
                                    </span>
                                    <span v-if="d.recorder">par {{ d.recorder.name }}</span>
                                </div>
                                <p v-if="d.notes" class="mt-1 text-xs text-muted-foreground italic">{{ d.notes }}</p>
                            </div>

                            <!-- Numéro d'ordre -->
                            <span class="shrink-0 text-xs text-muted-foreground">#{{ i + 1 }}</span>
                        </div>
                    </div>

                    <!-- Récap total -->
                    <div v-if="(order.decaissements ?? []).length > 0" class="border-t bg-muted/30 px-6 py-3">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">Total décaissé</span>
                            <span class="font-bold text-foreground">{{ formatAmount(totalDecaisse) }}</span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
