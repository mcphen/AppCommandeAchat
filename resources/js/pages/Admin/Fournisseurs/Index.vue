<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Fournisseur } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, Eye, Mail, MapPin, Pencil, Phone, Plus, ShieldAlert, ShieldCheck, Trash2, Truck } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ fournisseurs: Fournisseur[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Fournisseurs', href: '/admin/fournisseurs' },
];

const deleting = ref<number | null>(null);

const approvedCount = computed(() => props.fournisseurs.filter((f) => f.is_approved).length);
const pendingApprovalCount = computed(() => props.fournisseurs.filter((f) => !f.is_approved && f.is_active).length);
const inactiveCount = computed(() => props.fournisseurs.filter((f) => !f.is_active).length);

const destroy = (id: number) => {
    if (!confirm('Supprimer ce fournisseur ?')) return;
    deleting.value = id;
    router.delete(route('admin.fournisseurs.destroy', id), {
        onFinish: () => (deleting.value = null),
    });
};
</script>

<template>
    <Head title="Fournisseurs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Fournisseurs</h1>
                    <p class="text-sm text-muted-foreground">Gerez l'homologation, les contacts et l'historique d'achat de vos partenaires.</p>
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
                            <Truck class="h-8 w-8" />
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Referentiel fournisseurs</p>
                            <h2 class="mt-1 text-xl font-semibold text-foreground">Suivi des partenaires d'achat</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Validez les fournisseurs, suivez leur statut et centralisez les informations utiles pour les commandes.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:w-[560px]">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">
                                <Truck class="h-4 w-4" />
                                Fournisseurs
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ fournisseurs.length }} fournisseur{{ fournisseurs.length > 1 ? 's' : '' }} au total
                            </p>
                        </div>

                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                <ShieldCheck class="h-4 w-4" />
                                Homologues
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ approvedCount }} partenaire{{ approvedCount > 1 ? 's' : '' }} valide{{ approvedCount > 1 ? 's' : '' }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                                <ShieldAlert class="h-4 w-4" />
                                A surveiller
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ pendingApprovalCount }} non homologue{{ pendingApprovalCount > 1 ? 's' : '' }} actif{{
                                    pendingApprovalCount > 1 ? 's' : ''
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="route('admin.fournisseurs.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Nouveau fournisseur
                </Link>
            </div>

            <section v-if="fournisseurs.length > 0" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ fournisseurs.length }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">fournisseur{{ fournisseurs.length > 1 ? 's' : '' }}</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Homologues</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-300">{{ approvedCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">prets pour les commandes</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Non homologues</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-300">{{ pendingApprovalCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">fournisseurs actifs a valider</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Inactifs</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ inactiveCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">hors utilisation courante</p>
                </div>
            </section>

            <EmptyState
                v-if="fournisseurs.length === 0"
                :icon="Truck"
                icon-bg="bg-violet-50 dark:bg-violet-950/30"
                icon-color="text-violet-500 dark:text-violet-300"
                title="Aucun fournisseur"
                description="Ajoutez et approuvez vos fournisseurs pour les associer aux bons de commande et generer les ecritures comptables."
                :action-href="route('admin.fournisseurs.create')"
                action-label="Ajouter un fournisseur"
            />

            <section v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="f in fournisseurs"
                    :key="f.id"
                    class="flex flex-col gap-4 rounded-2xl border bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
                    :class="{ 'border-amber-200 dark:border-amber-900': !f.is_approved && f.is_active }"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                :class="
                                    f.is_approved
                                        ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-300'
                                        : 'bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-300'
                                "
                            >
                                <Truck class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-foreground">{{ f.name }}</p>
                                <p class="font-mono text-xs text-muted-foreground">{{ f.code }}</p>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <span
                                v-if="f.is_approved"
                                class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300"
                            >
                                <ShieldCheck class="h-3 w-3" />
                                Homologue
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300"
                            >
                                <ShieldAlert class="h-3 w-3" />
                                Non homologue
                            </span>

                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="
                                    f.is_active ? 'bg-blue-50 text-blue-600 dark:bg-blue-950/30 dark:text-blue-300' : 'bg-muted text-muted-foreground'
                                "
                            >
                                {{ f.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="!f.is_approved && f.is_active"
                        class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300"
                    >
                        <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                        Ce fournisseur n'est pas encore homologue.
                    </div>

                    <div class="grid gap-2 rounded-2xl border bg-muted/20 p-4">
                        <div v-if="f.email" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Mail class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ f.email }}</span>
                        </div>
                        <div v-if="f.phone" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Phone class="h-4 w-4 shrink-0" />
                            <span>{{ f.phone }}</span>
                        </div>
                        <div v-if="f.city" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <MapPin class="h-4 w-4 shrink-0" />
                            <span>{{ f.city }}</span>
                        </div>
                    </div>

                    <div class="rounded-2xl border bg-muted/20 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Activite</p>
                        <p class="mt-2 text-sm font-semibold text-foreground">
                            {{ f.order_lines_count }} ligne{{ (f.order_lines_count ?? 0) > 1 ? 's' : '' }} de commande
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-1 border-t border-border/50 pt-2">
                        <Link
                            :href="route('admin.fournisseurs.show', f.id)"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-100 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300 dark:hover:bg-blue-950/50"
                            title="Historique des achats"
                        >
                            <Eye class="h-3.5 w-3.5" />
                            Voir
                        </Link>
                        <Link
                            :href="route('admin.fournisseurs.edit', f.id)"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300 dark:hover:bg-amber-950/50"
                        >
                            <Pencil class="h-3.5 w-3.5" />
                            Modifier
                        </Link>
                        <button
                            @click="destroy(f.id)"
                            :disabled="deleting === f.id"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 transition-colors hover:bg-red-100 disabled:opacity-50 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300 dark:hover:bg-red-950/50"
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
