<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type SessionArbitrage } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Scale, Clock, CheckCircle2, XCircle, VoteIcon } from 'lucide-vue-next';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Arbitrage', href: '/arbitrage/sessions' },
];

defineProps<{
    sessions: SessionArbitrage[];
    isAdmin: boolean;
}>();

const statutConfig: Record<string, { label: string; class: string; icon: any }> = {
    brouillon: { label: 'Brouillon',     class: 'bg-slate-100 text-slate-600',   icon: Clock },
    en_vote:   { label: 'Vote en cours', class: 'bg-amber-100 text-amber-700',   icon: VoteIcon },
    cloturee:  { label: 'Clôturée',      class: 'bg-emerald-100 text-emerald-700', icon: CheckCircle2 },
    annulee:   { label: 'Annulée',       class: 'bg-red-100 text-red-600',       icon: XCircle },
};

function fmt(v: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Arbitrage des paiements" />

        <div class="flex flex-col gap-6 p-6">
            <PageHeader
                title="Arbitrage des paiements"
                :subtitle="`${sessions.length} session(s) d'arbitrage`"
                eyebrow="Arbitrage"
            >
                <template #actions>
                    <Link v-if="isAdmin" :href="route('arbitrage.sessions.create')"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 transition-colors">
                        <Plus class="h-4 w-4" /> Nouvelle session
                    </Link>
                </template>
            </PageHeader>

            <div v-if="sessions.length === 0" class="rounded-xl border bg-card p-12 text-center shadow-sm">
                <Scale class="mx-auto h-10 w-10 text-muted-foreground/40 mb-3" />
                <p class="font-medium text-muted-foreground">Aucune session d'arbitrage</p>
                <p v-if="isAdmin" class="text-sm text-muted-foreground/70 mt-1">Créez une session pour commencer l'arbitrage.</p>
                <p v-else class="text-sm text-muted-foreground/70 mt-1">Vous serez notifié lorsqu'une session sera ouverte.</p>
            </div>

            <div v-else class="rounded-xl border bg-card shadow-sm overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="border-b bg-muted/40">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Référence</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Titre</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Comité</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">DAPs</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Trésorerie</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Statut</th>
                            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Date</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr v-for="s in sessions" :key="s.id" class="hover:bg-muted/30 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-mono text-xs font-semibold text-primary">{{ s.reference }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium max-w-48 truncate">{{ s.titre }}</td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ s.comite?.nom }}
                                <span v-if="s.comite?.entreprise" class="block text-xs">{{ s.comite.entreprise.nom }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-muted text-xs font-semibold">
                                    {{ s.session_daps_count ?? 0 }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                <span v-if="s.tresorerie_disponible">{{ fmt(s.tresorerie_disponible) }}</span>
                                <span v-else class="text-xs italic">Non définie</span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                    :class="statutConfig[s.statut]?.class">
                                    <component :is="statutConfig[s.statut]?.icon" class="h-3 w-3" />
                                    {{ statutConfig[s.statut]?.label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground text-xs">
                                {{ new Date(s.created_at).toLocaleDateString('fr-FR') }}
                            </td>
                            <td class="px-4 py-3">
                                <Link :href="route('arbitrage.sessions.show', s.id)"
                                    class="rounded-md border px-2.5 py-1 text-xs font-medium hover:bg-muted transition-colors">
                                    Ouvrir
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>
