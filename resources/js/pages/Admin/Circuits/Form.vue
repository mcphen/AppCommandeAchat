<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type Circuit } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, Save } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    circuit?: Circuit;
}>();

const isEdit = computed(() => !!props.circuit);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Circuits', href: '/admin/circuits' },
    { title: isEdit.value ? 'Modifier' : 'Nouveau circuit', href: '#' },
];

const form = useForm({
    code: props.circuit?.code ?? '',
    name: props.circuit?.name ?? '',
    is_active: props.circuit?.is_active ?? true,
});

const submit = () => {
    if (isEdit.value) {
        form.put(route('admin.circuits.update', props.circuit!.id));
    } else {
        form.post(route('admin.circuits.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Modifier le circuit' : 'Nouveau circuit'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex w-full max-w-3xl flex-col gap-6 p-6">
            <div class="flex items-center gap-4">
                <Link :href="route('admin.circuits.index')" class="rounded-xl p-2 text-muted-foreground transition-colors hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">{{ isEdit ? 'Modifier le circuit' : 'Nouveau circuit' }}</h1>
                    <p class="text-sm text-muted-foreground">{{ isEdit ? props.circuit?.name : 'Ex : Achat, Prestation de service...' }}</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="flex flex-col gap-5">
                <div class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Code <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.code"
                                type="text"
                                placeholder="prestation"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                :class="{ 'border-red-400': form.errors.code }"
                            />
                            <p v-if="form.errors.code" class="text-xs text-red-500">{{ form.errors.code }}</p>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Nom <span class="text-red-500">*</span></label>
                            <input
                                v-model="form.name"
                                type="text"
                                placeholder="Prestation de service"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                :class="{ 'border-red-400': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border bg-muted/20 p-4">
                        <label class="flex items-center gap-3">
                            <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-input text-primary focus:ring-primary/30" />
                            <div>
                                <p class="text-sm font-medium text-foreground">Circuit actif</p>
                                <p class="text-xs text-muted-foreground">Un circuit inactif ne sera plus proposé pour les niveaux de validation.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('admin.circuits.index')" class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted">
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-70"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        {{ isEdit ? 'Enregistrer' : 'Créer le circuit' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
