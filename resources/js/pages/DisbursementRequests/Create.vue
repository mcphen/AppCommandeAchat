<script setup lang="ts">
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem, type Company, type NatureOperation, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { AlertCircle, AlertTriangle, ArrowLeft, Building2, ChevronDown, FileText, Loader2, Plus, Send, Store, Upload, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Demandes de décaissement', href: route('disbursement-requests.index') },
    { title: 'Nouvelle demande', href: '#' },
];

const props = defineProps<{
    boutique?: Boutique | null;
    boutiques: Boutique[];
    companies: Company[];
    natureOperations: NatureOperation[];
}>();

const page = usePage<SharedData>();
const isAdmin = computed(() => page.props.auth.user?.role?.slug === 'admin');
const natureOperations = ref<NatureOperation[]>([...props.natureOperations]);

const form = useForm({
    nature_operation_id: '',
    title: '',
    description: '',
    amount: '',
    boutique_id: props.boutique?.id ? String(props.boutique.id) : '',
    company_id: props.companies.length === 1 ? String(props.companies[0].id) : '',
    attachments: [] as File[],
    and_submit: false as boolean,
});

const fileInput = ref<HTMLInputElement | null>(null);
const dragOver = ref(false);
const submitting = ref(false);
const uploadError = ref('');
const showNatureModal = ref(false);
const natureProcessing = ref(false);
const natureForm = ref({
    name: '',
    description: '',
});
const natureErrors = ref<Record<string, string>>({});
const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024;

const selectedNature = computed(() =>
    natureOperations.value.find((n) => String(n.id) === form.nature_operation_id)
);

const formTouched = computed(() =>
    !!form.title || !!form.description || !!form.amount || form.attachments.length > 0
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

onUnmounted(() => {
    window.removeEventListener('beforeunload', beforeUnloadHandler);
});

const sortNatureOperations = (items: NatureOperation[]) =>
    [...items].sort((left, right) => left.name.localeCompare(right.name, 'fr', { sensitivity: 'base' }));

const openNatureModal = () => {
    natureForm.value = { name: '', description: '' };
    natureErrors.value = {};
    showNatureModal.value = true;
};

const closeNatureModal = () => {
    if (natureProcessing.value) {
        return;
    }

    showNatureModal.value = false;
    natureForm.value = { name: '', description: '' };
    natureErrors.value = {};
};

const submitNature = async () => {
    natureProcessing.value = true;
    natureErrors.value = {};

    try {
        const { data } = await axios.post<{ natureOperation: NatureOperation }>(
            route('disbursement-requests.nature-operations.store'),
            natureForm.value,
        );

        natureOperations.value = sortNatureOperations([
            ...natureOperations.value.filter((nature) => nature.id !== data.natureOperation.id),
            data.natureOperation,
        ]);
        form.nature_operation_id = String(data.natureOperation.id);
        closeNatureModal();
    } catch (error) {
        if (axios.isAxiosError(error) && error.response?.status === 422) {
            const errors = (error.response.data?.errors ?? {}) as Record<string, string[]>;

            natureErrors.value = Object.fromEntries(
                Object.entries(errors).map(([field, messages]) => [field, messages[0] ?? 'Champ invalide']),
            );

            return;
        }

        natureErrors.value = {
            general: 'La création de la nature a échoué. Réessayez.',
        };
    } finally {
        natureProcessing.value = false;
    }
};

const addFiles = (files: FileList | null) => {
    if (!files) return;
    const oversizedFiles: string[] = [];

    for (const file of Array.from(files)) {
        if (file.size > MAX_FILE_SIZE_BYTES) {
            oversizedFiles.push(`${file.name} (${formatSize(file.size)})`);
            continue;
        }

        if (!form.attachments.some((f) => f.name === file.name && f.size === file.size)) {
            form.attachments.push(file);
        }
    }

    uploadError.value = oversizedFiles.length > 0
        ? `Fichier${oversizedFiles.length > 1 ? 's' : ''} refusé${oversizedFiles.length > 1 ? 's' : ''} : ${oversizedFiles.join(', ')}. Taille maximale : 10 Mo par fichier.`
        : '';
};

const removeFile = (index: number) => {
    form.attachments.splice(index, 1);
};

const onFileChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    addFiles(input.files);
    input.value = '';
};

const onDrop = (e: DragEvent) => {
    dragOver.value = false;
    addFiles(e.dataTransfer?.files ?? null);
};

