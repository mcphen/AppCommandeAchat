<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem, type NiveauValidation } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Loader2, Settings } from 'lucide-vue-next';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Niveaux & Seuils', href: '/admin/niveaux-validation' },
];

const props = defineProps<{ niveaux: NiveauValidation[] }>();

const editing = ref<number | null>(null);
const forms = Object.fromEntries(
    props.niveaux.map(n => [n.id, useForm({ montant_seuil: n.seuil?.montant_seuil ?? 0 })])
);

function save(niveau: NiveauValidation) {
    forms[niveau.id].patch(route('admin.niveaux-validation.update', niveau.id), {
        onSuccess: () => { editing.value = null; },
    });
}

function formatMontant(v: number) {
    return new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'XOF', maximumFractionDigits: 0 }).format(v);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Niveaux de validation & Seuils" />
        <div class="flex w-full flex-col gap-6 p-6">
            <PageHeader
                title="Niveaux de validation & Seuils"
                subtitle="Configurez le montant minimum à partir duquel chaque niveau est requis. La Comptabilité (seuil = 0) valide toujours en premier."
                eyebrow="Gouvernance"
            />

            <div class="flex flex-col gap-3">
                <div v-for="niveau in niveaux" :key="niveau.id"
                    class="rounded-xl border bg-card p-5 shadow-sm flex items-center justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="flex h-9 w-9 items-center justify-center rounded-full bg-primary/10 text-primary font-bold text-sm shrink-0">
                            {{ niveau.ordre }}
                        </div>
                        <div>
                            <p class="font-semibold">{{ niveau.nom }}</p>
                            <p class="text-xs text-muted-foreground">{{ niveau.validateurs_count ?? 0 }} validateur(s) assigné(s)</p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <div v-if="editing === niveau.id" class="flex items-center gap-2">
                            <input v-model="forms[niveau.id].montant_seuil" type="number" min="0" step="1000"
                                class="w-36 rounded-lg border bg-background px-3 py-1.5 text-sm outline-none focus:ring-2 focus:ring-primary/40" />
                            <button @click="save(niveau)" :disabled="forms[niveau.id].processing"
                                class="inline-flex items-center gap-1 rounded-lg bg-primary px-3 py-1.5 text-xs font-medium text-primary-foreground hover:bg-primary/90 disabled:opacity-60 transition-colors">
                                <Loader2 v-if="forms[niveau.id].processing" class="h-3 w-3 animate-spin" />
                                Sauver
                            </button>
                            <button @click="editing = null" class="rounded-lg border px-3 py-1.5 text-xs hover:bg-muted transition-colors">
                                Annuler
                            </button>
                        </div>
                        <template v-else>
                            <div class="text-right">
                                <p class="text-xs text-muted-foreground">Seuil minimum</p>
                                <p class="font-semibold">{{ niveau.slug === 'compta' ? 'Toujours requis' : formatMontant(niveau.seuil?.montant_seuil ?? 0) }}</p>
                            </div>
                            <button v-if="niveau.slug !== 'compta'" @click="editing = niveau.id"
                                class="rounded-md border p-1.5 hover:bg-muted transition-colors">
                                <Settings class="h-4 w-4" />
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
