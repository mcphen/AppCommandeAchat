<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Upload, X, FileText, Loader2, Send } from 'lucide-vue-next';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Mes commandes', href: '/purchase-orders' },
    { title: 'Nouvelle commande', href: '/purchase-orders/create' },
];

const form = useForm({
    title: '',
    description: '',
    amount: '',
    attachments: [] as File[],
});

const dragOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const addFiles = (files: FileList | File[]) => {
    const arr = Array.from(files);
    const pdfs = arr.filter(f => f.type === 'application/pdf');
    form.attachments = [...form.attachments, ...pdfs];
};

const onDrop = (e: DragEvent) => {
    dragOver.value = false;
    if (e.dataTransfer?.files) addFiles(e.dataTransfer.files);
};

const onFileChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    if (input.files) addFiles(input.files);
};

const removeFile = (index: number) => {
    form.attachments = form.attachments.filter((_, i) => i !== index);
};

const formatSize = (bytes: number) => {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    return (bytes / 1024).toFixed(0) + ' KB';
};

const submit = () => {
    form.post(route('purchase-orders.store'), {
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Nouvelle commande" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 max-w-3xl sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('purchase-orders.index')" class="rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Nouvelle commande</h1>
                    <p class="text-sm text-muted-foreground">Remplissez les informations de votre demande d'achat</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-5">

                <!-- Titre -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <h2 class="font-semibold text-foreground text-sm uppercase tracking-wide text-muted-foreground">Informations générales</h2>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="title">Titre de la commande <span class="text-red-500">*</span></label>
                        <input
                            id="title"
                            v-model="form.title"
                            type="text"
                            placeholder="Ex : Achat de fournitures de bureau"
                            class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                            :class="{ 'border-red-400 focus:ring-red-300': form.errors.title }"
                        />
                        <p v-if="form.errors.title" class="text-xs text-red-500">{{ form.errors.title }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="description">Description <span class="text-red-500">*</span></label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            placeholder="Décrivez précisément l'objet de cette commande, la justification, les fournisseurs envisagés..."
                            class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm text-foreground ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-none"
                            :class="{ 'border-red-400': form.errors.description }"
                        />
                        <p v-if="form.errors.description" class="text-xs text-red-500">{{ form.errors.description }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="amount">Montant estimé (FCFA) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground text-sm font-medium">FCFA</span>
                            <input
                                id="amount"
                                v-model="form.amount"
                                type="number"
                                min="0"
                                step="1"
                                placeholder="0"
                                class="h-10 w-full rounded-xl border border-input bg-background pl-16 pr-4 text-sm text-foreground ring-offset-background placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.amount }"
                            />
                        </div>
                        <p v-if="form.errors.amount" class="text-xs text-red-500">{{ form.errors.amount }}</p>
                    </div>
                </div>

                <!-- Pièces jointes -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <div>
                        <h2 class="font-semibold text-foreground text-sm uppercase tracking-wide text-muted-foreground">Pièces jointes</h2>
                        <p class="text-xs text-muted-foreground mt-1">Fichiers PDF uniquement, 10 Mo max par fichier</p>
                    </div>

                    <!-- Zone de drop -->
                    <div
                        class="relative rounded-xl border-2 border-dashed p-8 text-center transition-colors cursor-pointer"
                        :class="dragOver ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50 hover:bg-muted/30'"
                        @dragover.prevent="dragOver = true"
                        @dragleave="dragOver = false"
                        @drop.prevent="onDrop"
                        @click="fileInput?.click()"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            accept=".pdf,application/pdf"
                            multiple
                            class="hidden"
                            @change="onFileChange"
                        />
                        <div class="flex flex-col items-center gap-3">
                            <div class="rounded-xl bg-muted p-3">
                                <Upload class="h-6 w-6 text-muted-foreground" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-foreground">Glissez vos fichiers PDF ici</p>
                                <p class="text-xs text-muted-foreground mt-1">ou <span class="text-primary font-medium">cliquez pour parcourir</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Liste des fichiers -->
                    <div v-if="form.attachments.length > 0" class="flex flex-col gap-2">
                        <div
                            v-for="(file, index) in form.attachments"
                            :key="index"
                            class="flex items-center justify-between rounded-xl border bg-muted/30 px-4 py-3"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50">
                                    <FileText class="h-4 w-4 text-red-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ file.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatSize(file.size) }}</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click.stop="removeFile(index)"
                                class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                    <p v-if="form.errors.attachments" class="text-xs text-red-500">{{ form.errors.attachments }}</p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="route('purchase-orders.index')"
                        class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:opacity-70"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Send v-else class="h-4 w-4" />
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
