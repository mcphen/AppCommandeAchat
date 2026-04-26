<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type Agent, type Boutique, type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, Save } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ agent?: Agent; boutiques: Boutique[] }>();
const isEdit = computed(() => !!props.agent);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '/admin' },
    { title: 'Agents', href: route('admin.agents.index') },
    { title: isEdit.value ? 'Modifier' : 'Nouvel agent', href: '#' },
];

const form = useForm({
    matricule:     props.agent?.matricule ?? '',
    nom:           props.agent?.nom ?? '',
    prenom:        props.agent?.prenom ?? '',
    telephone:     props.agent?.telephone ?? '',
    boutique_id:   props.agent?.boutique_id?.toString() ?? '',
    date_adhesion: props.agent?.date_adhesion ?? new Date().toISOString().slice(0, 10),
    is_active:     props.agent?.is_active ?? true,
    // Création de compte utilisateur (nouveau agent uniquement)
    creer_compte: false,
    email:        '',
    password:     '',
});

function submit() {
    if (isEdit.value) {
        form.put(route('admin.agents.update', props.agent!.id));
    } else {
        form.post(route('admin.agents.store'));
    }
}
</script>

<template>
    <Head :title="isEdit ? 'Modifier l\'agent' : 'Nouvel agent'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-2xl p-3 sm:p-6">
            <div class="flex items-center gap-4 mb-6">
                <Link :href="route('admin.agents.index')"
                    class="flex items-center gap-1.5 rounded-xl border px-3 py-2 text-xs font-semibold text-muted-foreground hover:bg-muted">
                    <ArrowLeft class="h-3.5 w-3.5" /> Retour
                </Link>
                <h1 class="text-2xl font-bold text-foreground">{{ isEdit ? 'Modifier l\'agent' : 'Nouvel agent' }}</h1>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-5 rounded-2xl border bg-card p-6 shadow-sm">
                <!-- Informations personnelles -->
                <h2 class="font-semibold text-foreground border-b pb-2">Informations personnelles</h2>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-foreground">Prénom <span class="text-red-500">*</span></label>
                        <input v-model="form.prenom" type="text" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                            :class="form.errors.prenom ? 'border-red-400' : ''" />
                        <p v-if="form.errors.prenom" class="mt-1 text-xs text-red-500">{{ form.errors.prenom }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-foreground">Nom <span class="text-red-500">*</span></label>
                        <input v-model="form.nom" type="text" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                            :class="form.errors.nom ? 'border-red-400' : ''" />
                        <p v-if="form.errors.nom" class="mt-1 text-xs text-red-500">{{ form.errors.nom }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-foreground">Matricule <span class="text-red-500">*</span></label>
                        <input v-model="form.matricule" type="text" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary/40"
                            :class="form.errors.matricule ? 'border-red-400' : ''" />
                        <p v-if="form.errors.matricule" class="mt-1 text-xs text-red-500">{{ form.errors.matricule }}</p>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-foreground">Téléphone</label>
                        <input v-model="form.telephone" type="text" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-foreground">Boutique</label>
                        <select v-model="form.boutique_id" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40">
                            <option value="">Aucune boutique</option>
                            <option v-for="b in boutiques" :key="b.id" :value="b.id.toString()">{{ b.name }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-sm font-medium text-foreground">Date d'adhésion <span class="text-red-500">*</span></label>
                        <input v-model="form.date_adhesion" type="date" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                            :class="form.errors.date_adhesion ? 'border-red-400' : ''" />
                        <p v-if="form.errors.date_adhesion" class="mt-1 text-xs text-red-500">{{ form.errors.date_adhesion }}</p>
                    </div>
                </div>

                <div v-if="isEdit" class="flex items-center gap-3">
                    <input v-model="form.is_active" type="checkbox" id="is_active" class="h-4 w-4 rounded border-input" />
                    <label for="is_active" class="text-sm font-medium text-foreground">Agent actif</label>
                </div>

                <!-- Compte utilisateur (nouveau agent uniquement) -->
                <template v-if="!isEdit">
                    <h2 class="font-semibold text-foreground border-b pb-2 mt-2">Accès au portail</h2>
                    <div class="flex items-center gap-3">
                        <input v-model="form.creer_compte" type="checkbox" id="creer_compte" class="h-4 w-4 rounded border-input" />
                        <label for="creer_compte" class="text-sm font-medium text-foreground">
                            Créer un compte utilisateur (accès au portail)
                        </label>
                    </div>
                    <template v-if="form.creer_compte">
                        <div class="grid grid-cols-2 gap-4 rounded-xl border bg-muted/20 p-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Email <span class="text-red-500">*</span></label>
                                <input v-model="form.email" type="email" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40"
                                    :class="form.errors.email ? 'border-red-400' : ''" />
                                <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-foreground">Mot de passe <span class="text-red-500">*</span></label>
                                <input v-model="form.password" type="password" class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/40" />
                                <p v-if="form.errors.password" class="mt-1 text-xs text-red-500">{{ form.errors.password }}</p>
                            </div>
                        </div>
                    </template>
                </template>

                <button type="submit" :disabled="form.processing"
                    class="flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90 disabled:opacity-50">
                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                    <Save v-else class="h-4 w-4" />
                    {{ isEdit ? 'Enregistrer les modifications' : 'Créer l\'agent' }}
                </button>
            </form>
        </div>
    </AppLayout>
</template>
