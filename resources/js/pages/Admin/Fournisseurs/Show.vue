<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Fournisseur, type PurchaseOrder } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft, Truck, Mail, Phone, MapPin,
    ShieldCheck, ShieldAlert, FileText, Eye,
    CheckCircle2, Clock, XCircle, FileStack, Wallet,
} from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    fournisseur: Fournisseur;
    orders: PurchaseOrder[];
    stats: Record<string, number>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Fournisseurs', href: '/admin/fournisseurs' },
    { title: props.fournisseur.name, href: '#' },
];

const statusConfig = {
    draft:    { label: 'Brouillon',  bg: 'bg-slate-100',  text: 'text-slate-600',   dot: 'bg-slate-400' },
    pending:  { label: 'En attente', bg: 'bg-amber-50',   text: 'text-amber-700',   dot: 'bg-amber-400' },
    approved: { label: 'Approuvée',  bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    rejected: { label: 'Refusée',    bg: 'bg-red-50',     text: 'text-red-700',     dot: 'bg-red-500' },
} as const;

const formatAmount = (v: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(v);

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const formatAmountShort = (v: number) => {
    if (v >= 1_000_000_000) return (v / 1_000_000_000).toFixed(1) + ' Md';
    if (v >= 1_000_000)     return (v / 1_000_000).toFixed(1) + ' M';
    if (v >= 1_000)         return (v / 1_000).toFixed(0) + ' K';
    return v.toString();
};

const approvedOrders  = computed(() => props.orders.filter(o => o.status === 'approved'));
const pendingOrders   = computed(() => props.orders.filter(o => o.status === 'pending'));
const rejectedOrders  = computed(() => props.orders.filter(o => o.status === 'rejected'));
</script>

<template>
    <Head :title="'Historique — ' + fournisseur.name" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('admin.fournisseurs.index')" class="rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">{{ fournisseur.name }}</h1>
                    <p class="text-sm text-muted-foreground font-mono">{{ fournisseur.code }}</p>
                </div>
            </div>

            <!-- Fiche fournisseur -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl"
                            :class="fournisseur.is_approved ? 'bg-emerald-50' : 'bg-amber-50'">
                            <Truck class="h-6 w-6" :class="fournisseur.is_approved ? 'text-emerald-600' : 'text-amber-600'" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span v-if="fournisseur.is_approved"
                                    class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                    <ShieldCheck class="h-3 w-3" /> Homologué
                                </span>
                                <span v-else
                                    class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                    <ShieldAlert class="h-3 w-3" /> Non homologué
                                </span>
                                <span :class="fournisseur.is_active ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-500'"
                                    class="rounded-full px-2.5 py-1 text-xs font-medium">
                                    {{ fournisseur.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>
                            <div class="flex flex-col gap-1 text-xs text-muted-foreground mt-1">
                                <div v-if="fournisseur.email" class="flex items-center gap-1.5"><Mail class="h-3.5 w-3.5" />{{ fournisseur.email }}</div>
                                <div v-if="fournisseur.phone" class="flex items-center gap-1.5"><Phone class="h-3.5 w-3.5" />{{ fournisseur.phone }}</div>
                                <div v-if="fournisseur.city" class="flex items-center gap-1.5"><MapPin class="h-3.5 w-3.5" />{{ fournisseur.city }}</div>
                            </div>
                        </div>
                    </div>
                    <Link :href="route('admin.fournisseurs.edit', fournisseur.id)"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold text-foreground hover:bg-muted transition-colors self-start">
                        Modifier
                    </Link>
                </div>
            </div>

            <!-- Alerte non homologué -->
            <div v-if="!fournisseur.is_approved"
                class="rounded-2xl border border-amber-200 bg-amber-50 p-4 flex items-start gap-3">
                <ShieldAlert class="h-5 w-5 text-amber-600 shrink-0 mt-0.5" />
                <div>
                    <p class="text-sm font-semibold text-amber-800">Fournisseur non homologué</p>
                    <p class="text-xs text-amber-700 mt-0.5">
                        Ce fournisseur n'a pas encore été validé. Toute commande le mentionnant affiche une alerte au demandeur.
                        Accédez à la fiche pour l'homologuer.
                    </p>
                </div>
            </div>

            <!-- Stats -->
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 sm:gap-4">
                <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-muted-foreground">Total</span>
                        <div class="rounded-lg bg-blue-50 p-1.5"><FileStack class="h-4 w-4 text-blue-600" /></div>
                    </div>
                    <p class="text-2xl font-bold text-foreground">{{ stats.total }}</p>
                    <p class="text-xs text-muted-foreground mt-1">commandes</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-muted-foreground">Approuvées</span>
                        <div class="rounded-lg bg-emerald-50 p-1.5"><CheckCircle2 class="h-4 w-4 text-emerald-600" /></div>
                    </div>
                    <p class="text-2xl font-bold text-foreground">{{ stats.approved }}</p>
                    <p class="text-xs text-emerald-600 font-medium mt-1">{{ formatAmountShort(stats.budget_approved) }} FCFA</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-muted-foreground">En attente</span>
                        <div class="rounded-lg bg-amber-50 p-1.5"><Clock class="h-4 w-4 text-amber-600" /></div>
                    </div>
                    <p class="text-2xl font-bold text-foreground">{{ stats.pending }}</p>
                    <p class="text-xs text-amber-600 font-medium mt-1">{{ formatAmountShort(stats.budget_pending) }} FCFA</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm sm:p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-medium text-muted-foreground">Budget validé</span>
                        <div class="rounded-lg bg-emerald-50 p-1.5"><Wallet class="h-4 w-4 text-emerald-600" /></div>
                    </div>
                    <p class="text-lg font-bold text-emerald-700">{{ formatAmountShort(stats.budget_approved) }}</p>
                    <p class="text-xs text-muted-foreground mt-1">FCFA approuvés</p>
                </div>
            </div>

            <!-- Liste des commandes -->
            <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b">
                    <h2 class="font-semibold text-foreground flex items-center gap-2">
                        <FileText class="h-4 w-4 text-muted-foreground" />
                        Historique des commandes ({{ orders.length }})
                    </h2>
                </div>

                <div v-if="orders.length === 0" class="px-5 py-10 text-center text-sm text-muted-foreground">
                    Aucune commande associée à ce fournisseur.
                </div>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/20">
                            <th class="text-left px-5 py-3 font-semibold text-xs uppercase tracking-wide text-muted-foreground">Commande</th>
                            <th class="text-left px-4 py-3 font-semibold text-xs uppercase tracking-wide text-muted-foreground hidden md:table-cell">Demandeur</th>
                            <th class="text-left px-4 py-3 font-semibold text-xs uppercase tracking-wide text-muted-foreground hidden sm:table-cell">Boutique</th>
                            <th class="text-right px-4 py-3 font-semibold text-xs uppercase tracking-wide text-muted-foreground">Montant</th>
                            <th class="text-center px-4 py-3 font-semibold text-xs uppercase tracking-wide text-muted-foreground">Statut</th>
                            <th class="text-center px-4 py-3 font-semibold text-xs uppercase tracking-wide text-muted-foreground hidden sm:table-cell">Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="order in orders" :key="order.id" class="hover:bg-muted/20 transition-colors">
                            <td class="px-5 py-3.5">
                                <p class="font-medium text-foreground truncate max-w-[220px]">{{ order.title }}</p>
                                <p class="text-xs text-muted-foreground">#{{ order.id }}</p>
                            </td>
                            <td class="px-4 py-3.5 hidden md:table-cell text-sm text-muted-foreground">{{ order.user?.name ?? '—' }}</td>
                            <td class="px-4 py-3.5 hidden sm:table-cell text-sm text-muted-foreground">{{ order.boutique?.name ?? '—' }}</td>
                            <td class="px-4 py-3.5 text-right font-semibold text-foreground">{{ formatAmount(Number(order.amount)) }}</td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="[statusConfig[order.status]?.bg, statusConfig[order.status]?.text]">
                                    <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[order.status]?.dot" />
                                    {{ statusConfig[order.status]?.label }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-center hidden sm:table-cell text-xs text-muted-foreground">
                                {{ formatDate(order.created_at) }}
                            </td>
                            <td class="px-4 py-3.5 text-center">
                                <Link :href="route('validations.show', order.id)"
                                    class="rounded-lg p-1.5 hover:bg-muted transition-colors text-muted-foreground hover:text-foreground inline-flex">
                                    <Eye class="h-4 w-4" />
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

        </div>
    </AppLayout>
</template>
