<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Budget, type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { PiggyBank, Plus, Pencil, Trash2, AlertTriangle, TrendingUp, Building2, FolderTree } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    budgets: Budget[];
    selectedYear: number;
    availableYears: number[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Budgets', href: '/admin/budgets' },
];

const deleting = ref<number | null>(null);

const formatAmount = (v: number) =>
    new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(v);

const formatK = (v: number) => {
    if (v >= 1_000_000_000) return (v / 1_000_000_000).toFixed(1) + ' Md';
    if (v >= 1_000_000)     return (v / 1_000_000).toFixed(1) + ' M';
    if (v >= 1_000)         return (v / 1_000).toFixed(0) + ' K';
    return v.toFixed(0);
};

const months = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sep', 'Oct', 'Nov', 'Déc'];

const summary = computed(() => {
    const total = props.budgets.reduce((s, b) => s + (b.amount ?? 0), 0);
    const engaged = props.budgets.reduce((s, b) => s + (b.consumption?.engaged ?? 0), 0);
    const exceeded = props.budgets.filter(b => b.consumption?.is_exceeded).length;
    const warning  = props.budgets.filter(b => b.consumption?.is_warning).length;
    return { total, engaged, exceeded, warning };
});

const changeYear = (year: number) => {
    router.get(route('admin.budgets.index'), { year }, { preserveState: true });
};

const destroy = (id: number) => {
    if (!confirm('Supprimer ce budget ?')) return;
    deleting.value = id;
    router.delete(route('admin.budgets.destroy', id), {
        onFinish: () => deleting.value = null,
    });
};

const barColor = (b: Budget) => {
    if (b.consumption?.is_exceeded) return 'bg-red-500';
    if (b.consumption?.is_warning)  return 'bg-amber-400';
    return 'bg-emerald-500';
};

const statusBadge = (b: Budget) => {
    if (b.consumption?.is_exceeded) return { cls: 'bg-red-100 text-red-700', label: 'Dépassé' };
    if (b.consumption?.is_warning)  return { cls: 'bg-amber-100 text-amber-700', label: 'Alerte' };
    return { cls: 'bg-emerald-50 text-emerald-700', label: 'OK' };
};
</script>

