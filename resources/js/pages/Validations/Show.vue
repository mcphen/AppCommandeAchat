<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PurchaseOrder, type ValidationLevel } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft, FileText, Download, CheckCircle2, XCircle,
    AlertTriangle, DollarSign, User, Calendar, Paperclip, Loader2,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Validations', href: '/validations' },
    { title: 'Examiner', href: '#' },
];

const props = defineProps<{
    order: PurchaseOrder;
    levels: ValidationLevel[];
}>();

const showRejectModal = ref(false);

const rejectForm = useForm({ comment: '' });
const approveForm = useForm({});

const approve = async () => {
    const result = await Swal.fire({
        title: 'Approuver cette commande ?',
        text: 'Votre approbation sera enregistrée et la commande avancera dans le circuit de validation.',
        icon: 'success',
        showCancelButton: true,
        confirmButtonText: 'Approuver',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) {
        approveForm.post(route('validations.approve', props.order.id));
    }
};

const reject = () => {
    rejectForm.post(route('validations.reject', props.order.id), {
        onSuccess: () => {
            showRejectModal.value = false;
            rejectForm.reset();
        },
    });
};

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const formatSize = (bytes: number) => {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    return (bytes / 1024).toFixed(0) + ' KB';
};
</script>

<template>
    <Head :title="`Validation — ${order.title}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 max-w-4xl sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-start gap-4">
                <Link :href="route('validations.index')" class="mt-1 rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div class="flex-1">
                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">Commande à valider</p>
                    <h1 class="text-2xl font-bold text-foreground">{{ order.title }}</h1>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6">
                <div class="col-span-2 flex flex-col gap-5">

                    <!-- Infos commande -->
                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground mb-4">Informations</h2>
                        <div class="grid grid-cols-2 gap-4 mb-5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50"><DollarSign class="h-4 w-4 text-blue-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Montant</p>
                                    <p class="font-bold text-foreground text-lg">{{ formatAmount(order.amount) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50"><User class="h-4 w-4 text-purple-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Demandeur</p>
                                    <p class="font-medium text-foreground">{{ order.user?.name }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 col-span-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-orange-50"><Calendar class="h-4 w-4 text-orange-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Soumise le</p>
                                    <p class="font-medium text-foreground">{{ order.submitted_at ? formatDate(order.submitted_at) : '—' }}</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-2">Description</p>
                            <p class="text-sm text-foreground leading-relaxed whitespace-pre-wrap">{{ order.description }}</p>
                        </div>
                    </div>

                    <!-- Pièces jointes -->
                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground mb-4 flex items-center gap-2">
                            <Paperclip class="h-4 w-4" />
                            Pièces jointes ({{ order.attachments?.length ?? 0 }})
                        </h2>
                        <div v-if="order.attachments?.length" class="flex flex-col gap-2">
                            <a
                                v-for="att in order.attachments"
                                :key="att.id"
                                :href="route('attachments.download', att.id)"
                                target="_blank"
                                class="flex items-center justify-between rounded-xl border bg-muted/30 px-4 py-3 hover:bg-muted/60 transition-colors group"
                            >
                                <div class="flex items-center gap-3 min-w-0">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50">
                                        <FileText class="h-4 w-4 text-red-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-foreground truncate">{{ att.file_name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ formatSize(att.file_size) }}</p>
                                    </div>
                                </div>
                                <Download class="h-4 w-4 text-muted-foreground group-hover:text-foreground transition-colors" />
                            </a>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">Aucune pièce jointe</p>
                    </div>

                    <!-- Boutons de décision -->
                    <div class="rounded-2xl border-2 border-dashed border-border bg-card p-6">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground mb-1">Votre décision</h2>
                        <p class="text-xs text-muted-foreground mb-5">Cette action est irréversible. Un email sera envoyé automatiquement.</p>
                        <div class="flex gap-3">
                            <button
                                @click="approve"
                                :disabled="approveForm.processing"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700 transition-colors disabled:opacity-70"
                            >
                                <Loader2 v-if="approveForm.processing" class="h-4 w-4 animate-spin" />
                                <CheckCircle2 v-else class="h-4 w-4" />
                                Approuver
                            </button>
                            <button
                                @click="showRejectModal = true"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-red-700 transition-colors"
                            >
                                <XCircle class="h-4 w-4" />
                                Refuser
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Circuit + Historique -->
                <div class="flex flex-col gap-5">
                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground mb-4">Circuit</h2>
                        <div class="relative flex flex-col gap-0">
                            <div v-for="(level, idx) in levels" :key="level.id" class="relative flex items-start gap-3 pb-4">
                                <div v-if="idx < levels.length - 1" class="absolute left-3.5 top-7 h-full w-px bg-border" />
                                <div
                                    class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700': order.current_level_order! > level.order,
                                        'bg-primary text-primary-foreground ring-2 ring-primary/30 shadow-sm': order.current_level_order === level.order,
                                        'bg-muted text-muted-foreground': order.current_level_order! < level.order,
                                    }"
                                >
                                    <CheckCircle2 v-if="order.current_level_order! > level.order" class="h-3.5 w-3.5" />
                                    <span v-else>{{ level.order }}</span>
                                </div>
                                <div class="pt-0.5">
                                    <p class="text-sm font-semibold text-foreground leading-tight">{{ level.name }}</p>
                                    <p v-if="order.current_level_order === level.order" class="text-xs text-primary font-medium mt-0.5">← Niveau actuel</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="order.validation_logs?.length" class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground mb-4">Historique</h2>
                        <div class="flex flex-col gap-3">
                            <div
                                v-for="log in order.validation_logs"
                                :key="log.id"
                                class="rounded-xl p-3 text-sm"
                                :class="log.action === 'approved' ? 'bg-emerald-50 border border-emerald-100' : 'bg-red-50 border border-red-100'"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-semibold" :class="log.action === 'approved' ? 'text-emerald-700' : 'text-red-700'">
                                        {{ log.action === 'approved' ? 'Approuvée' : 'Refusée' }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">{{ log.validation_level?.name }}</span>
                                </div>
                                <p class="text-xs text-muted-foreground">par {{ log.user?.name }}</p>
                                <p v-if="log.comment" class="mt-2 text-xs italic text-foreground/80 border-t border-current/10 pt-2">"{{ log.comment }}"</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de refus -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="scale-95 opacity-0"
                    enter-to-class="scale-100 opacity-100"
                >
                    <div class="w-full max-w-md rounded-2xl bg-card border shadow-xl p-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100">
                                <AlertTriangle class="h-5 w-5 text-red-600" />
                            </div>
                            <div>
                                <h3 class="font-semibold text-foreground">Refuser la commande</h3>
                                <p class="text-xs text-muted-foreground">Le demandeur sera notifié par email</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-1.5 mb-5">
                            <label class="text-sm font-medium text-foreground">
                                Motif du refus <span class="text-red-500">*</span>
                            </label>
                            <textarea
                                v-model="rejectForm.comment"
                                rows="4"
                                placeholder="Expliquez clairement le motif du refus pour permettre au demandeur de corriger sa commande..."
                                class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-red-300 focus:border-red-400 transition-colors resize-none"
                                :class="{ 'border-red-400': rejectForm.errors.comment }"
                                autofocus
                            />
                            <p v-if="rejectForm.errors.comment" class="text-xs text-red-500">{{ rejectForm.errors.comment }}</p>
                        </div>

                        <div class="flex gap-3">
                            <button
                                type="button"
                                @click="showRejectModal = false; rejectForm.reset()"
                                class="flex-1 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors"
                            >
                                Annuler
                            </button>
                            <button
                                @click="reject"
                                :disabled="rejectForm.processing || rejectForm.comment.length < 10"
                                class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 transition-colors disabled:opacity-60"
                            >
                                <Loader2 v-if="rejectForm.processing" class="h-4 w-4 animate-spin" />
                                <XCircle v-else class="h-4 w-4" />
                                Confirmer le refus
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </AppLayout>
</template>
