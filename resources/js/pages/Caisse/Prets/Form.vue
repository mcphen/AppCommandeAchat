<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type Agent, type BreadcrumbItem, type ModeReglement } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, HandCoins, Loader2, Save, UserCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

const props = defineProps<{
    agent?: Agent | null;
    agents: Agent[];
    modes: ModeReglement[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Caisse', href: route('caisse.index') },
    { title: 'Prêts', href: route('caisse.prets.index') },
    { title: 'Nouveau prêt', href: '#' },
];

const form = useForm({
    agent_id:        props.agent?.id ?? ('' as number | ''),
    montant_demande: '' as number | '',
    motif:           '',
});

const selectedAgent = computed(() =>
    form.agent_id ? props.agents.find(a => a.id === Number(form.agent_id)) ?? props.agent : null,
);

const plafond = computed(() => {
    const a = selectedAgent.value;
    if (!a?.compte) return null;
    const dispo = Math.max(0, Number(a.compte.solde_epargne) - Number(a.compte.solde_bloque));
    return dispo * 3;
});

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

function submit() {
    form.post(route('caisse.prets.store'));
}
</script>

<template>
    <Head title="Nouveau prêt" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-xl p-3 sm:p-6">
            <div class="mb-6 flex items-center gap-4">
                <Link :href="route('caisse.prets.index')"
                    class="flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold text-muted-foreground hover:bg-muted">
                    <ArrowLeft class="h-3.5 w-3.5" /> Retour
                </Link>
                <h1 class="text-2xl font-bold text-foreground">Nouvelle demande de prêt</h1>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-5 rounded-2xl border bg-card p-6 shadow-sm">
                <!-- Agent -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-foreground">Agent <span class="text-red-500">*</span></label>

                    <!-- Agent pré-sélectionné -->
                    <div v-if="agent" class="flex items-center gap-3 rounded-xl border bg-muted/30 px-4 py-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-100">
                            <UserCircle class="h-5 w-5 text-teal-600" />
                        </div>
                        <div>
                            <p class="font-semibold text-foreground">{{ agent.prenom }} {{ agent.nom }}</p>
                            <p class="font-mono text-xs text-blue-700">{{ agent.matricule }}</p>
                        </div>
                        <input type="hidden" v-model="form.agent_id" />
                    </div>

                    <!-- Sélecteur agent -->
                    <select v-else v-model="form.agent_id"
                        class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                        :class="form.errors.agent_id ? 'border-red-400' : ''">
                        <option value="">Sélectionner un agent…</option>
                        <option v-for="a in agents" :key="a.id" :value="a.id">
                            {{ a.prenom }} {{ a.nom }} — {{ a.matricule }}
                        </option>
                    </select>
                    <p v-if="form.errors.agent_id" class="mt-1 text-xs text-red-500">{{ form.errors.agent_id }}</p>
                </div>

                <!-- Info compte si agent sélectionné -->
                <div v-if="selectedAgent?.compte" class="rounded-xl border bg-blue-50 p-4 text-sm">
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div>
                            <p class="text-xs text-muted-foreground">Épargne</p>
                            <p class="font-bold text-foreground">{{ formatAmount(selectedAgent.compte.solde_epargne) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-orange-600">Bloqué</p>
                            <p class="font-bold text-orange-700">{{ formatAmount(selectedAgent.compte.solde_bloque) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-emerald-600">Plafond prêt (×3)</p>
                            <p class="font-bold text-emerald-700">{{ plafond !== null ? formatAmount(plafond) : '—' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Montant demandé -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-foreground">
                        Montant demandé <span class="text-red-500">*</span>
                        <span v-if="plafond" class="ml-1 text-xs text-muted-foreground">(max {{ formatAmount(plafond) }})</span>
                    </label>
                    <input v-model="form.montant_demande" type="number" step="1" min="1"
                        :max="plafond ?? undefined"
                        class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                        :class="form.errors.montant_demande ? 'border-red-400' : ''" />
                    <p v-if="form.errors.montant_demande" class="mt-1 text-xs text-red-500">{{ form.errors.montant_demande }}</p>
                </div>

                <!-- Motif -->
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-foreground">Motif <span class="text-muted-foreground">(optionnel)</span></label>
                    <textarea v-model="form.motif" rows="3"
                        placeholder="Raison de la demande de prêt…"
                        class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none" />
                    <p v-if="form.errors.motif" class="mt-1 text-xs text-red-500">{{ form.errors.motif }}</p>
                </div>

                <button type="submit" :disabled="form.processing"
                    class="flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:bg-primary/90 disabled:opacity-50">
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <HandCoins v-else class="h-4 w-4" />
                    Créer la demande de prêt
                </button>
            </form>
        </div>
    </AppLayout>
</template>
