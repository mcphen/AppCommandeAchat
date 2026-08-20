<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Circuit, type ValidationLevel } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Loader2, Pencil, Plus, Save, Settings, ShieldCheck, Trash2, Users, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { computed, ref } from 'vue';

const props = defineProps<{
    levels: (ValidationLevel & { validators_count: number })[];
    circuits: Circuit[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Administration', href: '#' },
    { title: 'Niveaux de validation', href: '/admin/validation-levels' },
];

// ── Stats ────────────────────────────────────────────────────────────────────
const totalValidators = computed(() => props.levels.reduce((s, l) => s + (l.validators_count ?? 0), 0));
const levelsWithValidators = computed(() => props.levels.filter(l => l.validators_count > 0).length);
const levelsWithout = computed(() => props.levels.filter(l => l.validators_count === 0).length);

// ── Regroupement par circuit ─────────────────────────────────────────────────
const groupedLevels = computed(() =>
    props.circuits.map(circuit => ({
        circuit,
        levels: props.levels.filter(l => l.circuit_id === circuit.id),
    }))
);

const nextOrderFor = (circuitId: number | null) =>
    !circuitId
        ? 1
        : (Math.max(0, ...props.levels.filter(l => l.circuit_id === circuitId).map(l => l.order)) + 1);

// ── Suppression ──────────────────────────────────────────────────────────────
const deleteLevel = async (level: ValidationLevel) => {
    const result = await Swal.fire({
        title: 'Supprimer ce niveau ?',
        text: `Le niveau "${level.name}" sera définitivement supprimé.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) {
        router.delete(route('admin.validation-levels.destroy', level.id));
    }
};

// ── Modal création / édition ─────────────────────────────────────────────────
const showModal = ref(false);
const editingLevel = ref<(ValidationLevel & { validators_count: number }) | null>(null);

const form = useForm({
    circuit_id: null as number | null,
    name: '',
    order: 1,
    description: '',
    type: 'validation',
});

const openModal = (circuitId?: number) => {
    editingLevel.value = null;
    form.reset();
    form.clearErrors();
    form.circuit_id = circuitId ?? props.circuits[0]?.id ?? null;
    form.order = nextOrderFor(form.circuit_id);
    showModal.value = true;
};

const openEditModal = (level: ValidationLevel & { validators_count: number }) => {
    editingLevel.value = level;
    form.circuit_id   = level.circuit_id;
    form.name         = level.name;
    form.order        = level.order;
    form.description  = level.description ?? '';
    form.type         = level.type;
    form.clearErrors();
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    editingLevel.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    if (editingLevel.value) {
        form.put(route('admin.validation-levels.update', editingLevel.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.validation-levels.store'), {
            onSuccess: () => closeModal(),
        });
    }
};
</script>

<template>
    <Head title="Niveaux de validation" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-7xl space-y-6 px-3 py-4 sm:px-6 sm:py-6">

            <!-- ── Header ────────────────────────────────────────────────── -->
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Circuits de validation</h1>
                    <p class="text-sm text-muted-foreground">Configurez les niveaux d'approbation par circuit (Achat, Prestation...).</p>
                </div>
                <div class="inline-flex items-center gap-2 self-start rounded-xl border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary">
                    <ShieldCheck class="h-4 w-4" />
                    Administration
                </div>
            </div>

            <!-- ── Bannière info ──────────────────────────────────────────── -->
            <section class="rounded-2xl border bg-card p-5 shadow-sm">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                            <Settings class="h-8 w-8" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Workflow d'approbation</p>
                            <h2 class="mt-1 text-xl font-semibold text-foreground">Niveaux de validation</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Chaque circuit (voir Admin &gt; Circuits) a sa propre séquence de niveaux que les commandes doivent franchir.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:w-[480px]">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">
                                <Settings class="h-4 w-4" />
                                Niveaux
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ levels.length }} niveau{{ levels.length > 1 ? 'x' : '' }} configuré{{ levels.length > 1 ? 's' : '' }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                <Users class="h-4 w-4" />
                                Validateurs
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ totalValidators }} utilisateur{{ totalValidators > 1 ? 's' : '' }} assigné{{ totalValidators > 1 ? 's' : '' }}
                            </p>
                        </div>
                        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                                <ShieldCheck class="h-4 w-4" />
                                Couverts
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">
                                {{ levelsWithValidators }} / {{ levels.length }} avec validateurs
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ── État vide global ──────────────────────────────────────── -->
            <EmptyState
                v-if="circuits.length === 0"
                :icon="Settings"
                icon-bg="bg-slate-100 dark:bg-slate-800"
                icon-color="text-slate-500 dark:text-slate-400"
                title="Aucun circuit configuré"
                description="Créez d'abord un circuit (Achat, Prestation de service...) depuis Admin > Circuits avant de lui définir des niveaux."
            />

            <!-- ── Une section par circuit ───────────────────────────────── -->
            <template v-for="group in (circuits.length > 0 ? groupedLevels : [])" :key="group.circuit.id">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-lg font-bold text-foreground">{{ group.circuit.name }}</h2>
                    <button
                        @click="openModal(group.circuit.id)"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                    >
                        <Plus class="h-4 w-4" />
                        Ajouter un niveau
                    </button>
                </div>

                <EmptyState
                    v-if="group.levels.length === 0"
                    :icon="Settings"
                    icon-bg="bg-slate-100 dark:bg-slate-800"
                    icon-color="text-slate-500 dark:text-slate-400"
                    title="Aucun niveau configuré"
                    :description="`Définissez les niveaux d'approbation du circuit « ${group.circuit.name} ».`"
                />

                <template v-else>
                    <!-- ── Circuit visuel ─────────────────────────────────── -->
                    <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                        <div class="border-b bg-muted/30 px-6 py-3.5">
                            <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                                {{ group.circuit.name }} ({{ group.levels.length }} niveau{{ group.levels.length !== 1 ? 'x' : '' }})
                            </p>
                        </div>
                        <div class="p-6">
                            <div class="flex flex-wrap items-center gap-0">
                                <template v-for="(level, idx) in group.levels" :key="level.id">
                                    <div class="flex flex-col items-center">
                                        <div
                                            class="w-44 rounded-2xl border-2 bg-card p-4 text-center transition-all hover:shadow-md"
                                            :class="level.validators_count === 0 ? 'border-amber-200 dark:border-amber-900' : 'border-border hover:border-primary/30'"
                                        >
                                            <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">
                                                {{ level.order }}
                                            </div>
                                            <p class="font-semibold text-foreground text-sm">{{ level.name }}</p>
                                            <span
                                                class="mt-1 inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide"
                                                :class="level.type === 'approbation'
                                                    ? 'bg-violet-100 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300'
                                                    : 'bg-blue-100 text-blue-700 dark:bg-blue-950/30 dark:text-blue-300'"
                                            >
                                                {{ level.type === 'approbation' ? 'Approbation' : 'Validation' }}
                                            </span>
                                            <p v-if="level.description" class="mt-1 text-xs text-muted-foreground line-clamp-2">{{ level.description }}</p>
                                            <div class="mt-3 flex items-center justify-center gap-1 text-xs"
                                                :class="level.validators_count === 0 ? 'text-amber-600 dark:text-amber-300' : 'text-muted-foreground'">
                                                <Users class="h-3 w-3" />
                                                {{ level.validators_count }} validateur{{ level.validators_count !== 1 ? 's' : '' }}
                                            </div>
                                            <div class="mt-3 flex items-center justify-center gap-1">
                                                <button
                                                    @click="openEditModal(level)"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2 py-1 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300"
                                                >
                                                    <Pencil class="h-3 w-3" />
                                                </button>
                                                <button
                                                    @click="deleteLevel(level)"
                                                    class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-2 py-1 text-xs font-medium text-red-700 transition-colors hover:bg-red-100 disabled:opacity-50 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300"
                                                >
                                                    <Trash2 class="h-3 w-3" />
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Flèche -->
                                    <div v-if="idx < group.levels.length - 1" class="flex items-center px-2 text-muted-foreground">
                                        <div class="h-px w-6 bg-border" />
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </template>

                                <!-- Flèche finale + Approuvée -->
                                <div class="flex items-center px-2 text-muted-foreground">
                                    <div class="h-px w-6 bg-border" />
                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                                <div class="w-32 rounded-2xl border-2 border-emerald-200 bg-emerald-50 p-4 text-center dark:border-emerald-900 dark:bg-emerald-950/30">
                                    <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500 text-white">
                                        <CheckCircle2 class="h-5 w-5" />
                                    </div>
                                    <p class="font-semibold text-emerald-700 text-sm dark:text-emerald-300">Approuvée</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </template>

        </div>
    </AppLayout>

    <!-- ── Modal Nouveau niveau ───────────────────────────────────────────── -->
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" @click="closeModal" />

                <!-- Panel -->
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                >
                    <div v-if="showModal" class="relative z-10 w-full max-w-lg rounded-2xl border bg-card shadow-2xl overflow-hidden">

                        <!-- Header modal -->
                        <div class="flex items-center justify-between border-b bg-muted/30 px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary/10">
                                    <Settings class="h-5 w-5 text-primary" />
                                </div>
                                <div>
                                    <h2 class="text-base font-semibold text-foreground">
                                        {{ editingLevel ? `Modifier — ${editingLevel.name}` : 'Nouveau niveau de validation' }}
                                    </h2>
                                    <p class="text-xs text-muted-foreground">
                                        {{ editingLevel ? `Niveau ${editingLevel.order} du circuit ${editingLevel.circuit?.name}` : 'Ajouter une étape au circuit d\'approbation' }}
                                    </p>
                                </div>
                            </div>
                            <button @click="closeModal" class="rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground">
                                <X class="h-5 w-5" />
                            </button>
                        </div>

                        <!-- Formulaire -->
                        <form @submit.prevent="submit" class="flex flex-col gap-4 p-6">

                            <!-- Circuit -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground" for="modal-circuit">
                                    Circuit <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="modal-circuit"
                                    v-model.number="form.circuit_id"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    :class="{ 'border-red-400': form.errors.circuit_id }"
                                >
                                    <option v-for="circuit in circuits" :key="circuit.id" :value="circuit.id">{{ circuit.name }}</option>
                                </select>
                                <p v-if="form.errors.circuit_id" class="text-xs text-red-500">{{ form.errors.circuit_id }}</p>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                <!-- Ordre -->
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground" for="modal-order">
                                        Ordre <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="modal-order"
                                        v-model.number="form.order"
                                        type="number"
                                        min="1"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                        :class="{ 'border-red-400': form.errors.order }"
                                    />
                                    <p v-if="form.errors.order" class="text-xs text-red-500">{{ form.errors.order }}</p>
                                </div>

                                <!-- Nom -->
                                <div class="col-span-2 flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground" for="modal-name">
                                        Nom du niveau <span class="text-red-500">*</span>
                                    </label>
                                    <input
                                        id="modal-name"
                                        v-model="form.name"
                                        type="text"
                                        placeholder="Ex : Chef de service, DAF…"
                                        autofocus
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                        :class="{ 'border-red-400': form.errors.name }"
                                    />
                                    <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                                </div>
                            </div>

                            <!-- Type d'étape -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground" for="modal-type">
                                    Type d'étape <span class="text-red-500">*</span>
                                </label>
                                <select
                                    id="modal-type"
                                    v-model="form.type"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    :class="{ 'border-red-400': form.errors.type }"
                                >
                                    <option value="validation">Validation</option>
                                    <option value="approbation">Approbation</option>
                                </select>
                                <p v-if="form.errors.type" class="text-xs text-red-500">{{ form.errors.type }}</p>
                            </div>

                            <!-- Description -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground" for="modal-desc">
                                    Description <span class="text-xs font-normal text-muted-foreground">(optionnel)</span>
                                </label>
                                <textarea
                                    id="modal-desc"
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Décrivez le rôle de ce niveau dans le circuit…"
                                    class="w-full resize-none rounded-xl border border-input bg-background px-4 py-2.5 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                />
                            </div>

                            <!-- Info -->
                            <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-xs text-blue-700 dark:border-blue-900 dark:bg-blue-950/30 dark:text-blue-300">
                                <strong>Note :</strong> Après la création, assignez des utilisateurs à ce niveau depuis la gestion des utilisateurs.
                            </div>

                            <!-- Actions -->
                            <div class="flex items-center justify-end gap-3 border-t border-border/50 pt-4">
                                <button
                                    type="button"
                                    @click="closeModal"
                                    class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-70"
                                >
                                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                                    <Save v-else class="h-4 w-4" />
                                    {{ editingLevel ? 'Enregistrer' : 'Créer le niveau' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>
