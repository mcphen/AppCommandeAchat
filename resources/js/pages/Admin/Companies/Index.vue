<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Building2,
    Check,
    Globe,
    Hash,
    Loader2,
    Mail,
    Pencil,
    Phone,
    Plus,
    Power,
    Trash2,
    Upload,
    X,
} from 'lucide-vue-next';
import Swal from 'sweetalert2';
import { ref } from 'vue';

interface Company {
    id: number;
    name: string;
    address: string | null;
    phone: string | null;
    email: string | null;
    website: string | null;
    nif: string | null;
    rccm: string | null;
    logo: string | null;
    logo_url: string | null;
    is_active: boolean;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Administration', href: '/admin/users' },
    { title: 'Entreprises', href: '/admin/companies' },
];

const props = defineProps<{ companies: Company[] }>();

// ─── Modal state ───────────────────────────────────────────────────────────────
const showModal = ref(false);
const editingCompany = ref<Company | null>(null);

const form = useForm({
    name: '',
    code: '',
    address: '',
    phone: '',
    email: '',
    website: '',
    nif: '',
    rccm: '',
    is_active: true as boolean,
});

const openCreate = () => {
    editingCompany.value = null;
    form.reset();
    form.is_active = true;
    showModal.value = true;
};

const openEdit = (company: Company) => {
    editingCompany.value = company;
    form.name = company.name;
    form.code = company.code ?? '';
    form.address = company.address ?? '';
    form.phone = company.phone ?? '';
    form.email = company.email ?? '';
    form.website = company.website ?? '';
    form.nif = company.nif ?? '';
    form.rccm = company.rccm ?? '';
    form.is_active = company.is_active;
    showModal.value = true;
};

const closeModal = () => {
    if (form.processing) return;
    showModal.value = false;
    editingCompany.value = null;
    form.reset();
};

