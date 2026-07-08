<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type AccountingEntry, type Boutique, type BreadcrumbItem, type PaginatedData } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { BookOpen, Building2, Download, FileText, Filter, ReceiptText, RotateCcw, Scale, ShieldCheck } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    entries: PaginatedData<AccountingEntry>;
    totals: { debit: number; credit: number };
    boutiques: Boutique[];
    filters: Record<string, string>;
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Comptabilite', href: '/admin/accounting' },
];

const fmt = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const formatAmount = (v: string | number) => fmt.format(Number(v));

const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const accountCode = ref(props.filters.account_code ?? '');
const pieceRef = ref(props.filters.piece_ref ?? '');
const boutiqueId = ref(props.filters.boutique_id ?? '');

const hasFilters = () => from.value || to.value || accountCode.value || pieceRef.value || boutiqueId.value;

const applyFilters = () => {
    router.get(
        route('admin.accounting.index'),
        {
            from: from.value || undefined,
            to: to.value || undefined,
            account_code: accountCode.value || undefined,
            piece_ref: pieceRef.value || undefined,
            boutique_id: boutiqueId.value || undefined,
        },
        { preserveState: true, replace: true },
    );
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
    if (from.value) params.set('from', from.value);
    if (to.value) params.set('to', to.value);
    if (accountCode.value) params.set('account_code', accountCode.value);
    if (pieceRef.value) params.set('piece_ref', pieceRef.value);
    if (boutiqueId.value) params.set('boutique_id', boutiqueId.value);
    const qs = params.toString();
    return route('admin.accounting.export', { format }) + (qs ? '?' + qs : '');
};
</script>