<template>
    <Head title="Budgets" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Gestion budgétaire</h1>
                    <p class="text-sm text-muted-foreground mt-1">{{ budgets.length }} budget{{ budgets.length > 1 ? 's' : '' }} — {{ selectedYear }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <!-- Sélecteur d'année -->
                    <select :value="selectedYear" @change="changeYear(+($event.target as HTMLSelectElement).value)"
                        class="h-9 rounded-xl border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors">
                        <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
                    </select>
                    <Link :href="route('admin.budgets.create')" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors shrink-0">
                        <Plus class="h-4 w-4" /> Nouveau budget
                    </Link>
                </div>
            </div>

            <!-- Résumé -->
            <div v-if="budgets.length > 0" class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">Total alloué</p>
                    <p class="text-xl font-bold text-foreground">{{ formatK(summary.total) }}</p>
                    <p class="text-xs text-muted-foreground mt-0.5">FCFA</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">Total engagé</p>
                    <p class="text-xl font-bold text-foreground">{{ formatK(summary.engaged) }}</p>
                    <p class="text-xs text-muted-foreground mt-0.5">FCFA</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">En alerte</p>
                    <p class="text-xl font-bold" :class="summary.warning > 0 ? 'text-amber-600' : 'text-foreground'">{{ summary.warning }}</p>
                    <p class="text-xs text-muted-foreground mt-0.5">budget{{ summary.warning > 1 ? 's' : '' }}</p>
                </div>
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide mb-1">Dépassés</p>
                    <p class="text-xl font-bold" :class="summary.exceeded > 0 ? 'text-red-600' : 'text-foreground'">{{ summary.exceeded }}</p>
                    <p class="text-xs text-muted-foreground mt-0.5">budget{{ summary.exceeded > 1 ? 's' : '' }}</p>
                </div>
            </div>

            <!-- Vide -->
            <EmptyState
                v-if="budgets.length === 0"
                :icon="PiggyBank"
                icon-bg="bg-emerald-50"
                icon-color="text-emerald-500"
                :title="`Aucun budget pour ${selectedYear}`"
                description="Définissez des enveloppes budgétaires par boutique et/ou catégorie pour contrôler les dépenses en temps réel."
                :action-href="route('admin.budgets.create')"
                action-label="Créer un budget"
            />

            <!-- Liste des budgets (cards) -->
            <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <div v-for="b in budgets" :key="b.id"
                    class="rounded-2xl border bg-card shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-shadow"
                    :class="{ 'border-red-200 bg-red-50/30': b.consumption?.is_exceeded, 'border-amber-200 bg-amber-50/30': b.consumption?.is_warning && !b.consumption?.is_exceeded }">

                    <!-- En-tête carte -->
                    <div class="flex items-start justify-between gap-2">
                        <div class="flex flex-col gap-1 min-w-0">
                            <!-- Périmètre -->
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span v-if="b.boutique" class="inline-flex items-center gap-1 rounded-lg bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-700">
                                    <Building2 class="h-3 w-3" /> {{ b.boutique.name }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                                    <Building2 class="h-3 w-3" /> Toutes boutiques
                                </span>
                                <span v-if="b.category" class="inline-flex items-center gap-1 rounded-lg bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700">
                                    <FolderTree class="h-3 w-3" /> {{ b.category.name }}
                                </span>
                                <span v-else class="inline-flex items-center gap-1 rounded-lg bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-500">
                                    <FolderTree class="h-3 w-3" /> Toutes catégories
                                </span>
                            </div>
                            <!-- Période -->
                            <p class="text-xs text-muted-foreground font-medium">{{ b.period }}</p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            <span :class="statusBadge(b).cls" class="rounded-full px-2 py-0.5 text-xs font-semibold">
                                {{ statusBadge(b).label }}
                            </span>
                        </div>
                    </div>

                    <!-- Montants -->
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-xl bg-muted/40 px-2 py-2">
                            <p class="text-[11px] text-muted-foreground mb-0.5">Alloué</p>
                            <p class="text-sm font-bold text-foreground">{{ formatK(b.amount) }}</p>
                        </div>
                        <div class="rounded-xl bg-muted/40 px-2 py-2">
                            <p class="text-[11px] text-muted-foreground mb-0.5">Consommé</p>
                            <p class="text-sm font-bold text-emerald-700">{{ formatK(b.consumption?.consumed ?? 0) }}</p>
                        </div>
                        <div class="rounded-xl bg-muted/40 px-2 py-2">
                            <p class="text-[11px] text-muted-foreground mb-0.5">En cours</p>
                            <p class="text-sm font-bold text-amber-600">{{ formatK(b.consumption?.in_validation ?? 0) }}</p>
                        </div>
                    </div>

                    <!-- Barre de progression -->
                    <div>
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-xs text-muted-foreground">Engagé</span>
                            <span class="text-xs font-semibold" :class="b.consumption?.is_exceeded ? 'text-red-600' : b.consumption?.is_warning ? 'text-amber-600' : 'text-foreground'">
                                {{ b.consumption?.percent_engaged ?? 0 }}%
                            </span>
                        </div>
                        <div class="h-2 w-full rounded-full bg-muted overflow-hidden">
                            <!-- Partie consommée -->
                            <div class="h-full rounded-full relative overflow-hidden" :style="{ width: Math.min(100, b.consumption?.percent_engaged ?? 0) + '%' }">
                                <div class="absolute inset-0" :class="barColor(b)" />
                                <!-- Partie en validation (overlay plus clair) -->
                                <div v-if="b.consumption && b.consumption.in_validation > 0"
                                    class="absolute inset-y-0 right-0 bg-amber-300/70"
                                    :style="{ width: b.consumption.amount > 0 ? Math.min(100, b.consumption.in_validation / b.consumption.amount * 100) + '%' : '0%' }" />
                            </div>
                        </div>
                        <div class="flex justify-between items-center mt-1">
                            <span class="text-xs text-muted-foreground">Disponible</span>
                            <span class="text-xs font-medium text-foreground">{{ formatAmount(b.consumption?.available ?? b.amount) }}</span>
                        </div>
                    </div>

                    <!-- Alerte dépassement -->
                    <div v-if="b.consumption?.is_exceeded" class="flex items-center gap-2 rounded-xl bg-red-100 px-3 py-2 text-xs font-medium text-red-700">
                        <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                        Budget dépassé de {{ formatAmount(Math.abs(b.consumption.available)) }}
                    </div>
                    <div v-else-if="b.consumption?.is_warning" class="flex items-center gap-2 rounded-xl bg-amber-100 px-3 py-2 text-xs font-medium text-amber-700">
                        <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                        Plus de 80% du budget engagé
                    </div>

                    <!-- Actions -->
                    <div class="flex items-center justify-end gap-1 pt-1 border-t border-border/50">
                        <Link :href="route('admin.budgets.edit', b.id)" class="rounded-lg px-3 py-1.5 text-xs font-medium hover:bg-muted transition-colors text-muted-foreground hover:text-foreground flex items-center gap-1.5">
                            <Pencil class="h-3.5 w-3.5" /> Modifier
                        </Link>
                        <button @click="destroy(b.id)" :disabled="deleting === b.id" class="rounded-lg px-3 py-1.5 text-xs font-medium hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600 disabled:opacity-50 flex items-center gap-1.5">
                            <Trash2 class="h-3.5 w-3.5" /> Supprimer
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
