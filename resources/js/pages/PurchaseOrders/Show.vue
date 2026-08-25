<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import OrderDiscussion from '@/components/OrderDiscussion.vue';
import { type BreadcrumbItem, type Project, type PurchaseOrder, type PurchaseOrderLine, type PurchaseOrderReception, type ValidationLevel } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import {
    ArrowLeft, Building2, Calendar, CheckCircle2, Clock, DollarSign,
    Download, FileDown, FileText, HardHat, Loader2, Paperclip, Pencil, Truck,
    User, XCircle, Package, ShoppingCart, ClipboardCheck, X, AlertCircle, Receipt,
    TrendingDown, TrendingUp, Tag, RotateCcw, Upload, Send, Bell, Trash2,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, reactive, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Mes commandes', href: '/purchase-orders' },
    { title: 'Detail', href: '#' },
];

type SavingsData = {
    vs_average: number;
    vs_best: number;
    lines_with_catalog: number;
    lines_total: number;
} | null;

const props = defineProps<{
    order: PurchaseOrder;
    levels: ValidationLevel[];
    savings: SavingsData;
    projects: Pick<Project, 'id' | 'code' | 'name'>[];
}>();

const page = usePage();
const authUser = computed(() => (page.props as any).auth?.user);
const isAdmin  = computed(() => authUser.value?.role?.slug === 'admin');
const isCreator = computed(() => authUser.value?.id === props.order.user_id);
const isValidateurNiveau1 = computed(() => {
    if (authUser.value?.role?.slug !== 'validateur') return false;
    const level = authUser.value?.validation_levels?.find((l: { circuit_id: number }) => l.circuit_id === props.order.circuit_id);
    return level?.order === 1;
});
const canEditAttachments = computed(() => props.order.status === 'draft' && (isAdmin.value || isCreator.value || isValidateurNiveau1.value));

// ─── Status configs ──────────────────────────────────────────────────────────
const statusConfig = {
    draft:          { label: 'Brouillon',          bg: 'bg-slate-100',  text: 'text-slate-700',  dot: 'bg-slate-400',  icon: Clock },
    pending:        { label: 'En attente',          bg: 'bg-amber-50',   text: 'text-amber-700',  dot: 'bg-amber-500',  icon: Clock },
    needs_revision: { label: 'Révision demandée',  bg: 'bg-indigo-50',  text: 'text-indigo-700', dot: 'bg-indigo-500', icon: AlertCircle },
    approved:       { label: 'Approuvée',           bg: 'bg-emerald-50', text: 'text-emerald-700',dot: 'bg-emerald-500',icon: CheckCircle2 },
    rejected:       { label: 'Refusée',             bg: 'bg-red-50',     text: 'text-red-700',    dot: 'bg-red-500',    icon: XCircle },
} as const;

const deliveryConfig = {
    ordered:             { label: 'Commandée',         bg: 'bg-blue-50',   text: 'text-blue-700',   dot: 'bg-blue-500' },
    partially_received:  { label: 'Reçue partiellement',bg: 'bg-orange-50', text: 'text-orange-700', dot: 'bg-orange-500' },
    received:            { label: 'Reçue entièrement', bg: 'bg-emerald-50',text: 'text-emerald-700',dot: 'bg-emerald-500' },
} as const;

// ─── Helpers ─────────────────────────────────────────────────────────────────
const formatAmount = (value: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(value));

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const formatDateShort = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const formatSize = (bytes: number) => {
    if (bytes >= 1048576) return `${(bytes / 1048576).toFixed(1)} MB`;
    return `${(bytes / 1024).toFixed(0)} KB`;
};

// ─── Validation progress ──────────────────────────────────────────────────────
const progressSummary = () => {
    const totalLevels = props.levels.length;
    if (props.order.status === 'approved') return `Commande entierement approuvee (${totalLevels}/${totalLevels} niveaux).`;
    if (props.order.status === 'rejected') return 'Commande arretee dans le circuit de validation.';
    if (props.order.status === 'pending' && props.order.current_level_order) {
        const done = Math.max(props.order.current_level_order - 1, 0);
        return `${done}/${totalLevels} niveaux valides. En attente du niveau ${props.order.current_level_order}.`;
    }
    return 'Commande non encore soumise au circuit.';
};

const getLogForLevel = (levelId: number) =>
    props.order.validation_logs?.find(log => log.validation_level_id === levelId) ?? null;

// ─── Reception : computed quantities ─────────────────────────────────────────
const totalReceived = (line: PurchaseOrderLine): number =>
    Number(line.quantity_received_total ?? 0);

const remaining = (line: PurchaseOrderLine): number =>
    Math.max(0, Number(line.quantity) - totalReceived(line));

const lineSubtotalTtc = (line: PurchaseOrderLine): number => {
    const ht  = Number(line.subtotal ?? Number(line.quantity) * Number(line.unit_price));
    const tva = line.vat_rate !== null && line.vat_rate !== undefined ? Number(line.vat_rate) : 0;
    return ht * (1 + tva / 100);
};

const receivedPercent = (line: PurchaseOrderLine): number => {
    const qty = Number(line.quantity);
    if (!qty) return 0;
    return Math.min(100, Math.round((totalReceived(line) / qty) * 100));
};

