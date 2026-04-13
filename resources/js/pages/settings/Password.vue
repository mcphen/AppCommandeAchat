<script setup lang="ts">
import { TransitionRoot } from '@headlessui/vue';
import { Head, useForm } from '@inertiajs/vue3';
import { KeyRound, LockKeyhole, ShieldCheck } from 'lucide-vue-next';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Mot de passe',
        href: '/settings/password',
    },
];

const passwordInput = ref<HTMLInputElement>();
const currentPasswordInput = ref<HTMLInputElement>();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: (errors: any) => {
            if (errors.password) {
                form.reset('password', 'password_confirmation');
                if (passwordInput.value instanceof HTMLInputElement) {
                    passwordInput.value.focus();
                }
            }

            if (errors.current_password) {
                form.reset('current_password');
                if (currentPasswordInput.value instanceof HTMLInputElement) {
                    currentPasswordInput.value.focus();
                }
            }
        },
    });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Mot de passe" />

        <SettingsLayout>
            <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-foreground">Securite du compte</h2>
                        <p class="text-sm text-muted-foreground">Mettez a jour votre mot de passe pour proteger votre acces.</p>
                    </div>

                    <div
                        class="inline-flex items-center gap-2 self-start rounded-xl border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary"
                    >
                        <ShieldCheck class="h-4 w-4" />
                        Protection du compte
                    </div>
                </div>

                <section class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                <KeyRound class="h-8 w-8" />
                            </div>

                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Acces et authentification</p>
                                <h3 class="mt-1 text-xl font-semibold text-foreground">Changer le mot de passe</h3>
                                <p class="mt-1 text-sm text-muted-foreground">Utilisez un mot de passe long, unique et difficile a deviner.</p>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 lg:w-[560px]">
                            <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">
                                    <LockKeyhole class="h-4 w-4" />
                                    Mot de passe actuel
                                </div>
                                <p class="mt-3 text-sm font-semibold text-foreground">Requis pour confirmer le changement</p>
                            </div>

                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                                <div
                                    class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300"
                                >
                                    <ShieldCheck class="h-4 w-4" />
                                    Securite
                                </div>
                                <p class="mt-3 text-sm font-semibold text-foreground">Choisissez un mot de passe robuste</p>
                            </div>

                            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                                    <KeyRound class="h-4 w-4" />
                                    Confirmation
                                </div>
                                <p class="mt-3 text-sm font-semibold text-foreground">Le nouveau mot de passe doit etre confirme</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="flex flex-col gap-5">
                        <div>
                            <HeadingSmall
                                title="Mettre a jour le mot de passe"
                                description="Renseignez votre mot de passe actuel puis definissez un nouveau mot de passe."
                            />
                        </div>

                        <form @submit.prevent="updatePassword" class="flex flex-col gap-5">
                            <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <div class="flex flex-col gap-2 xl:col-span-2">
                                    <Label for="current_password" class="text-sm font-medium text-foreground">Mot de passe actuel</Label>
                                    <Input
                                        id="current_password"
                                        ref="currentPasswordInput"
                                        v-model="form.current_password"
                                        type="password"
                                        autocomplete="current-password"
                                        placeholder="Saisissez votre mot de passe actuel"
                                        class="h-11 border-input bg-background shadow-sm focus-visible:ring-primary/30"
                                    />
                                    <InputError :message="form.errors.current_password" />
                                </div>

                                <div class="flex flex-col gap-2">
                                    <Label for="password" class="text-sm font-medium text-foreground">Nouveau mot de passe</Label>
                                    <Input
                                        id="password"
                                        ref="passwordInput"
                                        v-model="form.password"
                                        type="password"
                                        autocomplete="new-password"
                                        placeholder="Saisissez un nouveau mot de passe"
                                        class="h-11 border-input bg-background shadow-sm focus-visible:ring-primary/30"
                                    />
                                    <InputError :message="form.errors.password" />
                                </div>

                                <div class="flex flex-col gap-2">
                                    <Label for="password_confirmation" class="text-sm font-medium text-foreground">
                                        Confirmation du mot de passe
                                    </Label>
                                    <Input
                                        id="password_confirmation"
                                        v-model="form.password_confirmation"
                                        type="password"
                                        autocomplete="new-password"
                                        placeholder="Confirmez le nouveau mot de passe"
                                        class="h-11 border-input bg-background shadow-sm focus-visible:ring-primary/30"
                                    />
                                    <InputError :message="form.errors.password_confirmation" />
                                </div>
                            </div>

                            <div class="rounded-2xl border border-primary/20 bg-primary/5 p-4">
                                <p class="text-sm font-semibold text-foreground">Conseil de securite</p>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Evitez de reutiliser un mot de passe deja employe sur un autre service.
                                </p>
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
                                        Mot de passe mis a jour.
                                    </p>
                                </TransitionRoot>

                                <Button :disabled="form.processing" class="shadow-sm">
                                    {{ form.processing ? 'Enregistrement...' : 'Enregistrer le nouveau mot de passe' }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
