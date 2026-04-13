<script setup lang="ts">
import { type Article, type FournisseurPrix } from '@/types';
import axios from 'axios';
import { AlertTriangle, CheckCircle2, ChevronDown, Clock, Loader2, ShieldAlert, ShieldCheck, Tag, X } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps<{
    article: Article | null;
    currentFournisseurId?: number | '';
    currentPrice?: number;
}>();

const emit = defineEmits<{
    (e: 'select', payload: { fournisseur_id: number; unit_price: number }): void;
}>();

// ─── State ───────────────────────────────────────────────────────────────────
const open = ref(false);
const loading = ref(false);
const error = ref(false);
const prix = ref<FournisseurPrix[]>([]);
const rootEl = ref<HTMLElement | null>(null);

// ─── Fermeture au clic extérieur ─────────────────────────────────────────────
const onClickOutside = (e: MouseEvent) => {
    if (open.value && rootEl.value && !rootEl.value.contains(e.target as Node)) {
        open.value = false;
    }
};

onMounted(() => document.addEventListener('mousedown', onClickOutside));
onBeforeUnmount(() => document.removeEventListener('mousedown', onClickOutside));

// ─── Chargement des prix ──────────────────────────────────────────────────────
const loadPrix = async () => {
    if (!props.article?.id) return;
    loading.value = true;
    error.value = false;
    try {
        const res = await axios.get<FournisseurPrix[]>(`/articles/${props.article.id}/prix-fournisseurs`);
        prix.value = res.data;
    } catch {
        error.value = true;
    } finally {
        loading.value = false;
    }
};

// Ferme et réinitialise dès que l'article change (y compris passage à null)
watch(() => props.article?.id, () => {
    prix.value = [];
    open.value = false;
    error.value = false;
});

const toggle = async () => {
    if (!open.value && prix.value.length === 0) {
        await loadPrix();
    }
    open.value = !open.value;
};

const close = () => { open.value = false; };

// ─── Sélection ───────────────────────────────────────────────────────────────
const select = (p: FournisseurPrix) => {
    emit('select', { fournisseur_id: p.fournisseur_id, unit_price: p.unit_price });
    open.value = false;
};

// ─── Helpers ─────────────────────────────────────────────────────────────────
const hasPrix = computed(() => prix.value.length > 0);
const bestPrice = computed(() => prix.value[0]?.unit_price ?? null);

