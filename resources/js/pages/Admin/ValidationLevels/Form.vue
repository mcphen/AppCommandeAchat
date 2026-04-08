<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type ValidationLevel } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, Save } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    level?: ValidationLevel;
    nextOrder?: number;
}>();

const isEdit = computed(() => !!props.level);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Niveaux de validation', href: '/admin/validation-levels' },
    { title: isEdit.value ? 'Modifier' : 'Nouveau niveau', href: '#' },
];

const form = useForm({
    name: props.level?.name ?? '',
    order: props.level?.order ?? props.nextOrder ?? 1,
    description: props.level?.description ?? '',
});

const submit = () => {
    if (isEdit.value) {
        form.put(route('admin.validation-levels.update', props.level!.id));
    } else {
        form.post(route('admin.validation-levels.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Modifier le niveau' : 'Nouveau niveau'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6 max-w-xl">

            <div class="flex items-center gap-4">
                <Link :href="route('admin.validation-levels.index')" class="rounded-xl p-2 hover:bg-muted transition-colors text-muted-foreground">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">
                        {{ isEdit ? 'Modifier le niveau' : 'Nouveau niveau de validation' }}
                    </h1>
                    <p class="text-sm text-muted-foreground">{{ isEdit ? `Niveau ${level?.order} — ${level?.name}` : 'Ajouter une étape au circuit d\'approbation' }}</p>
                </div>
            </div>

            <form @submit.prevent="submit">
                <div class="rounded-2xl border bg-card p-6 shadow-sm flex flex-col gap-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Configuration du niveau</h2>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Ordre <span class="text-red-500">*</span></label>
                            <input
                                v-model.number="form.order"
                                type="number"
                                min="1"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.order }"
                            />
                            <p v-if="form.errors.order" class="text-xs text-red-500">{{ form.errors.order }}</p>
                        </div>
                        <div class="col-span-2 flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Nom du niveau <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Ex : Chef de service, DAF, Direction..."
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                :class="{ 'border-red-400': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground">Description <span class="text-xs font-normal text-muted-foreground">(optionnel)</span></label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            placeholder="Décrivez le rôle de ce niveau dans le circuit..."
                            class="w-full rounded-xl border border-input bg-background px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors resize-none"
                        />
                    </div>

                    <!-- Info -->
                    <div class="rounded-xl bg-blue-50 border border-blue-100 px-4 py-3 text-xs text-blue-700">
                        <strong>Note :</strong> Après la création, assignez des utilisateurs à ce niveau depuis la gestion des utilisateurs.
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 mt-5">
                    <Link :href="route('admin.validation-levels.index')" class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground hover:bg-muted transition-colors">
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors disabled:opacity-70"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        {{ isEdit ? 'Enregistrer' : 'Créer le niveau' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
