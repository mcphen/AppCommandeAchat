<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Company, type DisbursementRequest, type SharedData, type ValidationLevel } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    AlertCircle, ArrowLeft, Banknote, Building2, Calendar,
    CheckCircle2, ChevronDown, Download, FileDown, FileText, Landmark, Paperclip,
    Pencil, Send, Tag, User, X, XCircle,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Demandes de décaissement', href: route('disbursement-requests.index') },
    { title: 'Détails', href: '#' },
];

const props = defineProps<{
    order: DisbursementRequest;
    levels: ValidationLevel[];
    companies: Company[];
}>();

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const isAdmin = computed(() => user.value?.role?.slug === 'admin');

// Assignation entreprise (admin seulement)
const companyForm = useForm({ company_id: props.order.company_id ? String(props.order.company_id) : '' });
const savingCompany = ref(false);
const assignCompany = () => {
    savingCompany.value = true;
    companyForm.patch(route('disbursement-requests.assign-company', { disbursement_request: props.order.uuid }), {
        onFinish: () => { savingCompany.value = false; },
    });
};
const isOwner = computed(() => user.value?.id === props.order.user_id);

const canEdit   = computed(() => (isOwner.value || isAdmin.value) && (props.order.status === 'draft' || props.order.status === 'needs_revision'));
const canSubmit = computed(() => (isOwner.value || isAdmin.value) && (props.order.status === 'draft' || props.order.status === 'needs_revision'));
const canCancel = computed(() => (isOwner.value || isAdmin.value) && (props.order.status === 'draft' || props.order.status === 'pending'));
const canDelete = computed(() => (isOwner.value || isAdmin.value) && (props.order.status === 'draft' || props.order.status === 'rejected'));

