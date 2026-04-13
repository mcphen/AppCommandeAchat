<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Budget } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, Building2, FolderTree, Pencil, PiggyBank, Plus, ShieldCheck, Trash2, TrendingUp } from 'lucide-vue-next';
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

const formatAmount = (v: number) => new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', minimumFractionDigits: 0 }).format(v);

const formatK = (v: number) => {
    if (v >= 1_000_000_000) return (v / 1_000_000_000).toFixed(1) + ' Md';
    if (v >= 1_000_000) return (v / 1_000_000).toFixed(1) + ' M';
    if (v >= 1_000) return (v / 1_000).toFixed(0) + ' K';
    return v.toFixed(0);
};

const summary = computed(() => {
    const total = props.budgets.reduce((s, b) => s + (b.amount ?? 0), 0);
    const engaged = props.budgets.reduce((s, b) => s + (b.consumption?.engaged ?? 0), 0);
    const exceeded = props.budgets.filter((b) => b.consumption?.is_exceeded).length;
    const warning = props.budgets.filter((b) => b.consumption?.is_warning).length;
    return { total, engaged, exceeded, warning };
});

const changeYear = (year: number) => {
    router.get(route('admin.budgets.index'), { year }, { preserveState: true });
};

const destroy = (id: number) => {
    if (!confirm('Supprimer ce budget ?')) return;
    deleting.value = id;
    router.delete(route('admin.budgets.destroy', id), {
        onFinish: () => (deleting.value = null),
    });
};

const barColor = (b: Budget) => {
    if (b.consumption?.is_exceeded) return 'bg-red-500';
    if (b.consumption?.is_warning) return 'bg-amber-400';
    return 'bg-emerald-500';
};

const statusBadge = (b: Budget) => {
    if (b.consumption?.is_exceeded) {
        return {
            cls: 'border-red-200 bg-red-50 text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300',
            label: 'Depasse',
        };
    }
    if (b.consumption?.is_warning) {
        return {
            cls: 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300',
            label: 'Alerte',
        };
    }
    return {
        cls: 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300',
        label: 'OK',
    };
};
</script>

