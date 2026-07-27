<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { Building2, ImageIcon, Mail, Settings2, ShieldCheck } from 'lucide-vue-next';
import { ref } from 'vue';

import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type SharedData } from '@/types';

interface Props {
    settings: Record<string, string | null>;
    logoUrl: string | null;
}

const props = defineProps<Props>();

const page = usePage<SharedData>();
const flash = page.props.flash;

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: '#' },
    { title: 'Configuration', href: route('admin.settings.index') },
];

const s = props.settings;

const activeTab = ref<'company' | 'mail'>('company');

const logoPreview = ref<string | null>(props.logoUrl);
const logoInput = ref<HTMLInputElement | null>(null);

const companyForm = useForm({
    company_name: s.company_name ?? '',
    company_address: s.company_address ?? '',
    company_phone: s.company_phone ?? '',
    company_email: s.company_email ?? '',
    company_website: s.company_website ?? '',
    company_nif: s.company_nif ?? '',
    company_rccm: s.company_rccm ?? '',
    company_logo: null as File | null,
});

const onLogoChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    companyForm.company_logo = file;
    logoPreview.value = URL.createObjectURL(file);
};

const submitCompany = () => {
    companyForm
        .transform((data) => ({
            ...data,
            _method: 'patch',
        }))
        .post(route('admin.settings.company'), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                companyForm.company_logo = null;
                if (logoInput.value) logoInput.value.value = '';
            },
        });
};

const deleteLogo = useForm({});
const removeLogo = () => {
    deleteLogo.delete(route('admin.settings.logo.delete'), {
        preserveScroll: true,
        onSuccess: () => {
            logoPreview.value = null;
            companyForm.company_logo = null;
            if (logoInput.value) logoInput.value.value = '';
        },
    });
};

const mailForm = useForm({
    mail_mailer: s.mail_mailer ?? 'log',
    mail_host: s.mail_host ?? '',
    mail_port: s.mail_port ?? '587',
    mail_encryption: s.mail_encryption ?? 'tls',
    mail_username: s.mail_username ?? '',
    mail_password: '',
    mail_from_address: s.mail_from_address ?? '',
    mail_from_name: s.mail_from_name ?? '',
});

const submitMail = () => {
    mailForm.patch(route('admin.settings.mail'), { preserveScroll: true });
};