const statusConfig = {
    draft:          { label: 'Brouillon',          bg: 'bg-slate-100',  text: 'text-slate-700',  dot: 'bg-slate-400',  icon: FileText },
    pending:        { label: 'En attente',          bg: 'bg-amber-50',   text: 'text-amber-700',  dot: 'bg-amber-500',  icon: AlertCircle },
    needs_revision: { label: 'Révision demandée',   bg: 'bg-indigo-50',  text: 'text-indigo-700', dot: 'bg-indigo-500', icon: AlertCircle },
    approved:       { label: 'Approuvée',           bg: 'bg-emerald-50', text: 'text-emerald-700',dot: 'bg-emerald-500',icon: CheckCircle2 },
    rejected:       { label: 'Refusée',             bg: 'bg-red-50',     text: 'text-red-700',    dot: 'bg-red-500',    icon: XCircle },
    cancelled:      { label: 'Annulée',             bg: 'bg-slate-100',  text: 'text-slate-600',  dot: 'bg-slate-400',  icon: XCircle },
} as const;

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const formatDateShort = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const formatSize = (bytes: number) => {
    if (bytes >= 1048576) return `${(bytes / 1048576).toFixed(1)} MB`;
    return `${(bytes / 1024).toFixed(0)} KB`;
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

const getLogForLevel = (levelOrder: number) =>
    props.order.validation_logs?.find((l) => {
        const level = props.levels.find((lv) => lv.id === l.validation_level_id);
        return level?.order === levelOrder;
    }) ?? null;

const submitOrder = async () => {
    const result = await Swal.fire({
        title: 'Soumettre à la validation ?',
        text: 'La demande sera envoyée pour approbation et ne pourra plus être modifiée.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Soumettre',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) router.post(route('disbursement-requests.submit', props.order.uuid));
};

const cancelOrder = async () => {
    const result = await Swal.fire({
        title: 'Annuler la demande ?',
        text: 'La demande sera annulée et retirée du circuit de validation. Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, annuler',
        cancelButtonText: 'Retour',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) router.post(route('disbursement-requests.cancel', props.order.uuid));
};

const deleteOrder = async () => {
    const result = await Swal.fire({
        title: 'Supprimer définitivement ?',
        text: 'Cette action est irréversible.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) router.delete(route('disbursement-requests.destroy', props.order.uuid));
};
</script>

<template>
    <Head :title="order.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- En-tête -->
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <Link :href="route('disbursement-requests.index')" class="mt-1 rounded-xl p-2 text-muted-foreground transition-colors hover:bg-muted">
                        <ArrowLeft class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-foreground">{{ order.title }}</h1>
                        <p v-if="order.reference" class="mt-0.5 font-mono text-xs text-muted-foreground">{{ order.reference }}</p>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                                :class="[statusConfig[order.status]?.bg, statusConfig[order.status]?.text]"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status]?.dot" />
                                {{ statusConfig[order.status]?.label }}
                            </span>
                            <span v-if="order.nature_operation" class="inline-flex items-center gap-1 rounded-full bg-muted px-2.5 py-1 text-xs text-muted-foreground">
                                {{ order.nature_operation.name }}
                            </span>
                            <span v-if="order.status === 'pending'" class="text-sm text-muted-foreground">
                                Niveau {{ order.current_level_order }} — {{ levels.find(l => l.order === order.current_level_order)?.name }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <a
                        :href="route('disbursement-requests.pdf', order.uuid)"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200"
                        title="Télécharger en PDF"
                    >
                        <FileDown class="h-4 w-4" />
                        <span class="hidden sm:inline">PDF</span>
                    </a>
                    <Link
                        v-if="canEdit"
                        :href="route('disbursement-requests.edit', order.uuid)"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                    >
                        <Pencil class="h-4 w-4" />
                        Modifier
                    </Link>
                    <button
                        v-if="canSubmit"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                        @click="submitOrder"
                    >
                        <Send class="h-4 w-4" />
                        Soumettre
                    </button>
                    <button
                        v-if="canCancel"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 transition-colors hover:bg-red-100"
                        @click="cancelOrder"
                    >
                        <X class="h-4 w-4" />
                        Annuler la demande
                    </button>
                    <button
                        v-if="canDelete"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-600 transition-colors hover:bg-red-100"
                        @click="deleteOrder"
                    >
                        <X class="h-4 w-4" />
                        Supprimer
                    </button>
                </div>
            </div>

            <!-- Corps -->
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="flex flex-col gap-5 lg:col-span-2">

                    <!-- Détails généraux -->
                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Détails de la demande</h2>
                        <div class="mb-5 grid gap-4 md:grid-cols-2">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50">
                                    <Banknote class="h-4 w-4 text-blue-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Montant demandé</p>
                                    <p class="font-bold text-foreground">{{ formatAmount(order.amount) }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50">
                                    <Calendar class="h-4 w-4 text-purple-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Créée le</p>
                                    <p class="text-sm font-medium text-foreground">{{ formatDate(order.created_at) }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50">
                                    <User class="h-4 w-4 text-violet-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Demandeur</p>
                                    <p class="text-sm font-medium text-foreground">{{ order.user?.name ?? '—' }}</p>
                                    <p v-if="order.user?.email" class="text-xs text-muted-foreground">{{ order.user.email }}</p>
                                </div>
                            </div>

                            <div v-if="order.boutique" class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50">
                                    <Building2 class="h-4 w-4 text-cyan-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Boutique</p>
                                    <p class="text-sm font-medium text-foreground">
                                        {{ order.boutique.name }}
                                        <span class="text-muted-foreground">({{ order.boutique.code }})</span>
                                    </p>
                                </div>
                            </div>

                            <div v-if="order.company" class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50">
                                    <Landmark class="h-4 w-4 text-purple-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Entreprise</p>
                                    <p class="text-sm font-medium text-foreground">
                                        {{ order.company.name }}
                                        <span v-if="order.company.code" class="text-muted-foreground">({{ order.company.code }})</span>
                                    </p>
                                </div>
                            </div>

                            <div v-if="order.nature_operation" class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-orange-50">
                                    <Tag class="h-4 w-4 text-orange-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Nature de l'opération</p>
                                    <p class="text-sm font-medium text-foreground">{{ order.nature_operation.name }}</p>
                                </div>
                            </div>

                            <div v-if="order.submitted_at" class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50">
                                    <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                                </div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Soumise le</p>
                                    <p class="text-sm font-medium text-foreground">{{ formatDate(order.submitted_at) }}</p>
                                </div>
                            </div>
                        </div>

                        <div v-if="order.description">
                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Justification</p>
                            <p class="whitespace-pre-wrap text-sm leading-relaxed text-foreground">{{ order.description }}</p>
                        </div>
                    </div>

                    <!-- Pièces jointes -->
                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                            <Paperclip class="h-4 w-4" />
                            Pièces jointes ({{ order.attachments?.length ?? 0 }})
                        </h2>
                        <div v-if="order.attachments?.length" class="flex flex-col gap-2">
                            <a
                                v-for="att in order.attachments"
                                :key="att.id"
                                :href="route('disbursement-requests.attachments.download', [order.uuid, att.id])"
                                target="_blank"
                                class="group flex items-center justify-between rounded-xl border bg-muted/30 px-4 py-3 transition-colors hover:bg-muted/50"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50">
                                        <FileText class="h-4 w-4 text-red-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-foreground">{{ att.file_name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ formatSize(att.file_size) }}</p>
                                    </div>
                                </div>
                                <Download class="h-4 w-4 text-muted-foreground transition-colors group-hover:text-foreground" />
                            </a>
                        </div>
                        <p v-else class="text-sm text-muted-foreground">Aucune pièce jointe</p>
                    </div>
                </div>

                <!-- Colonne droite -->
                <div class="flex flex-col gap-5">

                    <!-- Circuit de validation -->
                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Circuit de validation</h2>
                        <div v-if="order.status === 'draft'" class="text-sm text-muted-foreground">
                            La demande n'a pas encore été soumise.
                        </div>
                        <div v-else class="relative flex flex-col gap-0">
                            <div v-for="(level, index) in levels" :key="level.id" class="relative flex items-start gap-3 pb-4">
                                <div v-if="index < levels.length - 1" class="absolute left-3.5 top-7 h-full w-px bg-border" />
                                <div
                                    class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700': levelStatus(level.order) === 'approved',
                                        'bg-primary text-primary-foreground ring-2 ring-primary/30': levelStatus(level.order) === 'current',
                                        'bg-red-100 text-red-600': levelStatus(level.order) === 'rejected',
                                        'bg-muted text-muted-foreground': levelStatus(level.order) === 'pending',
                                    }"
                                >
                                    <CheckCircle2 v-if="levelStatus(level.order) === 'approved'" class="h-3.5 w-3.5" />
                                    <XCircle v-else-if="levelStatus(level.order) === 'rejected'" class="h-3.5 w-3.5" />
                                    <span v-else>{{ level.order }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="text-sm font-semibold leading-tight text-foreground">{{ level.name }}</p>
                                    <p v-if="level.description" class="mt-0.5 text-xs text-muted-foreground">{{ level.description }}</p>
                                    <template v-if="getLogForLevel(level.order)">
                                        <div
                                            class="mt-1.5 flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs"
                                            :class="getLogForLevel(level.order)?.action === 'approved' ? 'bg-emerald-50' : 'bg-red-50'"
                                        >
                                            <User class="h-3 w-3 shrink-0" :class="getLogForLevel(level.order)?.action === 'approved' ? 'text-emerald-600' : 'text-red-500'" />
                                            <span :class="getLogForLevel(level.order)?.action === 'approved' ? 'text-emerald-700 font-medium' : 'text-red-600 font-medium'">
                                                {{ getLogForLevel(level.order)?.user?.name }}
                                            </span>
                                            <span class="ml-auto text-muted-foreground">{{ formatDateShort(getLogForLevel(level.order)!.created_at) }}</span>
                                        </div>
                                        <p v-if="getLogForLevel(level.order)?.comment" class="mt-1 text-xs italic text-muted-foreground line-clamp-2">
                                            "{{ getLogForLevel(level.order)?.comment }}"
                                        </p>
                                    </template>
                                    <p v-else-if="levelStatus(level.order) === 'current'" class="mt-0.5 text-xs text-amber-600">En cours d'examen</p>
                                    <p v-else class="mt-0.5 text-xs text-muted-foreground">En attente</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historique validations -->
                    <div v-if="order.validation_logs?.length" class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Historique validations</h2>
                        <div class="flex flex-col gap-3">
                            <div
                                v-for="log in order.validation_logs"
                                :key="log.id"
                                class="rounded-xl border p-3 text-sm"
                                :class="log.action === 'approved' ? 'border-emerald-100 bg-emerald-50' : 'border-red-100 bg-red-50'"
                            >
                                <div class="mb-1 flex items-center justify-between">
                                    <span class="font-semibold" :class="log.action === 'approved' ? 'text-emerald-700' : 'text-red-700'">
                                        {{ log.action === 'approved' ? 'Approuvée' : 'Refusée' }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">{{ log.validation_level?.name }}</span>
                                </div>
                                <p class="text-xs text-muted-foreground">par {{ log.user?.name }} · {{ formatDateShort(log.created_at) }}</p>
                                <p v-if="log.comment" class="mt-2 border-t border-current/10 pt-2 text-xs italic text-foreground/80">"{{ log.comment }}"</p>
                            </div>
                        </div>
                    </div>

                    <!-- Entreprise (admin) -->
                    <div v-if="isAdmin && companies.length > 0" class="rounded-2xl border bg-card p-5 shadow-sm">
                        <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Entreprise émettrice</h2>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <select
                                    v-model="companyForm.company_id"
                                    class="h-10 w-full appearance-none rounded-xl border border-input bg-background px-3 pr-8 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                >
                                    <option value="">— Aucune —</option>
                                    <option v-for="c in companies" :key="c.id" :value="String(c.id)">
                                        {{ c.name }}<template v-if="c.code"> · {{ c.code }}</template>
                                    </option>
                                </select>
                                <ChevronDown class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            </div>
                            <button
                                :disabled="savingCompany"
                                class="inline-flex items-center gap-1.5 rounded-xl bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-50"
                                @click="assignCompany"
                            >
                                <Landmark class="h-4 w-4" />
                                OK
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
