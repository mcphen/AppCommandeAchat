<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Fournisseur } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Truck, ShieldCheck, ShieldAlert } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{ fournisseur?: Fournisseur }>();
const isEdit = computed(() => !!props.fournisseur);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Fournisseurs', href: '/admin/fournisseurs' },
    { title: isEdit.value ? 'Modifier' : 'Nouveau fournisseur', href: '#' },
];

const form = useForm({
    name:        props.fournisseur?.name ?? '',
    code:        props.fournisseur?.code ?? '',
    email:       props.fournisseur?.email ?? '',
    phone:       props.fournisseur?.phone ?? '',
    address:     props.fournisseur?.address ?? '',
    city:        props.fournisseur?.city ?? '',
    is_active:   props.fournisseur?.is_active ?? true,
    is_approved: props.fournisseur?.is_approved ?? false,
});

const submit = () => {
    if (isEdit.value) {
        form.put(route('admin.fournisseurs.update', props.fournisseur!.id));
    } else {
        form.post(route('admin.fournisseurs.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Modifier le fournisseur' : 'Nouveau fournisseur'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6 max-w-2xl">

            <div class="flex items-center gap-4">
                <Link :href="route('admin.fournisseurs.index')" class="rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <h1 class="text-2xl font-bold text-foreground">{{ isEdit ? 'Modifier le fournisseur' : 'Nouveau fournisseur' }}</h1>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-5">

                <!-- Infos générales -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-5">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl mx-auto"
                        :class="form.is_approved ? 'bg-emerald-50' : 'bg-orange-50'">
                        <Truck class="h-6 w-6" :class="form.is_approved ? 'text-emerald-600' : 'text-orange-600'" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label class="text-sm font-medium text-foreground" for="name">Nom <span class="text-red-500">*</span></label>
                            <input id="name" v-model="form.name" type="text" placeholder="Ex : Bureau Sénégal SA"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.name }" />
                            <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground" for="code">Code <span class="text-red-500">*</span></label>
                            <input id="code" v-model="form.code" type="text" placeholder="Ex : BSN-001"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.code }" />
                            <p v-if="form.errors.code" class="text-xs text-red-500">{{ form.errors.code }}</p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground" for="email">Email</label>
                            <input id="email" v-model="form.email" type="email" placeholder="contact@fournisseur.com"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground" for="phone">Téléphone</label>
                            <input id="phone" v-model="form.phone" type="text" placeholder="+221 77 000 00 00"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground" for="city">Ville</label>
                            <input id="city" v-model="form.city" type="text" placeholder="Ex : Dakar"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors" />
                        </div>

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <label class="text-sm font-medium text-foreground" for="address">Adresse</label>
                            <input id="address" v-model="form.address" type="text" placeholder="Rue, quartier…"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors" />
                        </div>
                    </div>
                </div>

                <!-- Statuts -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Statuts</h2>

                    <!-- Homologation -->
                    <div class="flex items-center justify-between rounded-xl p-4"
                        :class="form.is_approved ? 'bg-emerald-50 border border-emerald-200' : 'bg-amber-50 border border-amber-200'">
                        <div class="flex items-center gap-3">
                            <ShieldCheck v-if="form.is_approved" class="h-5 w-5 text-emerald-600" />
                            <ShieldAlert v-else class="h-5 w-5 text-amber-600" />
                            <div>
                                <p class="text-sm font-semibold" :class="form.is_approved ? 'text-emerald-800' : 'text-amber-800'">
                                    {{ form.is_approved ? 'Fournisseur homologué' : 'Fournisseur non homologué' }}
                                </p>
                                <p class="text-xs" :class="form.is_approved ? 'text-emerald-600' : 'text-amber-600'">
                                    {{ form.is_approved
                                        ? 'Ce fournisseur est validé et peut être utilisé sans alerte.'
                                        : 'Une alerte sera affichée lors de l\'utilisation dans une commande.' }}
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="form.is_approved = !form.is_approved"
                            :class="form.is_approved ? 'bg-emerald-500' : 'bg-amber-400'"
                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition-colors">
                            <span :class="form.is_approved ? 'translate-x-6' : 'translate-x-1'"
                                class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform" />
                        </button>
                    </div>

                    <!-- Actif -->
                    <div class="flex items-center gap-3">
                        <button type="button" @click="form.is_active = !form.is_active"
                            :class="form.is_active ? 'bg-primary' : 'bg-muted'"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors">
                            <span :class="form.is_active ? 'translate-x-6' : 'translate-x-1'"
                                class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform" />
                        </button>
                        <span class="text-sm font-medium text-foreground">Fournisseur actif</span>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('admin.fournisseurs.index')" class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors">
                        Annuler
                    </Link>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:opacity-70">
                        {{ isEdit ? 'Enregistrer' : 'Créer le fournisseur' }}
                    </button>
                </div>
            </form>

        </div>
    </AppLayout>
</template>