const submitForm = () => {
    if (editingCompany.value) {
        form.patch(route('admin.companies.update', editingCompany.value.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('admin.companies.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

// ─── Logo upload ───────────────────────────────────────────────────────────────
const logoInput = ref<HTMLInputElement | null>(null);
const uploadingLogoId = ref<number | null>(null);

const uploadLogo = (company: Company) => {
    uploadingLogoId.value = company.id;
    logoInput.value?.click();
};

const onLogoChange = (event: Event) => {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    const companyId = uploadingLogoId.value;
    if (!file || !companyId) return;

    const logoForm = useForm({ logo: file });
    logoForm.post(route('admin.companies.logo.update', companyId), {
        forceFormData: true,
        onFinish: () => {
            uploadingLogoId.value = null;
            input.value = '';
        },
    });
};

const deleteLogo = async (company: Company) => {
    const result = await Swal.fire({
        title: 'Supprimer le logo ?',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });

    if (result.isConfirmed) {
        router.delete(route('admin.companies.logo.delete', company.id));
    }
};

// ─── Delete company ─────────────────────────────────────────────────────────────
const deleteCompany = async (company: Company) => {
    const result = await Swal.fire({
        title: 'Supprimer cette entreprise ?',
        text: `"${company.name}" sera définitivement supprimée si elle n'est liée à aucune demande.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });

    if (result.isConfirmed) {
        router.delete(route('admin.companies.destroy', company.id));
    }
};
</script>

<template>
    <Head title="Entreprises" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">

            <!-- Header -->
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold text-foreground sm:text-2xl">Entreprises</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ props.companies.length }} entreprise{{ props.companies.length !== 1 ? 's' : '' }} configurée{{ props.companies.length !== 1 ? 's' : '' }}
                    </p>
                </div>
                <button
                    @click="openCreate"
                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
                >
                    <Plus class="h-4 w-4" />
                    Nouvelle entreprise
                </button>
            </div>

            <!-- Hidden logo file input -->
            <input
                ref="logoInput"
                type="file"
                accept=".png,.jpg,.jpeg,.webp,.svg"
                class="hidden"
                @change="onLogoChange"
            />

            <!-- Company cards -->
            <div v-if="props.companies.length > 0" class="grid gap-4 lg:grid-cols-2">
                <div
                    v-for="company in props.companies"
                    :key="company.id"
                    class="rounded-2xl border bg-card p-5 shadow-sm"
                    :class="{ 'opacity-60': !company.is_active }"
                >
                    <!-- Card header: logo + name + badge + actions -->
                    <div class="flex items-start justify-between gap-3">
                        <div class="flex items-center gap-3 min-w-0">
                            <!-- Logo preview -->
                            <div class="relative shrink-0">
                                <div
                                    class="h-14 w-14 rounded-xl border bg-muted/40 overflow-hidden flex items-center justify-center cursor-pointer group"
                                    @click="uploadLogo(company)"
                                    :title="'Changer le logo'"
                                >
                                    <img
                                        v-if="company.logo_url"
                                        :src="company.logo_url"
                                        :alt="company.name"
                                        class="h-full w-full object-contain"
                                    />
                                    <Building2 v-else class="h-7 w-7 text-muted-foreground group-hover:text-primary transition-colors" />
                                    <div class="absolute inset-0 bg-black/30 opacity-0 group-hover:opacity-100 rounded-xl transition-opacity flex items-center justify-center">
                                        <Upload class="h-5 w-5 text-white" />
                                    </div>
                                </div>
                                <button
                                    v-if="company.logo_url"
                                    @click.stop="deleteLogo(company)"
                                    class="absolute -top-1.5 -right-1.5 h-5 w-5 rounded-full bg-red-500 text-white flex items-center justify-center hover:bg-red-600 transition-colors shadow"
                                    title="Supprimer le logo"
                                >
                                    <X class="h-3 w-3" />
                                </button>
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h2 class="text-base font-semibold text-foreground truncate">{{ company.name }}</h2>
                                    <span v-if="company.code" class="rounded-md bg-muted px-1.5 py-0.5 font-mono text-xs text-muted-foreground">{{ company.code }}</span>
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                        :class="company.is_active
                                            ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                                            : 'bg-gray-100 text-gray-500 border border-gray-200'"
                                    >
                                        <Check v-if="company.is_active" class="h-3 w-3" />
                                        <Power v-else class="h-3 w-3" />
                                        {{ company.is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                <p v-if="company.address" class="text-xs text-muted-foreground mt-0.5 truncate">{{ company.address }}</p>
                            </div>
                        </div>

                        <!-- Action buttons -->
                        <div class="flex shrink-0 items-center gap-1.5">
                            <button
                                @click="openEdit(company)"
                                class="rounded-lg p-2 text-muted-foreground hover:bg-muted hover:text-foreground transition-colors"
                                title="Modifier"
                            >
                                <Pencil class="h-4 w-4" />
                            </button>
                            <button
                                @click="deleteCompany(company)"
                                class="rounded-lg p-2 text-muted-foreground hover:bg-red-50 hover:text-red-600 transition-colors"
                                title="Supprimer"
                            >
                                <Trash2 class="h-4 w-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <div v-if="company.phone" class="flex items-center gap-2 text-xs text-muted-foreground">
                            <Phone class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{ company.phone }}</span>
                        </div>
                        <div v-if="company.email" class="flex items-center gap-2 text-xs text-muted-foreground">
                            <Mail class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{ company.email }}</span>
                        </div>
                        <div v-if="company.website" class="flex items-center gap-2 text-xs text-muted-foreground">
                            <Globe class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">{{ company.website }}</span>
                        </div>
                        <div v-if="company.nif" class="flex items-center gap-2 text-xs text-muted-foreground">
                            <Hash class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">NIF : {{ company.nif }}</span>
                        </div>
                        <div v-if="company.rccm" class="flex items-center gap-2 text-xs text-muted-foreground">
                            <Hash class="h-3.5 w-3.5 shrink-0" />
                            <span class="truncate">RCCM : {{ company.rccm }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center rounded-2xl border border-dashed bg-card p-16 text-center">
                <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-muted">
                    <Building2 class="h-8 w-8 text-muted-foreground" />
                </div>
                <h3 class="text-base font-semibold text-foreground">Aucune entreprise</h3>
                <p class="mt-1 text-sm text-muted-foreground">Créez votre première entreprise pour commencer.</p>
                <button
                    @click="openCreate"
                    class="mt-6 inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm hover:bg-primary/90 transition-colors"
                >
                    <Plus class="h-4 w-4" />
                    Nouvelle entreprise
                </button>
            </div>

        </div>

        <!-- Modal create / edit -->
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
                    <div v-if="showModal" class="w-full max-w-lg rounded-2xl border bg-card shadow-xl overflow-y-auto max-h-[90vh]">
                        <!-- Modal header -->
                        <div class="flex items-center justify-between border-b px-6 py-4">
                            <h2 class="text-base font-semibold text-foreground">
                                {{ editingCompany ? 'Modifier l\'entreprise' : 'Nouvelle entreprise' }}
                            </h2>
                            <button
                                type="button"
                                class="rounded-lg p-1.5 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                @click="closeModal"
                            >
                                <X class="h-4 w-4" />
                            </button>
                        </div>

                        <form @submit.prevent="submitForm" class="flex flex-col gap-4 px-6 py-5">

                            <!-- Name -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">
                                    Nom <span class="text-red-500">*</span>
                                </label>
                                <input
                                    v-model="form.name"
                                    type="text"
                                    placeholder="Ex : Ma Société SARL"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                    :class="{ 'border-red-400': form.errors.name }"
                                />
                                <p v-if="form.errors.name" class="flex items-center gap-1 text-xs text-red-500">
                                    <AlertTriangle class="h-3.5 w-3.5" /> {{ form.errors.name }}
                                </p>
                            </div>

                            <!-- Code -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Code</label>
                                <input
                                    v-model="form.code"
                                    type="text"
                                    placeholder="Ex : SCN, ENT1…"
                                    maxlength="20"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                />
                            </div>

                            <!-- Address -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Adresse</label>
                                <input
                                    v-model="form.address"
                                    type="text"
                                    placeholder="Ex : Avenue de l'Indépendance, Conakry"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                />
                            </div>

                            <!-- Phone + Email -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground">Téléphone</label>
                                    <input
                                        v-model="form.phone"
                                        type="text"
                                        placeholder="+224 620 000 000"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                        :class="{ 'border-red-400': form.errors.phone }"
                                    />
                                    <p v-if="form.errors.phone" class="text-xs text-red-500">{{ form.errors.phone }}</p>
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground">Email</label>
                                    <input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="contact@societe.gn"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                        :class="{ 'border-red-400': form.errors.email }"
                                    />
                                    <p v-if="form.errors.email" class="text-xs text-red-500">{{ form.errors.email }}</p>
                                </div>
                            </div>

                            <!-- Website -->
                            <div class="flex flex-col gap-1.5">
                                <label class="text-sm font-medium text-foreground">Site web</label>
                                <input
                                    v-model="form.website"
                                    type="url"
                                    placeholder="https://www.societe.gn"
                                    class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                    :class="{ 'border-red-400': form.errors.website }"
                                />
                                <p v-if="form.errors.website" class="text-xs text-red-500">{{ form.errors.website }}</p>
                            </div>

                            <!-- NIF + RCCM -->
                            <div class="grid grid-cols-2 gap-3">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground">NIF</label>
                                    <input
                                        v-model="form.nif"
                                        type="text"
                                        placeholder="NIF de l'entreprise"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                    />
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium text-foreground">RCCM</label>
                                    <input
                                        v-model="form.rccm"
                                        type="text"
                                        placeholder="RCCM de l'entreprise"
                                        class="h-10 w-full rounded-xl border border-input bg-background px-4 text-sm text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/30 focus:border-primary transition-colors"
                                    />
                                </div>
                            </div>

                            <!-- Active toggle (only on edit) -->
                            <div v-if="editingCompany" class="flex items-center gap-3">
                                <button
                                    type="button"
                                    @click="form.is_active = !form.is_active"
                                    class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none"
                                    :class="form.is_active ? 'bg-primary' : 'bg-muted-foreground/30'"
                                >
                                    <span
                                        class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200"
                                        :class="form.is_active ? 'translate-x-5' : 'translate-x-0'"
                                    />
                                </button>
                                <label class="text-sm font-medium text-foreground cursor-pointer" @click="form.is_active = !form.is_active">
                                    Entreprise active
                                </label>
                            </div>

                            <!-- Buttons -->
                            <div class="flex items-center justify-end gap-3 border-t pt-4 mt-1">
                                <button
                                    type="button"
                                    class="rounded-xl border px-4 py-2 text-sm font-semibold text-foreground transition-colors hover:bg-muted disabled:opacity-50"
                                    :disabled="form.processing"
                                    @click="closeModal"
                                >
                                    Annuler
                                </button>
                                <button
                                    type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-50"
                                    :disabled="form.processing"
                                >
                                    <Loader2 v-if="form.processing" class="h-4 w-4 animate-spin" />
                                    <Check v-else class="h-4 w-4" />
                                    {{ editingCompany ? 'Enregistrer' : 'Créer' }}
                                </button>
                            </div>
                        </form>
                    </div>
                </Transition>
            </div>
        </Transition>

    </AppLayout>
</template>
