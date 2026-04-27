<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type ComiteArbitrage } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Users, CheckCircle2, XCircle } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Comités d\'arbitrage', href: '/admin/comites-arbitrage' },
];

defineProps<{ comites: ComiteArbitrage[] }>();

function destroy(comite: ComiteArbitrage) {
    Swal.fire({
        title: 'Supprimer ce comité ?',
        text: comite.nom,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#ef4444',
    }).then(r => {
        if (r.isConfirmed) router.delete(route('admin.comites-arbitrage.destroy', comite.id));
    });
}

const roleBadge: Record<string, string> = {
    president:  'bg-violet-100 text-violet-700',
    membre:     'bg-sky-100 text-sky-700',
    secretaire: 'bg-amber-100 text-amber-700',
};

const roleLabel: Record<string, string> = {
    president:  'Président',
    membre:     'Membre',
    secretaire: 'Secrétaire',
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Comités d'arbitrage" />

        <div class="flex flex-col gap-6 p-6">
            <PageHeader
                title="Comités d'arbitrage"
                :subtitle="`${comites.length} comité(s)`"
                eyebrow="Administration"
            >
                <template #actions>
                    <Link :href="route('admin.comites-arbitrage.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors">
                        <Plus class="h-4 w-4" /> Nouveau comité
                    </Link>
                </template>
            </PageHeader>

            <div v-if="comites.length === 0" class="rounded-xl border bg-card p-12 text-center shadow-sm">
                <Users class="mx-auto h-10 w-10 text-muted-foreground/40 mb-3" />
                <p class="font-medium text-muted-foreground">Aucun comité créé</p>
                <p class="text-sm text-muted-foreground/70 mt-1">Commencez par créer un comité d'arbitrage.</p>
            </div>

            <div v-else class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-3">
                <div v-for="comite in comites" :key="comite.id"
                    class="rounded-xl border bg-card shadow-sm flex flex-col gap-4 p-5">

                    <!-- En-tête -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex flex-col gap-0.5 min-w-0">
                            <div class="flex items-center gap-2">
                                <h3 class="font-semibold truncate">{{ comite.nom }}</h3>
                                <span class="shrink-0 flex items-center gap-1 text-xs"
                                    :class="comite.is_active ? 'text-emerald-600' : 'text-muted-foreground'">
                                    <CheckCircle2 v-if="comite.is_active" class="h-3.5 w-3.5" />
                                    <XCircle v-else class="h-3.5 w-3.5" />
                                    {{ comite.is_active ? 'Actif' : 'Inactif' }}
                                </span>
                            </div>
                            <p v-if="comite.entreprise" class="text-xs text-muted-foreground">{{ comite.entreprise.nom }}</p>
                            <p v-else class="text-xs text-muted-foreground italic">Toutes entreprises</p>
                        </div>
                        <span class="shrink-0 rounded-full border px-2.5 py-1 text-xs font-medium text-muted-foreground">
                            Quorum {{ comite.quorum_pct }}%
                        </span>
                    </div>

                    <!-- Description -->
                    <p v-if="comite.description" class="text-sm text-muted-foreground line-clamp-2">{{ comite.description }}</p>

                    <!-- Membres -->
                    <div class="flex flex-col gap-1.5">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Membres ({{ comite.membres?.length ?? 0 }})</p>
                        <div class="flex flex-col gap-1">
                            <div v-for="m in comite.membres" :key="m.id"
                                class="flex items-center justify-between rounded-lg bg-muted/40 px-2.5 py-1.5">
                                <span class="text-sm font-medium truncate">{{ m.user?.name }}</span>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium" :class="roleBadge[m.role_membre]">
                                    {{ roleLabel[m.role_membre] }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="flex items-center gap-3 text-xs text-muted-foreground border-t pt-3">
                        <span>{{ comite.sessions_count ?? 0 }} session(s)</span>
                    </div>

                    <!-- Actions -->
                    <div class="flex gap-2">
                        <Link :href="route('admin.comites-arbitrage.edit', comite.id)"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm hover:bg-muted transition-colors">
                            <Pencil class="h-3.5 w-3.5" /> Modifier
                        </Link>
                        <button @click="destroy(comite)"
                            class="inline-flex items-center justify-center rounded-lg border border-red-200 px-2.5 py-1.5 text-red-500 hover:bg-red-50 transition-colors">
                            <Trash2 class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
