<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type Article, type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Package, Save, ShieldCheck } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    article?: Article;
    categories: { id: number; name: string; full_name: string; parent_id?: number | null }[];
    units: string[];
}>();

const isEdit = computed(() => !!props.article);
const pageTitle = computed(() => (isEdit.value ? "Modifier l'article" : 'Nouvel article'));

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Articles', href: '/admin/articles' },
    { title: isEdit.value ? 'Modifier' : 'Nouvel article', href: '#' },
];

const form = useForm({
    category_id: props.article?.category_id ?? '',
    name: props.article?.name ?? '',
    reference: props.article?.reference ?? '',
    description: props.article?.description ?? '',
    unit: props.article?.unit ?? props.units[0] ?? 'piece',
    unit_price: props.article?.unit_price ?? '',
    is_active: props.article?.is_active ?? true,
    nature: props.article?.nature ?? 'achat',
});

const submit = () => {
    if (isEdit.value) {
        form.put(route('admin.articles.update', props.article!.id));
        return;
    }

    form.post(route('admin.articles.store'));
};

const formatPrice = (value?: string | number | null) =>
    value == null || value === ''
        ? 'Non renseigne'
        : new Intl.NumberFormat('fr-FR', {
              style: 'currency',
              currency: 'XOF',
              minimumFractionDigits: 0,
          }).format(Number(value));

