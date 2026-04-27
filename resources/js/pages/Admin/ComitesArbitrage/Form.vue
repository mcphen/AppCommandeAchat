<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type ComiteArbitrage, type Entreprise, type User } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Plus, Trash2, Loader2 } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    comite?: ComiteArbitrage;
    entreprises: Entreprise[];
    users: User[];
}>();

const isEdit = computed(() => !!props.comite);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Comités d\'arbitrage', href: '/admin/comites-arbitrage' },
    { title: isEdit.value ? 'Modifier' : 'Nouveau', href: '#' },
];

interface MembreForm {
    user_id: number | null;
    role_membre: 'president' | 'membre' | 'secretaire';
}

const form = useForm({
    nom:           props.comite?.nom ?? '',
    description:   props.comite?.description ?? '',
    entreprise_id: props.comite?.entreprise_id ?? null as number | null,
    quorum_pct:    props.comite?.quorum_pct ?? 51,
    is_active:     props.comite?.is_active ?? true,
    membres:       (props.comite?.membres?.map(m => ({
        user_id:     m.user_id,
        role_membre: m.role_membre as 'president' | 'membre' | 'secretaire',
    })) ?? [{ user_id: null as number | null, role_membre: 'membre' as const }]) as MembreForm[],
});

function ajouterMembre() {
    form.membres.push({ user_id: null, role_membre: 'membre' });
}

function supprimerMembre(index: number) {
    form.membres.splice(index, 1);
}

function submit() {
    if (isEdit.value) {
        form.put(route('admin.comites-arbitrage.update', props.comite!.id));
    } else {
        form.post(route('admin.comites-arbitrage.store'));
    }
}

const roleOptions = [
    { value: 'president',  label: 'Président' },
    { value: 'membre',     label: 'Membre' },
    { value: 'secretaire', label: 'Secrétaire' },
];

const usersDisponibles = computed(() => {
    const selectionnes = new Set(form.membres.map(m => m.user_id).filter(Boolean));
    return (idx: number) => props.users.filter(u =>
        !selectionnes.has(u.id) || form.membres[idx]?.user_id === u.id
    );
});

const nbPresidents = computed(() => form.membres.filter(m => m.role_membre === 'president').length);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="isEdit ? 'Modifier le comité' : 'Nouveau comité'" />

        <div class="flex flex-col gap-6 p-6 max-w-3xl">
            <PageHeader
                :title="isEdit ? 'Modifier le comité' : 'Nouveau comité d\'arbitrage'"
                eyebrow="Administration"
            />

            <form @submit.prevent="submit" class="flex flex-col gap-6">

                <!-- Informations générales -->
                <div class="rounded-xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <h2 class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Informations générales</h2>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Nom du comité <span class="text-red-500">*</span></label>
                        <input v-model="form.nom" type="text" placeholder="Ex: Comité d'arbitrage achats"
                            class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                        <p v-if="form.errors.nom" class="text-xs text-red-500">{{ form.errors.nom }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Description</label>
                        <textarea v-model="form.description" rows="2" placeholder="Rôle et périmètre du comité..."
                            class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40 resize-none" />
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium">Entreprise (optionnel)</label>
                            <select v-model="form.entreprise_id"
                                class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40">
                                <option :value="null">Toutes les entreprises</option>
                                <option v-for="e in entreprises" :key="e.id" :value="e.id">{{ e.nom }}</option>
                            </select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium">Quorum requis (%)</label>
                            <input v-model.number="form.quorum_pct" type="number" min="1" max="100"
                                class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                            <p class="text-xs text-muted-foreground">Pourcentage de membres devant voter pour valider la session.</p>
                            <p v-if="form.errors.quorum_pct" class="text-xs text-red-500">{{ form.errors.quorum_pct }}</p>
                        </div>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input v-model="form.is_active" type="checkbox" class="rounded" />
                        <span class="text-sm font-medium">Comité actif</span>
                    </label>
                </div>

                <!-- Membres du comité -->
                <div class="rounded-xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <h2 class="font-semibold text-sm text-muted-foreground uppercase tracking-wide">Membres du comité</h2>
                        <button type="button" @click="ajouterMembre"
                            class="inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-sm hover:bg-muted transition-colors">
                            <Plus class="h-3.5 w-3.5" /> Ajouter
                        </button>
                    </div>

                    <div v-if="nbPresidents !== 1" class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-sm text-amber-700">
                        Le comité doit avoir exactement <strong>un Président</strong>. ({{ nbPresidents }} actuellement)
                    </div>

                    <div v-if="form.errors.membres" class="text-xs text-red-500">{{ form.errors.membres }}</div>

                    <div class="flex flex-col gap-2">
                        <div v-for="(membre, index) in form.membres" :key="index"
                            class="flex items-center gap-3 rounded-lg border bg-muted/20 p-3">

                            <div class="flex-1 min-w-0">
                                <select v-model="membre.user_id"
                                    class="w-full rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40">
                                    <option :value="null">— Choisir un utilisateur —</option>
                                    <option v-for="u in usersDisponibles(index)" :key="u.id" :value="u.id">
                                        {{ u.name }}{{ u.fonction ? ` · ${u.fonction}` : '' }}
                                    </option>
                                </select>
                            </div>

                            <div class="w-36 shrink-0">
                                <select v-model="membre.role_membre"
                                    class="w-full rounded-lg border px-2 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40"
                                    :class="membre.role_membre === 'president' ? 'bg-violet-50 border-violet-200 text-violet-700' : 'bg-background'">
                                    <option v-for="r in roleOptions" :key="r.value" :value="r.value">{{ r.label }}</option>
                                </select>
                            </div>

                            <button type="button" @click="supprimerMembre(index)"
                                class="shrink-0 rounded-md border border-red-200 p-1.5 text-red-500 hover:bg-red-50 transition-colors">
                                <Trash2 class="h-3.5 w-3.5" />
                            </button>
                        </div>

                        <p v-if="form.membres.length === 0" class="text-sm text-muted-foreground text-center py-4">
                            Aucun membre ajouté. Cliquez sur "Ajouter".
                        </p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-3">
                    <button type="submit" :disabled="form.processing || nbPresidents !== 1"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60 transition-colors">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        {{ isEdit ? 'Mettre à jour' : 'Créer le comité' }}
                    </button>
                    <a :href="route('admin.comites-arbitrage.index')"
                        class="rounded-lg border px-4 py-2 text-sm hover:bg-muted transition-colors">
                        Annuler
                    </a>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
