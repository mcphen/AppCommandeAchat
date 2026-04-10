<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PurchaseOrder, type ValidationLevel } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Building2, Calendar, CheckCircle2, Clock, DollarSign, Download, FileDown, FileText, Paperclip, Pencil, Send, User, XCircle } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Mes commandes', href: '/purchase-orders' },
    { title: 'Detail', href: '#' },
];

const props = defineProps<{
    order: PurchaseOrder;
    levels: ValidationLevel[];
}>();

const statusConfig = {
    draft: { label: 'Brouillon', bg: 'bg-slate-100', text: 'text-slate-700', dot: 'bg-slate-400', icon: Clock },
    pending: { label: 'En attente', bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500', icon: Clock },
    approved: { label: 'Approuvee', bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500', icon: CheckCircle2 },
    rejected: { label: 'Refusee', bg: 'bg-red-50', text: 'text-red-700', dot: 'bg-red-500', icon: XCircle },
} as const;

const formatAmount = (value: string | number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(Number(value));

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const formatSize = (bytes: number) => {
    if (bytes >= 1048576) return `${(bytes / 1048576).toFixed(1)} MB`;
    return `${(bytes / 1024).toFixed(0)} KB`;
};

const progressSummary = () => {
    const totalLevels = props.levels.length;

    if (props.order.status === 'approved') {
        return `Commande entierement approuvee (${totalLevels}/${totalLevels} niveaux).`;
    }

    if (props.order.status === 'rejected') {
        return 'Commande arretee dans le circuit de validation.';
    }

    if (props.order.status === 'pending' && props.order.current_level_order) {
        const completedLevels = Math.max(props.order.current_level_order - 1, 0);
        return `${completedLevels}/${totalLevels} niveaux valides. En attente du niveau ${props.order.current_level_order}.`;
    }

    return 'Commande non encore soumise au circuit.';
};

const getLogForLevel = (levelId: number) => {
    return props.order.validation_logs?.find(log => log.validation_level_id === levelId) ?? null;
};

const submitOrder = async () => {
    const result = await Swal.fire({
        title: 'Soumettre a la validation ?',
        text: 'La commande sera envoyee pour approbation et ne pourra plus etre modifiee.',
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
        <div class="flex max-w-4xl flex-col gap-4 p-3 sm:gap-6 sm:p-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-start gap-4">
                    <Link :href="route('purchase-orders.index')" class="mt-1 rounded-xl p-2 text-muted-foreground transition-colors hover:bg-muted">
                        <ArrowLeft class="h-5 w-5" />
                    </Link>
                    <div>
                        <h1 class="text-2xl font-bold text-foreground">{{ order.title }}</h1>
                        <div class="mt-2 flex items-center gap-3">
                            <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold" :class="[statusConfig[order.status]?.bg, statusConfig[order.status]?.text]">
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status]?.dot" />
                                {{ statusConfig[order.status]?.label }}
                            </span>
                            <span v-if="order.status === 'pending'" class="text-sm text-muted-foreground">
                                Niveau {{ order.current_level_order }} - {{ levels.find(level => level.order === order.current_level_order)?.name }}
                            </span>
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">{{ progressSummary() }}</p>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <a
                        :href="route('purchase-orders.pdf', order.id)"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-200"
                        title="Telecharger en PDF"
                    >
                        <FileDown class="h-4 w-4" />
                        <span class="hidden sm:inline">PDF</span>
                    </a>
                    <Link
                        v-if="order.status === 'draft' || order.status === 'rejected'"
                        :href="route('purchase-orders.edit', order.id)"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                    >
                        <Pencil class="h-4 w-4" />
                        Modifier
                    </Link>
                    <button
                        v-if="order.status === 'draft' || order.status === 'rejected'"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                        @click="submitOrder"
                    >
                        <Send class="h-4 w-4" />
                        Soumettre
                    </button>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="flex flex-col gap-5 lg:col-span-2">
                    <div class="rounded-2xl border bg-card p-6 shadow-sm">
                        <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Details de la commande</h2>
                        <div class="mb-5 grid gap-4 md:grid-cols-2">
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
                                    <p class="text-xs text-muted-foreground">Creee le</p>
                                    <p class="text-sm font-medium text-foreground">{{ formatDate(order.created_at) }}</p>
                                </div>
                            </div>
                            <div v-if="order.boutique" class="flex items-center gap-3 md:col-span-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-cyan-50"><Building2 class="h-4 w-4 text-cyan-600" /></div>
                                <div>
                                    <p class="text-xs text-muted-foreground">Boutique</p>
                                    <p class="text-sm font-medium text-foreground">{{ order.boutique.name }} <span class="text-muted-foreground">({{ order.boutique.code }})</span></p>
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
                                class="group flex items-center justify-between rounded-xl border bg-muted/30 px-4 py-3 transition-colors hover:bg-muted/50"
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
                </div>

                <div class="flex flex-col gap-5">
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
                                    <p class="text-sm font-semibold leading-tight text-foreground">{{ level.name }}</p>
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
    </AppLayout>
</template>
