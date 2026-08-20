<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type Circuit, type Role, type User, type ValidationLevel } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, Save } from 'lucide-vue-next';
import { computed, watch } from 'vue';

const props = defineProps<{
    user?: User;
    roles: Role[];
    levels: ValidationLevel[];
    circuits: Circuit[];
    boutiques: Boutique[];
}>();

const isEdit = computed(() => !!props.user);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Utilisateurs', href: '/admin/users' },
    { title: isEdit.value ? 'Modifier' : 'Nouvel utilisateur', href: '#' },
];

// Un select par circuit, garde sa propre valeur (id de niveau ou '') independamment des autres.
const levelByCircuit = Object.fromEntries(
    props.circuits.map(circuit => [
        circuit.id,
        props.user?.validation_levels?.find(l => l.circuit_id === circuit.id)?.id.toString() ?? '',
    ])
) as Record<number, string>;

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    password: '',
    password_confirmation: '',
    role_id: props.user?.role_id?.toString() ?? '',
    validation_level_ids: levelByCircuit,
    boutique_id: props.user?.boutique_id?.toString() ?? '',
});

const levelsForCircuit = (circuitId: number) => props.levels.filter(l => l.circuit_id === circuitId);

const submit = () => {
    form.transform((data) => ({
        ...data,
        validation_level_ids: Object.values(data.validation_level_ids).filter(Boolean),
    }));

    if (isEdit.value) {
        form.put(route('admin.users.update', props.user!.id));
    } else {
        form.post(route('admin.users.store'));
    }
};

const selectedRole = computed(() => props.roles.find(r => r.id === Number(form.role_id)));
const needsLevel = computed(() => selectedRole.value?.slug === 'validateur' || selectedRole.value?.slug === 'admin');
const needsBoutique = computed(() => selectedRole.value?.slug === 'demandeur');

watch(needsBoutique, (value) => {
    if (value) {
        for (const circuitId of Object.keys(form.validation_level_ids)) {
            form.validation_level_ids[Number(circuitId)] = '';
        }
        return;
    }

    form.boutique_id = '';
}, { immediate: true });
</script>

<template>
    <Head :title="isEdit ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-col gap-6 p-6">

            <div class="flex items-center gap-4">
                <Link :href="route('admin.users.index')" class="rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">
                        {{ isEdit ? 'Modifier l\'utilisateur' : 'Nouvel utilisateur' }}
                    </h1>
                    <p class="text-sm text-muted-foreground">{{ isEdit ? user?.email : 'Créer un nouveau compte' }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="flex w-full flex-col gap-5">
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Informations du compte</h2>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Nom complet <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Jean Dupont"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Adresse email <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.email"
                                type="email"
                                placeholder="jean@example.com"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.email }"
                            />
                            <p v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">
                                Mot de passe <span v-if="!isEdit" class="text-red-500">*</span>
                                <span v-else class="text-xs font-normal text-muted-foreground">(laisser vide = inchangé)</span>
                            </label>
                            <input
                                v-model="form.password"
                                type="password"
                                placeholder="••••••••"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.password }"
                            />
                            <p v-if="form.errors.password" class="text-xs text-red-500">{{ form.errors.password }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Confirmation</label>
                            <input
                                v-model="form.password_confirmation"
                                type="password"
                                placeholder="••••••••"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-black focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                            />
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Rôle et niveau</h2>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">Rôle <span class="text-red-500">*</span></label>
                        <select
                            v-model="form.role_id"
                            class="h-10 w-full cursor-pointer appearance-none rounded-xl border border-input bg-background px-4 text-sm text-black focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors"
                            :class="{ 'border-red-400': form.errors.role_id }"
                        >
                            <option value="">-- Sélectionner un rôle --</option>
                            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                        </select>
                        <p v-if="form.errors.role_id" class="text-xs text-red-500">{{ form.errors.role_id }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">
                            Société
                            <span v-if="needsBoutique" class="text-red-500">*</span>
                        </label>
                        <select
                            v-model="form.boutique_id"
                            :disabled="!needsBoutique"
                            class="h-10 w-full cursor-pointer appearance-none rounded-xl border border-input bg-background px-4 text-sm text-black focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors disabled:cursor-not-allowed disabled:text-black/60 disabled:opacity-50"
                            :class="{ 'border-red-400': form.errors.boutique_id }"
                        >
                            <option value="">-- SÃƒÂ©lectionner une société --</option>
                            <option v-for="boutique in boutiques" :key="boutique.id" :value="boutique.id">
                                {{ boutique.name }} <template v-if="boutique.city">({{ boutique.city }})</template>
                            </option>
                        </select>
                        <p v-if="form.errors.boutique_id" class="text-xs text-red-500">{{ form.errors.boutique_id }}</p>
                        <p class="text-xs text-muted-foreground">
                            <template v-if="needsBoutique">Obligatoire pour rattacher ce demandeur ÃƒÂ  une société.</template>
                            <template v-else>Les validateurs et admins restent rattachÃƒÂ©s au groupe.</template>
                        </p>
                    </div>

                    <div class="flex flex-col gap-3">
                        <label class="text-sm font-medium text-foreground">
                            Niveau de validation
                            <span v-if="needsLevel" class="text-xs font-normal text-muted-foreground">(optionnel pour admin)</span>
                        </label>
                        <p class="text-xs text-muted-foreground -mt-2">
                            <template v-if="selectedRole?.slug === 'validateur'">Un niveau par circuit au maximum — laisser sur « Aucun » pour un circuit où il ne valide pas.</template>
                            <template v-else-if="selectedRole?.slug === 'admin'">L'admin peut être assigné à un niveau par circuit pour y participer.</template>
                            <template v-else>Non applicable pour le rôle Demandeur.</template>
                        </p>
                        <div class="grid gap-3 md:grid-cols-2">
                            <div v-for="circuit in circuits" :key="circuit.id" class="flex flex-col gap-1.5">
                                <label class="text-xs font-medium text-muted-foreground">Niveau — {{ circuit.name }}</label>
                                <select
                                    v-model="form.validation_level_ids[circuit.id]"
                                    :disabled="!form.role_id || needsBoutique"
                                    class="h-10 w-full cursor-pointer appearance-none rounded-xl border border-input bg-background px-4 text-sm text-black focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30 transition-colors disabled:cursor-not-allowed disabled:text-black/60 disabled:opacity-50"
                                >
                                    <option value="">-- Aucun --</option>
                                    <option v-for="level in levelsForCircuit(circuit.id)" :key="level.id" :value="level.id.toString()">
                                        Niveau {{ level.order }} — {{ level.name }}
                                    </option>
                                </select>
                            </div>
                        </div>
                        <p v-if="form.errors.validation_level_ids" class="text-xs text-red-500">{{ form.errors.validation_level_ids }}</p>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('admin.users.index')" class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors">
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:opacity-70"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        {{ isEdit ? 'Enregistrer' : 'Créer l\'utilisateur' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>

<style scoped>
select,
select option {
    color: #000;
}
</style>
