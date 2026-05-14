<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type DisbursementRequest, type DisbursementRequestAttachment, type NatureOperation, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { AlertCircle, ArrowLeft, ChevronDown, FileText, Loader2, Pencil, Send, Store, Upload, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps<{
    order: DisbursementRequest;
    boutiques: Boutique[];
    natureOperations: NatureOperation[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Demandes de décaissement', href: route('disbursement-requests.index') },
    { title: props.order.reference, href: route('disbursement-requests.show', props.order.uuid) },
    { title: 'Modifier', href: '#' },
];

const page = usePage<SharedData>();
const isAdmin = computed(() => page.props.auth.user?.role?.slug === 'admin');

const form = useForm({
    nature_operation_id: String(props.order.nature_operation_id ?? ''),
    title:               props.order.title,
    description:         props.order.description ?? '',
    amount:              String(props.order.amount),
    boutique_id:         props.order.boutique_id ? String(props.order.boutique_id) : '',
    attachments:         [] as File[],
    deleted_attachment_ids: [] as number[],
    and_submit:          false,
});

const existingAttachments = ref<DisbursementRequestAttachment[]>([...(props.order.attachments ?? [])]);
const fileInput = ref<HTMLInputElement | null>(null);
const dragOver  = ref(false);
const submitting = ref(false);
const uploadError = ref('');
const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024;

const selectedNature = computed(() =>
    props.natureOperations.find((n) => String(n.id) === form.nature_operation_id)
);

const formTouched = computed(() =>
    form.isDirty || form.attachments.length > 0 || form.deleted_attachment_ids.length > 0
);

const beforeUnloadHandler = (e: BeforeUnloadEvent) => {
    if (formTouched.value && !submitting.value) {
        e.preventDefault();
        e.returnValue = '';
    }
};

onMounted(() => {
    window.addEventListener('beforeunload', beforeUnloadHandler);
    const cleanup = router.on('before', () => {
        if (formTouched.value && !submitting.value) {
            return confirm('Vous avez des modifications non sauvegardées. Quitter la page quand même ?');
        }
    });
    onUnmounted(cleanup);
});
onUnmounted(() => window.removeEventListener('beforeunload', beforeUnloadHandler));

const addFiles = (files: FileList | null) => {
    if (!files) return;
    const oversizedFiles: string[] = [];

    for (const file of Array.from(files)) {
        if (file.size > MAX_FILE_SIZE_BYTES) {
            oversizedFiles.push(`${file.name} (${formatSize(file.size)})`);
            continue;
        }

        if (!form.attachments.some((f) => f.name === file.name && f.size === file.size))
            form.attachments.push(file);
    }

    uploadError.value = oversizedFiles.length > 0
        ? `Fichier${oversizedFiles.length > 1 ? 's' : ''} refusé${oversizedFiles.length > 1 ? 's' : ''} : ${oversizedFiles.join(', ')}. Taille maximale : 10 Mo par fichier.`
        : '';
};

const removeNewFile = (i: number) => form.attachments.splice(i, 1);

const removeExisting = (att: DisbursementRequestAttachment) => {
    form.deleted_attachment_ids.push(att.id);
    existingAttachments.value = existingAttachments.value.filter((a) => a.id !== att.id);
};

const onDrop = (e: DragEvent) => {
    dragOver.value = false;
    addFiles(e.dataTransfer?.files ?? null);
};

const onFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    addFiles(input.files);
    input.value = '';
};

const formatSize = (bytes: number) => {
    if (bytes < 1024) return `${bytes} o`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`;
    return `${(bytes / 1024 / 1024).toFixed(1)} Mo`;
};

const submit = (andSubmit: boolean) => {
    form.and_submit = andSubmit;
    submitting.value = true;
    form.post(route('disbursement-requests.update', props.order.uuid), {
        forceFormData: true,
        _method: 'PUT',
        onFinish: () => { submitting.value = false; },
    } as any);
};
</script>

<template>
    <Head :title="`Modifier — ${order.reference}`" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex w-full flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link
                    :href="route('disbursement-requests.show', order.uuid)"
                    class="rounded-xl p-2 text-muted-foreground transition-colors hover:bg-muted"
                >
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Modifier la demande</h1>
                    <p class="font-mono text-xs text-muted-foreground">{{ order.reference }}</p>
                </div>
            </div>

            <!-- Boutique -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                        <Store class="h-5 w-5 text-blue-600" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Boutique émettrice</p>
                        <template v-if="order.boutique && !isAdmin">
                            <p class="text-base font-semibold text-foreground">{{ order.boutique.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ order.boutique.code }}
                                <template v-if="order.boutique.city"> · {{ order.boutique.city }}</template>
                            </p>
                        </template>
                        <template v-else>
                            <div class="relative mt-2">
                                <select
                                    v-model="form.boutique_id"
                                    class="h-10 w-full appearance-none rounded-xl border border-input bg-background px-4 pr-10 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    :class="{ 'border-red-400': form.errors.boutique_id }"
                                >
                                    <option value="">— Aucune boutique —</option>
                                    <option v-for="b in boutiques" :key="b.id" :value="String(b.id)">
                                        {{ b.name }}<template v-if="b.city"> · {{ b.city }}</template>
                                    </option>
                                </select>
                                <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            </div>
                            <p v-if="form.errors.boutique_id" class="mt-1 text-xs text-red-500">{{ form.errors.boutique_id }}</p>
                        </template>
                    </div>
                </div>
            </div>

            <form class="flex flex-col gap-5" @submit.prevent="submit(false)">

                <!-- Informations générales -->
                <div class="flex flex-col gap-4 rounded-2xl border bg-card p-6 shadow-sm">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Informations générales</h2>

                    <!-- Nature -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="nature">
                            Nature de l'opération <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select
                                id="nature"
                                v-model="form.nature_operation_id"
                                class="h-10 w-full appearance-none rounded-xl border border-input bg-background px-4 pr-10 text-sm text-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                :class="{ 'border-red-400': form.errors.nature_operation_id }"
                            >
                                <option value="">— Choisir une nature —</option>
                                <option v-for="n in natureOperations" :key="n.id" :value="String(n.id)">{{ n.name }}</option>
                            </select>
                            <ChevronDown class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        </div>
                        <p v-if="selectedNature?.description" class="text-xs text-muted-foreground">{{ selectedNature.description }}</p>
                        <p v-if="form.errors.nature_operation_id" class="text-xs text-red-500">{{ form.errors.nature_operation_id }}</p>
                    </div>

                    <!-- Titre -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="title">
                            Titre / Objet <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="title"
                            v-model="form.title"
                            type="text"
                            placeholder="Ex : Frais de mission Abidjan mars 2025"
                            class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground placeholder:text-muted-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                            :class="{ 'border-red-400': form.errors.title }"
                        />
                        <p v-if="form.errors.title" class="text-xs text-red-500">{{ form.errors.title }}</p>
                    </div>

                    <!-- Montant -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="amount">
                            Montant (FCFA) <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-sm font-medium text-muted-foreground">FCFA</span>
                            <input
                                id="amount"
                                v-model="form.amount"
                                type="number"
                                min="1"
                                step="1"
                                placeholder="0"
                                class="h-10 w-full rounded-xl border border-input bg-background pl-16 pr-4 text-sm text-foreground placeholder:text-muted-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                :class="{ 'border-red-400': form.errors.amount }"
                            />
                        </div>
                        <p v-if="form.errors.amount" class="text-xs text-red-500">{{ form.errors.amount }}</p>
                    </div>

                    <!-- Description -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="description">Description / Justification</label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            placeholder="Décrivez l'objet de la demande et les justifications nécessaires..."
                            class="w-full resize-none rounded-xl border border-input bg-background px-4 py-3 text-sm text-foreground placeholder:text-muted-foreground transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                        />
                    </div>
                </div>

                <!-- Pièces jointes -->
                <div class="flex flex-col gap-4 rounded-2xl border bg-card p-6 shadow-sm">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Pièces jointes</h2>
                        <p class="mt-1 text-xs text-muted-foreground">PDF, images, Word · 10 Mo max par fichier</p>
                    </div>

                    <!-- Fichiers existants -->
                    <div v-if="existingAttachments.length > 0" class="flex flex-col gap-2">
                        <p class="text-xs font-medium text-muted-foreground">Fichiers actuels</p>
                        <div
                            v-for="att in existingAttachments"
                            :key="att.id"
                            class="flex items-center justify-between rounded-xl border bg-muted/30 px-4 py-3"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50">
                                    <FileText class="h-4 w-4 text-red-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-foreground">{{ att.file_name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatSize(att.file_size) }}</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="ml-2 rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-600"
                                title="Supprimer ce fichier"
                                @click="removeExisting(att)"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Zone drag & drop -->
                    <div
                        class="relative cursor-pointer rounded-xl border-2 border-dashed p-8 text-center transition-colors"
                        :class="uploadError
                            ? 'border-red-300 bg-red-50/60'
                            : dragOver
                                ? 'border-primary bg-primary/5'
                                : 'border-border hover:border-primary/50 hover:bg-muted/30'"
                        @dragover.prevent="dragOver = true"
                        @dragleave="dragOver = false"
                        @drop.prevent="onDrop"
                        @click="fileInput?.click()"
                    >
                        <input
                            ref="fileInput"
                            type="file"
                            multiple
                            accept=".pdf,.doc,.docx,.png,.jpg,.jpeg"
                            class="hidden"
                            @change="onFileChange"
                        />
                        <div class="flex flex-col items-center gap-3">
                            <div class="rounded-xl bg-muted p-3">
                                <Upload class="h-6 w-6 text-muted-foreground" />
                            </div>
                            <div>
                                <p class="text-sm font-medium text-foreground">Glissez vos fichiers ici</p>
                                <p class="mt-1 text-xs text-muted-foreground">ou <span class="font-medium text-primary">cliquez pour parcourir</span></p>
                            </div>
                        </div>
                    </div>

                    <p v-if="uploadError" class="flex items-start gap-2 text-xs text-red-600">
                        <AlertCircle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                        <span>{{ uploadError }}</span>
                    </p>

                    <!-- Nouveaux fichiers à ajouter -->
                    <div v-if="form.attachments.length > 0" class="flex flex-col gap-2">
                        <p class="text-xs font-medium text-muted-foreground">Nouveaux fichiers à ajouter</p>
                        <div
                            v-for="(file, i) in form.attachments"
                            :key="i"
                            class="flex items-center justify-between rounded-xl border border-dashed border-primary/30 bg-primary/5 px-4 py-3"
                        >
                            <div class="flex min-w-0 items-center gap-3">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-primary/10">
                                    <FileText class="h-4 w-4 text-primary" />
                                </div>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium text-foreground">{{ file.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatSize(file.size) }}</p>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="ml-2 rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-600"
                                @click.stop="removeNewFile(i)"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Erreur pièces jointes -->
                    <p v-if="form.errors.attachments" class="flex items-center gap-1 text-xs text-red-500">
                        <AlertCircle class="h-3.5 w-3.5" /> {{ form.errors.attachments }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <Link
                        :href="route('disbursement-requests.show', order.uuid)"
                        class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                    >
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl border border-primary/30 bg-primary/10 px-5 py-2.5 text-sm font-semibold text-primary shadow-sm transition-colors hover:bg-primary/20 disabled:opacity-50"
                    >
                        <Loader2 v-if="form.processing && !form.and_submit" class="h-4 w-4 animate-spin" />
                        <Pencil v-else class="h-4 w-4" />
                        Enregistrer
                    </button>
                    <button
                        type="button"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-50"
                        @click="submit(true)"
                    >
                        <Loader2 v-if="form.processing && form.and_submit" class="h-4 w-4 animate-spin" />
                        <Send v-else class="h-4 w-4" />
                        Enregistrer et soumettre
                    </button>
                </div>

            </form>
        </div>
    </AppLayout>
</template>