// ─── Actions ─────────────────────────────────────────────────────────────────
const confirmOrder = async () => {
    const result = await Swal.fire({
        title: 'Confirmer la commande ?',
        text: 'Un numéro officiel BC sera généré et la commande passera en phase de livraison.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Confirmer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) router.post(route('purchase-orders.mark-ordered', props.order.id));
};

const restartValidation = async () => {
    const result = await Swal.fire({
        title: 'Reprendre la validation depuis le début ?',
        text: 'Toutes les validations déjà enregistrées seront annulées et la commande retournera au premier niveau.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, relancer le circuit',
        cancelButtonText: 'Conserver l’approbation',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });

    if (result.isConfirmed) {
        router.post(route('purchase-orders.restart-validation', props.order.id));
    }
};

// ─── Pièces jointes & soumission (commandes importées de Sage, statut 'draft') ──
const attachmentInput = ref<HTMLInputElement | null>(null);
const attachmentForm = useForm({ files: [] as File[] });
const uploadingAttachments = ref(false);

const MAX_ATTACHMENT_SIZE_MB = 5; // doit rester aligné avec AttachmentController::store (max:5120 Ko)

const onAttachmentFilesChange = (e: Event) => {
    const files = (e.target as HTMLInputElement).files;
    attachmentForm.files = files ? Array.from(files) : [];
};

const uploadAttachments = async () => {
    if (! attachmentForm.files.length) return;

    const tooLarge = attachmentForm.files.filter((f) => f.size > MAX_ATTACHMENT_SIZE_MB * 1024 * 1024);
    if (tooLarge.length) {
        Swal.fire({
            icon: 'warning',
            title: 'Fichier trop volumineux',
            html: `Taille maximale autorisée : ${MAX_ATTACHMENT_SIZE_MB} Mo.<br><br>Fichier(s) concerné(s) :<br><strong>${tooLarge.map((f) => f.name).join(', ')}</strong>`,
            confirmButtonColor: '#2563eb',
        });
        return;
    }

    const formData = new FormData();
    attachmentForm.files.forEach((file) => formData.append('files[]', file));

    uploadingAttachments.value = true;
    try {
        await axios.post(route('attachments.store', props.order.id), formData);

        attachmentForm.reset('files');
        if (attachmentInput.value) attachmentInput.value.value = '';

        router.reload({ only: ['order'] });

        Swal.fire({
            icon: 'success',
            title: 'Pièce(s) jointe(s) ajoutée(s)',
            confirmButtonColor: '#2563eb',
            timer: 1800,
            showConfirmButton: false,
        });
    } catch (error: any) {
        const status = error?.response?.status;

        if (status === 413) {
            Swal.fire({
                icon: 'error',
                title: 'Fichier trop volumineux',
                text: 'Le serveur a refusé le fichier car il dépasse la taille maximale acceptée. Réduisez sa taille ou contactez un administrateur.',
                confirmButtonColor: '#2563eb',
            });
        } else if (status === 422) {
            const messages: string[] = error?.response?.data?.errors?.['files.0'] ?? error?.response?.data?.errors?.files ?? ['Fichier(s) invalide(s).'];
            Swal.fire({
                icon: 'warning',
                title: 'Fichier(s) refusé(s)',
                html: messages.join('<br>'),
                confirmButtonColor: '#2563eb',
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: "Échec de l'envoi",
                text: "Une erreur est survenue lors de l'envoi de la pièce jointe. Réessayez.",
                confirmButtonColor: '#2563eb',
            });
        }
    } finally {
        uploadingAttachments.value = false;
    }
};

const deleteAttachment = async (attachmentId: number) => {
    const result = await Swal.fire({
        title: 'Supprimer cette pièce jointe ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) {
        router.delete(route('attachments.destroy', attachmentId), { preserveScroll: true });
    }
};

const submitOrder = async () => {
    if (! props.order.attachments?.length) {
        Swal.fire({
            title: 'Pièce jointe obligatoire',
            text: 'Vous devez joindre au moins un document avant de soumettre cette commande.',
            icon: 'warning',
            confirmButtonColor: '#2563eb',
        });
        return;
    }

    const result = await Swal.fire({
        title: 'Soumettre cette commande ?',
        text: 'Elle sera envoyée au circuit de validation et ne pourra plus être modifiée.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Soumettre',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) {
        router.post(route('purchase-orders.submit', props.order.id));
    }
};

const remindDemandeur = async () => {
    const result = await Swal.fire({
        title: 'Relancer le demandeur ?',
        text: `Un email de rappel sera envoyé à ${props.order.user?.name ?? 'le demandeur'} pour compléter et soumettre cette commande.`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Relancer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) {
        router.post(route('purchase-orders.remind', props.order.id), {}, { preserveScroll: true });
    }
};

// ─── Reception modal ──────────────────────────────────────────────────────────
const showModal = ref(false);

const receptionForm = reactive({
    received_at:    new Date().toISOString().slice(0, 10),
    notes:          '',
    invoice_number: '',
    invoice_date:   '',
    invoice_amount: '' as number | string,
    lines: [] as { purchase_order_line_id: number; quantity_received: number; max: number; label: string; unit: string }[],
});

// ─── Rapprochement : mini-form inline par réception ───────────────────────────
const invoiceEditing = ref<number | null>(null);
const invoiceForms   = reactive<Record<number, { invoice_number: string; invoice_date: string; invoice_amount: string | number }>>({});

const openInvoiceEdit = (reception: { id: number; invoice_number?: string | null; invoice_date?: string | null; invoice_amount?: string | number | null }) => {
    invoiceForms[reception.id] = {
        invoice_number: reception.invoice_number ?? '',
        invoice_date:   reception.invoice_date   ? reception.invoice_date.slice(0, 10) : '',
        invoice_amount: reception.invoice_amount ?? '',
    };
    invoiceEditing.value = reception.id;
};

const saveInvoice = (receptionId: number) => {
    const f = invoiceForms[receptionId];
    router.patch(route('purchase-orders.receptions.invoice', { purchase_order: props.order.id, reception: receptionId }), {
        invoice_number: f.invoice_number || null,
        invoice_date:   f.invoice_date   || null,
        invoice_amount: f.invoice_amount !== '' ? f.invoice_amount : null,
    }, { onSuccess: () => { invoiceEditing.value = null; } });
};

// Statut de rapprochement global du BC
const reconciliationStatus = computed(() => {
    const receptions = props.order.receptions ?? [];
    if (!receptions.length) return null;
    const hasInvoice  = receptions.some(r => r.invoice_number);
    const allInvoiced = receptions.every(r => r.invoice_number);
    if (!hasInvoice) return 'none';
    if (!allInvoiced) return 'partial';
    // Vérifie l'écart montant : total factures vs montant BC
    const totalInvoiced = receptions.reduce((s, r) => s + Number(r.invoice_amount ?? 0), 0);
    const bcAmount      = Number(props.order.amount);
    const gap           = Math.abs(totalInvoiced - bcAmount);
    if (gap < 1) return 'matched';
    return 'gap';
});

const openReceptionModal = () => {
    receptionForm.received_at = new Date().toISOString().slice(0, 10);
    receptionForm.notes = '';
    receptionForm.lines = (props.order.lines ?? [])
        .filter(l => remaining(l) > 0)
        .map(l => ({
            purchase_order_line_id: l.id!,
            quantity_received: remaining(l),
            max: remaining(l),
            label: l.article?.name ?? `Ligne #${l.id}`,
            unit: l.article?.unit ?? '',
        }));
    showModal.value = true;
};

const submitReception = () => {
    const payload = {
        received_at: receptionForm.received_at,
        notes: receptionForm.notes,
        lines: receptionForm.lines.map(l => ({
            purchase_order_line_id: l.purchase_order_line_id,
            quantity_received: l.quantity_received,
        })),
    };
    router.post(route('purchase-orders.receptions.store', props.order.id), payload, {
        onSuccess: () => { showModal.value = false; },
    });
};

// ─── Transferts des réceptions vers les chantiers ─────────────────────────────
const transferReceptionId = ref<number | null>(null);
const transferForm = reactive({
    project_id: '',
    transferred_at: new Date().toISOString().slice(0, 10),
    reference: '',
    notes: '',
    lines: [] as { reception_line_id: number; quantity_transferred: number; max: number; label: string }[],
});

const transferredQuantity = (line: any): number =>
    (line.transfer_lines ?? []).reduce((sum: number, item: any) => sum + Number(item.quantity_transferred), 0);

const openTransferForm = (reception: PurchaseOrderReception) => {
    transferReceptionId.value = reception.id;
    transferForm.project_id = props.order.project_id ? String(props.order.project_id) : '';
    transferForm.transferred_at = new Date().toISOString().slice(0, 10);
    transferForm.reference = '';
    transferForm.notes = '';
    transferForm.lines = (reception.lines ?? []).map(line => {
        const available = Math.max(0, Number(line.quantity_received) - transferredQuantity(line));
        return {
            reception_line_id: line.id,
            quantity_transferred: available,
            max: available,
            label: props.order.lines?.find(item => item.id === line.purchase_order_line_id)?.article?.name ?? `Ligne #${line.purchase_order_line_id}`,
        };
    }).filter(line => line.max > 0);
};

const submitTransfer = () => {
    if (!transferReceptionId.value) return;
    router.post(route('purchase-orders.receptions.transfers.store', {
        purchase_order: props.order.id,
        reception: transferReceptionId.value,
    }), {
        project_id: transferForm.project_id,
        transferred_at: transferForm.transferred_at,
        reference: transferForm.reference || null,
        notes: transferForm.notes || null,
        lines: transferForm.lines
            .filter(line => Number(line.quantity_transferred) > 0)
            .map(line => ({
                reception_line_id: line.reception_line_id,
                quantity_transferred: line.quantity_transferred,
            })),
    }, {
        preserveScroll: true,
        onSuccess: () => { transferReceptionId.value = null; },
    });
};
</script>

<template>
    <Head :title="order.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- En-tête -->
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <Link :href="route('purchase-orders.index')" class="mt-1 rounded-xl p-2 text-muted-foreground transition-colors hover:bg-muted">
                        <ArrowLeft class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-foreground">{{ order.title }}</h1>
                        <div class="mt-2 flex flex-wrap items-center gap-2">
                            <!-- Statut validation -->
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                                :class="[statusConfig[order.status]?.bg, statusConfig[order.status]?.text]"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status]?.dot" />
                                {{ statusConfig[order.status]?.label }}
                            </span>
                            <!-- Statut livraison -->
                            <span
                                v-if="order.delivery_status"
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                                :class="[deliveryConfig[order.delivery_status]?.bg, deliveryConfig[order.delivery_status]?.text]"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="deliveryConfig[order.delivery_status]?.dot" />
                                {{ deliveryConfig[order.delivery_status]?.label }}
                            </span>
                            <!-- Numéro BC officiel -->
                            <span v-if="order.order_number" class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-3 py-1 font-mono text-xs font-bold text-blue-700">
                                {{ order.order_number }}
                            </span>
                            <!-- Code affaire / chantier -->
                            <span v-if="order.project" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-3 py-1 font-mono text-xs font-bold text-amber-700">
                                Chantier : {{ order.project.name }}
                            </span>
                            <span v-if="order.status === 'pending'" class="text-sm text-muted-foreground">
                                Niveau {{ order.current_level_order }} — {{ levels.find(l => l.order === order.current_level_order)?.name }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">{{ progressSummary() }}</p>
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex flex-wrap items-center justify-end gap-2">
                    <a
                        :href="route('purchase-orders.pdf', order.id)"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200"
                        title="Telecharger en PDF"
                    >
                        <FileDown class="h-4 w-4" />
                        <span class="hidden sm:inline">PDF</span>
                    </a>
                    <!-- Soumettre (demandeur, validateur niveau 1 ou admin, commande importée de Sage en brouillon) -->
                    <button
                        v-if="(isAdmin || isCreator || isValidateurNiveau1) && order.status === 'draft'"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700"
                        @click="submitOrder"
                    >
                        <Send class="h-4 w-4" />
                        Soumettre pour validation
                    </button>
                    <!-- Relancer le demandeur (admin, commande en brouillon, demandeur identifié) -->
                    <button
                        v-if="isAdmin && order.status === 'draft' && order.user?.role"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-amber-50 hover:text-amber-700 hover:border-amber-200"
                        @click="remindDemandeur"
                        :title="order.last_reminder_sent_at ? `Dernière relance : ${formatDate(order.last_reminder_sent_at)}` : 'Aucune relance envoyée'"
                    >
                        <Bell class="h-4 w-4" />
                        <span class="hidden sm:inline">Relancer le demandeur</span>
                    </button>
                    <!-- Commande sans demandeur reconnu : seul un admin peut la compléter/soumettre -->
                    <span
                        v-else-if="isAdmin && order.status === 'draft'"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700"
                        title="Code collaborateur Sage non reconnu : personne d'autre ne peut compléter cette commande."
                    >
                        <AlertCircle class="h-4 w-4" />
                        <span class="hidden sm:inline">Aucun demandeur identifié</span>
                    </span>
                    <!-- Confirmer la commande (admin, approuvée, pas encore ordonnée) -->
                    <button
                        v-if="isAdmin && order.status === 'approved' && (!order.delivery_status || order.delivery_status === 'ordered')"
                        class="inline-flex items-center gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 shadow-sm transition-colors hover:bg-red-100"
                        @click="restartValidation"
                    >
                        <RotateCcw class="h-4 w-4" />
                        Reprendre la validation
                    </button>
                    <button
                        v-if="isAdmin && order.status === 'approved' && !order.delivery_status"
                        class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-blue-700"
                        @click="confirmOrder"
                    >
                        <ShoppingCart class="h-4 w-4" />
                        Confirmer commande
                    </button>
                    <!-- Saisir réception -->
                    <button
                        v-if="(isAdmin || isCreator) && (order.delivery_status === 'ordered' || order.delivery_status === 'partially_received')"
                        class="inline-flex items-center gap-2 rounded-xl bg-orange-500 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-orange-600"
                        @click="openReceptionModal"
                    >
                        <ClipboardCheck class="h-4 w-4" />
                        Saisir réception
                    </button>
                </div>
            </div>

            <!-- Corps -->
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="flex flex-col gap-5 lg:col-span-2">

                    <!-- Détails généraux -->
                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Details de la commande</h2>
                        <div class="mb-5 grid gap-4 md:grid-cols-2">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50"><DollarSign class="h-4 w-4 text-blue-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Montant HT</p>
                                    <p class="font-bold text-foreground">{{ formatAmount(order.amount) }}</p>
                                </div>
                            </div>
                            <div v-if="order.amount_ttc" class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50"><DollarSign class="h-4 w-4 text-blue-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Montant TTC (TVA {{ formatAmount(Number(order.amount_ttc) - Number(order.amount)) }})</p>
                                    <p class="font-bold text-foreground">{{ formatAmount(order.amount_ttc) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50"><Calendar class="h-4 w-4 text-purple-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Date de la commande</p>
                                    <p class="text-sm font-medium text-foreground">{{ formatDate(order.order_date ?? order.created_at) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-50"><User class="h-4 w-4 text-violet-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Demandeur</p>
                                    <p class="text-sm font-medium text-foreground">{{ order.user?.name ?? '—' }}</p>
                                    <p v-if="order.user?.email" class="text-xs text-muted-foreground">{{ order.user.email }}</p>
                                </div>
                            </div>
                            <div v-if="order.boutique" class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50"><Building2 class="h-4 w-4 text-cyan-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Société</p>
                                    <p class="text-sm font-medium text-foreground">{{ order.boutique.name }} <span class="text-muted-foreground">({{ order.boutique.code }})</span></p>
                                </div>
                            </div>
                            <div v-if="order.project" class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50"><HardHat class="h-4 w-4 text-amber-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Chantier</p>
                                    <p class="text-sm font-medium text-foreground">{{ order.project.name }}</p>
                                </div>
                            </div>
                            <div v-if="order.fournisseur" class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-orange-50"><Truck class="h-4 w-4 text-orange-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Fournisseur</p>
                                    <p class="text-sm font-medium text-foreground">{{ order.fournisseur.name }} <span class="text-muted-foreground">({{ order.fournisseur.code }})</span></p>
                                </div>
                            </div>
                            <div v-if="order.ordered_at" class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50"><ShoppingCart class="h-4 w-4 text-blue-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Commandée le</p>
                                    <p class="text-sm font-medium text-foreground">{{ formatDate(order.ordered_at) }}</p>
                                </div>
                            </div>
                            <div v-if="order.fully_received_at" class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-50"><CheckCircle2 class="h-4 w-4 text-emerald-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Réceptionnée le</p>
                                    <p class="text-sm font-medium text-foreground">{{ formatDate(order.fully_received_at) }}</p>
                                </div>
                            </div>
                        </div>
                        <div>
                            <p class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">Description</p>
                            <p class="whitespace-pre-wrap text-sm leading-relaxed text-foreground">{{ order.description }}</p>
                        </div>
                    </div>

                    <!-- Analyse économies vs catalogue (commandes approuvées) -->
                    <div v-if="savings" class="rounded-2xl border shadow-sm overflow-hidden"
                        :class="savings.vs_average >= 0
                            ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/20'
                            : 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/20'">
                        <div class="flex items-center gap-3 border-b px-5 py-3.5"
                            :class="savings.vs_average >= 0 ? 'border-emerald-100 dark:border-emerald-900' : 'border-amber-100 dark:border-amber-900'">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl"
                                :class="savings.vs_average >= 0 ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-amber-100 text-amber-600 dark:bg-amber-950/50 dark:text-amber-300'">
                                <Tag class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold"
                                    :class="savings.vs_average >= 0 ? 'text-emerald-800 dark:text-emerald-200' : 'text-amber-800 dark:text-amber-200'">
                                    Analyse des prix vs catalogue fournisseurs
                                </p>
                                <p class="text-xs"
                                    :class="savings.vs_average >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'">
                                    Basé sur {{ savings.lines_with_catalog }} ligne(s) référencée(s) sur {{ savings.lines_total }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-0 divide-y sm:grid-cols-2 sm:divide-x sm:divide-y-0"
                            :class="savings.vs_average >= 0 ? 'divide-emerald-100 dark:divide-emerald-900' : 'divide-amber-100 dark:divide-amber-900'">

                            <!-- Vs prix moyen du marché -->
                            <div class="flex items-center gap-4 px-5 py-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                    :class="savings.vs_average >= 0 ? 'bg-emerald-100 dark:bg-emerald-950/50' : 'bg-amber-100 dark:bg-amber-950/50'">
                                    <TrendingDown v-if="savings.vs_average >= 0"
                                        class="h-5 w-5 text-emerald-600 dark:text-emerald-300" />
                                    <TrendingUp v-else
                                        class="h-5 w-5 text-amber-600 dark:text-amber-300" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide"
                                        :class="savings.vs_average >= 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'">
                                        {{ savings.vs_average >= 0 ? 'Économie réalisée' : 'Surcoût constaté' }}
                                    </p>
                                    <p class="mt-0.5 text-xl font-bold"
                                        :class="savings.vs_average >= 0 ? 'text-emerald-800 dark:text-emerald-200' : 'text-amber-800 dark:text-amber-200'">
                                        {{ formatAmount(Math.abs(savings.vs_average)) }}
                                    </p>
                                    <p class="mt-0.5 text-xs"
                                        :class="savings.vs_average >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'">
                                        vs prix moyens du catalogue
                                    </p>
                                </div>
                            </div>

                            <!-- Vs meilleur prix disponible -->
                            <div class="flex items-center gap-4 px-5 py-4">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                    :class="savings.vs_best === 0 ? 'bg-emerald-100 dark:bg-emerald-950/50' : 'bg-slate-100 dark:bg-slate-800'">
                                    <CheckCircle2 v-if="savings.vs_best === 0"
                                        class="h-5 w-5 text-emerald-600 dark:text-emerald-300" />
                                    <DollarSign v-else
                                        class="h-5 w-5 text-slate-500 dark:text-slate-400" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium uppercase tracking-wide"
                                        :class="savings.vs_best === 0 ? 'text-emerald-700 dark:text-emerald-300' : 'text-muted-foreground'">
                                        {{ savings.vs_best === 0 ? 'Meilleurs prix obtenus' : 'Écart vs prix plancher' }}
                                    </p>
                                    <p class="mt-0.5 text-xl font-bold"
                                        :class="savings.vs_best === 0 ? 'text-emerald-800 dark:text-emerald-200' : 'text-foreground'">
                                        {{ savings.vs_best === 0 ? '—' : formatAmount(savings.vs_best) }}
                                    </p>
                                    <p class="mt-0.5 text-xs text-muted-foreground">
                                        {{ savings.vs_best === 0 ? 'Tous les prix au niveau le plus bas' : 'au-dessus des prix les plus bas' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Lignes de commande + suivi réception -->
                    <div v-if="order.lines && order.lines.length > 0" class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                        <div class="flex items-center justify-between px-6 py-4 border-b">
                            <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                <Package class="h-4 w-4" />
                                Lignes de commande ({{ order.lines.length }})
                            </h2>
                        </div>
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/20">
                                    <th class="text-left px-5 py-2.5 font-medium text-xs text-muted-foreground">Article</th>
                                    <th class="hidden text-left px-4 py-2.5 font-medium text-xs text-muted-foreground md:table-cell">Fournisseur</th>
                                    <th class="text-center px-4 py-2.5 font-medium text-xs text-muted-foreground">Qté cmd</th>
                                    <th class="text-right px-4 py-2.5 font-medium text-xs text-muted-foreground">Prix unit. HT</th>
                                    <th class="hidden text-center px-3 py-2.5 font-medium text-xs text-muted-foreground sm:table-cell">TVA</th>
                                    <th class="hidden text-center px-3 py-2.5 font-medium text-xs text-muted-foreground sm:table-cell">Remise</th>
                                    <th class="text-right px-5 py-2.5 font-medium text-xs text-muted-foreground">Sous-total HT</th>
                                    <th class="hidden text-right px-5 py-2.5 font-medium text-xs text-muted-foreground lg:table-cell">Sous-total TTC</th>
                                    <th v-if="order.delivery_status" class="text-center px-4 py-2.5 font-medium text-xs text-muted-foreground">Réception</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="line in order.lines" :key="line.id" class="hover:bg-muted/10">
                                    <td class="px-5 py-3">
                                        <p class="font-medium text-foreground">{{ line.article?.name ?? '—' }}</p>
                                        <p v-if="line.article?.reference" class="text-xs text-muted-foreground font-mono">{{ line.article.reference }}</p>
                                        <p v-if="line.note" class="text-xs text-muted-foreground italic mt-0.5">{{ line.note }}</p>
                                    </td>
                                    <td class="hidden px-4 py-3 text-sm text-muted-foreground md:table-cell">{{ line.fournisseur?.name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-center text-sm text-foreground">
                                        {{ line.quantity }} <span class="text-xs text-muted-foreground">{{ line.unit ?? line.article?.unit }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-foreground">{{ formatAmount(Number(line.unit_price)) }}</td>
                                    <td class="hidden px-3 py-3 text-center text-xs text-muted-foreground sm:table-cell">
                                        {{ line.vat_rate !== null && line.vat_rate !== undefined ? `${line.vat_rate}%` : '—' }}
                                    </td>
                                    <td class="hidden px-3 py-3 text-center text-xs text-muted-foreground sm:table-cell">
                                        {{ line.discount_rate !== null && line.discount_rate !== undefined ? `${line.discount_rate}%` : '—' }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-semibold text-foreground">{{ formatAmount(Number(line.subtotal)) }}</td>
                                    <td class="hidden px-5 py-3 text-right text-sm text-muted-foreground lg:table-cell">{{ formatAmount(lineSubtotalTtc(line)) }}</td>
                                    <!-- Colonne réception -->
                                    <td v-if="order.delivery_status" class="px-4 py-3">
                                        <div class="flex flex-col items-center gap-1 min-w-[90px]">
                                            <div class="flex items-center gap-1 text-xs">
                                                <span
                                                    class="font-semibold"
                                                    :class="totalReceived(line) >= Number(line.quantity) ? 'text-emerald-600' : 'text-orange-600'"
                                                >
                                                    {{ totalReceived(line) }}
                                                </span>
                                                <span class="text-muted-foreground">/ {{ line.quantity }}</span>
                                                <span class="text-muted-foreground text-[10px]">{{ line.article?.unit }}</span>
                                            </div>
                                            <div class="w-full h-1.5 rounded-full bg-muted overflow-hidden">
                                                <div
                                                    class="h-full rounded-full transition-all"
                                                    :class="receivedPercent(line) >= 100 ? 'bg-emerald-500' : 'bg-orange-400'"
                                                    :style="{ width: `${receivedPercent(line)}%` }"
                                                />
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr class="border-t bg-muted/20">
                                    <td colspan="6" class="px-5 py-3 text-sm font-semibold text-right text-foreground">Total</td>
                                    <td class="px-5 py-3 text-right font-bold text-foreground">{{ formatAmount(Number(order.amount)) }}</td>
                                    <td class="hidden px-5 py-3 text-right font-bold text-foreground lg:table-cell">{{ formatAmount(order.amount_ttc ?? order.lines.reduce((sum, l) => sum + lineSubtotalTtc(l), 0)) }}</td>
                                    <td v-if="order.delivery_status" />
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <!-- Pièces jointes -->
                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                            <Paperclip class="h-4 w-4" />
                            Pieces jointes ({{ order.attachments?.length ?? 0 }})
                        </h2>

                        <p v-if="order.status === 'draft' && !order.attachments?.length" class="mb-4 flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <AlertCircle class="h-4 w-4 shrink-0" />
                            Au moins une pièce jointe est obligatoire avant de pouvoir soumettre cette commande.
                        </p>

                        <div v-if="order.attachments?.length" class="mb-4 flex flex-col gap-2">
                            <div
                                v-for="attachment in order.attachments"
                                :key="attachment.id"
                                class="group flex items-center justify-between rounded-xl border bg-muted/30 px-4 py-3 transition-colors hover:bg-muted/50"
                            >
                                <a
                                    :href="route('attachments.download', attachment.id)"
                                    target="_blank"
                                    class="flex min-w-0 flex-1 items-center gap-3"
                                >
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50">
                                        <FileText class="h-4 w-4 text-red-600" />
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-medium text-foreground">{{ attachment.file_name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ formatSize(attachment.file_size) }}</p>
                                    </div>
                                </a>
                                <div class="flex shrink-0 items-center gap-1">
                                    <button
                                        v-if="canEditAttachments"
                                        type="button"
                                        class="rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-600"
                                        title="Supprimer"
                                        @click="deleteAttachment(attachment.id)"
                                    >
                                        <Trash2 class="h-4 w-4" />
                                    </button>
                                    <a :href="route('attachments.download', attachment.id)" target="_blank" class="rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground" title="Télécharger">
                                        <Download class="h-4 w-4" />
                                    </a>
                                </div>
                            </div>
                        </div>
                        <p v-else-if="order.status !== 'draft'" class="text-sm text-muted-foreground">Aucune piece jointe</p>

                        <!-- Ajout de pièce(s) jointe(s) : demandeur ou admin, tant que la commande est en brouillon -->
                        <div v-if="canEditAttachments" class="flex flex-col gap-2 rounded-xl border border-dashed p-4">
                            <input
                                ref="attachmentInput"
                                type="file"
                                multiple
                                class="hidden"
                                @change="onAttachmentFilesChange"
                            />
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-xs font-medium text-foreground transition-colors hover:bg-muted"
                                    @click="attachmentInput?.click()"
                                >
                                    <Paperclip class="h-3.5 w-3.5" />
                                    Choisir un ou plusieurs fichiers
                                </button>
                                <span v-if="attachmentForm.files.length" class="text-xs text-muted-foreground">
                                    {{ attachmentForm.files.length }} fichier(s) sélectionné(s)
                                </span>
                                <button
                                    v-if="attachmentForm.files.length"
                                    type="button"
                                    class="ml-auto inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-blue-700 disabled:opacity-50"
                                    :disabled="uploadingAttachments"
                                    @click="uploadAttachments"
                                >
                                    <Loader2 v-if="uploadingAttachments" class="h-3.5 w-3.5 animate-spin" />
                                    <Upload v-else class="h-3.5 w-3.5" />
                                    {{ uploadingAttachments ? 'Envoi...' : 'Envoyer' }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colonne droite -->
                <div class="flex flex-col gap-5">
                    <!-- Circuit de validation -->
                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Circuit de validation</h2>
                        <div class="relative flex flex-col gap-0">
                            <div v-for="(level, index) in levels" :key="level.id" class="relative flex items-start gap-3 pb-4">
                                <div v-if="index < levels.length - 1" class="absolute left-3.5 top-7 h-full w-px bg-border" />
                                <div
                                    class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700': order.status === 'approved' || (order.status === 'pending' && order.current_level_order! > level.order),
                                        'bg-primary text-primary-foreground ring-2 ring-primary/30': order.status === 'pending' && order.current_level_order === level.order,
                                        'bg-muted text-muted-foreground': order.status !== 'approved' && !(order.status === 'pending' && order.current_level_order! >= level.order),
                                        'bg-red-100 text-red-600': order.status === 'rejected' && order.validation_logs?.some(log => log.validation_level?.order === level.order && log.action === 'rejected'),
                                    }"
                                >
                                    <CheckCircle2 v-if="order.status === 'approved' || (order.status === 'pending' && order.current_level_order! > level.order)" class="h-3.5 w-3.5" />
                                    <XCircle v-else-if="order.status === 'rejected' && order.validation_logs?.some(log => log.validation_level?.order === level.order && log.action === 'rejected')" class="h-3.5 w-3.5" />
                                    <span v-else>{{ level.order }}</span>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-1.5">
                                        <p class="text-sm font-semibold leading-tight text-foreground">{{ level.name }}</p>
                                        <span
                                            v-if="level.type === 'approbation'"
                                            class="inline-flex items-center rounded-full bg-violet-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-violet-700 dark:bg-violet-950/30 dark:text-violet-300"
                                        >
                                            Approbation
                                        </span>
                                    </div>
                                    <p v-if="level.description" class="mt-0.5 text-xs text-muted-foreground">{{ level.description }}</p>
                                    <template v-if="getLogForLevel(level.id)">
                                        <div class="mt-1.5 flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-xs"
                                            :class="getLogForLevel(level.id)?.action === 'approved' ? 'bg-emerald-50' : 'bg-red-50'"
                                        >
                                            <User class="h-3 w-3 shrink-0" :class="getLogForLevel(level.id)?.action === 'approved' ? 'text-emerald-600' : 'text-red-500'" />
                                            <span :class="getLogForLevel(level.id)?.action === 'approved' ? 'text-emerald-700 font-medium' : 'text-red-600 font-medium'">
                                                {{ getLogForLevel(level.id)?.user?.name }}
                                            </span>
                                        </div>
                                        <p v-if="getLogForLevel(level.id)?.comment" class="mt-1 text-xs italic text-muted-foreground line-clamp-2">
                                            "{{ getLogForLevel(level.id)?.comment }}"
                                        </p>
                                    </template>
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
                                        {{ log.action === 'approved' ? 'Approuvee' : 'Refusee' }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">{{ log.validation_level?.name }}</span>
                                </div>
                                <p class="text-xs text-muted-foreground">par {{ log.user?.name }}</p>
                                <p v-if="log.comment" class="mt-2 border-t border-current/10 pt-2 text-xs italic text-foreground/80">"{{ log.comment }}"</p>
                            </div>
                        </div>
                    </div>

                    <!-- Historique réceptions -->
                    <div v-if="order.receptions?.length" class="rounded-2xl border bg-card p-6 shadow-sm">
                        <div class="flex items-center justify-between mb-4">
                            <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-muted-foreground">
                                <ClipboardCheck class="h-4 w-4" />
                                Réceptions ({{ order.receptions.length }})
                            </h2>
                            <!-- Badge rapprochement global -->
                            <span v-if="reconciliationStatus === 'matched'" class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 border border-emerald-200">
                                <CheckCircle2 class="h-3 w-3" /> Facture rapprochée
                            </span>
                            <span v-else-if="reconciliationStatus === 'gap'" class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 border border-amber-200">
                                <AlertCircle class="h-3 w-3" /> Écart de montant
                            </span>
                            <span v-else-if="reconciliationStatus === 'partial'" class="inline-flex items-center gap-1 rounded-full bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 border border-blue-200">
                                <Receipt class="h-3 w-3" /> Partiellement facturé
                            </span>
                            <span v-else-if="reconciliationStatus === 'none'" class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">
                                <Receipt class="h-3 w-3" /> Sans facture
                            </span>
                        </div>
                        <div class="flex flex-col gap-3">
                            <div
                                v-for="reception in order.receptions"
                                :key="reception.id"
                                class="rounded-xl border p-3 text-sm"
                                :class="reception.type === 'complete' ? 'border-emerald-100 bg-emerald-50' : 'border-orange-100 bg-orange-50'"
                            >
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-semibold" :class="reception.type === 'complete' ? 'text-emerald-700' : 'text-orange-700'">
                                        {{ reception.type === 'complete' ? 'Complète' : 'Partielle' }}
                                    </span>
                                    <span class="text-xs text-muted-foreground">{{ formatDateShort(reception.received_at) }}</span>
                                </div>
                                <p class="text-xs text-muted-foreground">par {{ reception.receiver?.name ?? '—' }}</p>
                                <p v-if="reception.notes" class="mt-2 border-t border-current/10 pt-2 text-xs italic text-foreground/80">
                                    "{{ reception.notes }}"
                                </p>
                                <div v-if="reception.lines?.length" class="mt-2 border-t border-current/10 pt-2 space-y-0.5">
                                    <div v-for="rline in reception.lines" :key="rline.id" class="flex justify-between text-xs text-muted-foreground">
                                        <span>{{ order.lines?.find(l => l.id === rline.purchase_order_line_id)?.article?.name ?? `Ligne #${rline.purchase_order_line_id}` }}</span>
                                        <span class="font-medium">+{{ rline.quantity_received }}</span>
                                    </div>
                                </div>


                                <!-- Affectations aux chantiers -->
                                <div class="mt-3 border-t border-current/10 pt-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <p class="flex items-center gap-1.5 text-xs font-semibold text-foreground">
                                            <HardHat class="h-3.5 w-3.5 text-amber-600" />
                                            Transferts chantiers
                                        </p>
                                        <button
                                            v-if="(isAdmin || isCreator) && reception.lines?.some(line => Number(line.quantity_received) > transferredQuantity(line))"
                                            type="button"
                                            class="rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 hover:bg-amber-100"
                                            @click="openTransferForm(reception)"
                                        >
                                            Transférer
                                        </button>
                                        <span v-else-if="reception.lines?.length" class="text-xs font-medium text-emerald-700">Tout transféré</span>
                                    </div>

                                    <div v-if="reception.transfers?.length" class="mt-2 space-y-2">
                                        <div v-for="transfer in reception.transfers" :key="transfer.id" class="rounded-lg border border-amber-100 bg-white/70 p-2.5">
                                            <div class="flex flex-wrap items-center justify-between gap-2">
                                                <span class="text-xs font-semibold text-amber-800">{{ transfer.project?.name ?? 'Chantier' }}</span>
                                                <span class="text-[11px] text-muted-foreground">{{ formatDateShort(transfer.transferred_at) }}</span>
                                            </div>
                                            <p class="mt-0.5 text-[11px] text-muted-foreground">
                                                par {{ transfer.actor?.name ?? '—' }}
                                                <span v-if="transfer.reference"> · Réf. {{ transfer.reference }}</span>
                                            </p>
                                            <div class="mt-1 space-y-0.5">
                                                <div v-for="line in transfer.lines" :key="line.id" class="flex justify-between text-[11px] text-muted-foreground">
                                                    <span>{{ order.lines?.find(item => item.id === reception.lines?.find(rline => rline.id === line.reception_line_id)?.purchase_order_line_id)?.article?.name ?? 'Article' }}</span>
                                                    <span class="font-semibold">× {{ line.quantity_transferred }}</span>
                                                </div>
                                            </div>
                                            <p v-if="transfer.notes" class="mt-1 text-[11px] italic text-muted-foreground">{{ transfer.notes }}</p>
                                        </div>
                                    </div>

                                    <div v-if="transferReceptionId === reception.id" class="mt-3 space-y-3 rounded-xl border border-amber-200 bg-white p-3">
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            <label class="text-xs font-medium text-muted-foreground">
                                                Chantier
                                                <select v-model="transferForm.project_id" class="mt-1 h-9 w-full rounded-lg border bg-white px-2 text-xs">
                                                    <option value="" disabled>Sélectionner…</option>
                                                    <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.code }} — {{ project.name }}</option>
                                                </select>
                                            </label>
                                            <label class="text-xs font-medium text-muted-foreground">
                                                Date du transfert
                                                <input v-model="transferForm.transferred_at" type="date" class="mt-1 h-9 w-full rounded-lg border bg-white px-2 text-xs" />
                                            </label>
                                            <label class="text-xs font-medium text-muted-foreground">
                                                Référence
                                                <input v-model="transferForm.reference" type="text" maxlength="100" placeholder="Bon de sortie…" class="mt-1 h-9 w-full rounded-lg border bg-white px-2 text-xs" />
                                            </label>
                                            <label class="text-xs font-medium text-muted-foreground">
                                                Notes
                                                <input v-model="transferForm.notes" type="text" maxlength="1000" class="mt-1 h-9 w-full rounded-lg border bg-white px-2 text-xs" />
                                            </label>
                                        </div>
                                        <div class="space-y-2">
                                            <div v-for="line in transferForm.lines" :key="line.reception_line_id" class="flex items-center gap-3">
                                                <span class="min-w-0 flex-1 truncate text-xs">{{ line.label }}</span>
                                                <input v-model.number="line.quantity_transferred" type="number" min="0" :max="line.max" step="0.01" class="h-8 w-24 rounded-lg border px-2 text-right text-xs" />
                                                <span class="w-20 text-[11px] text-muted-foreground">sur {{ line.max }}</span>
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="button" :disabled="!transferForm.project_id" class="rounded-lg bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white disabled:opacity-50" @click="submitTransfer">Enregistrer</button>
                                            <button type="button" class="rounded-lg border px-3 py-1.5 text-xs" @click="transferReceptionId = null">Annuler</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Facture rattachée -->
                                <div class="mt-2 border-t border-current/10 pt-2">
                                    <!-- Affichage facture existante -->
                                    <template v-if="invoiceEditing !== reception.id">
                                        <div v-if="reception.invoice_number" class="flex items-center justify-between">
                                            <div class="flex items-center gap-2">
                                                <Receipt class="h-3.5 w-3.5 text-blue-600 shrink-0" />
                                                <div>
                                                    <span class="text-xs font-semibold text-foreground">{{ reception.invoice_number }}</span>
                                                    <span v-if="reception.invoice_date" class="ml-2 text-xs text-muted-foreground">{{ formatDateShort(reception.invoice_date) }}</span>
                                                    <span v-if="reception.invoice_amount" class="ml-2 text-xs font-mono font-medium text-foreground">{{ formatAmount(reception.invoice_amount) }}</span>
                                                </div>
                                            </div>
                                            <button v-if="isAdmin || isCreator" @click="openInvoiceEdit(reception)" class="rounded-lg p-1 hover:bg-current/10 transition-colors">
                                                <Pencil class="h-3.5 w-3.5 text-muted-foreground" />
                                            </button>
                                        </div>
                                        <button v-else-if="isAdmin || isCreator" @click="openInvoiceEdit(reception)"
                                            class="flex items-center gap-1.5 text-xs text-blue-600 hover:underline">
                                            <Receipt class="h-3.5 w-3.5" /> Rattacher une facture
                                        </button>
                                    </template>

                                    <!-- Formulaire inline rapprochement -->
                                    <div v-else class="mt-1 space-y-2">
                                        <div class="grid grid-cols-2 gap-2">
                                            <input v-model="invoiceForms[reception.id].invoice_number" type="text" placeholder="N° facture"
                                                class="rounded-lg border border-input bg-white px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40" />
                                            <input v-model="invoiceForms[reception.id].invoice_date" type="date"
                                                class="rounded-lg border border-input bg-white px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40" />
                                        </div>
                                        <input v-model.number="invoiceForms[reception.id].invoice_amount" type="number" min="0" step="0.01" placeholder="Montant facturé (XOF)"
                                            class="w-full rounded-lg border border-input bg-white px-3 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-primary/40" />
                                        <div class="flex items-center gap-2">
                                            <button @click="saveInvoice(reception.id)"
                                                class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground hover:bg-primary/90 transition-colors">
                                                Enregistrer
                                            </button>
                                            <button @click="invoiceEditing = null"
                                                class="rounded-lg border px-3 py-1.5 text-xs font-medium text-muted-foreground hover:bg-muted/50 transition-colors">
                                                Annuler
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal réception -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm" @click.self="showModal = false">
                    <div class="relative w-full max-w-2xl rounded-2xl bg-white shadow-2xl flex flex-col max-h-[90vh]">
                        <!-- Header modal -->
                        <div class="flex items-center justify-between px-6 py-4 border-b">
                            <div class="flex items-center gap-2">
                                <ClipboardCheck class="h-5 w-5 text-orange-500" />
                                <h2 class="text-base font-semibold text-foreground">Saisir une réception</h2>
                            </div>
                            <button @click="showModal = false" class="rounded-lg p-1.5 hover:bg-muted transition-colors">
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Corps modal -->
                        <div class="overflow-y-auto flex-1 p-6 space-y-5">
                            <!-- Date de réception -->
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-1.5">Date de réception</label>
                                <input
                                    v-model="receptionForm.received_at"
                                    type="date"
                                    class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                                />
                            </div>

                            <!-- Lignes -->
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-2">Quantités reçues</label>
                                <div v-if="receptionForm.lines.length === 0" class="flex items-center gap-2 rounded-xl border border-orange-200 bg-orange-50 px-4 py-3 text-sm text-orange-700">
                                    <AlertCircle class="h-4 w-4 shrink-0" />
                                    Toutes les lignes ont déjà été entièrement réceptionnées.
                                </div>
                                <div v-else class="rounded-xl border overflow-hidden">
                                    <table class="w-full text-sm">
                                        <thead class="bg-muted/30">
                                            <tr>
                                                <th class="text-left px-4 py-2.5 text-xs font-medium text-muted-foreground">Article</th>
                                                <th class="text-center px-3 py-2.5 text-xs font-medium text-muted-foreground">Restant</th>
                                                <th class="text-center px-3 py-2.5 text-xs font-medium text-muted-foreground">Qté reçue</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y">
                                            <tr v-for="(rline, i) in receptionForm.lines" :key="rline.purchase_order_line_id" class="hover:bg-muted/10">
                                                <td class="px-4 py-3 font-medium text-foreground">
                                                    {{ rline.label }}
                                                    <span v-if="rline.unit" class="ml-1 text-xs text-muted-foreground">({{ rline.unit }})</span>
                                                </td>
                                                <td class="px-3 py-3 text-center text-sm text-muted-foreground">{{ rline.max }}</td>
                                                <td class="px-3 py-3 text-center">
                                                    <input
                                                        v-model.number="receptionForm.lines[i].quantity_received"
                                                        type="number"
                                                        :min="0"
                                                        :max="rline.max"
                                                        step="0.01"
                                                        class="w-24 rounded-lg border border-input bg-background px-2 py-1.5 text-center text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                                                    />
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- ─── Discussion ──────────────────────────────────────────────── -->
            <OrderDiscussion
                :order="order"
                :auth-user="authUser"
                :can-request-revision="false"
                :can-comment="['pending','needs_revision','draft'].includes(order.status)"
            />

                            <!-- Facture fournisseur -->
                            <div class="rounded-xl border border-dashed border-blue-200 bg-blue-50/50 p-4 space-y-3">
                                <p class="text-xs font-semibold text-blue-700 uppercase tracking-wide flex items-center gap-1.5">
                                    <Receipt class="h-3.5 w-3.5" /> Facture fournisseur <span class="font-normal normal-case">(optionnel — peut être saisi après)</span>
                                </p>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <label class="block text-xs font-medium text-muted-foreground mb-1">N° de facture</label>
                                        <input v-model="receptionForm.invoice_number" type="text" placeholder="FAC-2026-001"
                                            class="w-full rounded-lg border border-input bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-muted-foreground mb-1">Date de la facture</label>
                                        <input v-model="receptionForm.invoice_date" type="date"
                                            class="w-full rounded-lg border border-input bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-muted-foreground mb-1">Montant facturé (XOF)</label>
                                    <input v-model.number="receptionForm.invoice_amount" type="number" min="0" step="0.01" placeholder="0"
                                        class="w-full rounded-lg border border-input bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                                </div>
                            </div>

                            <!-- Notes / réserves -->
                            <div>
                                <label class="block text-xs font-medium text-muted-foreground mb-1.5">Réserves / observations</label>
                                <textarea
                                    v-model="receptionForm.notes"
                                    rows="3"
                                    placeholder="Ex: colis endommagé, quantité manquante..."
                                    class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none"
                                />
                            </div>
                        </div>

                        <!-- Footer modal -->
                        <div class="flex items-center justify-end gap-3 px-6 py-4 border-t bg-muted/20">
                            <button
                                @click="showModal = false"
                                class="rounded-xl border px-4 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                            >
                                Annuler
                            </button>
                            <button
                                :disabled="receptionForm.lines.length === 0"
                                @click="submitReception"
                                class="inline-flex items-center gap-2 rounded-xl bg-orange-500 px-5 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-orange-600 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <ClipboardCheck class="h-4 w-4" />
                                Enregistrer la réception
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.modal-enter-active, .modal-leave-active { transition: opacity 0.2s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; }
</style>
