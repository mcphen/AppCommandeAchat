<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type ComiteArbitrage, type DemandeAutorisationPaiement } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Loader2, Info } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Arbitrage', href: '/arbitrage/sessions' },
    { title: 'Nouvelle session', href: '#' },
];

const props = defineProps<{
    comites: ComiteArbitrage[];
    daps: DemandeAutorisationPaiement[];
}>();

const form = useForm({
    comite_arbitrage_id:   null as number | null,
    titre:                 '',
    description:           '',
    tresorerie_disponible: '' as string | number,
    bloquer_depassement:   true,
    dap_ids:               [] as number[],
});

function toggleDap(id: number) {
    const idx = form.dap_ids.indexOf(id);
    if (idx === -1) form.dap_ids.push(id);
    else form.dap_ids.splice(idx, 1);
}

function selectAll() {
    form.dap_ids = props.daps.map(d => d.id);
}
function deselectAll() {
    form.dap_ids = [];
}

function fmt(v: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
}

const totalSelectionne = computed(() =>
    props.daps.filter(d => form.dap_ids.includes(d.id)).reduce((s, d) => s + (d.expression_besoin?.montant ?? 0), 0)
);

const tresorerieNum = computed(() => Number(form.tresorerie_disponible) || 0);
const depassement = computed(() => tresorerieNum.value > 0 && totalSelectionne.value > tresorerieNum.value);

function submit() {
    form.post(route('arbitrage.sessions.store'));
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Nouvelle session d'arbitrage" />

        <div class="flex flex-col gap-6 p-6 max-w-4xl">
            <PageHeader title="Nouvelle session d'arbitrage" eyebrow="Arbitrage" />

            <form @submit.prevent="submit" class="flex flex-col gap-6">

                <!-- Paramètres de la session -->
                <div class="rounded-xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <h2 class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Paramètres de la session</h2>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2 flex flex-col gap-1.5">
                            <label class="text-sm font-medium">Titre <span class="text-red-500">*</span></label>
                            <input v-model="form.titre" type="text" placeholder="Ex: Arbitrage paiements Avril 2026"
                                class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                            <p v-if="form.errors.titre" class="text-xs text-red-500">{{ form.errors.titre }}</p>
                        </div>

                        <div class="col-span-2 flex flex-col gap-1.5">
                            <label class="text-sm font-medium">Comité d'arbitrage <span class="text-red-500">*</span></label>
                            <select v-model="form.comite_arbitrage_id"
                                class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40">
                                <option :value="null">— Choisir un comité —</option>
                                <option v-for="c in comites" :key="c.id" :value="c.id">
                                    {{ c.nom }}{{ c.entreprise ? ` · ${c.entreprise.nom}` : '' }} ({{ c.membres?.length ?? 0 }} membres)
                                </option>
                            </select>
                            <p v-if="form.errors.comite_arbitrage_id" class="text-xs text-red-500">{{ form.errors.comite_arbitrage_id }}</p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium">Trésorerie disponible</label>
                            <input v-model="form.tresorerie_disponible" type="number" step="1" min="0"
                                placeholder="Montant disponible..."
                                class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                            <p class="text-xs text-muted-foreground">Laissez vide si non contraint.</p>
                        </div>

                        <div class="flex flex-col gap-2 justify-center">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input v-model="form.bloquer_depassement" type="checkbox" :disabled="!form.tresorerie_disponible" class="rounded" />
                                <span class="text-sm font-medium" :class="!form.tresorerie_disponible ? 'text-muted-foreground' : ''">
                                    Bloquer le dépassement de trésorerie
                                </span>
                            </label>
                            <p class="text-xs text-muted-foreground ml-5">Les DAPs dépassant le budget disponible seront automatiquement reportées.</p>
                        </div>

                        <div class="col-span-2 flex flex-col gap-1.5">
                            <label class="text-sm font-medium">Description (optionnel)</label>
                            <textarea v-model="form.description" rows="2"
                                class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40 resize-none" />
                        </div>
                    </div>
                </div>

                <!-- Sélection des DAPs -->
                <div class="rounded-xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">DAPs à arbitrer</h2>
                            <p class="text-xs text-muted-foreground mt-0.5">Sélectionnez les factures validées à soumettre au comité.</p>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" @click="selectAll" class="text-xs text-primary underline">Tout sélectionner</button>
                            <span class="text-muted-foreground text-xs">·</span>
                            <button type="button" @click="deselectAll" class="text-xs text-muted-foreground underline">Tout désélectionner</button>
                        </div>
                    </div>

                    <p v-if="form.errors.dap_ids" class="text-xs text-red-500">{{ form.errors.dap_ids }}</p>

                    <div v-if="daps.length === 0" class="rounded-lg bg-muted/40 p-6 text-center">
                        <Info class="mx-auto h-6 w-6 text-muted-foreground/50 mb-2" />
                        <p class="text-sm text-muted-foreground">Aucune DAP validée en attente de paiement.</p>
                    </div>

                    <div v-else class="flex flex-col gap-2">
                        <label v-for="dap in daps" :key="dap.id"
                            class="flex items-start gap-3 rounded-lg border p-3 cursor-pointer transition-colors"
                            :class="form.dap_ids.includes(dap.id) ? 'border-primary/50 bg-primary/5' : 'hover:bg-muted/30'">
                            <input type="checkbox" :checked="form.dap_ids.includes(dap.id)" @change="toggleDap(dap.id)"
                                class="mt-0.5 rounded" />
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-3">
                                    <span class="font-medium text-sm truncate">{{ dap.expression_besoin?.objet }}</span>
                                    <span class="shrink-0 font-bold text-sm">{{ fmt(dap.expression_besoin?.montant ?? 0) }}</span>
                                </div>
                                <div class="flex items-center gap-2 mt-0.5 text-xs text-muted-foreground">
                                    <span class="font-mono">{{ dap.reference }}</span>
                                    <span>·</span>
                                    <span>{{ dap.expression_besoin?.user?.name }}</span>
                                    <span>·</span>
                                    <span>{{ dap.expression_besoin?.entreprise?.nom }}</span>
                                    <span>·</span>
                                    <span>{{ new Date(dap.created_at).toLocaleDateString('fr-FR') }}</span>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Récapitulatif -->
                    <div v-if="form.dap_ids.length > 0" class="rounded-lg border bg-muted/30 px-4 py-3 flex items-center justify-between">
                        <span class="text-sm font-medium">{{ form.dap_ids.length }} DAP(s) sélectionnée(s)</span>
                        <div class="text-right">
                            <span class="text-sm font-bold" :class="depassement ? 'text-red-600' : ''">
                                Total : {{ fmt(totalSelectionne) }}
                            </span>
                            <p v-if="tresorerieNum > 0" class="text-xs" :class="depassement ? 'text-red-500' : 'text-emerald-600'">
                                {{ depassement ? '⚠ Dépasse' : '✓ Dans' }} la trésorerie ({{ fmt(tresorerieNum) }})
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button type="submit" :disabled="form.processing || form.dap_ids.length === 0 || !form.comite_arbitrage_id"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60 transition-colors">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        Créer la session
                    </button>
                    <a :href="route('arbitrage.sessions.index')"
                        class="rounded-lg border px-4 py-2 text-sm hover:bg-muted transition-colors">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
