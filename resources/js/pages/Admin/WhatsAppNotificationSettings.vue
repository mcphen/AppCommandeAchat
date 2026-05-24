<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Role, type User } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { Mail, MessageCircle, Save, ToggleLeft, ToggleRight } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface UserRow extends User {
    role: Role | null;
}

const props = defineProps<{ users: UserRow[] }>();

const page = usePage<any>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin/users' },
    { title: 'Notifications WhatsApp', href: '#' },
];

const states = ref<Record<number, boolean>>(
    Object.fromEntries(props.users.map(u => [u.id, u.whatsapp_notifications ?? false]))
);

const dirty = ref(false);
const processing = ref(false);

const toggleUser = (id: number) => {
    states.value[id] = !states.value[id];
    dirty.value = true;
};

const enabledCount = computed(() => Object.values(states.value).filter(Boolean).length);

const setAll = (value: boolean) => {
    props.users.forEach(u => { states.value[u.id] = value; });
    dirty.value = true;
};

const save = () => {
    processing.value = true;
    router.patch(route('admin.notifications.whatsapp.update'), { users: states.value }, {
        onFinish: () => {
            processing.value = false;
            dirty.value = false;
        },
        preserveScroll: true,
    });
};

const roleLabel: Record<string, { label: string; classes: string }> = {
    admin:      { label: 'Admin',      classes: 'bg-violet-50 text-violet-700' },
    demandeur:  { label: 'Demandeur',  classes: 'bg-blue-50 text-blue-700' },
    validateur: { label: 'Validateur', classes: 'bg-emerald-50 text-emerald-700' },
    caissier:   { label: 'Caissier',   classes: 'bg-amber-50 text-amber-700' },
    agent:      { label: 'Agent',      classes: 'bg-gray-50 text-gray-600' },
};
</script>

<template>
    <Head title="Notifications WhatsApp" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-4 sm:p-6">

            <!-- En-tête -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Notifications WhatsApp</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Choisissez pour chaque utilisateur s'il reçoit les notifications par email seulement ou email + WhatsApp.
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <span class="rounded-full bg-green-50 px-3 py-1 text-xs font-semibold text-green-700">
                        {{ enabledCount }} / {{ users.length }} avec WhatsApp
                    </span>
                </div>
            </div>

            <!-- Info coût -->
            <div class="flex items-start gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                <MessageCircle class="mt-0.5 h-4 w-4 shrink-0 text-amber-500" />
                <span>
                    Chaque message WhatsApp envoyé via Twilio est facturé.
                    <strong>Email est gratuit</strong> — activez WhatsApp uniquement pour les utilisateurs qui en ont vraiment besoin.
                </span>
            </div>

            <!-- Actions globales + Sauvegarder -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    @click="setAll(true)"
                    class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-medium text-foreground transition-colors hover:bg-muted"
                >
                    <ToggleRight class="h-4 w-4 text-green-500" />
                    Tout activer
                </button>
                <button
                    type="button"
                    @click="setAll(false)"
                    class="inline-flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-medium text-foreground transition-colors hover:bg-muted"
                >
                    <ToggleLeft class="h-4 w-4 text-muted-foreground" />
                    Tout désactiver
                </button>

                <div class="ml-auto">
                    <button
                        type="button"
                        @click="save"
                        :disabled="!dirty || processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-50"
                    >
                        <Save class="h-4 w-4" />
                        {{ processing ? 'Enregistrement…' : 'Enregistrer' }}
                    </button>
                </div>
            </div>

            <!-- Flash success -->
            <div
                v-if="page.props.flash?.success"
                class="flex items-center gap-2 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-700"
            >
                ✓ {{ page.props.flash.success }}
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b bg-muted/30">
                            <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Utilisateur</th>
                            <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:table-cell sm:px-6">Rôle</th>
                            <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell sm:px-6">Téléphone</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Canal</th>
                            <th class="px-4 py-3.5 text-center text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">WhatsApp</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="user in users"
                            :key="user.id"
                            class="transition-colors hover:bg-muted/20"
                            :class="{ 'bg-green-50/30': states[user.id] }"
                        >
                            <!-- Utilisateur -->
                            <td class="px-4 py-3.5 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary/20 to-primary/40 text-xs font-bold text-primary">
                                        {{ user.name.charAt(0).toUpperCase() }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium text-foreground">{{ user.name }}</p>
                                        <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Rôle -->
                            <td class="hidden px-4 py-3.5 sm:table-cell sm:px-6">
                                <span
                                    v-if="user.role"
                                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                    :class="roleLabel[user.role.slug]?.classes ?? 'bg-muted text-muted-foreground'"
                                >
                                    {{ roleLabel[user.role.slug]?.label ?? user.role.name }}
                                </span>
                            </td>

                            <!-- Téléphone -->
                            <td class="hidden px-4 py-3.5 md:table-cell sm:px-6">
                                <span v-if="user.phone" class="font-mono text-xs text-foreground">{{ user.phone }}</span>
                                <span v-else class="text-xs text-muted-foreground italic">Non renseigné</span>
                            </td>

                            <!-- Canal actuel -->
                            <td class="px-4 py-3.5 text-center sm:px-6">
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium',
                                        states[user.id] ? 'bg-green-50 text-green-700' : 'bg-muted text-muted-foreground'
                                    ]"
                                >
                                    <MessageCircle v-if="states[user.id]" class="h-3 w-3" />
                                    <Mail v-else class="h-3 w-3" />
                                    {{ states[user.id] ? 'Email + WA' : 'Email' }}
                                </span>
                            </td>

                            <!-- Toggle -->
                            <td class="px-4 py-3.5 text-center sm:px-6">
                                <button
                                    type="button"
                                    @click="toggleUser(user.id)"
                                    :title="states[user.id] ? 'Désactiver WhatsApp' : 'Activer WhatsApp'"
                                    :class="[
                                        'inline-flex h-6 w-11 items-center rounded-full p-0.5 transition-colors focus:outline-none focus:ring-2 focus:ring-primary/30',
                                        states[user.id] ? 'bg-green-500' : 'bg-muted-foreground/30'
                                    ]"
                                >
                                    <span
                                        :class="[
                                            'h-5 w-5 rounded-full bg-white shadow transition-transform',
                                            states[user.id] ? 'translate-x-5' : 'translate-x-0'
                                        ]"
                                    />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Pied de tableau -->
                <div class="flex items-center justify-between border-t bg-muted/10 px-4 py-3 sm:px-6">
                    <p class="text-xs text-muted-foreground">
                        {{ enabledCount }} utilisateur{{ enabledCount !== 1 ? 's' : '' }} avec WhatsApp actif
                        — {{ users.length - enabledCount }} en email uniquement
                    </p>
                    <button
                        type="button"
                        @click="save"
                        :disabled="!dirty || processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-xs font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-50"
                    >
                        <Save class="h-3.5 w-3.5" />
                        {{ processing ? 'Enregistrement…' : 'Enregistrer les modifications' }}
                    </button>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
