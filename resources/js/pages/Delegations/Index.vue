<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm, router } from '@inertiajs/vue3';
import {
    UserCheck, Plus, X, Power, PowerOff, CalendarDays,
    CheckCircle2, Clock, AlertTriangle, ChevronDown, Info,
} from 'lucide-vue-next';
import { ref, computed } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Délégations', href: '/delegations' },
];

interface UserRef { id: number; name: string }
interface LevelRef { id: number; name: string; order: number; circuit?: { id: number; name: string } }

interface Delegation {
    id: number;
    delegator?: UserRef;
    delegatee?: UserRef;
    validationLevel: LevelRef;
    starts_at: string;
    ends_at: string;
    reason: string | null;
    is_active: boolean;
}

const props = defineProps<{
    given: Delegation[];
    received: Delegation[];
    users: UserRef[];
    levels: LevelRef[];
}>();

// ---- Tabs ----
const activeTab = ref<'given' | 'received'>('given');

// ---- Create form ----
const showForm = ref(false);
const form = useForm({
    delegatee_id: '',
    validation_level_id: '',
    starts_at: '',
    ends_at: '',
    reason: '',
});

const submit = () => {
    form.post(route('delegations.store'), {
        onSuccess: () => {
            showForm.value = false;
            form.reset();
        },
    });
};

// ---- Helpers ----
const today = new Date().toISOString().split('T')[0];

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const statusOf = (d: Delegation) => {
    if (!d.is_active) return 'disabled';
    const now = new Date();
    const start = new Date(d.starts_at);
    const end = new Date(d.ends_at);
    if (end < now) return 'expired';
    if (start > now) return 'upcoming';
    return 'active';
};

const statusConfig = {
    active:   { label: 'Active',      bg: 'bg-emerald-50',  text: 'text-emerald-700', dot: 'bg-emerald-500' },
    upcoming: { label: 'À venir',     bg: 'bg-sky-50',      text: 'text-sky-700',     dot: 'bg-sky-400' },
    expired:  { label: 'Expirée',     bg: 'bg-slate-100',   text: 'text-slate-500',   dot: 'bg-slate-400' },
    disabled: { label: 'Désactivée',  bg: 'bg-amber-50',    text: 'text-amber-700',   dot: 'bg-amber-400' },
};

const toggle = (d: Delegation) => {
    router.patch(route('delegations.toggle', d.id), {}, { preserveScroll: true });
};

const destroy = (d: Delegation) => {
    if (!confirm('Supprimer cette délégation ?')) return;
    router.delete(route('delegations.destroy', d.id), { preserveScroll: true });
};

const activeDelegationsReceived = computed(() =>
    props.received.filter(d => statusOf(d) === 'active')
);
</script>

