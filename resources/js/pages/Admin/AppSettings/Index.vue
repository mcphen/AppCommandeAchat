<script setup lang="ts">
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
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

// ── Onglet actif ──────────────────────────────────────────────────────────────
const activeTab = ref<'company' | 'mail'>('company');

// ── Formulaire Entreprise ─────────────────────────────────────────────────────
const logoPreview = ref<string | null>(props.logoUrl);
const logoInput = ref<HTMLInputElement | null>(null);

const companyForm = useForm({
    company_name:    s.company_name    ?? '',
    company_address: s.company_address ?? '',
    company_phone:   s.company_phone   ?? '',
    company_email:   s.company_email   ?? '',
    company_website: s.company_website ?? '',
    company_nif:     s.company_nif     ?? '',
    company_rccm:    s.company_rccm    ?? '',
    company_logo:    null as File | null,
});

const onLogoChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    companyForm.company_logo = file;
    logoPreview.value = URL.createObjectURL(file);
};

const submitCompany = () => {
    companyForm.patch(route('admin.settings.company'), {
        forceFormData: true,
        preserveScroll: true,
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

// ── Formulaire Mail ───────────────────────────────────────────────────────────
const mailForm = useForm({
    mail_mailer:       s.mail_mailer       ?? 'log',
    mail_host:         s.mail_host         ?? '',
    mail_port:         s.mail_port         ?? '587',
    mail_encryption:   s.mail_encryption   ?? 'tls',
    mail_username:     s.mail_username     ?? '',
    mail_password:     '',   // toujours vide à l'affichage
    mail_from_address: s.mail_from_address ?? '',
    mail_from_name:    s.mail_from_name    ?? '',
});

const submitMail = () => {
    mailForm.patch(route('admin.settings.mail'), { preserveScroll: true });
};

// ── Test SMTP ─────────────────────────────────────────────────────────────────
const testForm = useForm({ test_email: '' });
const sendTest = () => {
    testForm.post(route('admin.settings.test-mail'), { preserveScroll: true });
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Configuration" />

        <div class="mx-auto max-w-4xl px-4 py-8 space-y-6">

            <!-- En-tête -->
            <div>
                <h1 class="text-2xl font-bold text-slate-900">Configuration de l'application</h1>
                <p class="text-sm text-slate-500 mt-1">Paramètres globaux : identité de l'entreprise et envoi d'e-mails.</p>
            </div>

            <!-- Flash -->
            <div v-if="flash?.success" class="rounded-lg bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-700 font-medium">
                {{ flash.success }}
            </div>
            <div v-if="flash?.error" class="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700 font-medium">
                {{ flash.error }}
            </div>

            <!-- Onglets -->
            <div class="flex gap-1 rounded-xl bg-slate-100 p-1 w-fit">
                <button
                    v-for="tab in ([{ id: 'company', label: 'Entreprise' }, { id: 'mail', label: 'E-mail / SMTP' }] as const)"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    :class="[
                        'rounded-lg px-5 py-2 text-sm font-medium transition-all',
                        activeTab === tab.id
                            ? 'bg-white text-slate-900 shadow-sm'
                            : 'text-slate-500 hover:text-slate-700',
                    ]"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- ── TAB : Entreprise ───────────────────────────────────────────── -->
            <div v-show="activeTab === 'company'" class="bg-white rounded-2xl border border-slate-200 shadow-sm divide-y divide-slate-100">

                <!-- Logo -->
                <div class="p-6 space-y-4">
                    <HeadingSmall title="Logo" description="Affiché dans les PDF de bons de commande." />

                    <div class="flex items-start gap-6">
                        <!-- Aperçu -->
                        <div class="flex-shrink-0">
                            <div v-if="logoPreview" class="relative rounded-xl border border-slate-200 bg-slate-50 p-3 w-36 h-24 flex items-center justify-center">
                                <img :src="logoPreview" alt="Logo" class="max-h-full max-w-full object-contain" />
                                <button
                                    type="button"
                                    @click="removeLogo"
                                    :disabled="deleteLogo.processing"
                                    class="absolute -top-2 -right-2 h-5 w-5 rounded-full bg-red-500 text-white flex items-center justify-center text-xs hover:bg-red-600 transition"
                                    title="Supprimer le logo"
                                >✕</button>
                            </div>
                            <div v-else class="rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 w-36 h-24 flex flex-col items-center justify-center text-slate-300 text-xs gap-1">
                                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                <span>Aucun logo</span>
                            </div>
                        </div>

                        <!-- Upload -->
                        <div class="space-y-2">
                            <Label for="company_logo">Importer un logo</Label>
                            <Input
                                id="company_logo"
                                ref="logoInput"
                                type="file"
                                accept="image/png,image/jpeg,image/webp,image/svg+xml"
                                class="cursor-pointer max-w-xs"
                                @change="onLogoChange"
                            />
                            <p class="text-xs text-slate-400">PNG, SVG ou JPG — max 2 Mo. Fond transparent recommandé.</p>
                            <InputError :message="companyForm.errors.company_logo" />
                        </div>
                    </div>
                </div>

                <!-- Infos entreprise -->
                <form @submit.prevent="submitCompany" class="p-6 space-y-5">
                    <HeadingSmall title="Identité" description="Ces informations apparaissent dans l'en-tête et pied de page des PDF." />

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="company_name">Nom de l'entreprise <span class="text-red-500">*</span></Label>
                            <Input id="company_name" v-model="companyForm.company_name" placeholder="Ex : Groupe Harmonie SA" required />
                            <InputError :message="companyForm.errors.company_name" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="company_email">E-mail de contact</Label>
                            <Input id="company_email" type="email" v-model="companyForm.company_email" placeholder="contact@entreprise.com" />
                            <InputError :message="companyForm.errors.company_email" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="company_phone">Téléphone</Label>
                            <Input id="company_phone" v-model="companyForm.company_phone" placeholder="+225 27 XX XX XX XX" />
                            <InputError :message="companyForm.errors.company_phone" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="company_website">Site web</Label>
                            <Input id="company_website" v-model="companyForm.company_website" placeholder="https://www.entreprise.com" />
                            <InputError :message="companyForm.errors.company_website" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="company_nif">NIF</Label>
                            <Input id="company_nif" v-model="companyForm.company_nif" placeholder="Numéro d'identification fiscale" />
                            <InputError :message="companyForm.errors.company_nif" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="company_rccm">RCCM</Label>
                            <Input id="company_rccm" v-model="companyForm.company_rccm" placeholder="Registre du commerce" />
                            <InputError :message="companyForm.errors.company_rccm" />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="company_address">Adresse complète</Label>
                        <textarea
                            id="company_address"
                            v-model="companyForm.company_address"
                            rows="2"
                            placeholder="Rue, Quartier, Ville, Pays"
                            class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring resize-none"
                        />
                        <InputError :message="companyForm.errors.company_address" />
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <Button type="submit" :disabled="companyForm.processing">
                            {{ companyForm.processing ? 'Enregistrement…' : 'Enregistrer' }}
                        </Button>
                        <span v-if="companyForm.recentlySuccessful" class="text-sm text-emerald-600">Enregistré.</span>
                    </div>
                </form>
            </div>

            <!-- ── TAB : SMTP ─────────────────────────────────────────────────── -->
            <div v-show="activeTab === 'mail'" class="space-y-4">

                <!-- Config SMTP -->
                <form @submit.prevent="submitMail" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-5">
                    <HeadingSmall title="Configuration SMTP" description="Paramètres d'envoi des e-mails (notifications, validations…)." />

                    <!-- Mailer -->
                    <div class="space-y-1.5">
                        <Label for="mail_mailer">Driver d'envoi</Label>
                        <select
                            id="mail_mailer"
                            v-model="mailForm.mail_mailer"
                            class="w-full max-w-xs rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                        >
                            <option value="smtp">SMTP</option>
                            <option value="sendmail">Sendmail</option>
                            <option value="log">Log (développement)</option>
                        </select>
                        <InputError :message="mailForm.errors.mail_mailer" />
                    </div>

                    <div v-if="mailForm.mail_mailer === 'smtp'" class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <Label for="mail_host">Serveur SMTP</Label>
                            <Input id="mail_host" v-model="mailForm.mail_host" placeholder="smtp.gmail.com" />
                            <InputError :message="mailForm.errors.mail_host" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="mail_port">Port</Label>
                            <Input id="mail_port" type="number" v-model="mailForm.mail_port" placeholder="587" />
                            <InputError :message="mailForm.errors.mail_port" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="mail_encryption">Chiffrement</Label>
                            <select
                                id="mail_encryption"
                                v-model="mailForm.mail_encryption"
                                class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                            >
                                <option value="tls">TLS (recommandé)</option>
                                <option value="ssl">SSL</option>
                                <option value="starttls">STARTTLS</option>
                                <option value="">Aucun</option>
                            </select>
                            <InputError :message="mailForm.errors.mail_encryption" />
                        </div>
                        <div class="space-y-1.5">
                            <Label for="mail_username">Nom d'utilisateur</Label>
                            <Input id="mail_username" v-model="mailForm.mail_username" placeholder="votre@email.com" autocomplete="off" />
                            <InputError :message="mailForm.errors.mail_username" />
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <Label for="mail_password">Mot de passe</Label>
                            <Input id="mail_password" type="password" v-model="mailForm.mail_password" placeholder="Laisser vide pour ne pas modifier" autocomplete="new-password" />
                            <p class="text-xs text-slate-400">Laissez vide pour conserver le mot de passe actuel.</p>
                            <InputError :message="mailForm.errors.mail_password" />
                        </div>
                    </div>

                    <!-- Expéditeur -->
                    <div class="border-t border-slate-100 pt-5 space-y-4">
                        <p class="text-sm font-semibold text-slate-700">Expéditeur par défaut</p>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div class="space-y-1.5">
                                <Label for="mail_from_name">Nom affiché <span class="text-red-500">*</span></Label>
                                <Input id="mail_from_name" v-model="mailForm.mail_from_name" placeholder="AchatPro" required />
                                <InputError :message="mailForm.errors.mail_from_name" />
                            </div>
                            <div class="space-y-1.5">
                                <Label for="mail_from_address">Adresse e-mail <span class="text-red-500">*</span></Label>
                                <Input id="mail_from_address" type="email" v-model="mailForm.mail_from_address" placeholder="noreply@entreprise.com" required />
                                <InputError :message="mailForm.errors.mail_from_address" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-3 pt-2">
                        <Button type="submit" :disabled="mailForm.processing">
                            {{ mailForm.processing ? 'Enregistrement…' : 'Enregistrer la configuration' }}
                        </Button>
                        <span v-if="mailForm.recentlySuccessful" class="text-sm text-emerald-600">Enregistré.</span>
                    </div>
                </form>

                <!-- Test SMTP -->
                <div class="bg-slate-50 rounded-2xl border border-slate-200 p-6 space-y-4">
                    <HeadingSmall title="Tester l'envoi" description="Envoi d'un e-mail de test pour vérifier votre configuration SMTP." />
                    <form @submit.prevent="sendTest" class="flex items-end gap-3">
                        <div class="space-y-1.5 flex-1 max-w-sm">
                            <Label for="test_email">Adresse de test</Label>
                            <Input id="test_email" type="email" v-model="testForm.test_email" placeholder="test@exemple.com" required />
                            <InputError :message="testForm.errors.test_email" />
                        </div>
                        <Button type="submit" variant="outline" :disabled="testForm.processing">
                            {{ testForm.processing ? 'Envoi…' : 'Envoyer un test' }}
                        </Button>
                    </form>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
