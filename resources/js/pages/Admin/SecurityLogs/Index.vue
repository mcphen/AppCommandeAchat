<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData, type User } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Filter, KeyRound, LogIn, LogOut, RotateCcw, ShieldAlert, ShieldX, Trash2, UserCog, UserPlus,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Journal de sécurité', href: '/admin/security-logs' },
];

interface AuditLog {
    id: number;
    event: string;
    description: string | null;
    ip_address: string | null;
    created_at: string;
    actor: Pick<User, 'id' | 'name' | 'email'> | null;
    target_user: Pick<User, 'id' | 'name' | 'email'> | null;
}

const props = defineProps<{
    logs: PaginatedData<AuditLog>;
    filters: { event?: string; search?: string; date_from?: string; date_to?: string };
}>();

const showFilters = ref(!!(props.filters.event || props.filters.date_from || props.filters.date_to));

const localFilters = ref({
    search:    props.filters.search ?? '',
    event:     props.filters.event ?? '',
    date_from: props.filters.date_from ?? '',
    date_to:   props.filters.date_to ?? '',
});

const hasActiveFilters = computed(() => Object.values(localFilters.value).some(v => v !== ''));

const eventMeta: Record<string, { label: string; icon: any; classes: string }> = {
    login:                          { label: 'Connexion',                  icon: LogIn,     classes: 'bg-emerald-50 text-emerald-700' },
    logout:                         { label: 'Déconnexion',                icon: LogOut,    classes: 'bg-slate-50 text-slate-600' },
    login_failed:                   { label: 'Échec de connexion',         icon: ShieldX,   classes: 'bg-red-50 text-red-700' },
    access_denied:                  { label: 'Accès refusé',               icon: ShieldAlert, classes: 'bg-amber-50 text-amber-700' },
    user_created:                   { label: 'Compte créé',                icon: UserPlus,  classes: 'bg-indigo-50 text-indigo-700' },
    user_role_changed:              { label: 'Rôle modifié',               icon: UserCog,   classes: 'bg-violet-50 text-violet-700' },
    user_password_reset_by_admin:   { label: 'Mot de passe réinitialisé (admin)', icon: KeyRound, classes: 'bg-amber-50 text-amber-700' },
    password_changed:               { label: 'Mot de passe changé',        icon: KeyRound,  classes: 'bg-emerald-50 text-emerald-700' },
    user_deleted:                   { label: 'Compte supprimé',            icon: Trash2,    classes: 'bg-red-50 text-red-700' },
};

const metaFor = (event: string) => eventMeta[event] ?? { label: event, icon: ShieldAlert, classes: 'bg-slate-50 text-slate-600' };

const formatDate = (date: string) =>
    new Date(date).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const applyFilters = () => {
    const params: Record<string, string> = {};
    Object.entries(localFilters.value).forEach(([k, v]) => { if (v) params[k] = v; });
    router.get(route('admin.security-logs.index'), params, { preserveState: true, preserveScroll: true, replace: true });
};

const resetFilters = () => {
    localFilters.value = { search: '', event: '', date_from: '', date_to: '' };
    router.get(route('admin.security-logs.index'), {}, { preserveState: true, preserveScroll: true, replace: true });
};
</script>