<template>
    <Head title="Budgets" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Gestion budgetaire</h1>
                    <p class="text-sm text-muted-foreground">Pilotez les enveloppes par boutique, categorie et periode.</p>
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
                            <PiggyBank class="h-8 w-8" />
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Controle budgetaire</p>
                            <h2 class="mt-1 text-xl font-semibold text-foreground">Suivi des enveloppes {{ selectedYear }}</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Creez, surveillez et ajustez les budgets pour garder une vision claire des engagements en cours.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:w-[560px]">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">
                                <PiggyBank class="h-4 w-4" />
                                Budgets
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ budgets.length }} budget{{ budgets.length > 1 ? 's' : '' }} pour {{ selectedYear }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                <TrendingUp class="h-4 w-4" />
                                Engagement
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">Suivi du consomme, du disponible et des alertes</p>
                        </div>

                        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                                <Building2 class="h-4 w-4" />
                                Perimetre
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">Boutiques globales ou cibles avec categories dediees</p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-2">
                <select
                    :value="selectedYear"
                    @change="changeYear(+($event.target as HTMLSelectElement).value)"
                    class="h-10 rounded-xl border border-input bg-background px-3 text-sm text-foreground focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                >
                    <option v-for="y in availableYears" :key="y" :value="y">{{ y }}</option>
                </select>

                <Link
                    :href="route('admin.budgets.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Nouveau budget
                </Link>
            </div>

            <section v-if="budgets.length > 0" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total alloue</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ formatK(summary.total) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">FCFA</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total engage</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ formatK(summary.engaged) }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">FCFA</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">En alerte</p>
                    <p class="mt-2 text-2xl font-bold" :class="summary.warning > 0 ? 'text-amber-600 dark:text-amber-300' : 'text-foreground'">
                        {{ summary.warning }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">budget{{ summary.warning > 1 ? 's' : '' }}</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Depasses</p>
                    <p class="mt-2 text-2xl font-bold" :class="summary.exceeded > 0 ? 'text-red-600 dark:text-red-300' : 'text-foreground'">
                        {{ summary.exceeded }}
                    </p>
                    <p class="mt-1 text-xs text-muted-foreground">budget{{ summary.exceeded > 1 ? 's' : '' }}</p>
                </div>
            </section>

            <EmptyState
                v-if="budgets.length === 0"
                :icon="PiggyBank"
                icon-bg="bg-emerald-50 dark:bg-emerald-950/30"
                icon-color="text-emerald-500 dark:text-emerald-300"
                :title="`Aucun budget pour ${selectedYear}`"
                description="Definissez des enveloppes budgetaires par boutique et/ou categorie pour controler les depenses en temps reel."
                :action-href="route('admin.budgets.create')"
                action-label="Creer un budget"
            />

            <section v-else class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="b in budgets"
                    :key="b.id"
                    class="flex flex-col gap-4 rounded-2xl border bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
                    :class="{
                        'border-red-200 bg-red-50/30 dark:border-red-900 dark:bg-red-950/10': b.consumption?.is_exceeded,
                        'border-amber-200 bg-amber-50/30 dark:border-amber-900 dark:bg-amber-950/10':
                            b.consumption?.is_warning && !b.consumption?.is_exceeded,
                    }"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 space-y-2">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span
                                    v-if="b.boutique"
                                    class="inline-flex items-center gap-1 rounded-lg border border-violet-100 bg-violet-50 px-2 py-0.5 text-xs font-medium text-violet-700 dark:border-violet-900 dark:bg-violet-950/30 dark:text-violet-300"
                                >
                                    <Building2 class="h-3 w-3" />
                                    {{ b.boutique.name }}
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-lg border border-border bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                                >
                                    <Building2 class="h-3 w-3" />
                                    Toutes boutiques
                                </span>

                                <span
                                    v-if="b.category"
                                    class="inline-flex items-center gap-1 rounded-lg border border-blue-100 bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300"
                                >
                                    <FolderTree class="h-3 w-3" />
                                    {{ b.category.name }}
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-lg border border-border bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground"
                                >
                                    <FolderTree class="h-3 w-3" />
                                    Toutes categories
                                </span>
                            </div>

                            <p class="text-xs font-medium text-muted-foreground">{{ b.period }}</p>
                        </div>

                        <span :class="statusBadge(b).cls" class="rounded-full border px-2.5 py-1 text-xs font-semibold">
                            {{ statusBadge(b).label }}
                        </span>
                    </div>

                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div class="rounded-xl bg-muted/40 px-2 py-2.5">
                            <p class="text-[11px] text-muted-foreground">Alloue</p>
                            <p class="mt-1 text-sm font-bold text-foreground">{{ formatK(b.amount) }}</p>
                        </div>
                        <div class="rounded-xl bg-muted/40 px-2 py-2.5">
                            <p class="text-[11px] text-muted-foreground">Consomme</p>
                            <p class="mt-1 text-sm font-bold text-emerald-700 dark:text-emerald-300">{{ formatK(b.consumption?.consumed ?? 0) }}</p>
                        </div>
                        <div class="rounded-xl bg-muted/40 px-2 py-2.5">
                            <p class="text-[11px] text-muted-foreground">En cours</p>
                            <p class="mt-1 text-sm font-bold text-amber-600 dark:text-amber-300">{{ formatK(b.consumption?.in_validation ?? 0) }}</p>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">Engage</span>
                            <span
                                class="text-xs font-semibold"
                                :class="
                                    b.consumption?.is_exceeded
                                        ? 'text-red-600 dark:text-red-300'
                                        : b.consumption?.is_warning
                                          ? 'text-amber-600 dark:text-amber-300'
                                          : 'text-foreground'
                                "
                            >
                                {{ b.consumption?.percent_engaged ?? 0 }}%
                            </span>
                        </div>

                        <div class="h-2 w-full overflow-hidden rounded-full bg-muted">
                            <div
                                class="relative h-full overflow-hidden rounded-full"
                                :style="{ width: Math.min(100, b.consumption?.percent_engaged ?? 0) + '%' }"
                            >
                                <div class="absolute inset-0" :class="barColor(b)" />
                                <div
                                    v-if="b.consumption && b.consumption.in_validation > 0"
                                    class="absolute inset-y-0 right-0 bg-amber-300/70"
                                    :style="{
                                        width:
                                            b.consumption.amount > 0
                                                ? Math.min(100, (b.consumption.in_validation / b.consumption.amount) * 100) + '%'
                                                : '0%',
                                    }"
                                />
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="text-xs text-muted-foreground">Disponible</span>
                            <span class="text-xs font-medium text-foreground">{{ formatAmount(b.consumption?.available ?? b.amount) }}</span>
                        </div>
                    </div>

                    <div
                        v-if="b.consumption?.is_exceeded"
                        class="flex items-center gap-2 rounded-xl border border-red-200 bg-red-100 px-3 py-2 text-xs font-medium text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300"
                    >
                        <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                        Budget depasse de {{ formatAmount(Math.abs(b.consumption.available)) }}
                    </div>
                    <div
                        v-else-if="b.consumption?.is_warning"
                        class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-100 px-3 py-2 text-xs font-medium text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300"
                    >
                        <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                        Plus de 80% du budget engage
                    </div>

                    <div class="flex items-center justify-end gap-1 border-t border-border/50 pt-2">
                        <Link
                            :href="route('admin.budgets.edit', b.id)"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        >
                            <Pencil class="h-3.5 w-3.5" />
                            Modifier
                        </Link>
                        <button
                            @click="destroy(b.id)"
                            :disabled="deleting === b.id"
                            class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-600 disabled:opacity-50 dark:hover:bg-red-950/30"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            Supprimer
                        </button>
                    </div>
                </article>
            </section>
        </div>
    </AppLayout>
</template>
