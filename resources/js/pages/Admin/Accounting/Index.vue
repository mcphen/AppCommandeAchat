<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type AccountingEntry, type BreadcrumbItem, type Boutique, type PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Download, FileText, Filter, RotateCcw, X } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    entries:  PaginatedData<AccountingEntry>;
    totals:   { debit: number; credit: number };
    boutiques: Boutique[];
    filters:  Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Comptabilité', href: '/admin/accounting' },
];

const fmt = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatAmount = (v: string | number) => fmt.format(Number(v));

// Filtres
const from         = ref(props.filters.from ?? '');
const to           = ref(props.filters.to ?? '');
const accountCode  = ref(props.filters.account_code ?? '');
const pieceRef     = ref(props.filters.piece_ref ?? '');
const boutiqueId   = ref(props.filters.boutique_id ?? '');

const hasFilters = () => from.value || to.value || accountCode.value || pieceRef.value || boutiqueId.value;

const applyFilters = () => {
    router.get(route('admin.accounting.index'), {
        from:         from.value || undefined,
        to:           to.value || undefined,
        account_code: accountCode.value || undefined,
        piece_ref:    pieceRef.value || undefined,
        boutique_id:  boutiqueId.value || undefined,
    }, { preserveState: true, replace: true });
};

const clearFilters = () => {
    from.value = '';
    to.value = '';
    accountCode.value = '';
    pieceRef.value = '';
    boutiqueId.value = '';
    router.get(route('admin.accounting.index'), {}, { preserveState: false, replace: true });
};

const exportUrl = (format: 'fec' | 'csv') => {
    const params = new URLSearchParams();
    if (from.value)        params.set('from', from.value);
    if (to.value)          params.set('to', to.value);
    if (accountCode.value) params.set('account_code', accountCode.value);
    if (pieceRef.value)    params.set('piece_ref', pieceRef.value);
    if (boutiqueId.value)  params.set('boutique_id', boutiqueId.value);
    const qs = params.toString();
    return route('admin.accounting.export', { format }) + (qs ? '?' + qs : '');
};
</script>