const testForm = useForm({ test_email: '' });
const sendTest = () => {
    testForm.post(route('admin.settings.test-mail'), { preserveScroll: true });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Configuration" />

        <div class="mx-auto max-w-5xl space-y-6 px-4 py-8">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Configuration de l'application</h1>
                    <p class="text-sm text-muted-foreground">Parametres globaux : identite de l'entreprise et envoi d'e-mails.</p>
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
                            <Settings2 class="h-8 w-8" />
                        </div>

                        <div class="min-w-0">
                            <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Parametres globaux</p>
                            <h2 class="mt-1 text-xl font-semibold text-foreground">Piloter l'identite et la messagerie</h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Definissez les informations de l'entreprise, le logo officiel et les regles d'envoi des e-mails.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3 lg:w-[560px]">
                        <div class="rounded-2xl border border-blue-100 bg-blue-50 p-4 dark:border-blue-900 dark:bg-blue-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-300">
                                <Building2 class="h-4 w-4" />
                                Entreprise
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">Coordonnees, NIF, RCCM et adresse officielle</p>
                        </div>

                        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300">
                                <Mail class="h-4 w-4" />
                                Messagerie
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">SMTP, expediteur par defaut et envoi de test</p>
                        </div>

                        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                            <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                                <ImageIcon class="h-4 w-4" />
                                Identite visuelle
                            </div>
                            <p class="mt-3 text-sm font-semibold text-foreground">Logo utilise dans les PDF et documents de commande</p>
                        </div>
                    </div>
                </div>
            </section>

            <div
                v-if="flash?.success"
                class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-300"
            >
                {{ flash.success }}
            </div>
            <div
                v-if="flash?.error"
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700 dark:border-red-900 dark:bg-red-950/30 dark:text-red-300"
            >
                {{ flash.error }}
            </div>

            <div class="flex w-fit gap-1 rounded-xl bg-muted p-1">
                <button
                    v-for="tab in [
                        { id: 'company', label: 'Entreprise' },
                        { id: 'mail', label: 'E-mail / SMTP' },
                    ] as const"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        'rounded-lg px-5 py-2 text-sm font-medium transition-all',
                        activeTab === tab.id ? 'bg-background text-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground',
                    ]"
                >
                    {{ tab.label }}
                </button>
            </div>

            <section v-show="activeTab === 'company'" class="rounded-2xl border bg-card p-6 shadow-sm">
                <div class="flex flex-col gap-6">
                    <div class="rounded-2xl border bg-muted/20 p-5">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex items-start gap-3">
                                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                    <ImageIcon class="h-5 w-5" />
                                </div>
                                <div>
                                    <HeadingSmall title="Logo" description="Affiche dans les PDF de bons de commande." />
                                </div>
                            </div>

                            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                <div class="shrink-0">
                                    <div
                                        v-if="logoPreview"
                                        class="relative flex h-24 w-36 items-center justify-center rounded-xl border border-border bg-background p-3"
                                    >
                                        <img :src="logoPreview" alt="Logo" class="max-h-full max-w-full object-contain" />
                                        <button
                                            type="button"
                                            @click="removeLogo"
                                            :disabled="deleteLogo.processing"
                                            class="absolute -right-2 -top-2 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-xs text-white transition hover:bg-red-600"
                                            title="Supprimer le logo"
                                        >
                                            x
                                        </button>
                                    </div>
                                    <div
                                        v-else
                                        class="flex h-24 w-36 flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed border-border bg-background text-xs text-muted-foreground"
                                    >
                                        <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="1.5"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            />
                                        </svg>
                                        <span>Aucun logo</span>
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <Label for="company_logo">Importer un logo</Label>
                                    <Input
                                        id="company_logo"
                                        ref="logoInput"
                                        type="file"
                                        accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                        class="max-w-xs cursor-pointer"
                                        @change="onLogoChange"
                                    />
                                    <p class="text-xs text-muted-foreground">PNG, SVG ou JPG - max 2 Mo. Fond transparent recommande.</p>
                                    <InputError :message="companyForm.errors.company_logo" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <form @submit.prevent="submitCompany" class="flex flex-col gap-5">
                        <div>
                            <HeadingSmall
                                title="Identite de l'entreprise"
                                description="Ces informations apparaissent dans l'en-tete et le pied de page des PDF."
                            />
                        </div>

                        <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                            <div class="flex flex-col gap-2">
                                <Label for="company_name">Nom de l'entreprise <span class="text-red-500">*</span></Label>
                                <Input id="company_name" v-model="companyForm.company_name" placeholder="Ex : Groupe Harmonie SA" required />
                                <InputError :message="companyForm.errors.company_name" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label for="company_email">E-mail de contact</Label>
                                <Input id="company_email" v-model="companyForm.company_email" type="email" placeholder="contact@entreprise.com" />
                                <InputError :message="companyForm.errors.company_email" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label for="company_phone">Telephone</Label>
                                <Input id="company_phone" v-model="companyForm.company_phone" placeholder="+225 27 XX XX XX XX" />
                                <InputError :message="companyForm.errors.company_phone" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label for="company_website">Site web</Label>
                                <Input id="company_website" v-model="companyForm.company_website" placeholder="https://www.entreprise.com" />
                                <InputError :message="companyForm.errors.company_website" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label for="company_nif">NIF</Label>
                                <Input id="company_nif" v-model="companyForm.company_nif" placeholder="Numero d'identification fiscale" />
                                <InputError :message="companyForm.errors.company_nif" />
                            </div>
                            <div class="flex flex-col gap-2">
                                <Label for="company_rccm">RCCM</Label>
                                <Input id="company_rccm" v-model="companyForm.company_rccm" placeholder="Registre du commerce" />
                                <InputError :message="companyForm.errors.company_rccm" />
                            </div>
                        </div>

                        <div class="flex flex-col gap-2">
                            <Label for="company_address">Adresse complete</Label>
                            <textarea
                                id="company_address"
                                v-model="companyForm.company_address"
                                rows="2"
                                placeholder="Rue, Quartier, Ville, Pays"
                                class="w-full resize-none rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            />
                            <InputError :message="companyForm.errors.company_address" />
                        </div>

                        <div class="flex items-center justify-end gap-3 pt-2">
                            <span v-if="companyForm.recentlySuccessful" class="text-sm text-emerald-600 dark:text-emerald-300">Enregistre.</span>
                            <Button type="submit" :disabled="companyForm.processing">
                                {{ companyForm.processing ? 'Enregistrement...' : 'Enregistrer' }}
                            </Button>
                        </div>
                    </form>
                </div>
            </section>

            <div v-show="activeTab === 'mail'" class="space-y-4">
                <section class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="flex flex-col gap-5">
                        <div>
                            <HeadingSmall
                                title="Configuration SMTP"
                                description="Parametres d'envoi des e-mails : notifications, validations et tests."
                            />
                        </div>

                        <form @submit.prevent="submitMail" class="flex flex-col gap-5">
                            <div class="flex flex-col gap-2">
                                <Label for="mail_mailer">Driver d'envoi</Label>
                                <select
                                    id="mail_mailer"
                                    v-model="mailForm.mail_mailer"
                                    class="w-full max-w-xs rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                >
                                    <option value="resend">Resend (API)</option>
                                    <option value="smtp">SMTP</option>
                                    <option value="sendmail">Sendmail</option>
                                    <option value="log">Log (developpement)</option>
                                </select>
                                <InputError :message="mailForm.errors.mail_mailer" />
                            </div>

                            <div v-if="mailForm.mail_mailer === 'smtp'" class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                <div class="flex flex-col gap-2">
                                    <Label for="mail_host">Serveur SMTP</Label>
                                    <Input id="mail_host" v-model="mailForm.mail_host" placeholder="smtp.gmail.com" />
                                    <InputError :message="mailForm.errors.mail_host" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <Label for="mail_port">Port</Label>
                                    <Input id="mail_port" v-model="mailForm.mail_port" type="number" placeholder="587" />
                                    <InputError :message="mailForm.errors.mail_port" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <Label for="mail_encryption">Chiffrement</Label>
                                    <select
                                        id="mail_encryption"
                                        v-model="mailForm.mail_encryption"
                                        class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                                    >
                                        <option value="tls">TLS (recommande)</option>
                                        <option value="ssl">SSL</option>
                                        <option value="starttls">STARTTLS</option>
                                        <option value="">Aucun</option>
                                    </select>
                                    <InputError :message="mailForm.errors.mail_encryption" />
                                </div>
                                <div class="flex flex-col gap-2">
                                    <Label for="mail_username">Nom d'utilisateur</Label>
                                    <Input id="mail_username" v-model="mailForm.mail_username" autocomplete="off" placeholder="votre@email.com" />
                                    <InputError :message="mailForm.errors.mail_username" />
                                </div>
                                <div class="flex flex-col gap-2 xl:col-span-2">
                                    <Label for="mail_password">Mot de passe</Label>
                                    <Input
                                        id="mail_password"
                                        v-model="mailForm.mail_password"
                                        type="password"
                                        autocomplete="new-password"
                                        placeholder="Laisser vide pour ne pas modifier"
                                    />
                                    <p class="text-xs text-muted-foreground">Laissez vide pour conserver le mot de passe actuel.</p>
                                    <InputError :message="mailForm.errors.mail_password" />
                                </div>
                            </div>

                            <div class="rounded-2xl border bg-muted/20 p-5">
                                <p class="text-sm font-semibold text-foreground">Expediteur par defaut</p>
                                <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
                                    <div class="flex flex-col gap-2">
                                        <Label for="mail_from_name">Nom affiche <span class="text-red-500">*</span></Label>
                                        <Input id="mail_from_name" v-model="mailForm.mail_from_name" placeholder="AchatPro" required />
                                        <InputError :message="mailForm.errors.mail_from_name" />
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <Label for="mail_from_address">Adresse e-mail <span class="text-red-500">*</span></Label>
                                        <Input
                                            id="mail_from_address"
                                            v-model="mailForm.mail_from_address"
                                            type="email"
                                            placeholder="noreply@entreprise.com"
                                            required
                                        />
                                        <InputError :message="mailForm.errors.mail_from_address" />
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center justify-end gap-3 pt-2">
                                <span v-if="mailForm.recentlySuccessful" class="text-sm text-emerald-600 dark:text-emerald-300">Enregistre.</span>
                                <Button type="submit" :disabled="mailForm.processing">
                                    {{ mailForm.processing ? 'Enregistrement...' : 'Enregistrer la configuration' }}
                                </Button>
                            </div>
                        </form>
                    </div>
                </section>

                <section class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="flex flex-col gap-5">
                        <div>
                            <HeadingSmall title="Tester l'envoi" description="Envoyez un e-mail de test pour verifier votre configuration SMTP." />
                        </div>

                        <div class="rounded-2xl border bg-muted/20 p-5">
                            <form @submit.prevent="sendTest" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                                <div class="max-w-sm flex-1 space-y-2">
                                    <Label for="test_email">Adresse de test</Label>
                                    <Input id="test_email" v-model="testForm.test_email" type="email" placeholder="test@exemple.com" required />
                                    <InputError :message="testForm.errors.test_email" />
                                </div>
                                <Button type="submit" variant="outline" :disabled="testForm.processing">
                                    {{ testForm.processing ? 'Envoi...' : 'Envoyer un test' }}
                                </Button>
                            </form>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
