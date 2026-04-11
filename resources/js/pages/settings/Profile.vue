<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

import DeleteUser from '@/components/DeleteUser.vue';
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

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Paramètres du profil', href: '/settings/profile' },
];

const page = usePage<SharedData>();
const user = page.props.auth.user as User;

const canHaveSignature = user?.role?.slug === 'validateur' || user?.role?.slug === 'admin';

// Formulaire infos
const form = useForm({
    name: user.name,
    email: user.email,
});

const submit = () => {
    form.patch(route('profile.update'), { preserveScroll: true });
};

// Signature
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
        onSuccess: () => {
            // le serveur renvoie la nouvelle URL via Inertia shared props au rechargement
        },
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
        <Head title="Paramètres du profil" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Informations du profil" description="Mettez à jour votre nom et votre adresse e-mail" />

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Nom</Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.name" required autocomplete="name" placeholder="Nom complet" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Adresse e-mail</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            required
                            autocomplete="username"
                            placeholder="Adresse e-mail"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="mt-2 text-sm text-neutral-800">
                            Votre adresse e-mail n'est pas vérifiée.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="focus:outline-hidden rounded-md text-sm text-neutral-600 underline hover:text-neutral-900 focus:ring-2 focus:ring-offset-2"
                            >
                                Cliquez ici pour renvoyer l'e-mail de vérification.
                            </Link>
                        </p>
                        <div v-if="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            Un nouveau lien de vérification a été envoyé à votre adresse e-mail.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button :disabled="form.processing">Enregistrer</Button>
                        <TransitionRoot
                            :show="form.recentlySuccessful"
                            enter="transition ease-in-out"
                            enter-from="opacity-0"
                            leave="transition ease-in-out"
                            leave-to="opacity-0"
                        >
                            <p class="text-sm text-neutral-600">Enregistré.</p>
                        </TransitionRoot>
                    </div>
                </form>
            </div>

            <!-- ── Signature électronique (validateurs et admins) ─────────── -->
            <div v-if="canHaveSignature" class="mt-8 flex flex-col space-y-4 border-t pt-6">
                <HeadingSmall
                    title="Signature électronique"
                    description="Cette image apparaîtra dans les PDF des bons de commande que vous validez."
                />

                <!-- Aperçu -->
                <div v-if="signaturePreview" class="flex items-start gap-4">
                    <div class="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-3">
                        <img
                            :src="signaturePreview"
                            alt="Aperçu de votre signature"
                            class="h-20 w-auto object-contain"
                        />
                    </div>
                    <div class="flex flex-col gap-2 pt-1">
                        <p class="text-sm text-slate-500">Signature actuelle</p>
                        <Button
                            type="button"
                            variant="destructive"
                            size="sm"
                            :disabled="deleteSignatureForm.processing"
                            @click="removeSignature"
                        >
                            Supprimer
                        </Button>
                    </div>
                </div>

                <div v-else class="flex items-center gap-3 rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-sm text-slate-400">
                    <svg class="h-8 w-8 shrink-0 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.232 5.232l3.536 3.536M9 13l6.586-6.586a2 2 0 112.828 2.828L11.828 15.828a2 2 0 01-1.414.586H9v-2a2 2 0 01.586-1.414z" />
                    </svg>
                    <span>Aucune signature enregistrée</span>
                </div>

                <!-- Upload -->
                <form @submit.prevent="uploadSignature" class="flex items-end gap-3">
                    <div class="grid gap-2">
                        <Label for="signature">
                            {{ signaturePreview ? 'Remplacer la signature' : 'Importer une signature' }}
                        </Label>
                        <Input
                            id="signature"
                            ref="signatureInput"
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            class="mt-1 block w-full max-w-xs cursor-pointer"
                            @change="onSignatureChange"
                        />
                        <p class="text-xs text-slate-400">PNG, JPG ou WebP — max 2 Mo. Fond transparent recommandé (PNG).</p>
                        <InputError :message="signatureForm.errors.signature" />
                    </div>
                    <Button
                        type="submit"
                        :disabled="!signatureForm.signature || signatureForm.processing"
                    >
                        {{ signatureForm.processing ? 'Envoi…' : 'Enregistrer la signature' }}
                    </Button>
                </form>

                <TransitionRoot
                    :show="status === 'signature-uploaded' || status === 'signature-deleted'"
                    enter="transition ease-in-out"
                    enter-from="opacity-0"
                    leave="transition ease-in-out"
                    leave-to="opacity-0"
                >
                    <p class="text-sm text-green-600">
                        {{ status === 'signature-uploaded' ? 'Signature enregistrée.' : 'Signature supprimée.' }}
                    </p>
                </TransitionRoot>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>
