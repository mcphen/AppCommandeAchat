<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type NatureOperation } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import { AlertCircle, Pencil, Plus, Tag, Trash2, X } from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Administration', href: '/admin/users' },
    { title: "Natures d'opérations", href: '#' },
];

const props = defineProps<{
    natureOperations: NatureOperation[];
}>();

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

/* ── Modal état ── */
const showModal = ref(false);
const modalMode = ref<'create' | 'edit'>('create');
const editingNature = ref<NatureOperation | null>(null);

const form = useForm({ name: '', description: '', is_active: true });

const openCreate = () => {
    form.reset();
    form.clearErrors();
    modalMode.value = 'create';
    editingNature.value = null;
    showModal.value = true;
};

const openEdit = (n: NatureOperation) => {
    form.name = n.name;
    form.description = n.description ?? '';
    form.is_active = n.is_active;
    form.clearErrors();
    modalMode.value = 'edit';
    editingNature.value = n;
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
    editingNature.value = null;
};

const submitForm = () => {
    if (modalMode.value === 'create') {
        form.post(route('admin.nature-operations.store'), { onSuccess: closeModal });
    } else if (editingNature.value) {
        form.put(route('admin.nature-operations.update', editingNature.value.id), { onSuccess: closeModal });
    }
};

const confirmDelete = async (n: NatureOperation) => {
    const result = await Swal.fire({
        title: 'Supprimer cette nature ?',
        text: `"${n.name}" sera définitivement supprimée.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });
    if (result.isConfirmed) router.delete(route('admin.nature-operations.destroy', n.id));
};
</script>

<template>
    <Head title="Natures d'opérations" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- En-tête -->
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-xl font-bold text-foreground sm:text-2xl">Natures d'opérations</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ natureOperations.length }} nature{{ natureOperations.length !== 1 ? 's' : '' }} configurée{{ natureOperations.length !== 1 ? 's' : '' }}
                    </p>
                </div>
                <button
                    class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-primary px-3 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 sm:px-4"
                    @click="openCreate"
                >
                    <Plus class="h-4 w-4" />
                    <span class="hidden sm:inline">Nouvelle nature</span>
                </button>
            </div>

            <!-- Tableau -->
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <template v-if="natureOperations.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/30">
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Nom</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell sm:px-6">Description</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Statut</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:table-cell sm:px-6">Créée le</th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr v-for="n in natureOperations" :key="n.id" class="transition-colors hover:bg-muted/20">
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-orange-50">
                                                <Tag class="h-4 w-4 text-orange-500" />
                                            </div>
                                            <p class="font-medium text-foreground">{{ n.name }}</p>
                                        </div>
                                    </td>
                                    <td class="hidden px-4 py-4 md:table-cell sm:px-6">
                                        <p v-if="n.description" class="max-w-xs truncate text-sm text-muted-foreground">{{ n.description }}</p>
                                        <span v-else class="text-xs text-muted-foreground">—</span>
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <span
                                            class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                            :class="n.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500'"
                                        >
                                            <span
                                                class="h-1.5 w-1.5 rounded-full"
                                                :class="n.is_active ? 'bg-emerald-500' : 'bg-slate-400'"
                                            />
                                            {{ n.is_active ? 'Active' : 'Désactivée' }}
                                        </span>
                                    </td>
                                    <td class="hidden px-4 py-4 text-sm text-muted-foreground xl:table-cell sm:px-6">
                                        {{ formatDate(n.created_at) }}
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center justify-end gap-1">
                                            <button
                                                class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                                @click="openEdit(n)"
                                            >
                                                <Pencil class="h-4 w-4" />
                                            </button>
                                            <button
                                                class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-600"
                                                @click="confirmDelete(n)"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </template>

                <EmptyState
                    v-else
                    :icon="Tag"
                    icon-bg="bg-orange-50"
                    icon-color="text-orange-500"
                    title="Aucune nature d'opération"
                    description="Créez des natures d'opérations pour catégoriser les demandes de décaissement."
                    action-label="Nouvelle nature"
                    :bordered="false"
                    @action="openCreate"
                />
            </div>
        </div>

        <!-- Modal Créer / Modifier -->
        <Transition
            enter-active-class="duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4 backdrop-blur-sm"
                @click.self="closeModal"
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
                        v-if="showModal"
                        class="w-full max-w-md rounded-2xl border bg-card shadow-xl"
                    >
                        <!-- En-tête modal -->
                        <div class="flex items-center justify-between border-b px-6 py-4">
                            <h2 class="text-base font-semibold text-foreground">
                                {{ modalMode === 'create' ? "Nouvelle nature d'opération" : "Modifier la nature" }}
                            </h2>
                            <button
                                class="rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                @click="closeModal"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <!-- Corps modal -->
                        <div class="space-y-4 px-6 py-5">
                            <!-- Nom -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">
                                    Nom <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Ex : Frais de déplacement"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                                    :class="form.errors.name ? 'border-red-400' : ''"
                                    autofocus
                                />
                                <p v-if="form.errors.name" class="flex items-center gap-1 text-xs text-red-500">
                                    <AlertCircle class="h-3.5 w-3.5" /> {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Description -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Description</label>
                                <textarea
                                    v-model="form.description"
                                    rows="3"
                                    placeholder="Description optionnelle..."
                                    class="w-full rounded-xl border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30"
                                />
                            </div>

                            <!-- Statut (edit seulement) -->
                            <div v-if="modalMode === 'edit'" class="flex items-center gap-3 rounded-xl border bg-muted/30 px-4 py-3">
                                <input
                                    id="is_active"
                                    v-model="form.is_active"
                                    type="checkbox"
                                    class="h-4 w-4 rounded accent-primary"
                                />
                                <label for="is_active" class="cursor-pointer text-sm font-medium text-foreground">
                                    Nature active
                                </label>
                                <span class="ml-auto text-xs text-muted-foreground">
                                    {{ form.is_active ? 'Visible dans les formulaires' : 'Masquée des formulaires' }}
                                </span>
                            </div>
                        </div>

                        <!-- Pied modal -->
                        <div class="flex justify-end gap-2 border-t px-6 py-4">
                            <button
                                class="rounded-xl border px-4 py-2 text-sm font-medium text-foreground transition-colors hover:bg-muted"
                                @click="closeModal"
                            >
                                Annuler
                            </button>
                            <button
                                class="rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-50"
                                :disabled="form.processing"
                                @click="submitForm"
                            >
                                {{ modalMode === 'create' ? 'Créer' : 'Enregistrer' }}
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </AppLayout>
</template>