<template>
    <Head title="Délégations de validation" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">

            <!-- Header -->
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <div class="rounded-xl bg-indigo-50 p-2.5">
                        <UserCheck class="h-5 w-5 text-indigo-600" />
                    </div>
                    <div>
                        <h1 class="text-xl font-bold text-foreground sm:text-2xl">Délégations de validation</h1>
                        <p class="text-sm text-muted-foreground mt-0.5">Gérez les validations par intérim en cas d'absence</p>
                    </div>
                </div>
                <button
                    @click="showForm = !showForm"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700"
                >
                    <Plus class="h-4 w-4" />
                    Nouvelle délégation
                </button>
            </div>

            <!-- Banner : délégations actives reçues -->
            <div v-if="activeDelegationsReceived.length > 0"
                 class="rounded-2xl border border-indigo-200 bg-indigo-50 p-4">
                <div class="flex items-start gap-3">
                    <Info class="mt-0.5 h-4 w-4 shrink-0 text-indigo-600" />
                    <div class="text-sm text-indigo-800">
                        <p class="font-semibold mb-1">Vous validez actuellement par intérim pour :</p>
                        <ul class="space-y-0.5">
                            <li v-for="d in activeDelegationsReceived" :key="d.id">
                                <span class="font-medium">{{ d.delegator?.name }}</span>
                                — niveau <span class="font-medium">{{ d.validationLevel.name }}</span>
                                jusqu'au {{ formatDate(d.ends_at) }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Formulaire de création -->
            <div v-if="showForm" class="rounded-2xl border bg-card p-5 shadow-sm">
                <h2 class="mb-4 font-semibold text-foreground flex items-center gap-2">
                    <Plus class="h-4 w-4 text-indigo-500" />
                    Créer une délégation
                </h2>
                <form @submit.prevent="submit" class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">Déléguer à <span class="text-red-500">*</span></label>
                        <select v-model="form.delegatee_id"
                                class="rounded-lg border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                required>
                            <option value="">Sélectionner un utilisateur</option>
                            <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                        </select>
                        <p v-if="form.errors.delegatee_id" class="text-xs text-red-500">{{ form.errors.delegatee_id }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">Niveau de validation <span class="text-red-500">*</span></label>
                        <select v-model="form.validation_level_id"
                                class="rounded-lg border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                required>
                            <option value="">Sélectionner un niveau</option>
                            <option v-for="l in levels" :key="l.id" :value="l.id">{{ l.circuit?.name }} — N{{ l.order }} — {{ l.name }}</option>
                        </select>
                        <p v-if="form.errors.validation_level_id" class="text-xs text-red-500">{{ form.errors.validation_level_id }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">Date de début <span class="text-red-500">*</span></label>
                        <input v-model="form.starts_at" type="date" :min="today"
                               class="rounded-lg border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               required />
                        <p v-if="form.errors.starts_at" class="text-xs text-red-500">{{ form.errors.starts_at }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">Date de fin <span class="text-red-500">*</span></label>
                        <input v-model="form.ends_at" type="date" :min="form.starts_at || today"
                               class="rounded-lg border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-indigo-500"
                               required />
                        <p v-if="form.errors.ends_at" class="text-xs text-red-500">{{ form.errors.ends_at }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5 sm:col-span-2">
                        <label class="text-sm font-medium text-foreground">Motif <span class="text-muted-foreground text-xs">(optionnel)</span></label>
                        <input v-model="form.reason" type="text" placeholder="Ex : Congé annuel, Mission externe…"
                               class="rounded-lg border bg-background px-3 py-2 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-indigo-500" />
                    </div>

                    <div class="flex items-center gap-3 sm:col-span-2">
                        <button type="submit" :disabled="form.processing"
                                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-60">
                            <CheckCircle2 class="h-4 w-4" />
                            Créer la délégation
                        </button>
                        <button type="button" @click="showForm = false; form.reset()"
                                class="inline-flex items-center gap-1.5 rounded-lg border px-4 py-2 text-sm font-medium text-muted-foreground transition hover:bg-muted">
                            <X class="h-4 w-4" /> Annuler
                        </button>
                    </div>
                </form>
            </div>

            <!-- Tabs -->
            <div class="flex gap-1 rounded-xl bg-muted p-1 w-fit">
                <button
                    v-for="tab in [{ key: 'given', label: 'Délégations données', count: given.length }, { key: 'received', label: 'Reçues', count: received.length }]"
                    :key="tab.key"
                    @click="activeTab = tab.key as 'given' | 'received'"
                    class="flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-medium transition"
                    :class="activeTab === tab.key
                        ? 'bg-background text-foreground shadow-sm'
                        : 'text-muted-foreground hover:text-foreground'"
                >
                    {{ tab.label }}
                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                        {{ tab.count }}
                    </span>
                </button>
            </div>

            <!-- Liste Délégations données -->
            <template v-if="activeTab === 'given'">
                <EmptyState
                    v-if="given.length === 0"
                    :icon="UserCheck"
                    icon-bg="bg-indigo-50"
                    icon-color="text-indigo-400"
                    title="Aucune délégation créée"
                    description="Déléguez temporairement votre pouvoir de validation à un collègue pendant vos absences ou congés."
                />
                <div v-else class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Délégataire</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Niveau</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell">Période</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:table-cell">Motif</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-muted-foreground">Statut</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="d in given" :key="d.id" class="transition-colors hover:bg-muted/30">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700 uppercase">
                                            {{ d.delegatee?.name?.charAt(0) }}
                                        </div>
                                        <span class="font-medium text-foreground">{{ d.delegatee?.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                        N{{ d.validationLevel.order }} — {{ d.validationLevel.name }}
                                    </span>
                                </td>
                                <td class="hidden px-4 py-3 md:table-cell">
                                    <div class="flex items-center gap-1.5 text-muted-foreground text-xs">
                                        <CalendarDays class="h-3.5 w-3.5 shrink-0" />
                                        {{ formatDate(d.starts_at) }} → {{ formatDate(d.ends_at) }}
                                    </div>
                                </td>
                                <td class="hidden px-4 py-3 text-muted-foreground lg:table-cell">
                                    {{ d.reason ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                          :class="[statusConfig[statusOf(d)].bg, statusConfig[statusOf(d)].text]">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[statusOf(d)].dot" />
                                        {{ statusConfig[statusOf(d)].label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-2">
                                        <button
                                            v-if="statusOf(d) !== 'expired'"
                                            @click="toggle(d)"
                                            :title="d.is_active ? 'Désactiver' : 'Réactiver'"
                                            class="rounded-lg border p-1.5 text-muted-foreground transition hover:bg-muted"
                                        >
                                            <PowerOff v-if="d.is_active" class="h-3.5 w-3.5" />
                                            <Power v-else class="h-3.5 w-3.5 text-emerald-600" />
                                        </button>
                                        <button
                                            @click="destroy(d)"
                                            title="Supprimer"
                                            class="rounded-lg border p-1.5 text-muted-foreground transition hover:bg-red-50 hover:text-red-600 hover:border-red-200"
                                        >
                                            <X class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <!-- Liste Délégations reçues -->
            <template v-if="activeTab === 'received'">
                <EmptyState
                    v-if="received.length === 0"
                    :icon="UserCheck"
                    icon-bg="bg-slate-100"
                    icon-color="text-slate-400"
                    title="Aucune délégation reçue"
                    description="Vous n'avez reçu aucune délégation de validation pour l'instant. Elles apparaîtront ici lorsqu'un collègue vous délèguera ses droits."
                />
                <div v-else class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="border-b bg-muted/40">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Délégué par</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Niveau</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell">Période</th>
                                <th class="hidden px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:table-cell">Motif</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-muted-foreground">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="d in received" :key="d.id" class="transition-colors hover:bg-muted/30">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-700 uppercase">
                                            {{ d.delegator?.name?.charAt(0) }}
                                        </div>
                                        <span class="font-medium text-foreground">{{ d.delegator?.name }}</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                        N{{ d.validationLevel.order }} — {{ d.validationLevel.name }}
                                    </span>
                                </td>
                                <td class="hidden px-4 py-3 md:table-cell">
                                    <div class="flex items-center gap-1.5 text-muted-foreground text-xs">
                                        <CalendarDays class="h-3.5 w-3.5 shrink-0" />
                                        {{ formatDate(d.starts_at) }} → {{ formatDate(d.ends_at) }}
                                    </div>
                                </td>
                                <td class="hidden px-4 py-3 text-muted-foreground lg:table-cell">
                                    {{ d.reason ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold"
                                          :class="[statusConfig[statusOf(d)].bg, statusConfig[statusOf(d)].text]">
                                        <span class="h-1.5 w-1.5 rounded-full" :class="statusConfig[statusOf(d)].dot" />
                                        {{ statusConfig[statusOf(d)].label }}
                                    </span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

        </div>
    </AppLayout>
</template>
