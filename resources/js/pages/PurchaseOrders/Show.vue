<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PurchaseOrder, type ValidationLevel } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft, FileText, Download, Pencil, Send, CheckCircle2,
    XCircle, Clock, User, Calendar, DollarSign, Paperclip,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Mes commandes', href: '/purchase-orders' },
    { title: 'Détail', href: '#' },
];

const props = defineProps<{
    order: PurchaseOrder;
    levels: ValidationLevel[];
}>();

const statusConfig = {
    draft:    { label: 'Brouillon',  bg: 'bg-slate-100',  text: 'text-slate-700',   dot: 'bg-slate-400',   icon: Clock },
    pending:  { label: 'En attente', bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-500',   icon: Clock },
    approved: { label: 'Approuvée',  bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', icon: CheckCircle2 },
    rejected: { label: 'Refusée',    bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500',     icon: XCircle },
} as const;

const formatAmount = (v: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(v));

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const formatSize = (bytes: number) => {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    return (bytes / 1024).toFixed(0) + ' KB';
};

const submitOrder = async () => {
    const result = await Swal.fire({
        title: 'Soumettre à la validation ?',
        text: 'La commande sera envoyée pour approbation et ne pourra plus être modifiée.',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Soumettre',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#4f46e5',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) {
        router.post(route('purchase-orders.submit', props.order.id));
    }
};
</script>

<template>
    <Head :title="order.title" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 max-w-4xl sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <Link :href="route('purchase-orders.index')" class="mt-1 rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                        <ArrowLeft class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-foreground">{{ order.title }}</h1>
                        <div class="flex items-center gap-3 mt-2">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold"
                                :class="[statusConfig[order.status]?.bg, statusConfig[order.status]?.text]"
                            >
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status]?.dot" />
                                {{ statusConfig[order.status]?.label }}
                            </span>
                            <span v-if="order.status === 'pending'" class="text-sm text-muted-foreground">
                                Niveau {{ order.current_level_order }}
                                — {{ levels.find(l => l.order === order.current_level_order)?.name }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <Link
                        v-if="order.status === 'draft' || order.status === 'rejected'"
                        :href="route('purchase-orders.edit', order.id)"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted transition-colors"
                    >
                        <Pencil class="h-4 w-4" />
                        Modifier
                    </Link>
                    <button
                        v-if="order.status === 'draft' || order.status === 'rejected'"
                        @click="submitOrder"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors"
                    >
                        <Send class="h-4 w-4" />
                        Soumettre
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-6">

                <!-- Colonne principale -->
                <div class="col-span-2 flex flex-col gap-5">

                    <!-- Infos clés -->
                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground mb-4">Détails de la commande</h2>
                        <div class="grid grid-cols-2 gap-4 mb-5">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-50"><DollarSign class="h-4 w-4 text-blue-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Montant</p>
                                    <p class="font-bold text-foreground">{{ formatAmount(order.amount) }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-purple-50"><Calendar class="h-4 w-4 text-purple-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Créée le</p>
                                    <p class="font-medium text-foreground text-sm">{{ formatDate(order.created_at) }}</p>
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
                                class="flex items-center justify-between rounded-xl border bg-muted/30 px-4 py-3 hover:bg-muted/50 transition-colors group"
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
                </div>

                <!-- Colonne historique -->
                <div class="flex flex-col gap-5">
                    <!-- Circuit de validation -->
                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground mb-4">Circuit de validation</h2>
                        <div class="relative flex flex-col gap-0">
                            <div
                                v-for="(level, idx) in levels"
                                :key="level.id"
                                class="relative flex items-start gap-3 pb-4"
                            >
                                <!-- Ligne verticale -->
                                <div v-if="idx < levels.length - 1" class="absolute left-3.5 top-7 h-full w-px bg-border" />

                                <div
                                    class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold"
                                    :class="{
                                        'bg-emerald-100 text-emerald-700': order.status === 'approved' || (order.status === 'pending' && order.current_level_order! > level.order),
                                        'bg-primary text-primary-foreground ring-2 ring-primary/30': order.status === 'pending' && order.current_level_order === level.order,
                                        'bg-muted text-muted-foreground': order.status !== 'approved' && !(order.status === 'pending' && order.current_level_order! >= level.order),
                                        'bg-red-100 text-red-600': order.status === 'rejected' && order.validation_logs?.some(l => l.validation_level?.order === level.order && l.action === 'rejected'),
                                    }"
                                >
                                    <CheckCircle2 v-if="order.status === 'approved' || (order.status === 'pending' && order.current_level_order! > level.order)" class="h-3.5 w-3.5" />
                                    <XCircle v-else-if="order.status === 'rejected' && order.validation_logs?.some(l => l.validation_level?.order === level.order && l.action === 'rejected')" class="h-3.5 w-3.5" />
                                    <span v-else>{{ level.order }}</span>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold text-foreground leading-tight">{{ level.name }}</p>
                                    <p v-if="level.description" class="text-xs text-muted-foreground mt-0.5">{{ level.description }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historique -->
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
                                <p v-if="log.comment" class="mt-2 text-xs italic text-foreground/80 border-t border-current/10 pt-2">
                                    "{{ log.comment }}"
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
