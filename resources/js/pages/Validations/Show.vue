<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import OrderDiscussion from '@/components/OrderDiscussion.vue';
import { type BreadcrumbItem, type PurchaseOrder, type ValidationLevel } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { AlertTriangle, ArrowLeft, Building2, Calendar, CheckCircle2, DollarSign, Download, FileDown, FileText, Loader2, Paperclip, User, XCircle } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const page = usePage();
const authUser = computed(() => (page.props as any).auth?.user);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Validations', href: '/validations' },
    { title: 'Examiner', href: '#' },
];

const props = defineProps<{
    order: PurchaseOrder;
    levels: ValidationLevel[];
    readOnly?: boolean;
}>();

const showRejectModal = ref(false);
const rejectForm = useForm({ comment: '' });
const approveForm = useForm({});

const approve = async () => {
    const result = await Swal.fire({
        title: 'Approuver cette commande ?',
        text: 'Votre approbation sera enregistree et la commande avancera dans le circuit de validation.',
        icon: 'success',
        showCancelButton: true,
        confirmButtonText: 'Approuver',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });

    if (result.isConfirmed) {
        approveForm.post(route('validations.approve', props.order.uuid));
    }
};

const reject = () => {
    rejectForm.post(route('validations.reject', props.order.uuid), {
        onSuccess: () => {
            showRejectModal.value = false;
            rejectForm.reset();
        },
    });
};

const formatAmount = (value: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(value));

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const formatSize = (bytes: number) => {
    if (bytes >= 1048576) return `${(bytes / 1048576).toFixed(1)} MB`;
    return `${(bytes / 1024).toFixed(0)} KB`;
};

const getLogForLevel = (levelId: number) => {
    return props.order.validation_logs?.find(log => log.validation_level_id === levelId) ?? null;
};

const progressSummary = () => {
    const totalLevels = props.levels.length;

    if (props.order.status === 'pending' && props.order.current_level_order) {
        const completedLevels = Math.max(props.order.current_level_order - 1, 0);
        return `${completedLevels}/${totalLevels} niveaux deja valides. Vous traitez actuellement le niveau ${props.order.current_level_order}.`;
    }

    return 'Commande en cours de traitement.';
};
</script>

