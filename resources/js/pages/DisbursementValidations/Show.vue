<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type DisbursementRequest, type ValidationLevel } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { AlertCircle, CheckCircle2, Loader2, Paperclip, XCircle } from 'lucide-vue-next';
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
        <div class="mx-auto max-w-4xl space-y-6 px-3 py-6 sm:px-6">
            <!-- Header -->
            <div>
                <p class="font-mono text-xs text-muted-foreground">{{ order.reference }}</p>
                <h1 class="mt-1 text-2xl font-bold">{{ order.title }}</h1>
                <p v-if="readOnly" class="mt-1 text-sm text-amber-600 font-medium">Mode lecture seule — historique de validation</p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Left: info + attachments -->
                <div class="space-y-5 lg:col-span-2">
                    <!-- Info card -->
                    <div class="rounded-2xl border bg-card shadow-sm">
                        <div class="border-b px-5 py-4">
                            <h2 class="font-semibold">Informations de la demande</h2>
                        </div>
                        <dl class="divide-y px-5">
                            <div class="flex items-center justify-between py-3 text-sm">
                                <dt class="text-muted-foreground">Montant demandé</dt>
                                <dd class="text-lg font-semibold">{{ formatAmount(order.amount) }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-3 text-sm">
                                <dt class="text-muted-foreground">Nature de l'opération</dt>
                                <dd>{{ order.nature_operation?.name ?? '—' }}</dd>
                            </div>
                            <div class="flex items-center justify-between py-3 text-sm">
                                <dt class="text-muted-foreground">Demandeur</dt>
                                <dd>{{ order.user?.name ?? '—' }}</dd>
                            </div>
                            <div v-if="order.boutique" class="flex items-center justify-between py-3 text-sm">
                                <dt class="text-muted-foreground">Boutique</dt>
                                <dd>{{ order.boutique.name }}</dd>
                            </div>
                            <div v-if="order.submitted_at" class="flex items-center justify-between py-3 text-sm">
                                <dt class="text-muted-foreground">Soumis le</dt>
                                <dd>{{ formatDate(order.submitted_at) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <!-- Description -->
                    <div v-if="order.description" class="rounded-2xl border bg-card shadow-sm">
                        <div class="border-b px-5 py-4">
                            <h2 class="font-semibold">Justification</h2>
                        </div>
                        <div class="px-5 py-4 text-sm text-muted-foreground whitespace-pre-wrap">{{ order.description }}</div>
                    </div>

                    <!-- Attachments -->
                    <div v-if="order.attachments?.length" class="rounded-2xl border bg-card shadow-sm">
                        <div class="border-b px-5 py-4">
                            <h2 class="font-semibold">Pièces jointes ({{ order.attachments.length }})</h2>
                        </div>
                        <ul class="divide-y px-5">
                            <li v-for="att in order.attachments" :key="att.id" class="flex items-center gap-2 py-3 text-sm">
                                <Paperclip class="h-4 w-4 shrink-0 text-muted-foreground" />
                                <span class="truncate flex-1">{{ att.file_name }}</span>
                                <span class="text-xs text-muted-foreground">{{ formatSize(att.file_size) }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Decision block (not readOnly) -->
                    <div v-if="!readOnly" class="rounded-2xl border bg-card shadow-sm">
                        <div class="border-b px-5 py-4">
                            <h2 class="font-semibold">Votre décision</h2>
                        </div>
                        <div class="px-5 py-5 space-y-4">
                            <div v-if="!showRejectForm" class="flex flex-col gap-3 sm:flex-row">
                                <button @click="approveOrder"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-emerald-700">
                                    <CheckCircle2 class="h-4 w-4" /> Approuver
                                </button>
                                <button @click="showRejectForm = true"
                                    class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl border border-red-300 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50">
                                    <XCircle class="h-4 w-4" /> Refuser
                                </button>
                            </div>

                            <div v-else class="space-y-3">
                                <p class="text-sm font-medium text-red-600">Motif du refus</p>
                                <textarea
                                    v-model="rejectForm.comment"
                                    rows="4"
                                    placeholder="Précisez le motif du refus..."
                                    class="w-full rounded-xl border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-400"
                                    :class="rejectForm.errors.comment ? 'border-red-500' : ''"
                                />
                                <p v-if="rejectForm.errors.comment" class="flex items-center gap-1 text-xs text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5" /> {{ rejectForm.errors.comment }}
                                </p>
                                <div class="flex gap-2 justify-end">
                                    <button @click="showRejectForm = false" class="rounded-xl border px-3 py-1.5 text-sm hover:bg-accent">Annuler</button>
                                    <button @click="submitReject" :disabled="rejectForm.processing"
                                        class="inline-flex items-center gap-1.5 rounded-xl bg-red-600 px-4 py-1.5 text-sm text-white hover:bg-red-700 disabled:opacity-50">
                                        <Loader2 v-if="rejectForm.processing" class="h-3.5 w-3.5 animate-spin" />
                                        Confirmer le refus
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Circuit -->
                <div class="rounded-2xl border bg-card shadow-sm">
                    <div class="border-b px-5 py-4">
                        <h2 class="font-semibold">Circuit de validation</h2>
                    </div>
                    <ul class="space-y-0 px-5 py-4">
                        <li v-for="level in levels" :key="level.id" class="relative pb-6 last:pb-0">
                            <span v-if="level !== levels[levels.length - 1]" class="absolute left-3.5 top-7 h-full w-0.5"
                                :class="levelStatus(level.order) === 'approved' ? 'bg-emerald-300' : 'bg-muted'" />
                            <div class="flex items-start gap-3">
                                <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700 ring-2 ring-emerald-300': levelStatus(level.order) === 'approved',
                                        'bg-amber-100 text-amber-700 ring-2 ring-amber-300 animate-pulse': levelStatus(level.order) === 'current',
                                        'bg-red-100 text-red-700 ring-2 ring-red-300': levelStatus(level.order) === 'rejected',
                                        'bg-muted text-muted-foreground': levelStatus(level.order) === 'pending',
                                    }">
                                    {{ level.order }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-sm">{{ level.name }}</p>
                                    <template v-if="getLogForLevel(level.order)">
                                        <p class="text-xs text-muted-foreground mt-0.5">
                                            {{ getLogForLevel(level.order)?.user?.name ?? '?' }} ·
                                            {{ new Date(getLogForLevel(level.order)!.created_at).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short' }) }}
                                        </p>
                                        <p v-if="getLogForLevel(level.order)?.comment" class="mt-1 text-xs rounded bg-muted p-2">{{ getLogForLevel(level.order)?.comment }}</p>
                                    </template>
                                    <p v-else-if="levelStatus(level.order) === 'current'" class="text-xs text-amber-600 mt-0.5">En cours d'examen</p>
                                    <p v-else class="text-xs text-muted-foreground mt-0.5">En attente</p>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
