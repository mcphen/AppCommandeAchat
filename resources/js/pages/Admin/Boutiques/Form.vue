<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type Boutique, type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Loader2, Save } from 'lucide-vue-next';
import { computed } from 'vue';

const props = defineProps<{
    boutique?: Boutique;
}>();

const isEdit = computed(() => !!props.boutique);

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Sociétés', href: '/admin/boutiques' },
    { title: isEdit.value ? 'Modifier' : 'Nouvelle société', href: '#' },
];

const form = useForm({
    code: props.boutique?.code ?? '',
    name: props.boutique?.name ?? '',
    address: props.boutique?.address ?? '',
    city: props.boutique?.city ?? '',
    is_active: props.boutique?.is_active ?? true,
});

const submit = () => {
    if (isEdit.value) {
        form.put(route('admin.boutiques.update', props.boutique!.id));
    } else {
        form.post(route('admin.boutiques.store'));
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Modifier la société' : 'Nouvelle société'" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex w-full max-w-3xl flex-col gap-6 p-6">
            <div class="flex items-center gap-4">
                <Link :href="route('admin.boutiques.index')" class="rounded-xl p-2 text-muted-foreground transition-colors hover:bg-muted">
                    <ArrowLeft class="h-5 w-5" />
                </Link>
                <div>
                    <h1 class="text-2xl font-bold text-foreground">{{ isEdit ? 'Modifier la société' : 'Nouvelle société' }}</h1>
                    <p class="text-sm text-muted-foreground">{{ isEdit ? props.boutique?.name : 'Configurer une nouvelle société du groupe' }}</p>
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
                                placeholder="ABJ-PLT"
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
                                placeholder="Société Plateau"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                                :class="{ 'border-red-400': form.errors.name }"
                            />
                            <p v-if="form.errors.name" class="text-xs text-red-500">{{ form.errors.name }}</p>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Ville</label>
                            <input
                                v-model="form.city"
                                type="text"
                                placeholder="Abidjan"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                            />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <label class="text-sm font-medium text-foreground">Adresse</label>
                            <input
                                v-model="form.address"
                                type="text"
                                placeholder="Avenue Chardy"
                                class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm transition-colors focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/30"
                            />
                        </div>
                    </div>

                    <div class="mt-4 rounded-xl border bg-muted/20 p-4">
                        <label class="flex items-center gap-3">
                            <input v-model="form.is_active" type="checkbox" class="h-4 w-4 rounded border-input text-primary focus:ring-primary/30" />
                            <div>
                                <p class="text-sm font-medium text-foreground">Société active</p>
                                <p class="text-xs text-muted-foreground">Une société inactive ne sera plus proposee dans l'affectation des gerants.</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3">
                    <Link :href="route('admin.boutiques.index')" class="rounded-xl border px-5 py-2.5 text-sm font-semibold text-foreground transition-colors hover:bg-muted">
                        Annuler
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-70"
                    >
                        <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                        <Save v-else class="h-4 w-4" />
                        {{ isEdit ? 'Enregistrer' : 'Creer la société' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