<template>
    <Head :title="`Validation - ${order.title}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex max-w-4xl flex-col gap-4 p-3 sm:gap-6 sm:p-6">
            <div class="flex items-start gap-4">
                <Link :href="route('validations.index')" class="mt-1 rounded-xl p-2 text-muted-foreground transition-colors hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div class="flex-1">
                    <p class="mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">Commande a valider</p>
                    <h1 class="text-2xl font-bold text-foreground">{{ order.title }}</h1>
                    <p class="mt-2 text-sm text-muted-foreground">{{ progressSummary() }}</p>
                </div>
                <a
                    :href="route('purchase-orders.pdf', order.uuid)"
                    target="_blank"
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200"
                    title="Telecharger en PDF"
                >
                    <FileDown class="h-4 w-4" />
                    <span class="hidden sm:inline">PDF</span>
                </a>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="flex flex-col gap-5 lg:col-span-2">
                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Informations</h2>
                        <div class="mb-5 grid gap-4 md:grid-cols-2">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50"><DollarSign class="h-4 w-4 text-blue-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Montant</p>
                                    <p class="text-lg font-bold text-foreground">{{ formatAmount(order.amount) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50"><User class="h-4 w-4 text-purple-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Demandeur</p>
                                    <p class="font-medium text-foreground">{{ order.user?.name }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 md:col-span-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-orange-50"><Calendar class="h-4 w-4 text-orange-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Soumise le</p>
                                    <p class="font-medium text-foreground">{{ order.submitted_at ? formatDate(order.submitted_at) : '-' }}</p>
                                </div>
                            </div>
                            <div v-if="order.boutique" class="flex items-center gap-3 md:col-span-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50"><Building2 class="h-4 w-4 text-cyan-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Boutique</p>
                                    <p class="font-medium text-foreground">{{ order.boutique.name }} <span class="text-muted-foreground">({{ order.boutique.code }})</span></p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Description</p>
                            <p class="whitespace-pre-wrap text-sm leading-relaxed text-foreground">{{ order.description }}</p>
                        </div>
                    </div>

                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                            <Paperclip class="h-4 w-4" />
                            Pieces jointes ({{ order.attachments?.length ?? 0 }})
                        </h2>
                        <div v-if="order.attachments?.length" class="flex flex-col gap-2">
                            <a
                                v-for="attachment in order.attachments"
                                :key="attachment.id"
                                :href="route('attachments.download', attachment.id)"
                                target="_blank"
                                class="group flex items-center justify-between rounded-xl border bg-muted/30 px-4 py-3 transition-colors hover:bg-muted/60"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50">
                                        <FileText class="h-4 w-4 text-red-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-foreground">{{ attachment.file_name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ formatSize(attachment.file_size) }}</p>
                                    </div>
                                </div>
                                <Download class="h-4 w-4 text-muted-foreground transition-colors group-hover:text-foreground" />
                            </a>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">Aucune piece jointe</p>
                    </div>

                    <div v-if="!readOnly" class="rounded-2xl border-2 border-dashed border-border bg-card p-6">
                        <h2 class="mb-1 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Votre decision</h2>
                        <p class="mb-5 text-xs text-muted-foreground">Cette action est irreversible. Le demandeur sera notifie automatiquement.</p>
                        <div class="flex gap-3">
                            <button
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-emerald-700 disabled:opacity-70"
                                :disabled="approveForm.processing"
                                @click="approve"
                            >
                                <Loader2 v-if="approveForm.processing" class="h-4 w-4 animate-spin" />
                                <CheckCircle2 v-else class="h-4 w-4" />
                                Approuver
                            </button>
                            <button
                                class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-red-700"
                                @click="showRejectModal = true"
                            >
                                <XCircle class="h-4 w-4" />
                                Refuser
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex flex-col gap-5">
                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Circuit</h2>
                        <div class="relative flex flex-col gap-0">
                            <div v-for="(level, index) in levels" :key="level.id" class="relative flex items-start gap-3 pb-4">
                                <div v-if="index < levels.length - 1" class="absolute left-3.5 top-7 h-full w-px bg-border" />
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
                                <div class="min-w-0 flex-1 pt-0.5">
                                    <p class="text-sm font-semibold leading-tight text-foreground">{{ level.name }}</p>
                                    <p v-if="order.current_level_order === level.order" class="mt-0.5 text-xs font-medium text-primary">Niveau actuel</p>
                                    <template v-if="getLogForLevel(level.id)">
                                        <div class="mt-1.5 flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs bg-emerald-50">
                                            <User class="h-3 w-3 shrink-0 text-emerald-600" />
                                            <span class="text-emerald-700 font-medium">{{ getLogForLevel(level.id)?.user?.name }}</span>
                                        </div>
                                        <p v-if="getLogForLevel(level.id)?.comment" class="mt-1 text-xs italic text-muted-foreground line-clamp-2">
                                            "{{ getLogForLevel(level.id)?.comment }}"
                                        </p>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="order.validation_logs?.length" class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Historique</h2>
                        <div class="flex flex-col gap-3">
                            <div
                                v-for="log in order.validation_logs"
                                :key="log.id"
                                class="rounded-xl border p-3 text-sm"
                                :class="log.action === 'approved' ? 'border-emerald-100 bg-emerald-50' : 'border-red-100 bg-red-50'"
                            >
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="font-semibold" :class="log.action === 'approved' ? 'text-emerald-700' : 'text-red-700'">
                                        {{ log.action === 'approved' ? 'Approuvee' : 'Refusee' }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">{{ log.validation_level?.name }}</span>
                                </div>
                                <p class="text-xs text-muted-foreground">par {{ log.user?.name }}</p>
                                <p v-if="log.comment" class="mt-2 border-t border-current/10 pt-2 text-xs italic text-foreground/80">"{{ log.comment }}"</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Discussion ──────────────────────────────────────────────── -->
        <div class="max-w-4xl px-3 pb-2 sm:px-6">
            <OrderDiscussion
                :order="order"
                :auth-user="authUser"
                :can-request-revision="order.status === 'pending'"
                :can-comment="true"
            />
        </div>

        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showRejectModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm">
                <div class="w-full max-w-md rounded-2xl border bg-card p-6 shadow-xl">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-red-100">
                            <AlertTriangle class="h-5 w-5 text-red-600" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-foreground">Refuser la commande</h3>
                            <p class="text-xs text-muted-foreground">Le demandeur sera notifie par email</p>
                        </div>
                    </div>

                    <div class="mb-5 flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">Motif du refus <span class="text-red-500">*</span></label>
                        <textarea
                            v-model="rejectForm.comment"
                            rows="4"
                            placeholder="Expliquez clairement le motif du refus..."
                            class="w-full resize-none rounded-xl border border-input bg-background px-4 py-3 text-sm transition-colors focus:border-red-400 focus:outline-none focus:ring-2 focus:ring-red-300"
                            :class="{ 'border-red-400': rejectForm.errors.comment }"
                            autofocus
                        />
                        <p v-if="rejectForm.errors.comment" class="text-xs text-red-500">{{ rejectForm.errors.comment }}</p>
                    </div>

                    <div class="flex gap-3">
                        <button
                            type="button"
                            class="flex-1 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                            @click="showRejectModal = false; rejectForm.reset()"
                        >
                            Annuler
                        </button>
                        <button
                            class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-red-700 disabled:opacity-60"
                            :disabled="rejectForm.processing || rejectForm.comment.length < 10"
                            @click="reject"
                        >
                            <Loader2 v-if="rejectForm.processing" class="h-4 w-4 animate-spin" />
                            <XCircle v-else class="h-4 w-4" />
                            Confirmer le refus
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </AppLayout>
</template>