<template>
    <Head title="Journal de sécurité" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center gap-2">
                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100">
                    <ShieldAlert class="h-5 w-5 text-amber-600" />
                </div>
                <div>
                    <h1 class="text-xl font-bold text-foreground sm:text-2xl">Journal de sécurité</h1>
                    <p class="text-sm text-muted-foreground">
                        {{ logs.total }} événement{{ logs.total !== 1 ? 's' : '' }} : connexions, accès refusés, comptes créés/modifiés/supprimés.
                    </p>
                </div>
            </div>

            <!-- Filtres -->
            <div class="rounded-2xl border bg-card shadow-sm">
                <div class="flex items-center gap-3 p-4">
                    <div class="relative flex-1">
                        <input
                            v-model="localFilters.search"
                            type="text"
                            placeholder="Rechercher par nom, email ou description..."
                            class="h-10 w-full rounded-xl border border-input bg-background pl-4 pr-4 text-sm text-foreground placeholder:text-muted-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                            @keyup.enter="applyFilters"
                        />
                    </div>
                    <button
                        class="inline-flex items-center gap-2 rounded-xl border px-3 py-2.5 text-sm font-medium transition-colors"
                        :class="showFilters ? 'border-primary bg-primary/5 text-primary' : 'text-foreground hover:bg-muted'"
                        @click="showFilters = !showFilters"
                    >
                        <Filter class="h-4 w-4" />
                        <span class="hidden sm:inline">Filtres</span>
                    </button>
                    <button
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-3 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                        @click="applyFilters"
                    >
                        Rechercher
                    </button>
                </div>

                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 -translate-y-1"
                    enter-to-class="opacity-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 translate-y-0"
                    leave-to-class="opacity-0 -translate-y-1"
                >
                    <div v-if="showFilters" class="border-t px-4 pb-4 pt-4">
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Type d'événement</label>
                                <select v-model="localFilters.event" class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30">
                                    <option value="">Tous</option>
                                    <option v-for="(meta, key) in eventMeta" :key="key" :value="key">{{ meta.label }}</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Période (du)</label>
                                <input v-model="localFilters.date_from" type="date" class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30" />
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-muted-foreground">Période (au)</label>
                                <input v-model="localFilters.date_to" type="date" class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30" />
                            </div>
                        </div>

                        <div class="mt-3 flex justify-end">
                            <button
                                v-if="hasActiveFilters"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                @click="resetFilters"
                            >
                                <RotateCcw class="h-3.5 w-3.5" />
                                Réinitialiser les filtres
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Table logs -->
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <template v-if="logs.data.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/30">
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Date</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Événement</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell sm:px-6">Acteur</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:table-cell sm:px-6">Compte concerné</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Détails</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:table-cell sm:px-6">IP</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="log in logs.data" :key="log.id" class="transition-colors hover:bg-muted/20">
                                    <td class="px-4 py-4 text-xs text-muted-foreground sm:px-6">{{ formatDate(log.created_at) }}</td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                            :class="metaFor(log.event).classes"
                                        >
                                            <component :is="metaFor(log.event).icon" class="h-3 w-3" />
                                            {{ metaFor(log.event).label }}
                                        </span>
                                    </td>
                                    <td class="hidden px-4 py-4 text-sm text-foreground md:table-cell sm:px-6">
                                        {{ log.actor?.name ?? '—' }}
                                    </td>
                                    <td class="hidden px-4 py-4 text-sm text-foreground lg:table-cell sm:px-6">
                                        {{ log.target_user?.name ?? '—' }}
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <p class="max-w-[280px] truncate text-xs text-muted-foreground" :title="log.description ?? ''">
                                            {{ log.description ?? '—' }}
                                        </p>
                                    </td>
                                    <td class="hidden px-4 py-4 text-xs text-muted-foreground xl:table-cell sm:px-6">
                                        {{ log.ip_address ?? '—' }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div v-if="logs.last_page > 1" class="flex flex-col items-center gap-3 border-t px-4 py-4 sm:flex-row sm:justify-between sm:px-6">
                        <p class="text-sm text-muted-foreground">{{ logs.from }}–{{ logs.to }} sur {{ logs.total }}</p>
                        <div class="flex flex-wrap items-center justify-center gap-1">
                            <Link
                                v-for="link in logs.links"
                                :key="link.label"
                                :href="link.url ?? '#'"
                                :class="[
                                    'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                                    link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted',
                                    !link.url ? 'pointer-events-none opacity-40' : '',
                                ]"
                            >
                                <span v-html="link.label" />
                            </Link>
                        </div>
                    </div>
                </template>

                <EmptyState
                    v-else
                    :icon="ShieldAlert"
                    icon-bg="bg-muted"
                    icon-color="text-muted-foreground"
                    title="Aucun événement enregistré"
                    description="Les connexions, accès refusés et actions sur les comptes apparaîtront ici."
                    :bordered="false"
                />
            </div>
        </div>
    </AppLayout>
</template>