<template>
    <Head title="Comptabilité — Écritures" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Écritures comptables</h1>
                    <p class="text-sm text-muted-foreground mt-1">Journal des achats — {{ entries.total }} ligne{{ entries.total > 1 ? 's' : '' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="exportUrl('csv')" class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors shadow-sm">
                        <Download class="h-4 w-4" />
                        Export CSV
                    </a>
                    <a :href="exportUrl('fec')" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                        <FileText class="h-4 w-4" />
                        Export FEC
                    </a>
                </div>
            </div>

            <!-- Totaux -->
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs text-muted-foreground mb-1">Total Débit</p>
                    <p class="text-xl font-bold text-foreground font-mono">{{ formatAmount(totals.debit) }}</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs text-muted-foreground mb-1">Total Crédit</p>
                    <p class="text-xl font-bold text-foreground font-mono">{{ formatAmount(totals.credit) }}</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm col-span-2 sm:col-span-1">
                    <p class="text-xs text-muted-foreground mb-1">Équilibre</p>
                    <p class="text-xl font-bold font-mono" :class="Math.abs(totals.debit - totals.credit) < 0.01 ? 'text-emerald-600' : 'text-red-500'">
                        {{ Math.abs(totals.debit - totals.credit) < 0.01 ? 'Équilibré' : formatAmount(Math.abs(totals.debit - totals.credit)) }}
                    </p>
                </div>
            </div>

            <!-- Filtres -->
            <div class="rounded-2xl border bg-card p-4 shadow-sm">
                <div class="flex items-center gap-2 mb-3">
                    <Filter class="h-4 w-4 text-muted-foreground" />
                    <span class="text-sm font-medium text-foreground">Filtres</span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-muted-foreground">Du</label>
                        <input v-model="from" type="date" class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-muted-foreground">Au</label>
                        <input v-model="to" type="date" class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-muted-foreground">Compte (préfixe)</label>
                        <input v-model="accountCode" type="text" placeholder="604..." class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary/30" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-muted-foreground">N° BC</label>
                        <input v-model="pieceRef" type="text" placeholder="BC-2026-..." class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <label class="text-xs font-medium text-muted-foreground">Boutique</label>
                        <select v-model="boutiqueId" class="h-9 w-full rounded-lg border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30">
                            <option value="">Toutes</option>
                            <option v-for="b in boutiques" :key="b.id" :value="b.id">{{ b.name }}</option>
                        </select>
                    </div>
                </div>
                <div class="flex items-center gap-2 mt-3">
                    <button @click="applyFilters" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 transition-colors">
                        <Filter class="h-3.5 w-3.5" /> Filtrer
                    </button>
                    <button v-if="hasFilters()" @click="clearFilters" class="inline-flex items-center gap-2 rounded-lg border px-4 py-2 text-sm font-medium text-muted-foreground hover:bg-muted transition-colors">
                        <X class="h-3.5 w-3.5" /> Réinitialiser
                    </button>
                </div>
            </div>

            <!-- Table vide -->
            <EmptyState
                v-if="entries.data.length === 0"
                :icon="BookOpen"
                icon-bg="bg-blue-50"
                icon-color="text-blue-500"
                title="Aucune écriture comptable"
                description="Les écritures sont générées automatiquement à chaque confirmation de bon de commande. Confirmez une première commande pour les voir apparaître ici."
                :action-href="route('purchase-orders.index')"
                action-label="Voir les commandes"
            />

            <!-- Table -->
            <div v-else class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/40">
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Journal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">N° pièce</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Compte</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Libellé</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-muted-foreground uppercase tracking-wide">Auxiliaire</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wide">Débit</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-muted-foreground uppercase tracking-wide">Crédit</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="entry in entries.data" :key="entry.id" class="border-b last:border-b-0 hover:bg-muted/20 transition-colors">
                                <td class="px-4 py-3 text-sm text-foreground whitespace-nowrap">
                                    {{ new Date(entry.entry_date).toLocaleDateString('fr-FR') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-mono font-medium text-slate-600">{{ entry.journal_code }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <Link
                                        v-if="entry.purchase_order"
                                        :href="route('purchase-orders.show', entry.purchase_order_id)"
                                        class="text-primary hover:underline text-xs font-mono font-medium"
                                    >
                                        {{ entry.piece_ref }}
                                    </Link>
                                    <span v-else class="text-xs font-mono text-muted-foreground">{{ entry.piece_ref }}</span>
                                    <!-- Référence facture si rapprochée -->
                                    <p v-if="entry.purchase_order?.receptions?.some((r: any) => r.invoice_number)"
                                        class="text-xs text-blue-600 mt-0.5">
                                        FAC : {{ entry.purchase_order.receptions.filter((r: any) => r.invoice_number).map((r: any) => r.invoice_number).join(', ') }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-sm font-semibold text-foreground">{{ entry.account_code }}</span>
                                    <p class="text-xs text-muted-foreground">{{ entry.account_label }}</p>
                                </td>
                                <td class="px-4 py-3 text-sm text-foreground max-w-xs truncate" :title="entry.entry_label">
                                    {{ entry.entry_label }}
                                </td>
                                <td class="px-4 py-3">
                                    <template v-if="entry.aux_code">
                                        <span class="text-xs font-mono text-muted-foreground">{{ entry.aux_code }}</span>
                                        <p class="text-xs text-foreground">{{ entry.aux_label }}</p>
                                    </template>
                                    <span v-else class="text-muted-foreground">—</span>
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-sm font-semibold" :class="Number(entry.debit) > 0 ? 'text-foreground' : 'text-muted-foreground'">
                                    {{ Number(entry.debit) > 0 ? formatAmount(entry.debit) : '—' }}
                                </td>
                                <td class="px-4 py-3 text-right font-mono text-sm font-semibold" :class="Number(entry.credit) > 0 ? 'text-foreground' : 'text-muted-foreground'">
                                    {{ Number(entry.credit) > 0 ? formatAmount(entry.credit) : '—' }}
                                </td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="border-t bg-muted/40">
                                <td colspan="6" class="px-4 py-3 text-sm font-semibold text-foreground text-right">Totaux (page filtrée)</td>
                                <td class="px-4 py-3 text-right font-mono text-sm font-bold text-foreground">{{ formatAmount(totals.debit) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-sm font-bold text-foreground">{{ formatAmount(totals.credit) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="entries.last_page > 1" class="flex items-center justify-between px-4 py-3 border-t bg-muted/20">
                    <p class="text-sm text-muted-foreground">
                        Lignes {{ entries.from }}–{{ entries.to }} sur {{ entries.total }}
                    </p>
                    <div class="flex items-center gap-1">
                        <template v-for="link in entries.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                                :class="link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'"
                                v-html="link.label"
                            />
                            <span v-else class="rounded-lg px-3 py-1.5 text-xs font-medium text-muted-foreground/40" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </div>

            <!-- Info format FEC -->
            <div class="rounded-2xl border border-blue-100 bg-blue-50/50 p-4 flex gap-3">
                <BookOpen class="h-4 w-4 text-blue-600 shrink-0 mt-0.5" />
                <div class="text-xs text-blue-700 space-y-1">
                    <p><strong>Export FEC</strong> — Format standard séparé par des <code>|</code>, compatible avec l'import dans Sage 100, Odoo, et tout logiciel SYSCOHADA.</p>
                    <p><strong>Export CSV</strong> — Format séparé par des <code>;</code>, encodage UTF-8 avec BOM, compatible Excel, Epegase, et import générique.</p>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
