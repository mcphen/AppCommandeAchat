<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Fournisseur } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import axios from 'axios';
import {
    AlertTriangle,
    CheckCircle2,
    ChevronRight,
    Download,
    Eye,
    FileSpreadsheet,
    Mail,
    MapPin,
    Pencil,
    Phone,
    Plus,
    ShieldAlert,
    ShieldCheck,
    Trash2,
    Truck,
    Upload,
    X,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{ fournisseurs: Fournisseur[] }>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Fournisseurs', href: '/admin/fournisseurs' },
];

const deleting = ref<number | null>(null);

const approvedCount = computed(() => props.fournisseurs.filter((f) => f.is_approved).length);
const pendingApprovalCount = computed(() => props.fournisseurs.filter((f) => !f.is_approved && f.is_active).length);
const inactiveCount = computed(() => props.fournisseurs.filter((f) => !f.is_active).length);

const destroy = (id: number) => {
    if (!confirm('Supprimer ce fournisseur ?')) return;
    deleting.value = id;
    router.delete(route('admin.fournisseurs.destroy', id), {
        onFinish: () => (deleting.value = null),
    });
};

// ─── Import modal ────────────────────────────────────────────────────────────
type Step = 'upload' | 'preview' | 'result';

interface PreviewRow {
    row: number;
    data: (string | null)[];
    exists: boolean;
    errors: string[];
}

interface ParseResult {
    temp_key: string;
    extension: string;
    headers: string[];
    preview: PreviewRow[];
    total_rows: number;
}

interface ImportResult {
    created: number;
    updated: number;
    errors: { row: number; message: string }[];
}

const showModal = ref(false);
const step = ref<Step>('upload');

const fileInput = ref<HTMLInputElement | null>(null);
const selectedFile = ref<File | null>(null);
const dragOver = ref(false);

const uploading = ref(false);
const parseResult = ref<ParseResult | null>(null);
const parseError = ref('');

const confirming = ref(false);
const importResult = ref<ImportResult | null>(null);

const openModal = () => {
    showModal.value = true;
    step.value = 'upload';
    selectedFile.value = null;
    parseResult.value = null;
    parseError.value = '';
    importResult.value = null;
};

const closeModal = () => {
    if (uploading.value || confirming.value) return;
    showModal.value = false;
};

const onFileChange = (e: Event) => {
    const input = e.target as HTMLInputElement;
    if (input.files?.[0]) selectedFile.value = input.files[0];
};

const onDrop = (e: DragEvent) => {
    dragOver.value = false;
    const file = e.dataTransfer?.files[0];
    if (file) selectedFile.value = file;
};

const uploadAndParse = async () => {
    if (!selectedFile.value) return;
    uploading.value = true;
    parseError.value = '';

    const form = new FormData();
    form.append('file', selectedFile.value);

    try {
        const { data } = await axios.post(route('admin.fournisseurs.import.parse'), form, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });
        parseResult.value = data;
        step.value = 'preview';
    } catch (err: any) {
        parseError.value = err?.response?.data?.message ?? 'Erreur lors de la lecture du fichier.';
    } finally {
        uploading.value = false;
    }
};

const confirmImport = async () => {
    if (!parseResult.value) return;
    confirming.value = true;

    try {
        const { data } = await axios.post(route('admin.fournisseurs.import.confirm'), {
            temp_key:  parseResult.value.temp_key,
            extension: parseResult.value.extension,
        });
        importResult.value = data;
        step.value = 'result';
    } catch (err: any) {
        parseError.value = err?.response?.data?.message ?? 'Erreur lors de l\'import.';
    } finally {
        confirming.value = false;
    }
};

const finishImport = () => {
    showModal.value = false;
    router.reload();
};
</script>

