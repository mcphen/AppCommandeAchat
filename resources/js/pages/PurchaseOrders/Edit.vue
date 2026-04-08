<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PurchaseOrder } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Upload, X, FileText, Loader2, Save, Trash2 } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{ order: PurchaseOrder }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Mes commandes', href: '/purchase-orders' },
    { title: 'Modifier', href: '#' },
];

const form = useForm({
    title: props.order.title,
    description: props.order.description,
    amount: props.order.amount,
    attachments: [] as File[],
    deleted_attachment_ids: [] as number[],
    _method: 'PUT',
});

const existingAttachments = ref(props.order.attachments ?? []);
const dragOver = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const markDelete = (id: number) => {
    existingAttachments.value = existingAttachments.value.filter(a => a.id !== id);
    form.deleted_attachment_ids.push(id);
};

const addFiles = (files: FileList | File[]) => {
    const pdfs = Array.from(files).filter(f => f.type === 'application/pdf');
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

const removeNewFile = (index: number) => {
    form.attachments = form.attachments.filter((_, i) => i !== index);
};

const formatSize = (bytes: number) => {
    if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    return (bytes / 1024).toFixed(0) + ' KB';
};

const submit = () => {
    form.post(route('purchase-orders.update', props.order.id), { forceFormData: true });
};
</script>

<template>
    <Head title="Modifier la commande" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 max-w-3xl">

            <div class="flex items-center gap-4">
                <Link :href="route('purchase-orders.index')" class="rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Modifier la commande</h1>
                    <p class="text-sm text-muted-foreground">
                        <span v-if="order.status === 'rejected'" class="text-red-600 font-medium">Commande refusée</span>
                        <span v-else>Brouillon</span>
                        — corrigez puis re-soumettez
                    </p>
                </div>
            </div>

            <!-- Motif de refus -->
            <div v-if="order.status === 'rejected' && order.validation_logs?.length" class="rounded-2xl border border-red-200 bg-red-50 p-5">
                <p class="text-sm font-semibold text-red-800 mb-1">Motif du dernier refus</p>
                <p class="text-sm text-red-700">{{ order.validation_logs[0]?.comment }}</p>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-5">
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <h2 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Informations générales</h2>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="title">Titre <span class="text-red-500">*</span></label>
                        <input
                            id="title"
                            v-model="form.title"
                            type="text"
                            class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                            :class="{ 'border-red-400': form.errors.title }"
                        />
                        <p v-if="form.errors.title" class="text-xs text-red-500">{{ form.errors.title }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="description">Description <span class="text-red-500">*</span></label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-none"
                            :class="{ 'border-red-400': form.errors.description }"
                        />
                        <p v-if="form.errors.description" class="text-xs text-red-500">{{ form.errors.description }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="amount">Montant (FCFA) <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground text-sm font-medium">FCFA</span>
                            <input
                                id="amount"
                                v-model="form.amount"
                                type="number"
                                min="0"
                                class="h-10 w-full rounded-xl border border-input bg-background pl-16 pr-4 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.amount }"
                            />
                        </div>
                        <p v-if="form.errors.amount" class="text-xs text-red-500">{{ form.errors.amount }}</p>
                    </div>
                </div>

                <!-- Pièces jointes existantes -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <div>
                        <h2 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Pièces jointes</h2>
                        <p class="text-xs text-muted-foreground mt-1">PDF uniquement · 10 Mo max par fichier</p>
                    </div>

                    <!-- Existantes -->
                    <div v-if="existingAttachments.length > 0" class="flex flex-col gap-2">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Fichiers actuels</p>
                        <div
                            v-for="att in existingAttachments"
                            :key="att.id"
                            class="flex items-center justify-between rounded-xl border bg-muted/30 px-4 py-3"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50">
                                    <FileText class="h-4 w-4 text-red-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ att.file_name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatSize(att.file_size) }}</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                @click="markDelete(att.id)"
                                class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Zone d'ajout -->
                    <div
                        class="relative rounded-xl border-2 border-dashed p-6 text-center cursor-pointer transition-colors"
                        :class="dragOver ? 'border-primary bg-primary/5' : 'border-border hover:border-primary/50 hover:bg-muted/20'"
                        @dragover.prevent="dragOver = true"
                        @dragleave="dragOver = false"
                        @drop.prevent="onDrop"
                        @click="fileInput?.click()"
                    >
                        <input ref="fileInput" type="file" accept=".pdf" multiple class="hidden" @change="onFileChange" />
                        <div class="flex flex-col items-center gap-2">
                            <Upload class="h-5 w-5 text-muted-foreground" />
                            <p class="text-sm text-muted-foreground">Ajouter des fichiers PDF</p>
                        </div>
                    </div>

                    <!-- Nouveaux fichiers -->
                    <div v-if="form.attachments.length > 0" class="flex flex-col gap-2">
                        <p class="text-xs font-medium text-muted-foreground uppercase tracking-wide">Nouveaux fichiers</p>
                        <div
                            v-for="(file, index) in form.attachments"
                            :key="index"
                            class="flex items-center justify-between rounded-xl border border-primary/30 bg-primary/5 px-4 py-3"
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                    <FileText class="h-4 w-4 text-primary" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ file.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatSize(file.size) }}</p>
                                </div>
                            </div>
                            <button type="button" @click.stop="removeNewFile(index)" class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600">
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('purchase-orders.show', order.id)" class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors">
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:opacity-70"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
