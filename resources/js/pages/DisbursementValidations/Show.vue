<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type DisbursementRequest, type ValidationLevel } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, CheckCircle2, Clock, Download, Loader2, Paperclip, XCircle } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const props = defineProps<{
    order: DisbursementRequest;
    levels: ValidationLevel[];
    readOnly?: boolean;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    {
        title: props.readOnly ? 'Historique validations DD' : 'Validations DD',
        href: props.readOnly ? route('disbursement-validations.history') : route('disbursement-validations.index'),
    },
    { title: props.order.reference, href: '#' },
];

const rejectForm = useForm({ comment: '' });
const showRejectForm = ref(false);

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const formatDateShort = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' });

const formatSize = (bytes: number) => {
    if (bytes < 1024) return `${bytes} o`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`;
    return `${(bytes / 1024 / 1024).toFixed(1)} Mo`;
};

const levelStatus = (levelOrder: number) => {
    const order = props.order;
    if (order.status === 'approved') return 'approved';
    if (order.status === 'rejected') {
        const rejectedLog = order.validation_logs?.find((l) => l.action === 'rejected');
        const rejectedLevel = props.levels.find((l) => l.id === rejectedLog?.validation_level_id);
        if (rejectedLevel && rejectedLevel.order === levelOrder) return 'rejected';
        if (rejectedLevel && levelOrder < rejectedLevel.order) return 'approved';
        return 'pending';
    }
    if (!order.current_level_order) return 'pending';
    if (levelOrder < order.current_level_order) return 'approved';
    if (levelOrder === order.current_level_order) return 'current';
    return 'pending';
};

const getLogForLevel = (levelOrder: number) => {
    return props.order.validation_logs?.find((l) => {
        const level = props.levels.find((lv) => lv.id === l.validation_level_id);
        return level?.order === levelOrder;
    });
};

const statusBadge = computed(() => {
    const map: Record<string, { label: string; cls: string }> = {
        pending:        { label: 'En attente',         cls: 'bg-amber-100 text-amber-800 border border-amber-200' },
        approved:       { label: 'Approuvée',          cls: 'bg-emerald-100 text-emerald-800 border border-emerald-200' },
        rejected:       { label: 'Refusée',            cls: 'bg-red-100 text-red-800 border border-red-200' },
        needs_revision: { label: 'Révision demandée',  cls: 'bg-orange-100 text-orange-800 border border-orange-200' },
        cancelled:      { label: 'Annulée',            cls: 'bg-gray-100 text-gray-700 border border-gray-200' },
        draft:          { label: 'Brouillon',          cls: 'bg-gray-100 text-gray-700 border border-gray-200' },
    };
    return map[props.order.status] ?? { label: props.order.status, cls: 'bg-gray-100 text-gray-700' };
});

const approveOrder = async () => {
    const result = await Swal.fire({
        title: 'Approuver cette demande ?',
        text: `Vous allez approuver "${props.order.title}" (${formatAmount(props.order.amount)}).`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Approuver',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#10b981',
        reverseButtons: true,
    });
    if (result.isConfirmed) {
        router.post(route('disbursement-validations.approve', props.order.uuid));
    }
};

const submitReject = () => {
    rejectForm.post(route('disbursement-validations.reject', props.order.uuid));
};
</script>

<template>
    <Head :title="`${readOnly ? '[Historique]' : '[Validation]'} ${order.reference}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6">

            <!-- Header -->
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-3">
                    <Link
                        :href="readOnly ? route('disbursement-validations.history') : route('disbursement-validations.index')"
                        class="mt-1 flex h-8 w-8 shrink-0 items-center justify-center rounded-lg border border-gray-200 bg-white text-gray-500 shadow-sm hover:bg-gray-50"
                    >
                        <ArrowLeft class="h-4 w-4" />
                    </Link>
                    <div>
                        <p class="font-mono text-xs font-semibold text-gray-400 uppercase tracking-wider">{{ order.reference }}</p>
                        <h1 class="mt-0.5 text-xl font-bold text-gray-900">{{ order.title }}</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold', statusBadge.cls]">
                                {{ statusBadge.label }}
                            </span>
                            <span v-if="order.boutique" class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 border border-blue-100">
                                {{ order.boutique.name }}
                            </span>
                            <span v-if="order.current_level_order" class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-medium text-indigo-700 border border-indigo-100">
                                <Clock class="h-3 w-3" />
                                Niveau {{ order.current_level_order }} — {{ levels.find(l => l.order === order.current_level_order)?.name }}
                            </span>
                        </div>
                        <p v-if="readOnly" class="mt-2 text-xs font-medium text-amber-600">Mode lecture seule — historique de validation</p>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <!-- Colonne gauche -->
                <div class="space-y-5 lg:col-span-2">

                    <!-- Informations -->
                    <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Informations de la demande</h2>
                        </div>
                        <dl class="divide-y divide-gray-100">
                            <div class="flex items-center justify-between px-5 py-3.5">
                                <dt class="text-sm text-gray-500">Montant demandé</dt>
                                <dd class="text-lg font-bold text-gray-900">{{ formatAmount(order.amount) }}</dd>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3.5">
                                <dt class="text-sm text-gray-500">Nature de l'opération</dt>
                                <dd class="text-sm font-semibold text-gray-800">{{ order.nature_operation?.name ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3.5">
                                <dt class="text-sm text-gray-500">Demandeur</dt>
                                <dd class="text-sm font-semibold text-gray-800">{{ order.user?.name ?? '—' }}</dd>
                            </div>
                            <div v-if="order.boutique" class="flex items-center justify-between px-5 py-3.5">
                                <dt class="text-sm text-gray-500">Boutique</dt>
                                <dd class="text-sm font-semibold text-gray-800">{{ order.boutique.name }}</dd>
                            </div>
                            <div v-if="order.submitted_at" class="flex items-center justify-between px-5 py-3.5">
                                <dt class="text-sm text-gray-500">Soumis le</dt>
                                <dd class="text-sm font-semibold text-gray-800">{{ formatDate(order.submitted_at) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Justification -->
                    <div v-if="order.description" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Justification</h2>
                        </div>
                        <div class="px-5 py-4 text-sm leading-relaxed text-gray-700 whitespace-pre-wrap">{{ order.description }}</div>
                    </div>

                    <!-- Pièces jointes -->
                    <div v-if="order.attachments?.length" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">
                                Pièces jointes
                                <span class="ml-1.5 rounded-full bg-gray-200 px-2 py-0.5 text-xs font-bold text-gray-600">{{ order.attachments.length }}</span>
                            </h2>
                        </div>
                        <ul class="divide-y divide-gray-100">
                            <li v-for="att in order.attachments" :key="att.id">
                                <a
                                    :href="route('disbursement-requests.attachments.download', { disbursement_request: order.uuid, attachment: att.id })"
                                    target="_blank"
                                    class="flex items-center gap-3 px-5 py-3 text-sm transition-colors hover:bg-indigo-50 group"
                                >
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600 group-hover:bg-indigo-100">
                                        <Paperclip class="h-4 w-4" />
                                    </div>
                                    <span class="flex-1 truncate font-medium text-gray-800 group-hover:text-indigo-700 group-hover:underline">{{ att.file_name }}</span>
                                    <span class="text-xs font-medium text-gray-400">{{ formatSize(att.file_size) }}</span>
                                    <Download class="h-4 w-4 shrink-0 text-gray-300 group-hover:text-indigo-500" />
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Décision -->
                    <div v-if="!readOnly" class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                        <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Votre décision</h2>
                        </div>
                        <div class="px-5 py-5 space-y-4">
                            <div v-if="!showRejectForm" class="flex flex-col gap-3 sm:flex-row">
                                <button @click="approveOrder"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 active:scale-95 transition-all">
                                    <CheckCircle2 class="h-4 w-4" />
                                    Approuver la demande
                                </button>
                                <button @click="showRejectForm = true"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl border-2 border-red-200 bg-red-50 px-5 py-3 text-sm font-semibold text-red-700 hover:bg-red-100 active:scale-95 transition-all">
                                    <XCircle class="h-4 w-4" />
                                    Refuser la demande
                                </button>
                            </div>

                            <div v-else class="space-y-3">
                                <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                                    <p class="mb-2 text-sm font-semibold text-red-700">Motif du refus <span class="text-red-500">*</span></p>
                                    <textarea
                                        v-model="rejectForm.comment"
                                        rows="4"
                                        placeholder="Précisez le motif du refus..."
                                        class="w-full rounded-xl border border-red-200 bg-white px-3 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:outline-none focus:ring-2 focus:ring-red-400"
                                        :class="rejectForm.errors.comment ? 'border-red-500' : ''"
                                    />
                                    <p v-if="rejectForm.errors.comment" class="mt-1 flex items-center gap-1 text-xs text-red-600">
                                        <AlertCircle class="h-3.5 w-3.5" /> {{ rejectForm.errors.comment }}
                                    </p>
                                </div>
                                <div class="flex gap-2 justify-end">
                                    <button @click="showRejectForm = false"
                                        class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                        Annuler
                                    </button>
                                    <button @click="submitReject" :disabled="rejectForm.processing"
                                        class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700 disabled:opacity-50">
                                        <Loader2 v-if="rejectForm.processing" class="h-3.5 w-3.5 animate-spin" />
                                        Confirmer le refus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Circuit de validation -->
                <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                    <div class="border-b border-gray-100 bg-gray-50 px-5 py-3">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-gray-500">Circuit de validation</h2>
                    </div>
                    <ul class="space-y-0 px-5 py-4">
                        <li v-for="level in levels" :key="level.id" class="relative pb-6 last:pb-0">
                            <!-- Ligne verticale entre niveaux -->
                            <span
                                v-if="level !== levels[levels.length - 1]"
                                class="absolute left-3.5 top-7 h-full w-0.5"
                                :class="levelStatus(level.order) === 'approved' ? 'bg-emerald-300' : 'bg-gray-200'"
                            />
                            <div class="flex items-start gap-3">
                                <!-- Badge numéro -->
                                <div
                                    class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                    :class="{
                                        'bg-emerald-500 text-white ring-2 ring-emerald-200':    levelStatus(level.order) === 'approved',
                                        'bg-amber-400 text-white ring-2 ring-amber-200 animate-pulse': levelStatus(level.order) === 'current',
                                        'bg-red-500 text-white ring-2 ring-red-200':             levelStatus(level.order) === 'rejected',
                                        'bg-gray-200 text-gray-500':                             levelStatus(level.order) === 'pending',
                                    }"
                                >
                                    {{ level.order }}
                                </div>
                                <div class="flex-1 min-w-0 pt-0.5">
                                    <p class="text-sm font-semibold text-gray-800">{{ level.name }}</p>
                                    <template v-if="getLogForLevel(level.order)">
                                        <p class="mt-0.5 text-xs font-medium text-gray-500">
                                            {{ getLogForLevel(level.order)?.user?.name ?? '?' }}
                                            <span class="text-gray-400">·</span>
                                            {{ formatDateShort(getLogForLevel(level.order)!.created_at) }}
                                        </p>
                                        <p v-if="getLogForLevel(level.order)?.comment"
                                            class="mt-1.5 rounded-lg border border-red-100 bg-red-50 px-2.5 py-1.5 text-xs text-red-700">
                                            {{ getLogForLevel(level.order)?.comment }}
                                        </p>
                                    </template>
                                    <p v-else-if="levelStatus(level.order) === 'current'"
                                        class="mt-0.5 text-xs font-semibold text-amber-600">
                                        En cours d'examen
                                    </p>
                                    <p v-else class="mt-0.5 text-xs text-gray-400">En attente</p>
                                </div>
                            </div>
                        </li>
                    </ul>

                    <!-- Historique des validations -->
                    <div v-if="order.validation_logs?.length" class="border-t border-gray-100 px-5 py-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">Historique</p>
                        <ul class="space-y-2">
                            <li v-for="log in order.validation_logs" :key="log.id"
                                class="flex items-start gap-2 rounded-xl p-2.5"
                                :class="log.action === 'approved' ? 'bg-emerald-50' : 'bg-red-50'">
                                <div class="mt-0.5 h-2 w-2 shrink-0 rounded-full"
                                    :class="log.action === 'approved' ? 'bg-emerald-500' : 'bg-red-500'" />
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-semibold"
                                        :class="log.action === 'approved' ? 'text-emerald-700' : 'text-red-700'">
                                        {{ log.action === 'approved' ? 'Approuvé' : 'Refusé' }}
                                        <span class="font-normal text-gray-500">par {{ log.user?.name ?? '?' }}</span>
                                    </p>
                                    <p v-if="log.comment" class="mt-0.5 text-xs text-gray-600">{{ log.comment }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
