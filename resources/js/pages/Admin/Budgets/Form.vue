<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type Budget, type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, PiggyBank } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    budget?: Budget;
    boutiques: { id: number; code: string; name: string }[];
    categories: { id: number; name: string; full_name: string; parent_id?: number | null }[];
    years: number[];
}>();

const isEdit = computed(() => !!props.budget);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Budgets', href: '/admin/budgets' },
    { title: isEdit.value ? 'Modifier' : 'Nouveau budget', href: '#' },
];

const form = useForm({
    boutique_id: props.budget?.boutique_id ?? '',
    category_id: props.budget?.category_id ?? '',
    year:        props.budget?.year ?? new Date().getFullYear(),
    month:       props.budget?.month ?? '',
    amount:      props.budget?.amount ?? '',
    notes:       props.budget?.notes ?? '',
});

const submit = () => {
    if (isEdit.value) {
        form.put(route('admin.budgets.update', props.budget!.id));
    } else {
        form.post(route('admin.budgets.store'));
    }
};

const monthOptions = [
    { value: '', label: '— Annuel —' },
    { value: 1,  label: 'Janvier' },
    { value: 2,  label: 'Février' },
    { value: 3,  label: 'Mars' },
    { value: 4,  label: 'Avril' },
    { value: 5,  label: 'Mai' },
    { value: 6,  label: 'Juin' },
    { value: 7,  label: 'Juillet' },
    { value: 8,  label: 'Août' },
    { value: 9,  label: 'Septembre' },
    { value: 10, label: 'Octobre' },
    { value: 11, label: 'Novembre' },
    { value: 12, label: 'Décembre' },
];

const groupedCategories = computed(() => {
    const roots = props.categories.filter(c => !c.parent_id);
    const result: { id: number; label: string }[] = [];
    for (const root of roots) {
        result.push({ id: root.id, label: root.name });
        const children = props.categories.filter(c => c.parent_id === root.id);
        for (const child of children) {
            result.push({ id: child.id, label: '  › ' + child.name });
        }
    }
    return result;
});

const scopeLabel = computed(() => {
    const boutique = props.boutiques.find(b => b.id == (form.boutique_id as unknown as number));
    const category = props.categories.find(c => c.id == (form.category_id as unknown as number));
    const parts = [];
    if (boutique) parts.push(boutique.name);
    else parts.push('Toutes boutiques');
    if (category) parts.push(category.name);
    else parts.push('Toutes catégories');
    return parts.join(' · ');
});
</script>

<template>
    <Head :title="isEdit ? 'Modifier le budget' : 'Nouveau budget'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <div class="flex items-center gap-4">
                <Link :href="route('admin.budgets.index')" class="rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <h1 class="text-2xl font-bold text-foreground">
                    {{ isEdit ? 'Modifier le budget' : 'Nouveau budget' }}
                </h1>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-5">
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-5">

                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 mx-auto">
                        <PiggyBank class="h-6 w-6 text-primary" />
                    </div>

                    <!-- Aperçu du périmètre -->
                    <div class="rounded-xl bg-muted/50 px-4 py-3 text-sm text-muted-foreground text-center font-medium">
                        {{ scopeLabel }}
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                        <!-- Boutique -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground" for="boutique_id">Boutique</label>
                            <select id="boutique_id" v-model="form.boutique_id"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors">
                                <option value="">— Toutes les boutiques —</option>
                                <option v-for="b in boutiques" :key="b.id" :value="b.id">{{ b.name }} ({{ b.code }})</option>
                            </select>
                            <p class="text-xs text-muted-foreground">Laisser vide pour un budget global.</p>
                        </div>

                        <!-- Catégorie -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground" for="category_id">Catégorie</label>
                            <select id="category_id" v-model="form.category_id"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors">
                                <option value="">— Toutes les catégories —</option>
                                <option v-for="c in groupedCategories" :key="c.id" :value="c.id">{{ c.label }}</option>
                            </select>
                            <p class="text-xs text-muted-foreground">Laisser vide pour toutes les catégories.</p>
                        </div>

                        <!-- Année -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground" for="year">Année <span class="text-red-500">*</span></label>
                            <select id="year" v-model="form.year"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.year }">
                                <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
                            </select>
                            <p v-if="form.errors.year" class="text-xs text-red-500">{{ form.errors.year }}</p>
                        </div>

                        <!-- Mois -->
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground" for="month">Périodicité</label>
                            <select id="month" v-model="form.month"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors">
                                <option v-for="m in monthOptions" :key="m.value" :value="m.value">{{ m.label }}</option>
                            </select>
                            <p class="text-xs text-muted-foreground">Annuel = toute l'année, ou choisir un mois spécifique.</p>
                        </div>

                        <!-- Montant -->
                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label class="text-sm font-medium text-foreground" for="amount">Montant alloué (FCFA) <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <span class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground text-sm font-medium">FCFA</span>
                                <input id="amount" v-model="form.amount" type="number" min="1" step="1" placeholder="0"
                                    class="h-10 w-full rounded-xl border border-input bg-background pl-16 pr-4 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                    :class="{ 'border-red-400': form.errors.amount }" />
                            </div>
                            <p v-if="form.errors.amount" class="text-xs text-red-500">{{ form.errors.amount }}</p>
                        </div>

                        <!-- Notes -->
                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label class="text-sm font-medium text-foreground" for="notes">Notes internes</label>
                            <textarea id="notes" v-model="form.notes" rows="3"
                                placeholder="Justification du budget, décision de comité…"
                                class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-none" />
                        </div>

                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('admin.budgets.index')" class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors">
                        Annuler
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:opacity-70">
                        {{ isEdit ? 'Enregistrer' : 'Créer le budget' }}
                    </button>
                </div>
            </form>

        </div>
    </AppLayout>
</template>