const groupedCategories = computed(() => {
    const roots = props.categories.filter((category) => !category.parent_id);
    const result: { id: number; label: string }[] = [];

    for (const root of roots) {
        result.push({ id: root.id, label: root.name });
        const children = props.categories.filter((category) => category.parent_id === root.id);
        for (const child of children) {
            result.push({ id: child.id, label: `  > ${child.name}` });
        }
    }

    return result;
});
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">Administration catalogue</p>
                    <h1 class="mt-1 text-2xl font-bold text-foreground">{{ pageTitle }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Renseignez une fiche article claire, classable et directement exploitable dans les demandes d'achat.
                    </p>
                </div>

                <div
                    class="inline-flex items-center gap-2 self-start rounded-xl border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary"
                >
                    <ShieldCheck class="h-4 w-4" />
                    Administration
                </div>
            </div>

            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="flex items-start gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <Package class="h-8 w-8" />
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Dossier article</p>
                            <h2 class="mt-1 text-xl font-semibold text-foreground">
                                {{ form.name || (isEdit ? 'Article existant' : 'Nouvelle fiche article') }}
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Le nom, la categorie, l'unite et le prix indicatif structurent l'article dans le catalogue.
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-medium"
                                    :class="
                                        form.is_active
                                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ form.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-medium"
                                    :class="
                                        form.nature === 'interne'
                                            ? 'bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300'
                                            : 'bg-sky-50 text-sky-700 dark:bg-sky-950/30 dark:text-sky-300'
                                    "
                                >
                                    {{ form.nature === 'interne' ? 'Dépense interne' : 'Achat / Revente' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 xl:w-[520px]">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Reference</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ form.reference || 'A definir' }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">code interne</p>
                        </div>

                        <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950/30">
                            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">Classification</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ form.category_id ? 'Categorie attribuee' : 'Sans categorie' }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ form.unit || 'Unite a definir' }}</p>
                        </div>

                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Prix indicatif</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ form.unit_price ? formatPrice(form.unit_price) : 'Non renseigne' }}
                            </p>
                            <p class="mt-1 text-xs text-muted-foreground">modifiable ensuite</p>
                        </div>
                    </div>
                </div>
            </section>

            <form class="grid gap-6 xl:grid-cols-[1.12fr_0.88fr]" @submit.prevent="submit">
                <div class="space-y-6">
                    <section class="rounded-2xl border bg-card p-5 shadow-sm">
                        <div>
                            <h2 class="text-base font-semibold text-foreground">Informations generales</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Les champs principaux definissent la lisibilite de l'article pour les utilisateurs.
                            </p>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-foreground" for="name"
                                    >Nom <span class="text-red-500">*</span></label
                                >
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Ex : Ordinateur portable Dell"
                                    class="h-11 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    :class="{ 'border-red-400 focus:border-red-400 focus:ring-red-200': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground" for="reference">Reference</label>
                                <input
                                    id="reference"
                                    v-model="form.reference"
                                    type="text"
                                    placeholder="Ex : INF-001"
                                    class="h-11 w-full rounded-xl border border-input bg-background px-4 font-mono text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    :class="{ 'border-red-400 focus:border-red-400 focus:ring-red-200': form.errors.reference }"
                                />
                                <p v-if="form.errors.reference" class="mt-1 text-xs text-red-500">{{ form.errors.reference }}</p>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground" for="category_id">Categorie</label>
                                <select
                                    id="category_id"
                                    v-model="form.category_id"
                                    class="h-11 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                >
                                    <option value="">- Sans categorie -</option>
                                    <option v-for="category in groupedCategories" :key="category.id" :value="category.id">
                                        {{ category.label }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground" for="unit"
                                    >Unite <span class="text-red-500">*</span></label
                                >
                                <select
                                    id="unit"
                                    v-model="form.unit"
                                    class="h-11 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                >
                                    <option v-for="unit in units" :key="unit" :value="unit">{{ unit }}</option>
                                </select>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground" for="unit_price"
                                    >Prix unitaire de reference (FCFA)</label
                                >
                                <div class="relative">
                                    <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground">FCFA</span>
                                    <input
                                        id="unit_price"
                                        v-model="form.unit_price"
                                        type="number"
                                        min="0"
                                        step="1"
                                        placeholder="0"
                                        class="h-11 w-full rounded-xl border border-input bg-background pl-16 pr-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">Valeur indicative, modifiable ensuite sur les lignes de commande.</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1.5 block text-sm font-medium text-foreground" for="description">Description</label>
                                <textarea
                                    id="description"
                                    v-model="form.description"
                                    rows="4"
                                    placeholder="Caracteristiques, specifications techniques, remarques utiles..."
                                    class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                            </div>

                            <div class="sm:col-span-2">
                                <div class="rounded-2xl border bg-muted/20 p-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div>
                                            <p class="text-sm font-semibold text-foreground">
                                                {{ form.is_active ? 'Article actif' : 'Article inactif' }}
                                            </p>
                                            <p class="mt-1 text-sm text-muted-foreground">
                                                {{
                                                    form.is_active
                                                        ? "L'article peut etre selectionne dans les nouvelles demandes."
                                                        : "L'article reste archive mais n'apparait plus dans les parcours courants."
                                                }}
                                            </p>
                                        </div>

                                        <button
                                            type="button"
                                            class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors"
                                            :class="form.is_active ? 'bg-primary' : 'bg-muted-foreground/30'"
                                            @click="form.is_active = !form.is_active"
                                        >
                                            <span
                                                class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"
                                                :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                                            />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-2 block text-sm font-medium text-foreground">
                                    Nature <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <button
                                        type="button"
                                        @click="form.nature = 'achat'"
                                        class="flex flex-col items-start gap-1 rounded-2xl border-2 p-4 text-left transition-colors"
                                        :class="
                                            form.nature === 'achat'
                                                ? 'border-sky-500 bg-sky-50 dark:bg-sky-950/30'
                                                : 'border-border bg-background hover:bg-muted/30'
                                        "
                                    >
                                        <span
                                            class="text-sm font-semibold"
                                            :class="form.nature === 'achat' ? 'text-sky-700 dark:text-sky-300' : 'text-foreground'"
                                        >
                                            Achat / Revente
                                        </span>
                                        <span class="text-xs text-muted-foreground">Article destiné à être revendu ou livré</span>
                                    </button>

                                    <button
                                        type="button"
                                        @click="form.nature = 'interne'"
                                        class="flex flex-col items-start gap-1 rounded-2xl border-2 p-4 text-left transition-colors"
                                        :class="
                                            form.nature === 'interne'
                                                ? 'border-amber-500 bg-amber-50 dark:bg-amber-950/30'
                                                : 'border-border bg-background hover:bg-muted/30'
                                        "
                                    >
                                        <span
                                            class="text-sm font-semibold"
                                            :class="form.nature === 'interne' ? 'text-amber-700 dark:text-amber-300' : 'text-foreground'"
                                        >
                                            Dépense interne
                                        </span>
                                        <span class="text-xs text-muted-foreground">Consommé en interne (papier, fournitures…)</span>
                                    </button>
                                </div>
                                <p v-if="form.errors.nature" class="mt-1 text-xs text-red-500">{{ form.errors.nature }}</p>
                            </div>

                            <div class="flex flex-wrap justify-end gap-2 sm:col-span-2">
                                <Link
                                    :href="route('admin.articles.index')"
                                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                                >
                                    <ArrowLeft class="h-4 w-4" />
                                    Annuler
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-70"
                                >
                                    <Save class="h-4 w-4" />
                                    {{ isEdit ? 'Enregistrer' : "Creer l'article" }}
                                </button>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section class="rounded-2xl border bg-card p-5 shadow-sm">
                        <h2 class="text-base font-semibold text-foreground">Resume</h2>
                        <div class="mt-4 grid gap-3">
                            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                                <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Nom</p>
                                <p class="mt-2 text-sm font-semibold text-foreground">{{ form.name || 'A definir' }}</p>
                            </div>

                            <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950/30">
                                <p class="text-xs font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">Classement</p>
                                <p class="mt-2 text-sm font-semibold text-foreground">
                                    {{ form.category_id ? 'Categorie attribuee' : 'Sans categorie' }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ form.unit || 'Unite a definir' }}</p>
                            </div>

                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Prix indicatif</p>
                                <p class="mt-2 text-sm font-semibold text-foreground">{{ formatPrice(form.unit_price) }}</p>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border bg-card p-5 shadow-sm">
                        <h2 class="text-base font-semibold text-foreground">Conseils</h2>
                        <div class="mt-4 grid gap-3">
                            <div class="rounded-2xl border bg-muted/20 p-4">
                                <p class="text-sm font-semibold text-foreground">Gardez une reference stable</p>
                                <p class="mt-1 text-sm text-muted-foreground">Elle facilite la recherche, les exports et le suivi du catalogue.</p>
                            </div>

                            <div class="rounded-2xl border bg-muted/20 p-4">
                                <p class="text-sm font-semibold text-foreground">Renseignez le prix seulement comme base indicative</p>
                                <p class="mt-1 text-sm text-muted-foreground">Le montant reel peut rester ajuste ensuite dans chaque commande.</p>
                            </div>
                        </div>
                    </section>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
