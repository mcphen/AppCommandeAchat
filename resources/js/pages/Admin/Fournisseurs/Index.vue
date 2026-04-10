<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Fournisseur } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Truck, Plus, Pencil, Trash2, Mail, Phone, MapPin, ShieldCheck, ShieldAlert, Eye } from 'lucide-vue-next';
import { ref } from 'vue';

defineProps<{ fournisseurs: Fournisseur[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Fournisseurs', href: '/admin/fournisseurs' },
];

const deleting = ref<number | null>(null);

const destroy = (id: number) => {
    if (!confirm('Supprimer ce fournisseur ?')) return;
    deleting.value = id;
    router.delete(route('admin.fournisseurs.destroy', id), {
        onFinish: () => deleting.value = null,
    });
};
</script>

<template>
    <Head title="Fournisseurs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Fournisseurs</h1>
                    <p class="text-sm text-muted-foreground mt-1">
                        {{ fournisseurs.length }} fournisseur{{ fournisseurs.length > 1 ? 's' : '' }} ·
                        <span class="text-emerald-600 font-medium">{{ fournisseurs.filter(f => f.is_approved).length }} homologué{{ fournisseurs.filter(f => f.is_approved).length > 1 ? 's' : '' }}</span>
                        <template v-if="fournisseurs.filter(f => !f.is_approved && f.is_active).length > 0">
                            · <span class="text-amber-600 font-medium">{{ fournisseurs.filter(f => !f.is_approved && f.is_active).length }} non homologué{{ fournisseurs.filter(f => !f.is_approved && f.is_active).length > 1 ? 's' : '' }}</span>
                        </template>
                    </p>
                </div>
                <Link :href="route('admin.fournisseurs.create')" class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                    <Plus class="h-4 w-4" /> Nouveau fournisseur
                </Link>
            </div>

            <!-- Vide -->
            <EmptyState
                v-if="fournisseurs.length === 0"
                :icon="Truck"
                icon-bg="bg-violet-50"
                icon-color="text-violet-500"
                title="Aucun fournisseur"
                description="Ajoutez et approuvez vos fournisseurs pour les associer aux bons de commande et générer les écritures comptables."
                :action-href="route('admin.fournisseurs.create')"
                action-label="Ajouter un fournisseur"
            />

            <!-- Grille -->
            <div v-else class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 sm:gap-4">
                <div
                    v-for="f in fournisseurs" :key="f.id"
                    class="rounded-2xl border bg-card p-5 shadow-sm flex flex-col gap-4 hover:shadow-md transition-shadow"
                    :class="{ 'border-amber-200': !f.is_approved && f.is_active }"
                >
                    <!-- Top -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                :class="f.is_approved ? 'bg-emerald-50' : 'bg-amber-50'">
                                <Truck class="h-5 w-5" :class="f.is_approved ? 'text-emerald-600' : 'text-amber-600'" />
                            </div>
                            <div class="min-w-0">
                                <p class="font-semibold text-foreground text-sm truncate">{{ f.name }}</p>
                                <p class="text-xs text-muted-foreground font-mono">{{ f.code }}</p>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <!-- Homologation -->
                            <span v-if="f.is_approved"
                                class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">
                                <ShieldCheck class="h-3 w-3" /> Homologué
                            </span>
                            <span v-else
                                class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700">
                                <ShieldAlert class="h-3 w-3" /> Non homologué
                            </span>
                            <!-- Actif -->
                            <span :class="f.is_active ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-500'"
                                class="rounded-full px-2 py-0.5 text-xs font-medium">
                                {{ f.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>

                    <!-- Alerte non homologué -->
                    <div v-if="!f.is_approved && f.is_active"
                        class="flex items-center gap-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-700">
                        <ShieldAlert class="h-3.5 w-3.5 shrink-0" />
                        Ce fournisseur n'est pas encore homologué.
                    </div>

                    <!-- Infos contact -->
                    <div class="flex flex-col gap-1.5 text-xs text-muted-foreground">
                        <div v-if="f.email" class="flex items-center gap-1.5">
                            <Mail class="h-3.5 w-3.5 shrink-0" /><span class="truncate">{{ f.email }}</span>
                        </div>
                        <div v-if="f.phone" class="flex items-center gap-1.5">
                            <Phone class="h-3.5 w-3.5 shrink-0" /><span>{{ f.phone }}</span>
                        </div>
                        <div v-if="f.city" class="flex items-center gap-1.5">
                            <MapPin class="h-3.5 w-3.5 shrink-0" /><span>{{ f.city }}</span>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between pt-2 border-t">
                        <span class="text-xs text-muted-foreground">{{ f.order_lines_count }} ligne{{ (f.order_lines_count ?? 0) > 1 ? 's' : '' }} de commande</span>
                        <div class="flex items-center gap-1">
                            <Link :href="route('admin.fournisseurs.show', f.id)"
                                class="rounded-lg p-1.5 hover:bg-muted transition-colors text-muted-foreground hover:text-foreground"
                                title="Historique des achats">
                                <Eye class="h-4 w-4" />
                            </Link>
                            <Link :href="route('admin.fournisseurs.edit', f.id)"
                                class="rounded-lg p-1.5 hover:bg-muted transition-colors text-muted-foreground hover:text-foreground">
                                <Pencil class="h-4 w-4" />
                            </Link>
                            <button @click="destroy(f.id)" :disabled="deleting === f.id"
                                class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600 disabled:opacity-50">
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