const formatPrice = (v: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(v);

const isSelected = (p: FournisseurPrix) =>
    p.fournisseur_id === Number(props.currentFournisseurId) &&
    Math.abs(p.unit_price - (props.currentPrice ?? 0)) < 0.01;

const priceDiff = (price: number) => {
    if (!props.currentPrice || props.currentPrice === 0) return null;
    const diff = ((price - props.currentPrice) / props.currentPrice) * 100;
    return Math.round(diff);
};

const isExpired = (p: FournisseurPrix) =>
    p.valide_jusqu_au ? new Date(p.valide_jusqu_au) < new Date() : false;
</script>

<template>
    <div ref="rootEl" class="relative">
        <!-- Bouton déclencheur -->
        <button
            v-if="article"
            type="button"
            @click="toggle"
            class="inline-flex items-center gap-1.5 rounded-lg border border-violet-200 bg-violet-50 px-2.5 py-1.5 text-xs font-medium text-violet-700 transition-colors hover:bg-violet-100 dark:border-violet-900 dark:bg-violet-950/30 dark:text-violet-300 dark:hover:bg-violet-950/50"
            :title="`Comparer les prix fournisseurs pour ${article.name}`"
        >
            <Tag class="h-3.5 w-3.5" />
            Comparer
            <Loader2 v-if="loading" class="h-3 w-3 animate-spin" />
            <ChevronDown v-else class="h-3 w-3 transition-transform" :class="{ 'rotate-180': open }" />
        </button>

        <!-- Panneau comparateur -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-if="open"
                class="absolute left-0 top-full z-50 mt-1.5 w-80 overflow-hidden rounded-xl border bg-card shadow-xl"
            >
                <!-- En-tête -->
                <div class="flex items-center justify-between border-b bg-muted/30 px-4 py-2.5">
                    <div class="flex items-center gap-2">
                        <Tag class="h-4 w-4 text-violet-600 dark:text-violet-300" />
                        <span class="text-xs font-semibold text-foreground">Prix fournisseurs</span>
                    </div>
                    <button
                        type="button"
                        @click="close"
                        class="rounded-lg p-1 text-muted-foreground transition-colors hover:bg-muted"
                    >
                        <X class="h-3.5 w-3.5" />
                    </button>
                </div>

                <!-- Article ciblé -->
                <div class="border-b bg-muted/10 px-4 py-2">
                    <p class="truncate text-xs font-medium text-foreground">{{ article?.name }}</p>
                    <p class="mt-0.5 font-mono text-xs text-muted-foreground">{{ article?.reference ?? 'Sans référence' }}</p>
                </div>

                <!-- Chargement -->
                <div v-if="loading" class="flex items-center justify-center gap-2 py-8 text-sm text-muted-foreground">
                    <Loader2 class="h-4 w-4 animate-spin" />
                    Chargement…
                </div>

                <!-- Erreur -->
                <div v-else-if="error" class="px-4 py-6 text-center">
                    <AlertTriangle class="mx-auto h-6 w-6 text-amber-500" />
                    <p class="mt-2 text-xs text-muted-foreground">Impossible de charger les prix.</p>
                </div>

                <!-- Aucun prix -->
                <div v-else-if="!hasPrix" class="px-4 py-6 text-center">
                    <Tag class="mx-auto h-6 w-6 text-muted-foreground/40" />
                    <p class="mt-2 text-xs font-medium text-foreground">Aucun tarif enregistré</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        Ajoutez des tarifs dans la fiche de chaque fournisseur.
                    </p>
                </div>

                <!-- Liste des prix -->
                <ul v-else class="max-h-64 divide-y divide-border/60 overflow-y-auto">
                    <li
                        v-for="(p, idx) in prix"
                        :key="p.fournisseur_id"
                        class="group relative flex items-start gap-3 px-4 py-3 transition-colors"
                        :class="isSelected(p) ? 'bg-violet-50 dark:bg-violet-950/20' : 'hover:bg-muted/30'"
                    >
                        <!-- Rang -->
                        <div
                            class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                            :class="idx === 0
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300'
                                : 'bg-muted text-muted-foreground'"
                        >
                            {{ idx + 1 }}
                        </div>

                        <!-- Infos -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5">
                                <span class="truncate text-xs font-semibold text-foreground">{{ p.fournisseur_name }}</span>
                                <ShieldCheck
                                    v-if="p.fournisseur_is_approved"
                                    class="h-3 w-3 shrink-0 text-emerald-500"
                                    title="Fournisseur homologué"
                                />
                                <ShieldAlert
                                    v-else
                                    class="h-3 w-3 shrink-0 text-amber-500"
                                    title="Non homologué"
                                />
                            </div>

                            <div class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-0.5">
                                <!-- Prix -->
                                <span
                                    class="text-sm font-bold"
                                    :class="idx === 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-foreground'"
                                >
                                    {{ formatPrice(p.unit_price) }}
                                </span>

                                <!-- Ecart vs prix courant -->
                                <span
                                    v-if="currentPrice && currentPrice > 0 && !isSelected(p)"
                                    class="text-xs font-medium"
                                    :class="priceDiff(p.unit_price)! < 0
                                        ? 'text-emerald-600 dark:text-emerald-400'
                                        : 'text-red-600 dark:text-red-400'"
                                >
                                    {{ priceDiff(p.unit_price)! > 0 ? '+' : '' }}{{ priceDiff(p.unit_price) }}%
                                </span>

                                <!-- Délai -->
                                <span
                                    v-if="p.delai_livraison_jours"
                                    class="flex items-center gap-0.5 text-xs text-muted-foreground"
                                >
                                    <Clock class="h-3 w-3" />
                                    {{ p.delai_livraison_jours }}j
                                </span>
                            </div>

                            <!-- Réf fournisseur -->
                            <p v-if="p.reference_fournisseur" class="mt-0.5 font-mono text-xs text-muted-foreground">
                                Réf. {{ p.reference_fournisseur }}
                            </p>

                            <!-- Expiration -->
                            <p v-if="p.valide_jusqu_au" class="mt-0.5 text-xs text-muted-foreground">
                                Valide jusqu'au {{ new Date(p.valide_jusqu_au).toLocaleDateString('fr-FR') }}
                                <span v-if="isExpired(p)" class="ml-1 font-medium text-red-500">(expiré)</span>
                            </p>
                        </div>

                        <!-- Bouton choisir -->
                        <div class="shrink-0">
                            <button
                                v-if="!isSelected(p)"
                                type="button"
                                @click="select(p)"
                                class="rounded-lg border border-violet-200 bg-white px-2.5 py-1 text-xs font-medium text-violet-700 shadow-sm transition-colors hover:bg-violet-50 dark:border-violet-800 dark:bg-transparent dark:text-violet-300 dark:hover:bg-violet-950/50"
                            >
                                Choisir
                            </button>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 rounded-lg bg-violet-100 px-2.5 py-1 text-xs font-medium text-violet-700 dark:bg-violet-950/40 dark:text-violet-300"
                            >
                                <CheckCircle2 class="h-3 w-3" />
                                Sélectionné
                            </span>
                        </div>
                    </li>
                </ul>

                <!-- Footer -->
                <div v-if="hasPrix" class="border-t bg-muted/10 px-4 py-2">
                    <p class="text-xs text-muted-foreground">
                        Meilleur prix : <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ formatPrice(bestPrice!) }}</span>
                        — {{ prix.length }} fournisseur(s) référencé(s)
                    </p>
                </div>
            </div>
        </Transition>
    </div>
</template>