<template>
    <Head title="Fournisseurs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Fournisseurs</h1>
                    <p class="text-sm text-muted-foreground">Gerez l'homologation, les contacts et l'historique d'achat de vos partenaires.</p>
                </div>

                <div
                    class="inline-flex items-center gap-2 self-start rounded-xl border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary"
                >
                    <ShieldCheck class="h-4 w-4" />
                    Administration
                </div>
            </div>

            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <Truck class="h-8 w-8" />
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Referentiel fournisseurs</p>
                            <h2 class="mt-1 text-xl font-semibold text-foreground">Suivi des partenaires d'achat</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Validez les fournisseurs, suivez leur statut et centralisez les informations utiles pour les commandes.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:w-[560px]">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">
                                <Truck class="h-4 w-4" />
                                Fournisseurs
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ fournisseurs.length }} fournisseur{{ fournisseurs.length > 1 ? 's' : '' }} au total
                            </p>
                        </div>

                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                <ShieldCheck class="h-4 w-4" />
                                Homologues
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ approvedCount }} partenaire{{ approvedCount > 1 ? 's' : '' }} valide{{ approvedCount > 1 ? 's' : '' }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                                <ShieldAlert class="h-4 w-4" />
                                A surveiller
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ pendingApprovalCount }} non homologue{{ pendingApprovalCount > 1 ? 's' : '' }} actif{{
                                    pendingApprovalCount > 1 ? 's' : ''
                                }}
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Actions bar -->
            <div class="flex flex-wrap items-center gap-2">
                <Link
                    :href="route('admin.fournisseurs.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Nouveau fournisseur
                </Link>

                <!-- Télécharger modèle -->
                <a
                    :href="route('admin.fournisseurs.template')"
                    class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-700 transition-colors hover:bg-emerald-100 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300 dark:hover:bg-emerald-950/50"
                >
                    <Download class="h-4 w-4" />
                    Modèle Excel
                </a>

                <!-- Importer -->
                <button
                    @click="openModal"
                    class="inline-flex items-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-700 transition-colors hover:bg-violet-100 dark:border-violet-900 dark:bg-violet-950/30 dark:text-violet-300 dark:hover:bg-violet-950/50"
                >
                    <Upload class="h-4 w-4" />
                    Importer
                </button>
            </div>

            <section v-if="fournisseurs.length > 0" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Total</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ fournisseurs.length }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">fournisseur{{ fournisseurs.length > 1 ? 's' : '' }}</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Homologues</p>
                    <p class="mt-2 text-2xl font-bold text-emerald-600 dark:text-emerald-300">{{ approvedCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">prets pour les commandes</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Non homologues</p>
                    <p class="mt-2 text-2xl font-bold text-amber-600 dark:text-amber-300">{{ pendingApprovalCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">fournisseurs actifs a valider</p>
                </div>

                <div class="rounded-2xl border bg-card p-4 shadow-sm">
                    <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Inactifs</p>
                    <p class="mt-2 text-2xl font-bold text-foreground">{{ inactiveCount }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">hors utilisation courante</p>
                </div>
            </section>

            <EmptyState
                v-if="fournisseurs.length === 0"
                :icon="Truck"
                icon-bg="bg-violet-50 dark:bg-violet-950/30"
                icon-color="text-violet-500 dark:text-violet-300"
                title="Aucun fournisseur"
                description="Ajoutez et approuvez vos fournisseurs pour les associer aux bons de commande et generer les ecritures comptables."
                :action-href="route('admin.fournisseurs.create')"
                action-label="Ajouter un fournisseur"
            />

            <section v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="f in fournisseurs"
                    :key="f.id"
                    class="flex flex-col gap-4 rounded-2xl border bg-card p-5 shadow-sm transition-shadow hover:shadow-md"
                    :class="{ 'border-amber-200 dark:border-amber-900': !f.is_approved && f.is_active }"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                :class="
                                    f.is_approved
                                        ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/30 dark:text-emerald-300'
                                        : 'bg-amber-50 text-amber-600 dark:bg-amber-950/30 dark:text-amber-300'
                                "
                            >
                                <Truck class="h-5 w-5" />
                            </div>

                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-foreground">{{ f.name }}</p>
                                <p class="font-mono text-xs text-muted-foreground">{{ f.code }}</p>
                            </div>
                        </div>

                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <span
                                v-if="f.is_approved"
                                class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300"
                            >
                                <ShieldCheck class="h-3 w-3" />
                                Homologue
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-1 rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300"
                            >
                                <ShieldAlert class="h-3 w-3" />
                                Non homologue
                            </span>

                            <span
                                class="rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="
                                    f.is_active ? 'bg-blue-50 text-blue-600 dark:bg-blue-950/30 dark:text-blue-300' : 'bg-muted text-muted-foreground'
                                "
                            >
                                {{ f.is_active ? 'Actif' : 'Inactif' }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="!f.is_approved && f.is_active"
                        class="flex items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-medium text-amber-700 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300"
                    >
                        <AlertTriangle class="h-3.5 w-3.5 shrink-0" />
                        Ce fournisseur n'est pas encore homologue.
                    </div>

                    <div class="grid gap-2 rounded-2xl border bg-muted/20 p-4">
                        <div v-if="f.email" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Mail class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ f.email }}</span>
                        </div>
                        <div v-if="f.phone" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <Phone class="h-4 w-4 shrink-0" />
                            <span>{{ f.phone }}</span>
                        </div>
                        <div v-if="f.city" class="flex items-center gap-2 text-sm text-muted-foreground">
                            <MapPin class="h-4 w-4 shrink-0" />
                            <span>{{ f.city }}</span>
                        </div>
                    </div>

                    <div class="rounded-2xl border bg-muted/20 p-4">
                        <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Activite</p>
                        <p class="mt-2 text-sm font-semibold text-foreground">
                            {{ f.order_lines_count }} ligne{{ (f.order_lines_count ?? 0) > 1 ? 's' : '' }} de commande
                        </p>
                    </div>

                    <div class="flex items-center justify-end gap-1 border-t border-border/50 pt-2">
                        <Link
                            :href="route('admin.fournisseurs.show', f.id)"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 transition-colors hover:bg-blue-100 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300 dark:hover:bg-blue-950/50"
                            title="Historique des achats"
                        >
                            <Eye class="h-3.5 w-3.5" />
                            Voir
                        </Link>
                        <Link
                            :href="route('admin.fournisseurs.edit', f.id)"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300 dark:hover:bg-amber-950/50"
                        >
                            <Pencil class="h-3.5 w-3.5" />
                            Modifier
                        </Link>
                        <button
                            @click="destroy(f.id)"
                            :disabled="deleting === f.id"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-700 transition-colors hover:bg-red-100 disabled:opacity-50 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300 dark:hover:bg-red-950/50"
                        >
                            <Trash2 class="h-3.5 w-3.5" />
                            Supprimer
                        </button>
                    </div>
                </article>
            </section>
        </div>

        <!-- ─── Modal Import ─────────────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition name="modal">
                <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <!-- Backdrop -->
                    <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal" />

                    <div class="relative z-10 w-full max-w-2xl rounded-2xl border bg-card shadow-2xl">
                        <!-- Header -->
                        <div class="flex items-center justify-between border-b px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-violet-100 dark:bg-violet-950/40">
                                    <FileSpreadsheet class="h-5 w-5 text-violet-600 dark:text-violet-300" />
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-foreground">Importer des fournisseurs</h2>
                                    <p class="text-xs text-muted-foreground">Fichier Excel (.xlsx, .xls) ou CSV</p>
                                </div>
                            </div>
                            <button
                                @click="closeModal"
                                :disabled="uploading || confirming"
                                class="rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground disabled:opacity-40"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Stepper -->
                        <div class="flex items-center gap-1 border-b px-6 py-3">
                            <template v-for="(s, i) in (['upload', 'preview', 'result'] as const)" :key="s">
                                <div
                                    class="flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold transition-colors"
                                    :class="
                                        step === s
                                            ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300'
                                            : (['upload', 'preview', 'result'].indexOf(step) > i)
                                              ? 'text-emerald-600 dark:text-emerald-400'
                                              : 'text-muted-foreground'
                                    "
                                >
                                    <CheckCircle2
                                        v-if="['upload', 'preview', 'result'].indexOf(step) > i"
                                        class="h-3.5 w-3.5"
                                    />
                                    <span v-else class="flex h-4 w-4 items-center justify-center rounded-full border text-[10px]">{{ i + 1 }}</span>
                                    {{ s === 'upload' ? 'Fichier' : s === 'preview' ? 'Prévisualisation' : 'Résultat' }}
                                </div>
                                <ChevronRight v-if="i < 2" class="h-3.5 w-3.5 text-muted-foreground/40" />
                            </template>
                        </div>

                        <!-- Body -->
                        <div class="max-h-[60vh] overflow-y-auto px-6 py-5">

                            <!-- ÉTAPE 1 : Upload -->
                            <template v-if="step === 'upload'">
                                <!-- Zone drag & drop -->
                                <div
                                    class="relative flex flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed p-10 text-center transition-colors"
                                    :class="dragOver ? 'border-violet-400 bg-violet-50 dark:bg-violet-950/20' : 'border-border hover:border-violet-300 hover:bg-muted/30'"
                                    @dragover.prevent="dragOver = true"
                                    @dragleave="dragOver = false"
                                    @drop.prevent="onDrop"
                                    @click="fileInput?.click()"
                                >
                                    <input ref="fileInput" type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="onFileChange" />
                                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-violet-100 dark:bg-violet-950/40">
                                        <Upload class="h-7 w-7 text-violet-600 dark:text-violet-300" />
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-foreground">
                                            {{ selectedFile ? selectedFile.name : 'Glissez votre fichier ici' }}
                                        </p>
                                        <p class="mt-1 text-xs text-muted-foreground">
                                            {{ selectedFile ? `${(selectedFile.size / 1024).toFixed(1)} Ko` : 'ou cliquez pour parcourir (.xlsx, .xls, .csv)' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Colonnes attendues -->
                                <div class="mt-4 rounded-xl border bg-muted/20 p-4">
                                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Colonnes attendues dans le fichier</p>
                                    <div class="flex flex-wrap gap-2">
                                        <span v-for="col in ['nom *', 'code *', 'email', 'telephone', 'adresse', 'ville', 'actif', 'homologue']" :key="col"
                                            class="rounded-lg border px-2.5 py-1 font-mono text-xs"
                                            :class="col.includes('*') ? 'border-violet-200 bg-violet-50 text-violet-700 dark:border-violet-900 dark:bg-violet-950/30 dark:text-violet-300' : 'border-border bg-muted/30 text-muted-foreground'"
                                        >
                                            {{ col }}
                                        </span>
                                    </div>
                                    <p class="mt-2 text-xs text-muted-foreground">* Champs obligatoires. Pour actif/homologue : oui/non ou 1/0.</p>
                                </div>

                                <!-- Erreur -->
                                <div v-if="parseError" class="mt-4 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                                    <XCircle class="mt-0.5 h-4 w-4 shrink-0" />
                                    {{ parseError }}
                                </div>
                            </template>

                            <!-- ÉTAPE 2 : Prévisualisation -->
                            <template v-if="step === 'preview' && parseResult">
                                <div class="mb-4 flex items-center justify-between">
                                    <p class="text-sm text-muted-foreground">
                                        <span class="font-semibold text-foreground">{{ parseResult.total_rows }}</span> ligne{{ parseResult.total_rows > 1 ? 's' : '' }} détectée{{ parseResult.total_rows > 1 ? 's' : '' }}
                                        <span class="text-xs">(aperçu des 10 premières)</span>
                                    </p>
                                </div>

                                <div class="overflow-x-auto rounded-xl border">
                                    <table class="w-full text-xs">
                                        <thead>
                                            <tr class="border-b bg-muted/40">
                                                <th class="px-3 py-2 text-left font-semibold text-muted-foreground">#</th>
                                                <th v-for="h in parseResult.headers" :key="h" class="px-3 py-2 text-left font-semibold text-muted-foreground">{{ h }}</th>
                                                <th class="px-3 py-2 text-left font-semibold text-muted-foreground">Statut</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="row in parseResult.preview"
                                                :key="row.row"
                                                class="border-b last:border-0"
                                                :class="row.errors.length ? 'bg-red-50/50 dark:bg-red-950/10' : row.exists ? 'bg-amber-50/50 dark:bg-amber-950/10' : ''"
                                            >
                                                <td class="px-3 py-2 font-mono text-muted-foreground">{{ row.row }}</td>
                                                <td v-for="(cell, ci) in row.data" :key="ci" class="max-w-[140px] truncate px-3 py-2 text-foreground">
                                                    {{ cell ?? '—' }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    <span v-if="row.errors.length" class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold text-red-700 dark:bg-red-950/40 dark:text-red-300">
                                                        <XCircle class="h-3 w-3" /> Erreur
                                                    </span>
                                                    <span v-else-if="row.exists" class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                                        Mise à jour
                                                    </span>
                                                    <span v-else class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                        <CheckCircle2 class="h-3 w-3" /> Nouveau
                                                    </span>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <!-- Erreurs détaillées -->
                                <div v-if="parseResult.preview.some(r => r.errors.length)" class="mt-3 space-y-1">
                                    <p class="text-xs font-semibold text-red-600">Problèmes détectés :</p>
                                    <ul class="space-y-1">
                                        <li
                                            v-for="row in parseResult.preview.filter(r => r.errors.length)"
                                            :key="row.row"
                                            class="flex items-start gap-1.5 text-xs text-red-600 dark:text-red-400"
                                        >
                                            <XCircle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                            Ligne {{ row.row }} : {{ row.errors.join(', ') }}
                                        </li>
                                    </ul>
                                </div>

                                <!-- Erreur confirm -->
                                <div v-if="parseError" class="mt-4 flex items-start gap-2 rounded-xl border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300">
                                    <XCircle class="mt-0.5 h-4 w-4 shrink-0" />
                                    {{ parseError }}
                                </div>
                            </template>

                            <!-- ÉTAPE 3 : Résultat -->
                            <template v-if="step === 'result' && importResult">
                                <div class="flex flex-col items-center gap-4 py-4 text-center">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-2xl bg-emerald-100 dark:bg-emerald-950/40">
                                        <CheckCircle2 class="h-8 w-8 text-emerald-600 dark:text-emerald-400" />
                                    </div>
                                    <div>
                                        <p class="text-lg font-semibold text-foreground">Import terminé</p>
                                        <p class="mt-1 text-sm text-muted-foreground">Les fournisseurs ont été importés avec succès.</p>
                                    </div>

                                    <div class="flex w-full gap-3">
                                        <div class="flex-1 rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/20">
                                            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ importResult.created }}</p>
                                            <p class="text-xs text-muted-foreground">créé{{ importResult.created > 1 ? 's' : '' }}</p>
                                        </div>
                                        <div class="flex-1 rounded-2xl border border-amber-100 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/20">
                                            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">{{ importResult.updated }}</p>
                                            <p class="text-xs text-muted-foreground">mis à jour</p>
                                        </div>
                                        <div v-if="importResult.errors.length" class="flex-1 rounded-2xl border border-red-100 bg-red-50 p-4 dark:border-red-900 dark:bg-red-950/20">
                                            <p class="text-2xl font-bold text-red-600 dark:text-red-400">{{ importResult.errors.length }}</p>
                                            <p class="text-xs text-muted-foreground">erreur{{ importResult.errors.length > 1 ? 's' : '' }}</p>
                                        </div>
                                    </div>

                                    <!-- Erreurs import -->
                                    <div v-if="importResult.errors.length" class="w-full rounded-xl border bg-muted/20 p-3 text-left">
                                        <p class="mb-2 text-xs font-semibold text-red-600">Lignes ignorées :</p>
                                        <ul class="max-h-32 space-y-1 overflow-y-auto">
                                            <li
                                                v-for="err in importResult.errors"
                                                :key="err.row"
                                                class="flex items-start gap-1.5 text-xs text-red-600 dark:text-red-400"
                                            >
                                                <XCircle class="mt-0.5 h-3.5 w-3.5 shrink-0" />
                                                Ligne {{ err.row }} : {{ err.message }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <!-- Footer -->
                        <div class="flex items-center justify-end gap-3 border-t px-6 py-4">
                            <template v-if="step === 'upload'">
                                <button
                                    @click="closeModal"
                                    class="rounded-xl border px-4 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted"
                                >
                                    Annuler
                                </button>
                                <button
                                    @click="uploadAndParse"
                                    :disabled="!selectedFile || uploading"
                                    class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-violet-700 disabled:opacity-50"
                                >
                                    <span v-if="uploading" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white" />
                                    <Upload v-else class="h-4 w-4" />
                                    {{ uploading ? 'Analyse en cours...' : 'Analyser le fichier' }}
                                </button>
                            </template>

                            <template v-if="step === 'preview'">
                                <button
                                    @click="step = 'upload'; parseError = ''"
                                    :disabled="confirming"
                                    class="rounded-xl border px-4 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted disabled:opacity-50"
                                >
                                    Retour
                                </button>
                                <button
                                    @click="confirmImport"
                                    :disabled="confirming"
                                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2 text-sm font-semibold text-white transition-colors hover:bg-emerald-700 disabled:opacity-50"
                                >
                                    <span v-if="confirming" class="h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white" />
                                    <CheckCircle2 v-else class="h-4 w-4" />
                                    {{ confirming ? 'Import en cours...' : 'Confirmer l\'import' }}
                                </button>
                            </template>

                            <template v-if="step === 'result'">
                                <button
                                    @click="finishImport"
                                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground transition-colors hover:bg-primary/90"
                                >
                                    Terminer
                                </button>
                            </template>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>
    </AppLayout>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
    transition: opacity 0.2s ease;
}
.modal-enter-from,
.modal-leave-to {
    opacity: 0;
}
.modal-enter-active .relative.z-10,
.modal-leave-active .relative.z-10 {
    transition: transform 0.2s ease, opacity 0.2s ease;
}
.modal-enter-from .relative.z-10,
.modal-leave-to .relative.z-10 {
    transform: scale(0.96) translateY(8px);
    opacity: 0;
}
</style>
