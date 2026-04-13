<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Fournisseur } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle2, Mail, MapPin, Phone, Save, ShieldAlert, ShieldCheck, Truck } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ fournisseur?: Fournisseur }>();

const isEdit = computed(() => !!props.fournisseur);
const pageTitle = computed(() => (isEdit.value ? 'Modifier le fournisseur' : 'Nouveau fournisseur'));
const pageDescription = computed(() =>
    isEdit.value
        ? "Mettez a jour les informations de contact, l'etat d'homologation et la disponibilite du partenaire."
        : "Creez un nouveau partenaire d'achat avec une fiche propre, exploitable et coherente avec le referentiel.",
);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Fournisseurs', href: '/admin/fournisseurs' },
    { title: isEdit.value ? 'Modifier' : 'Nouveau fournisseur', href: '#' },
];

const form = useForm({
    name: props.fournisseur?.name ?? '',
    code: props.fournisseur?.code ?? '',
    email: props.fournisseur?.email ?? '',
    phone: props.fournisseur?.phone ?? '',
    address: props.fournisseur?.address ?? '',
    city: props.fournisseur?.city ?? '',
    is_active: props.fournisseur?.is_active ?? true,
    is_approved: props.fournisseur?.is_approved ?? false,
});

const submit = () => {
    if (isEdit.value) {
        form.put(route('admin.fournisseurs.update', props.fournisseur!.id));
        return;
    }

    form.post(route('admin.fournisseurs.store'));
};
</script>