const formatSize = (bytes: number) => {
    if (bytes < 1024) return `${bytes} o`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} Ko`;
    return `${(bytes / 1024 / 1024).toFixed(1)} Mo`;
};

const submit = (andSubmit: boolean) => {
    form.and_submit = andSubmit;
    submitting.value = true;
    form.post(route('disbursement-requests.store'), {
        forceFormData: true,
        onFinish: () => { submitting.value = false; },
    });
};
</script>

<template>
    <Head title="Nouvelle demande de décaissement" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 w-full sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center gap-4">
                <Link :href="route('disbursement-requests.index')"
                    class="rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Nouvelle demande de décaissement</h1>
                    <p class="text-sm text-muted-foreground">Remplissez les informations de votre demande</p>
                </div>
            </div>

            <!-- Alert danger : pas de boutique rattachée -->
            <div v-if="!props.boutique && !isAdmin"
                class="rounded-2xl border border-red-300 bg-red-50 p-4 flex items-start gap-3">
                <AlertTriangle class="h-5 w-5 text-red-600 shrink-0 mt-0.5" />
                <div>
                    <p class="text-sm font-semibold text-red-800">Aucune boutique rattachée à votre compte</p>
                    <p class="text-xs text-red-700 mt-0.5">
                        Contactez un administrateur pour vous rattacher à une boutique avant de soumettre une demande.
                    </p>
                </div>
            </div>

            <!-- Boutique -->
            <div class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                        <Store class="h-5 w-5 text-blue-600" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Boutique émettrice</p>
                        <template v-if="props.boutique && !isAdmin">
                            <p class="text-base font-semibold text-foreground">{{ props.boutique.name }}</p>
                            <p class="text-sm text-muted-foreground">
                                {{ props.boutique.code }}
                                <template v-if="props.boutique.city"> · {{ props.boutique.city }}</template>
                            </p>
                        </template>
                        <template v-else>
                            <div class="relative mt-2">
                                <select v-model="form.boutique_id"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-4 pr-10 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors appearance-none"
                                    :class="{ 'border-red-400': form.errors.boutique_id }">
                                    <option value="">— Aucune boutique —</option>
                                    <option v-for="b in props.boutiques" :key="b.id" :value="String(b.id)">
                                        {{ b.name }}<template v-if="b.city"> · {{ b.city }}</template>
                                    </option>
                                </select>
                                <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                            </div>
                            <p v-if="form.errors.boutique_id" class="text-xs text-red-500 mt-1">{{ form.errors.boutique_id }}</p>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Entreprise émettrice -->
            <div v-if="props.companies.length > 0" class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex items-start gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-purple-50">
                        <Building2 class="h-5 w-5 text-purple-600" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Entreprise émettrice</p>
                        <template v-if="props.companies.length === 1">
                            <p class="text-base font-semibold text-foreground">{{ props.companies[0].name }}</p>
                        </template>
                        <template v-else>
                            <div class="relative mt-2">
                                <select v-model="form.company_id"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-4 pr-10 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors appearance-none"
                                    :class="{ 'border-red-400': form.errors.company_id }">
                                    <option value="">— Choisir une entreprise —</option>
                                    <option v-for="c in props.companies" :key="c.id" :value="String(c.id)">{{ c.name }}</option>
                                </select>
                                <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
                            </div>
                            <p v-if="form.errors.company_id" class="text-xs text-red-500 mt-1">{{ form.errors.company_id }}</p>
                        </template>
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit(false)" class="flex flex-col gap-5">

                <!-- Nature + Informations générales -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <h2 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Informations générales</h2>

                    <!-- Nature d'opération -->
                    <div class="flex flex-col gap-1.5">
                        <div class="flex items-center justify-between gap-3">
                            <label class="text-sm font-medium text-foreground" for="nature">
                                Nature de l'opération <span class="text-red-500">*</span>
                            </label>
                            <button
                                type="button"
                                class="inline-flex items-center gap-2 rounded-lg border border-primary/20 bg-primary/5 px-3 py-1.5 text-xs font-semibold text-primary transition-colors hover:bg-primary/10"
                                @click="openNatureModal"
                            >
                                <Plus class="h-3.5 w-3.5" />
                                Créer une nature
                            </button>
                        </div>
                        <div class="relative">
                            <select
                                id="nature"
                                v-model="form.nature_operation_id"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 pr-10 text-sm text-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors appearance-none"
                                :class="{ 'border-red-400': form.errors.nature_operation_id }"
                            >
                                <option value="">— Choisir une nature —</option>
                                <option v-for="n in natureOperations" :key="n.id" :value="String(n.id)">{{ n.name }}</option>
                            </select>
                            <ChevronDown class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
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
                            class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
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
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-muted-foreground text-sm font-medium">FCFA</span>
                            <input
                                id="amount"
                                v-model="form.amount"
                                type="number"
                                min="1"
                                step="1"
                                placeholder="0"
                                class="h-10 w-full rounded-xl border border-input bg-background pl-16 pr-4 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.amount }"
                            />
                        </div>
                        <p v-if="form.errors.amount" class="text-xs text-red-500">{{ form.errors.amount }}</p>
                        <p v-else-if="form.amount !== '' && Number(form.amount) <= 0" class="text-xs text-red-500">
                            Le montant doit être supérieur à 0.
                        </p>
                    </div>

                    <!-- Description -->
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="description">
                            Description / Justification
                        </label>
                        <textarea
                            id="description"
                            v-model="form.description"
                            rows="4"
                            placeholder="Décrivez l'objet de la demande et les justifications nécessaires..."
                            class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-none"
                            :class="{ 'border-red-400': form.errors.description }"
                        />
                        <p v-if="form.errors.description" class="text-xs text-red-500">{{ form.errors.description }}</p>
                    </div>
                </div>

                <!-- Pièces jointes -->
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <div>
                        <h2 class="font-semibold text-sm uppercase tracking-wide text-muted-foreground">Pièces jointes</h2>
                        <p class="text-xs text-muted-foreground mt-1">PDF, images, Word · 10 Mo max par fichier</p>
                    </div>

                    <div
                        class="relative rounded-xl border-2 border-dashed p-8 text-center transition-colors cursor-pointer"
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
                                <p class="text-xs text-muted-foreground mt-1">ou <span class="text-primary font-medium">cliquez pour parcourir</span></p>
                            </div>
                        </div>
                    </div>

                    <p v-if="uploadError" class="flex items-start gap-2 text-xs text-red-600">
                        <AlertTriangle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                        <span>{{ uploadError }}</span>
                    </p>

                    <div v-if="form.attachments.length > 0" class="flex flex-col gap-2">
                        <div v-for="(file, i) in form.attachments" :key="i"
                            class="flex items-center justify-between rounded-xl border bg-muted/30 px-4 py-3">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-red-50">
                                    <FileText class="h-4 w-4 text-red-600" />
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-medium text-foreground truncate">{{ file.name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ formatSize(file.size) }}</p>
                                </div>
                            </div>
                            <button type="button" @click.stop="removeFile(i)"
                                class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600">
                                <X class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3 flex-wrap">
                    <Link :href="route('disbursement-requests.index')"
                        class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors">
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl border border-primary/30 bg-primary/10 px-5 py-2.5 text-sm font-semibold text-primary shadow-sm hover:bg-primary/20 transition-colors disabled:opacity-50"
                    >
                        <Loader2 v-if="form.processing && !form.and_submit" class="h-4 w-4 animate-spin" />
                        <FileText v-else class="h-4 w-4" />
                        Enregistrer en brouillon
                    </button>
                    <button
                        type="button"
                        :disabled="form.processing"
                        @click="submit(true)"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:opacity-50"
                    >
                        <Loader2 v-if="form.processing && form.and_submit" class="h-4 w-4 animate-spin" />
                        <Send v-else class="h-4 w-4" />
                        Enregistrer et soumettre
                    </button>
                </div>

            </form>
        </div>

        <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showNatureModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
                @click.self="closeNatureModal"
            >
                <Transition
                    enter-active-class="duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100"
                    leave-to-class="opacity-0 scale-95"
                >
                    <div
                        v-if="showNatureModal"
                        class="w-full max-w-md rounded-2xl border bg-card shadow-xl"
                    >
                        <div class="flex items-center justify-between border-b px-6 py-4">
                            <h2 class="text-base font-semibold text-foreground">Nouvelle nature d'opération</h2>
                            <button
                                type="button"
                                class="rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                @click="closeNatureModal"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <form class="space-y-4 px-6 py-5" @submit.prevent="submitNature">
                            <p class="text-sm text-muted-foreground">
                                Ajoutez une nature sans quitter la demande. Elle sera sélectionnée automatiquement.
                            </p>

                            <div v-if="natureErrors.general" class="flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                                <AlertTriangle class="mt-0.5 h-4 w-4 shrink-0" />
                                <span>{{ natureErrors.general }}</span>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">
                                    Nom <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="natureForm.name"
                                    type="text"
                                    autocomplete="off"
                                    placeholder="Ex : Frais de déplacement"
                                    class="h-10 w-full rounded-xl border border-input bg-white px-3 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    :class="natureErrors.name ? 'border-red-400' : ''"
                                    autofocus
                                />
                                <p v-if="natureErrors.name" class="flex items-center gap-1 text-xs text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5" /> {{ natureErrors.name }}
                                </p>
                            </div>

                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Description</label>
                                <textarea
                                    v-model="natureForm.description"
                                    rows="3"
                                    autocomplete="off"
                                    placeholder="Description optionnelle..."
                                    class="w-full rounded-xl border border-input bg-white px-3 py-2 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    :class="natureErrors.description ? 'border-red-400' : ''"
                                />
                                <p v-if="natureErrors.description" class="flex items-center gap-1 text-xs text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5" /> {{ natureErrors.description }}
                                </p>
                            </div>

                            <div class="flex items-center justify-end gap-3 border-t pt-4">
                                <button
                                    type="button"
                                    class="rounded-xl border px-4 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                                    :disabled="natureProcessing"
                                    @click="closeNatureModal"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-50"
                                    :disabled="natureProcessing"
                                >
                                    <Loader2 v-if="natureProcessing" class="h-4 w-4 animate-spin" />
                                    <Plus v-else class="h-4 w-4" />
                                    Créer la nature
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </AppLayout>
</template>
