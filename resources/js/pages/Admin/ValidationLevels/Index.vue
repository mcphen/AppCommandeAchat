<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type ValidationLevel } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Settings, Pencil, Trash2, Users, GripVertical, ArrowUp, ArrowDown } from 'lucide-vue-next';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Administration', href: '#' },
    { title: 'Niveaux de validation', href: '/admin/validation-levels' },
];

defineProps<{ levels: (ValidationLevel & { validators_count: number })[] }>();

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
</script>

<template>
    <Head title="Niveaux de validation" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Circuit de validation</h1>
                    <p class="text-sm text-muted-foreground mt-1">Configurez les niveaux d'approbation des commandes</p>
                </div>
                <Link
                    :href="route('admin.validation-levels.create')"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors"
                >
                    <Plus class="h-4 w-4" />
                    Ajouter un niveau
                </Link>
            </div>

            <!-- Circuit visuel -->
            <template v-if="levels.length > 0">
                <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                    <div class="border-b bg-muted/30 px-6 py-3.5">
                        <p class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Circuit de validation ({{ levels.length }} niveau{{ levels.length !== 1 ? 'x' : '' }})</p>
                    </div>

                    <div class="p-6">
                        <div class="flex items-center gap-0 flex-wrap">
                            <template v-for="(level, idx) in levels" :key="level.id">
                                <!-- Carte de niveau -->
                                <div class="flex flex-col items-center">
                                    <div class="w-44 rounded-2xl border-2 border-border bg-card p-4 text-center hover:border-primary/30 hover:shadow-md transition-all">
                                        <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">
                                            {{ level.order }}
                                        </div>
                                        <p class="font-semibold text-foreground text-sm">{{ level.name }}</p>
                                        <p v-if="level.description" class="text-xs text-muted-foreground mt-1 line-clamp-2">{{ level.description }}</p>
                                        <div class="mt-3 flex items-center justify-center gap-1 text-xs text-muted-foreground">
                                            <Users class="h-3 w-3" />
                                            {{ level.validators_count }} validateur{{ level.validators_count !== 1 ? 's' : '' }}
                                        </div>
                                        <div class="mt-3 flex items-center justify-center gap-1">
                                            <Link
                                                :href="route('admin.validation-levels.edit', level.id)"
                                                class="rounded-lg p-1.5 hover:bg-muted transition-colors text-muted-foreground hover:text-foreground"
                                            >
                                                <Pencil class="h-3.5 w-3.5" />
                                            </Link>
                                            <button
                                                @click="deleteLevel(level)"
                                                class="rounded-lg p-1.5 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600"
                                                :title="level.validators_count > 0 ? 'Réassignez les validateurs avant de supprimer' : 'Supprimer'"
                                            >
                                                <Trash2 class="h-3.5 w-3.5" />
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Flèche entre les niveaux -->
                                <div v-if="idx < levels.length - 1" class="flex items-center px-2 text-muted-foreground">
                                    <div class="flex items-center gap-0">
                                        <div class="h-px w-6 bg-border" />
                                        <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </div>
                                </div>
                            </template>

                            <!-- Fin : Approuvé -->
                            <div v-if="levels.length > 0" class="flex items-center px-2 text-muted-foreground">
                                <div class="flex items-center gap-0">
                                    <div class="h-px w-6 bg-border" />
                                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                    </svg>
                                </div>
                            </div>
                            <div class="w-32 rounded-2xl border-2 border-emerald-200 bg-emerald-50 p-4 text-center">
                                <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-emerald-500 text-white">
                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <p class="font-semibold text-emerald-700 text-sm">Approuvée</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tableau détaillé -->
                <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/30">
                                <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Ordre</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Nom</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Description</th>
                                <th class="px-6 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground">Validateurs</th>
                                <th class="px-6 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="level in levels" :key="level.id" class="hover:bg-muted/20 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-primary-foreground text-sm font-bold">
                                        {{ level.order }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-semibold text-foreground">{{ level.name }}</td>
                                <td class="px-6 py-4 text-sm text-muted-foreground max-w-xs">
                                    {{ level.description ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-foreground">
                                        <Users class="h-3 w-3" />
                                        {{ level.validators_count }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link :href="route('admin.validation-levels.edit', level.id)" class="rounded-lg p-2 hover:bg-muted transition-colors text-muted-foreground hover:text-foreground">
                                            <Pencil class="h-4 w-4" />
                                        </Link>
                                        <button @click="deleteLevel(level)" class="rounded-lg p-2 hover:bg-red-50 transition-colors text-muted-foreground hover:text-red-600">
                                            <Trash2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </template>

            <!-- État vide -->
            <div v-else class="rounded-2xl border-2 border-dashed border-border bg-card p-16 text-center">
                <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-muted">
                    <Settings class="h-7 w-7 text-muted-foreground" />
                </div>
                <h3 class="font-semibold text-foreground mb-2">Aucun niveau configuré</h3>
                <p class="text-sm text-muted-foreground mb-6">Définissez les niveaux de validation du circuit d'approbation</p>
                <Link :href="route('admin.validation-levels.create')" class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors">
                    <Plus class="h-4 w-4" />
                    Créer le premier niveau
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