<template>
    <Head :title="pageTitle" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-muted-foreground">Administration fournisseurs</p>
                    <h1 class="mt-1 text-2xl font-bold text-foreground">{{ pageTitle }}</h1>
                    <p class="mt-1 text-sm text-muted-foreground">{{ pageDescription }}</p>
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
                        <div
                            class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl"
                            :class="
                                form.is_approved
                                    ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-300'
                                    : 'bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-300'
                            "
                        >
                            <Truck class="h-8 w-8" />
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Dossier fournisseur</p>
                            <h2 class="mt-1 text-xl font-semibold text-foreground">
                                {{ form.name || (isEdit ? 'Fournisseur existant' : 'Nouvelle fiche fournisseur') }}
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Le code, les coordonnees et les statuts definissent la disponibilite du fournisseur dans les processus d'achat.
                            </p>

                            <div class="mt-4 flex flex-wrap gap-2">
                                <span
                                    class="rounded-full px-3 py-1 text-xs font-medium"
                                    :class="
                                        form.is_active
                                            ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300'
                                            : 'bg-muted text-muted-foreground'
                                    "
                                >
                                    {{ form.is_active ? 'Actif' : 'Inactif' }}
                                </span>

                                <span
                                    v-if="form.is_approved"
                                    class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300"
                                >
                                    <ShieldCheck class="h-3.5 w-3.5" />
                                    Homologue
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300"
                                >
                                    <ShieldAlert class="h-3.5 w-3.5" />
                                    Non homologue
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 xl:w-[520px]">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">Code</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ form.code || 'A definir' }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">identifiant interne</p>
                        </div>

                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Homologation</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ form.is_approved ? 'Validee' : 'En attente' }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">impacte les alertes sur les commandes</p>
                        </div>

                        <div class="rounded-2xl border border-violet-100 bg-violet-50 p-4 dark:border-violet-900 dark:bg-violet-950/30">
                            <p class="text-xs font-semibold uppercase tracking-wide text-violet-700 dark:text-violet-300">Disponibilite</p>
                            <p class="mt-3 text-sm font-semibold text-foreground">{{ form.is_active ? 'Utilisable' : 'Masquee' }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">visible pour les nouveaux achats</p>
                        </div>
                    </div>
                </div>
            </section>

            <form class="grid gap-6 xl:grid-cols-[1.12fr_0.88fr]" @submit.prevent="submit">
                <div class="space-y-6">
                    <section class="rounded-2xl border bg-card p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-primary/10 p-2 text-primary">
                                <Truck class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-foreground">Informations generales</h2>
                                <p class="text-sm text-muted-foreground">
                                    Renseignez l'identite principale du fournisseur et ses coordonnees de contact.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="name" class="mb-1.5 block text-sm font-medium text-foreground">
                                    Nom <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="name"
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Ex : Bureau Senegal SA"
                                    class="h-11 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    :class="{ 'border-red-400 focus:border-red-400 focus:ring-red-200': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                            </div>

                            <div>
                                <label for="code" class="mb-1.5 block text-sm font-medium text-foreground">
                                    Code <span class="text-red-500">*</span>
                                </label>
                                <input
                                    id="code"
                                    v-model="form.code"
                                    type="text"
                                    placeholder="Ex : BSN-001"
                                    class="h-11 w-full rounded-xl border border-input bg-background px-4 font-mono text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    :class="{ 'border-red-400 focus:border-red-400 focus:ring-red-200': form.errors.code }"
                                />
                                <p v-if="form.errors.code" class="mt-1 text-xs text-red-500">{{ form.errors.code }}</p>
                            </div>

                            <div>
                                <label for="city" class="mb-1.5 block text-sm font-medium text-foreground">Ville</label>
                                <div class="relative">
                                    <MapPin class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <input
                                        id="city"
                                        v-model="form.city"
                                        type="text"
                                        placeholder="Ex : Dakar"
                                        class="h-11 w-full rounded-xl border border-input bg-background pl-10 pr-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                                <p v-if="form.errors.city" class="mt-1 text-xs text-red-500">{{ form.errors.city }}</p>
                            </div>

                            <div>
                                <label for="email" class="mb-1.5 block text-sm font-medium text-foreground">Email</label>
                                <div class="relative">
                                    <Mail class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <input
                                        id="email"
                                        v-model="form.email"
                                        type="email"
                                        placeholder="contact@fournisseur.com"
                                        class="h-11 w-full rounded-xl border border-input bg-background pl-10 pr-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                                <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                            </div>

                            <div>
                                <label for="phone" class="mb-1.5 block text-sm font-medium text-foreground">Telephone</label>
                                <div class="relative">
                                    <Phone class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                    <input
                                        id="phone"
                                        v-model="form.phone"
                                        type="text"
                                        placeholder="+221 77 000 00 00"
                                        class="h-11 w-full rounded-xl border border-input bg-background pl-10 pr-4 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                    />
                                </div>
                                <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
                            </div>

                            <div class="sm:col-span-2">
                                <label for="address" class="mb-1.5 block text-sm font-medium text-foreground">Adresse</label>
                                <textarea
                                    id="address"
                                    v-model="form.address"
                                    rows="4"
                                    placeholder="Rue, quartier, points de repere..."
                                    class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/20"
                                />
                                <p v-if="form.errors.address" class="mt-1 text-xs text-red-500">{{ form.errors.address }}</p>
                            </div>

                            <div class="flex flex-wrap justify-end gap-2 sm:col-span-2">
                                <Link
                                    :href="route('admin.fournisseurs.index')"
                                    class="inline-flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                                >
                                    <ArrowLeft class="h-4 w-4" />
                                    Annuler
                                </Link>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:cursor-not-allowed disabled:opacity-70"
                                >
                                    <Save class="h-4 w-4" />
                                    {{ isEdit ? 'Enregistrer' : 'Creer le fournisseur' }}
                                </button>
                            </div>
                        </div>
                    </section>

                    <section class="rounded-2xl border bg-card p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="rounded-xl bg-violet-50 p-2 text-violet-600 dark:bg-violet-950/30 dark:text-violet-300">
                                <CheckCircle2 class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-foreground">Verification rapide</h2>
                                <p class="text-sm text-muted-foreground">
                                    Avant validation, assurez-vous que la fiche est suffisamment exploitable pour les achats.
                                </p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border bg-muted/20 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Identite</p>
                                <p class="mt-2 text-sm font-semibold text-foreground">{{ form.name && form.code ? 'Complete' : 'A completer' }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">nom et code obligatoires</p>
                            </div>

                            <div class="rounded-2xl border bg-muted/20 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Contact</p>
                                <p class="mt-2 text-sm font-semibold text-foreground">{{ form.email || form.phone ? 'Disponible' : 'Manquant' }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">email ou telephone recommande</p>
                            </div>

                            <div class="rounded-2xl border bg-muted/20 p-4">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Localisation</p>
                                <p class="mt-2 text-sm font-semibold text-foreground">
                                    {{ form.city || form.address ? 'Renseignee' : 'Optionnelle' }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">utile pour les livraisons</p>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section class="rounded-2xl border bg-card p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded-xl p-2"
                                :class="
                                    form.is_approved
                                        ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-300'
                                        : 'bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-300'
                                "
                            >
                                <ShieldCheck v-if="form.is_approved" class="h-5 w-5" />
                                <ShieldAlert v-else class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-foreground">Statuts</h2>
                                <p class="text-sm text-muted-foreground">Ces options pilotent la disponibilite du fournisseur dans l'application.</p>
                            </div>
                        </div>

                        <div class="mt-5 grid gap-4">
                            <div
                                class="rounded-2xl border p-4"
                                :class="
                                    form.is_approved
                                        ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-900 dark:bg-emerald-950/30'
                                        : 'border-amber-200 bg-amber-50 dark:border-amber-900 dark:bg-amber-950/30'
                                "
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p
                                            class="text-sm font-semibold"
                                            :class="
                                                form.is_approved ? 'text-emerald-800 dark:text-emerald-200' : 'text-amber-800 dark:text-amber-200'
                                            "
                                        >
                                            {{ form.is_approved ? 'Homologation validee' : 'Homologation en attente' }}
                                        </p>
                                        <p
                                            class="mt-1 text-sm"
                                            :class="
                                                form.is_approved ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'
                                            "
                                        >
                                            {{
                                                form.is_approved
                                                    ? "Ce fournisseur pourra etre utilise sans message d'alerte supplementaire."
                                                    : "Les utilisateurs verront une alerte lorsqu'ils le choisiront sur une commande."
                                            }}
                                        </p>
                                    </div>

                                    <button
                                        type="button"
                                        class="relative inline-flex h-7 w-12 shrink-0 items-center rounded-full transition-colors"
                                        :class="form.is_approved ? 'bg-emerald-500' : 'bg-amber-400'"
                                        @click="form.is_approved = !form.is_approved"
                                    >
                                        <span
                                            class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition-transform"
                                            :class="form.is_approved ? 'translate-x-6' : 'translate-x-1'"
                                        />
                                    </button>
                                </div>
                            </div>

                            <div class="rounded-2xl border bg-muted/20 p-4">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-sm font-semibold text-foreground">
                                            {{ form.is_active ? 'Fournisseur actif' : 'Fournisseur inactif' }}
                                        </p>
                                        <p class="mt-1 text-sm text-muted-foreground">
                                            {{
                                                form.is_active
                                                    ? "Le fournisseur est propose dans les nouveaux parcours d'achat."
                                                    : 'La fiche reste conservee mais ne doit plus etre sollicitee en operation courante.'
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
                    </section>

                    <section class="rounded-2xl border bg-card p-5 shadow-sm">
                        <h2 class="text-base font-semibold text-foreground">Conseils</h2>
                        <div class="mt-4 grid gap-3">
                            <div class="rounded-2xl border bg-muted/20 p-4">
                                <p class="text-sm font-semibold text-foreground">Toujours renseigner un code stable</p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Il facilite le rapprochement avec les commandes, exports et controles comptables.
                                </p>
                            </div>

                            <div class="rounded-2xl border bg-muted/20 p-4">
                                <p class="text-sm font-semibold text-foreground">Valider l'homologation seulement si la verification est faite</p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Le statut influence directement les alertes visibles par les demandeurs pendant leurs saisies.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
