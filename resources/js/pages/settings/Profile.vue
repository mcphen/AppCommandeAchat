<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { BadgeCheck, Mail, PenSquare, ShieldCheck, Signature, UploadCloud } from 'lucide-vue-next';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type SharedData, type User } from '@/types';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
    signatureUrl?: string | null;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Parametres du profil', href: '/settings/profile' }];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const canHaveSignature = user?.role?.slug === 'validateur' || user?.role?.slug === 'admin';
const userInitial = user?.name?.trim()?.charAt(0)?.toUpperCase() ?? 'U';
const roleLabel = user?.role?.name ?? 'Utilisateur';
const verificationLabel = user?.email_verified_at ? 'Verifie' : 'A verifier';

const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), { preserveScroll: true });
};

const signatureInput = ref<HTMLInputElement | null>(null);
const signaturePreview = ref<string | null>(props.signatureUrl ?? null);
const signatureForm = useForm({ signature: null as File | null });
const deleteSignatureForm = useForm({});

const onSignatureChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    signatureForm.signature = file;
    signaturePreview.value = URL.createObjectURL(file);
};

const uploadSignature = () => {
    signatureForm.post(route('profile.signature.upload'), {
        forceFormData: true,
        preserveScroll: true,
    });
};

const removeSignature = () => {
    deleteSignatureForm.delete(route('profile.signature.delete'), {
        preserveScroll: true,
        onSuccess: () => {
            signaturePreview.value = null;
            signatureForm.reset();
            if (signatureInput.value) signatureInput.value.value = '';
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Parametres du profil" />

        <SettingsLayout>
            <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-foreground">Profil utilisateur</h2>
                        <p class="text-sm text-muted-foreground">Mettez a jour vos informations et votre signature de validation.</p>
                    </div>

                    <div
                        class="inline-flex items-center gap-2 self-start rounded-xl border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary"
                    >
                        <ShieldCheck class="h-4 w-4" />
                        {{ roleLabel }}
                    </div>
                </div>

                <section class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex items-center gap-4">
                            <div
                                class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-2xl font-bold text-primary"
                            >
                                {{ userInitial }}
                            </div>

                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Compte utilisateur</p>
                                <h3 class="mt-1 truncate text-xl font-semibold text-foreground">{{ user.name }}</h3>
                                <p class="mt-1 truncate text-sm text-muted-foreground">{{ user.email }}</p>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 lg:w-[560px]">
                            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">
                                    <Mail class="h-4 w-4" />
                                    Adresse e-mail
                                </div>
                                <p class="mt-3 text-sm font-semibold text-foreground">{{ verificationLabel }}</p>
                            </div>

                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                                <div
                                    class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300"
                                >
                                    <BadgeCheck class="h-4 w-4" />
                                    Profil
                                </div>
                                <p class="mt-3 text-sm font-semibold text-foreground">Informations personnelles modifiables</p>
                            </div>

                            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                                    <Signature class="h-4 w-4" />
                                    Signature
                                </div>
                                <p class="mt-3 text-sm font-semibold text-foreground">
                                    {{ canHaveSignature ? 'Utilisee sur les PDF' : 'Non requise pour ce role' }}
                                </p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="flex flex-col gap-5">
                        <div>
                            <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Informations du profil</h2>
                            <p class="mt-1 text-sm text-muted-foreground">Modifiez votre nom et votre adresse e-mail principale.</p>
                        </div>

                        <form @submit.prevent="submit" class="flex flex-col gap-5">
                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <div class="flex flex-col gap-2">
                                    <Label for="name" class="text-sm font-medium text-foreground">Nom complet</Label>
                                    <Input
                                        id="name"
                                        v-model="form.name"
                                        required
                                        autocomplete="name"
                                        placeholder="Votre nom complet"
                                        class="h-11 border-input bg-background shadow-sm focus-visible:ring-primary/30"
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>

                                <div class="flex flex-col gap-2">
                                    <Label for="email" class="text-sm font-medium text-foreground">Adresse e-mail</Label>
                                    <div class="rounded-xl border border-input bg-muted/30 px-4 py-3">
                                        <p class="text-sm font-medium text-foreground">{{ form.email }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">L'adresse e-mail ne peut pas etre modifiee depuis ce profil.</p>
                                    </div>
                                    <InputError :message="form.errors.email" />
                                </div>
                            </div>

                            <div
                                v-if="mustVerifyEmail && !user.email_verified_at"
                                class="rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30"
                            >
                                <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                                    Votre adresse e-mail n'est pas encore verifiee.
                                </p>
                                <p class="mt-1 text-sm text-amber-700 dark:text-amber-300">
                                    Cliquez ci-dessous pour renvoyer un lien de verification.
                                </p>
                                <Link
                                    :href="route('verification.send')"
                                    method="post"
                                    as="button"
                                    class="mt-3 inline-flex rounded-xl bg-amber-500 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-amber-600"
                                >
                                    Renvoyer l'e-mail de verification
                                </Link>

                                <div
                                    v-if="status === 'verification-link-sent'"
                                    class="mt-3 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300"
                                >
                                    Un nouveau lien de verification a ete envoye.
                                </div>
                            </div>

                            <div class="flex flex-wrap items-center justify-end gap-3">
                                <TransitionRoot
                                    :show="form.recentlySuccessful"
                                    enter="transition ease-in-out duration-300"
                                    enter-from="opacity-0 translate-y-1"
                                    enter-to="opacity-100 translate-y-0"
                                    leave="transition ease-in-out duration-200"
                                    leave-from="opacity-100 translate-y-0"
                                    leave-to="opacity-0 translate-y-1"
                                >
                                    <p
                                        class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300"
                                    >
                                        Profil enregistre.
                                    </p>
                                </TransitionRoot>

                                <Button :disabled="form.processing" class="shadow-sm">
                                    {{ form.processing ? 'Enregistrement...' : 'Enregistrer les modifications' }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </section>

                <section v-if="canHaveSignature" class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="flex flex-col gap-5">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <Signature class="h-5 w-5" />
                            </div>
                            <div>
                                <HeadingSmall
                                    title="Signature electronique"
                                    description="Cette image sera ajoutee dans les PDF des bons de commande que vous validez."
                                />
                            </div>
                        </div>

                        <div v-if="signaturePreview" class="rounded-2xl border bg-muted/20 p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="space-y-3">
                                    <p class="text-sm font-medium text-foreground">Signature actuelle</p>
                                    <div class="flex min-h-36 items-center justify-center rounded-2xl border border-dashed bg-background p-4">
                                        <img :src="signaturePreview" alt="Apercu de votre signature" class="h-24 w-auto object-contain" />
                                    </div>
                                </div>

                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="sm"
                                    :disabled="deleteSignatureForm.processing"
                                    @click="removeSignature"
                                >
                                    {{ deleteSignatureForm.processing ? 'Suppression...' : 'Supprimer' }}
                                </Button>
                            </div>
                        </div>

                        <div
                            v-else
                            class="flex flex-col items-center justify-center gap-3 rounded-2xl border border-dashed bg-muted/20 px-6 py-8 text-center"
                        >
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                <PenSquare class="h-6 w-6" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-foreground">Aucune signature enregistree</p>
                                <p class="mt-1 text-sm text-muted-foreground">Importez une image propre et lisible pour vos validations PDF.</p>
                            </div>
                        </div>

                        <form @submit.prevent="uploadSignature" class="rounded-2xl border bg-muted/20 p-5">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start">
                                <div class="hidden rounded-2xl bg-primary/10 p-3 text-primary lg:block">
                                    <UploadCloud class="h-5 w-5" />
                                </div>

                                <div class="flex-1 space-y-4">
                                    <div>
                                        <Label for="signature" class="text-sm font-medium text-foreground">
                                            {{ signaturePreview ? 'Remplacer la signature' : 'Importer une signature' }}
                                        </Label>
                                        <p class="mt-1 text-sm text-muted-foreground">
                                            PNG, JPG ou WebP, 2 Mo max. Un fond transparent est recommande.
                                        </p>
                                    </div>

                                    <div class="grid gap-2">
                                        <Input
                                            id="signature"
                                            ref="signatureInput"
                                            type="file"
                                            accept="image/png,image/jpeg,image/webp"
                                            class="cursor-pointer border-input bg-background shadow-sm focus-visible:ring-primary/30"
                                            @change="onSignatureChange"
                                        />
                                        <InputError :message="signatureForm.errors.signature" />
                                    </div>

                                    <div class="flex flex-wrap items-center justify-end gap-3">
                                        <TransitionRoot
                                            :show="status === 'signature-uploaded' || status === 'signature-deleted'"
                                            enter="transition ease-in-out duration-300"
                                            enter-from="opacity-0 translate-y-1"
                                            enter-to="opacity-100 translate-y-0"
                                            leave="transition ease-in-out duration-200"
                                            leave-from="opacity-100 translate-y-0"
                                            leave-to="opacity-0 translate-y-1"
                                        >
                                            <p
                                                class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300"
                                            >
                                                {{ status === 'signature-uploaded' ? 'Signature enregistree.' : 'Signature supprimee.' }}
                                            </p>
                                        </TransitionRoot>

                                        <Button type="submit" :disabled="!signatureForm.signature || signatureForm.processing" class="shadow-sm">
                                            {{ signatureForm.processing ? 'Envoi...' : 'Enregistrer la signature' }}
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
