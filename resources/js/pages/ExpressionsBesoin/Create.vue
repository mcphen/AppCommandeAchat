<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PageHeader from '@/components/PageHeader.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { Upload, X, Loader2 } from 'lucide-vue-next';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Expressions de besoin', href: '/expressions-besoin' },
    { title: 'Nouvelle expression', href: '/expressions-besoin/create' },
];

const form = useForm({
    objet: '',
    description: '',
    montant: '',
    beneficiaire: '',
    justification: '',
    attachments: [] as File[],
});

const fileInput = ref<HTMLInputElement>();

function handleFiles(e: Event) {
    const files = (e.target as HTMLInputElement).files;
    if (files) {
        form.attachments = [...form.attachments, ...Array.from(files)];
    }
}

function removeFile(index: number) {
    form.attachments.splice(index, 1);
}

function submit() {
    form.post(route('expressions-besoin.store'));
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Nouvelle expression de besoin" />

        <div class="flex w-full flex-col gap-6 p-6">
            <PageHeader
                title="Nouvelle expression de besoin"
                subtitle="Décrivez votre besoin et le montant demandé."
                eyebrow="Saisie de demande"
            />

            <form @submit.prevent="submit" class="flex flex-col gap-5">
                <div class="rounded-xl border bg-card p-6 flex flex-col gap-5 shadow-sm">
                    <!-- Objet -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Objet <span class="text-red-500">*</span></label>
                        <input v-model="form.objet" type="text"
                            class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40 transition"
                            placeholder="Ex: Avance sur contrat prestataire XYZ" />
                        <p v-if="form.errors.objet" class="text-xs text-red-500">{{ form.errors.objet }}</p>
                    </div>

                    <!-- Bénéficiaire -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Bénéficiaire</label>
                        <input v-model="form.beneficiaire" type="text"
                            class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40 transition"
                            placeholder="Nom du prestataire / fournisseur / bénéficiaire" />
                    </div>

                    <!-- Montant -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Montant (FCFA) <span class="text-red-500">*</span></label>
                        <input v-model="form.montant" type="number" min="0" step="1"
                            class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40 transition"
                            placeholder="0" />
                        <p v-if="form.errors.montant" class="text-xs text-red-500">{{ form.errors.montant }}</p>
                    </div>

                    <!-- Description -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Description <span class="text-red-500">*</span></label>
                        <textarea v-model="form.description" rows="4"
                            class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40 transition resize-none"
                            placeholder="Décrivez en détail votre besoin, le contexte, l'utilisation prévue..."></textarea>
                        <p v-if="form.errors.description" class="text-xs text-red-500">{{ form.errors.description }}</p>
                    </div>

                    <!-- Justification -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Justification complémentaire</label>
                        <textarea v-model="form.justification" rows="3"
                            class="rounded-lg border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40 transition resize-none"
                            placeholder="Toute information complémentaire pour appuyer votre demande..."></textarea>
                    </div>

                    <!-- Pièces jointes -->
                    <div class="flex flex-col gap-2">
                        <label class="text-sm font-medium">Pièces jointes</label>
                        <button type="button" @click="fileInput?.click()"
                            class="flex items-center gap-2 rounded-lg border-2 border-dashed border-border px-4 py-3 text-sm text-muted-foreground hover:border-primary/50 hover:text-primary transition-colors">
                            <Upload class="h-4 w-4" />
                            Ajouter des fichiers (contrats, devis, etc.)
                        </button>
                        <input ref="fileInput" type="file" multiple class="hidden" @change="handleFiles" />

                        <div v-if="form.attachments.length > 0" class="flex flex-col gap-1.5">
                            <div v-for="(file, i) in form.attachments" :key="i"
                                class="flex items-center justify-between rounded-lg border bg-muted/30 px-3 py-2 text-sm">
                                <span class="truncate">{{ file.name }}</span>
                                <button type="button" @click="removeFile(i)"
                                    class="ml-2 text-muted-foreground hover:text-red-500 transition-colors">
                                    <X class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <a :href="route('expressions-besoin.index')"
                        class="rounded-lg border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors">
                        Annuler
                    </a>
                    <button type="submit" :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2 text-sm font-medium text-primary-foreground shadow hover:bg-primary/90 disabled:opacity-60 transition-colors">
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        Soumettre l'expression
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