<template>
    <Head title="Comptabilite - Ecritures" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Ecritures comptables</h1>
                    <p class="text-sm text-muted-foreground">Journal des achats et export comptable de l'application.</p>
                </div>

                <div
                    class="inline-flex items-center gap-2 self-start rounded-xl border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary"
                >
                    <ShieldCheck class="h-4 w-4" />
                    Administration
                </div>
            </div>

            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <BookOpen class="h-8 w-8" />
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Journal comptable</p>
                            <h2 class="mt-1 text-xl font-semibold text-foreground">Suivi des ecritures d'achat</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Consultez les lignes generees, filtrez les pieces et exportez vos donnees en CSV ou au format FEC.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:w-[560px]">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">
                                <ReceiptText class="h-4 w-4" />
                                Ecritures
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ entries.total }} ligne{{ entries.total > 1 ? 's' : '' }} disponibles
                            </p>
                        </div>

                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                <Download class="h-4 w-4" />
                                Exports
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">CSV et FEC compatibles avec vos outils comptables</p>
                        </div>

                        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                                <Building2 class="h-4 w-4" />
                                Sociétés
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">Filtrage par piece, compte, periode et société</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-2">
                <a
                    :href="exportUrl('csv')"
                    class="inline-flex items-center gap-2 rounded-xl border border-border bg-card px-4 py-2.5 text-sm font-semibold text-foreground shadow-sm transition-colors hover:bg-muted"
                >
                    <Download class="h-4 w-4" />
                    Export CSV
                </a>
                <a
                    :href="exportUrl('fec')"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <FileText class="h-4 w-4" />
                    Export FEC
                </a>
            </div>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total debit</p>
                    <p class="mt-2 font-mono text-2xl font-bold text-foreground">{{ formatAmount(totals.debit) }}</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total credit</p>
                    <p class="mt-2 font-mono text-2xl font-bold text-foreground">{{ formatAmount(totals.credit) }}</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Equilibre</p>
                    <p
                        class="mt-2 font-mono text-2xl font-bold"
                        :class="
                            Math.abs(totals.debit - totals.credit) < 0.01
                                ? 'text-emerald-600 dark:text-emerald-300'
                                : 'text-red-500 dark:text-red-300'
                        "
                    >
                        {{ Math.abs(totals.debit - totals.credit) < 0.01 ? 'Equilibre' : formatAmount(Math.abs(totals.debit - totals.credit)) }}
                    </p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        <Scale class="h-4 w-4" />
                        Controle
                    </div>
                    <p class="mt-2 text-sm font-semibold text-foreground">
                        {{
                            Math.abs(totals.debit - totals.credit) < 0.01 ? 'Journal coherent pour la periode affichee' : 'Un ecart doit etre verifie'
                        }}
                    </p>
                </div>
            </section>

            <section class="rounded-2xl border bg-card p-6 shadow-sm">
                <div class="flex flex-col gap-5">
                    <div>
                        <p class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Filtres</p>
                        <p class="mt-1 text-sm text-muted-foreground">Affinez le journal par periode, compte, piece comptable ou société.</p>
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">
                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-foreground">Du</label>
                            <input
                                v-model="from"
                                type="date"
                                class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-foreground">Au</label>
                            <input
                                v-model="to"
                                type="date"
                                class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-foreground">Compte</label>
                            <input
                                v-model="accountCode"
                                type="text"
                                placeholder="604..."
                                class="h-10 w-full rounded-xl border border-input bg-background px-3 font-mono text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-foreground">No piece</label>
                            <input
                                v-model="pieceRef"
                                type="text"
                                placeholder="BC-2026-..."
                                class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
                            />
                        </div>

                        <div class="flex flex-col gap-2">
                            <label class="text-sm font-medium text-foreground">Société</label>
                            <select
                                v-model="boutiqueId"
                                class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
                            >
                                <option value="">Toutes</option>
                                <option v-for="b in boutiques" :key="b.id" :value="b.id">{{ b.name }}</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <button
                            @click="applyFilters"
                            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                        >
                            <Filter class="h-4 w-4" />
                            Filtrer
                        </button>
                        <button
                            v-if="hasFilters()"
                            @click="clearFilters"
                            class="inline-flex items-center gap-2 rounded-xl border border-border px-4 py-2.5 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        >
                            <RotateCcw class="h-4 w-4" />
                            Reinitialiser
                        </button>
                    </div>
                </div>
            </section>

            <EmptyState
                v-if="entries.data.length === 0"
                :icon="BookOpen"
                icon-bg="bg-blue-50 dark:bg-blue-950/30"
                icon-color="text-blue-500 dark:text-blue-300"
                title="Aucune ecriture comptable"
                description="Les ecritures sont generees automatiquement a chaque confirmation de bon de commande. Confirmez une premiere commande pour les voir apparaitre ici."
                :action-href="route('purchase-orders.index')"
                action-label="Voir les commandes"
            />

            <section v-else class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[980px] text-sm">
                        <thead>
                            <tr class="border-b bg-muted/40">
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Journal</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">No piece</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Compte</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Libelle</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">Auxiliaire</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Debit</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-muted-foreground">Credit</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr v-for="entry in entries.data" :key="entry.id" class="border-b transition-colors last:border-b-0 hover:bg-muted/20">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-foreground">
                                    {{ new Date(entry.entry_date).toLocaleDateString('fr-FR') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full border border-blue-100 bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300"
                                    >
                                        {{ entry.journal_code }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <Link
                                        v-if="entry.purchase_order"
                                        :href="route('purchase-orders.show', entry.purchase_order_id)"
                                        class="font-mono text-xs font-medium text-primary hover:underline"
                                    >
                                        {{ entry.piece_ref }}
                                    </Link>
                                    <span v-else class="font-mono text-xs text-muted-foreground">{{ entry.piece_ref }}</span>
                                    <p
                                        v-if="entry.purchase_order?.receptions?.some((r: any) => r.invoice_number)"
                                        class="mt-0.5 text-xs text-blue-600 dark:text-blue-300"
                                    >
                                        FAC :
                                        {{
                                            entry.purchase_order.receptions
                                                .filter((r: any) => r.invoice_number)
                                                .map((r: any) => r.invoice_number)
                                                .join(', ')
                                        }}
                                    </p>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="font-mono text-sm font-semibold text-foreground">{{ entry.account_code }}</span>
                                    <p class="text-xs text-muted-foreground">{{ entry.account_label }}</p>
                                </td>
                                <td class="max-w-xs truncate px-4 py-3 text-sm text-foreground" :title="entry.entry_label">
                                    {{ entry.entry_label }}
                                </td>
                                <td class="px-4 py-3">
                                    <template v-if="entry.aux_code">
                                        <span class="font-mono text-xs text-muted-foreground">{{ entry.aux_code }}</span>
                                        <p class="text-xs text-foreground">{{ entry.aux_label }}</p>
                                    </template>
                                    <span v-else class="text-muted-foreground">-</span>
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-mono text-sm font-semibold"
                                    :class="Number(entry.debit) > 0 ? 'text-foreground' : 'text-muted-foreground'"
                                >
                                    {{ Number(entry.debit) > 0 ? formatAmount(entry.debit) : '-' }}
                                </td>
                                <td
                                    class="px-4 py-3 text-right font-mono text-sm font-semibold"
                                    :class="Number(entry.credit) > 0 ? 'text-foreground' : 'text-muted-foreground'"
                                >
                                    {{ Number(entry.credit) > 0 ? formatAmount(entry.credit) : '-' }}
                                </td>
                            </tr>
                        </tbody>

                        <tfoot>
                            <tr class="border-t bg-muted/40">
                                <td colspan="6" class="px-4 py-3 text-right text-sm font-semibold text-foreground">Totaux (page filtree)</td>
                                <td class="px-4 py-3 text-right font-mono text-sm font-bold text-foreground">{{ formatAmount(totals.debit) }}</td>
                                <td class="px-4 py-3 text-right font-mono text-sm font-bold text-foreground">{{ formatAmount(totals.credit) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div
                    v-if="entries.last_page > 1"
                    class="flex flex-col gap-3 border-t bg-muted/20 px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p class="text-sm text-muted-foreground">Lignes {{ entries.from }}-{{ entries.to }} sur {{ entries.total }}</p>
                    <div class="flex flex-wrap items-center gap-1">
                        <template v-for="link in entries.links" :key="link.label">
                            <Link
                                v-if="link.url"
                                :href="link.url"
                                class="rounded-lg px-3 py-1.5 text-xs font-medium transition-colors"
                                :class="
                                    link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                                "
                                v-html="link.label"
                            />
                            <span v-else class="rounded-lg px-3 py-1.5 text-xs font-medium text-muted-foreground/40" v-html="link.label" />
                        </template>
                    </div>
                </div>
            </section>

            <section class="flex gap-3 rounded-2xl border border-blue-100 bg-blue-50/70 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                <BookOpen class="mt-0.5 h-4 w-4 shrink-0 text-blue-600 dark:text-blue-300" />
                <div class="space-y-1 text-xs text-blue-700 dark:text-blue-300">
                    <p>
                        <strong>Export FEC</strong> - Format standard separe par des <code>|</code>, compatible avec l'import dans Sage 100, Odoo et
                        les logiciels SYSCOHADA.
                    </p>
                    <p>
                        <strong>Export CSV</strong> - Format separe par des <code>;</code>, encodage UTF-8 avec BOM, compatible Excel et imports
                        generiques.
                    </p>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
