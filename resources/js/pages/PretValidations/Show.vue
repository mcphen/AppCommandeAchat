<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Pret, type ValidationLevel } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft, Building2, CheckCircle2, HandCoins, Loader2, ThumbsDown, ThumbsUp, XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    pret: Pret;
    levels: ValidationLevel[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Validations Prêts', href: route('pret-validations.index') },
    { title: `Prêt #${props.pret.id}`, href: '#' },
];

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));
const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const rejectForm = useForm({ comment: '' });

const approving = ref(false);
function approve() {
    if (!confirm('Approuver cette demande de prêt ?')) return;
    approving.value = true;
    router.post(route('pret-validations.approve', props.pret.id), {}, {
        onFinish: () => { approving.value = false; },
    });
}
function reject() {
    rejectForm.post(route('pret-validations.reject', props.pret.id));
}

const currentLevelName = computed(() => {
    if (!props.pret.current_level_order) return null;
    return props.levels.find(l => l.order === props.pret.current_level_order)?.name ?? null;
});

const soldeDispo = computed(() => {
    if (!props.pret.compte_epargne) return null;
    return Math.max(0, Number(props.pret.compte_epargne.solde_epargne) - Number(props.pret.compte_epargne.solde_bloque));
});
</script>

<template>
    <Head :title="`Validation prêt #${pret.id}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl flex flex-col gap-6 p-3 sm:p-6">

            <!-- En-tête -->
            <div class="flex items-center gap-4">
                <Link :href="route('pret-validations.index')"
                    class="flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold text-muted-foreground hover:bg-muted">
                    <ArrowLeft class="h-3.5 w-3.5" /> Retour
                </Link>
                <div>
                    <h1 class="text-xl font-bold text-foreground">Demande de prêt #{{ pret.id }}</h1>
                    <p v-if="currentLevelName" class="text-sm text-yellow-700 font-medium">En attente : {{ currentLevelName }}</p>
                </div>
            </div>

            <!-- Infos agent + montant -->
            <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                <div class="flex items-start gap-4">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-teal-100">
                        <HandCoins class="h-6 w-6 text-teal-600" />
                    </div>
                    <div class="flex-1">
                        <p class="text-lg font-bold text-foreground">
                            {{ pret.agent ? `${pret.agent.prenom} ${pret.agent.nom}` : '—' }}
                        </p>
                        <div class="mt-1 flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
                            <span v-if="pret.agent?.matricule" class="font-mono font-bold text-blue-700">{{ pret.agent.matricule }}</span>
                            <span v-if="pret.agent?.boutique" class="flex items-center gap-1">
                                <Building2 class="h-3.5 w-3.5" /> {{ pret.agent.boutique.name }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Montants -->
                <div class="grid grid-cols-2 gap-4 border-t pt-4 sm:grid-cols-3">
                    <div>
                        <p class="text-xs text-muted-foreground">Montant demandé</p>
                        <p class="mt-0.5 text-xl font-bold text-foreground">{{ formatAmount(pret.montant_demande) }}</p>
                    </div>
                    <div v-if="pret.compte_epargne">
                        <p class="text-xs text-muted-foreground">Épargne totale</p>
                        <p class="mt-0.5 text-xl font-bold text-foreground">{{ formatAmount(pret.compte_epargne.solde_epargne) }}</p>
                    </div>
                    <div v-if="soldeDispo !== null">
                        <p class="text-xs text-emerald-600">Solde disponible</p>
                        <p class="mt-0.5 text-xl font-bold text-emerald-700">{{ formatAmount(soldeDispo) }}</p>
                    </div>
                </div>

                <!-- Motif -->
                <div v-if="pret.motif" class="rounded-xl bg-muted/40 px-4 py-3">
                    <p class="text-xs text-muted-foreground mb-1">Motif</p>
                    <p class="text-sm text-foreground italic">{{ pret.motif }}</p>
                </div>

                <!-- Date soumission -->
                <p v-if="pret.submitted_at" class="text-xs text-muted-foreground">
                    Soumis le {{ formatDate(pret.submitted_at) }}
                </p>
            </div>

            <!-- Circuit de validation -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <h2 class="mb-4 font-semibold text-foreground">Circuit de validation</h2>
                <div class="flex flex-col gap-2">
                    <div v-for="level in levels" :key="level.id"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm"
                        :class="pret.current_level_order === level.order ? 'bg-yellow-50 border border-yellow-200' : 'bg-muted/20'">
                        <template v-if="pret.validation_logs?.some(l => l.validation_level?.order === level.order && l.action === 'approved')">
                            <CheckCircle2 class="h-4 w-4 shrink-0 text-emerald-600" />
                            <span class="font-medium text-emerald-700">{{ level.name }}</span>
                            <span class="ml-auto text-xs text-emerald-600">Approuvé</span>
                        </template>
                        <template v-else-if="pret.validation_logs?.some(l => l.validation_level?.order === level.order && l.action === 'rejected')">
                            <XCircle class="h-4 w-4 shrink-0 text-red-500" />
                            <span class="font-medium text-red-600">{{ level.name }}</span>
                            <span class="ml-auto text-xs text-red-500">Rejeté</span>
                        </template>
                        <template v-else-if="pret.current_level_order === level.order">
                            <span class="h-2 w-2 shrink-0 rounded-full bg-yellow-400 animate-pulse" />
                            <span class="font-semibold text-yellow-800">{{ level.name }}</span>
                            <span class="ml-auto text-xs font-semibold text-yellow-700">En attente</span>
                        </template>
                        <template v-else>
                            <span class="h-2 w-2 shrink-0 rounded-full bg-muted-foreground/30" />
                            <span class="text-muted-foreground">{{ level.name }}</span>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Historique logs existants -->
            <div v-if="(pret.validation_logs ?? []).length" class="rounded-2xl border bg-card shadow-sm">
                <div class="border-b px-6 py-4">
                    <h2 class="font-semibold text-foreground">Historique</h2>
                </div>
                <div class="divide-y">
                    <div v-for="log in pret.validation_logs" :key="log.id" class="flex items-start gap-3 px-5 py-4">
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg"
                            :class="log.action === 'approved' ? 'bg-emerald-50' : 'bg-red-50'">
                            <CheckCircle2 v-if="log.action === 'approved'" class="h-4 w-4 text-emerald-600" />
                            <XCircle v-else class="h-4 w-4 text-red-500" />
                        </div>
                        <div>
                            <div class="flex flex-wrap items-center gap-2 text-sm">
                                <span class="font-semibold">{{ log.validation_level?.name }}</span>
                                <span :class="log.action === 'approved' ? 'text-emerald-600' : 'text-red-500'">
                                    {{ log.action === 'approved' ? '— Approuvé' : '— Rejeté' }}
                                </span>
                                <span class="text-muted-foreground">par {{ log.user?.name }}</span>
                            </div>
                            <p v-if="log.comment" class="mt-0.5 text-xs text-muted-foreground italic">{{ log.comment }}</p>
                            <p class="mt-0.5 text-xs text-muted-foreground">{{ formatDate(log.created_at) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions validation -->
            <div class="rounded-2xl border border-yellow-200 bg-yellow-50 p-5 shadow-sm">
                <h2 class="mb-4 font-semibold text-yellow-900">Votre décision</h2>

                <!-- Rejet avec commentaire -->
                <form @submit.prevent="reject" class="mb-4">
                    <label class="mb-1.5 block text-sm font-medium text-foreground">
                        Motif de rejet <span class="text-red-500">*</span>
                        <span class="text-muted-foreground">(min. 10 caractères)</span>
                    </label>
                    <textarea v-model="rejectForm.comment" rows="3"
                        placeholder="Expliquez la raison du rejet…"
                        class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-300 resize-none"
                        :class="rejectForm.errors.comment ? 'border-red-400' : ''" />
                    <p v-if="rejectForm.errors.comment" class="mt-1 text-xs text-red-500">{{ rejectForm.errors.comment }}</p>
                </form>

                <div class="flex gap-3">
                    <button @click="approve" :disabled="approving"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50">
                        <Loader2 v-if="approving" class="h-4 w-4 animate-spin" />
                        <ThumbsUp v-else class="h-4 w-4" />
                        Approuver
                    </button>
                    <button @click="reject" :disabled="rejectForm.processing || rejectForm.comment.trim().length < 10"
                        class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50">
                        <Loader2 v-if="rejectForm.processing" class="h-4 w-4 animate-spin" />
                        <ThumbsDown v-else class="h-4 w-4" />
                        Rejeter
                    </button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
