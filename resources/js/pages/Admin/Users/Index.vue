<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type BreadcrumbItem, type PaginatedData, type User } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Plus, Pencil, Trash2, Users, Shield, CheckSquare, ShoppingCart, KeyRound } from 'lucide-vue-next';
import { computed, ref } from 'vue';
import Swal from 'sweetalert2';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Administration', href: '#' },
    { title: 'Utilisateurs', href: '/admin/users' },
];

const props = defineProps<{ users: PaginatedData<User> }>();

const roleConfig = {
    admin: { label: 'Admin', classes: 'bg-violet-50 text-violet-700', icon: Shield },
    demandeur: { label: 'Demandeur', classes: 'bg-blue-50 text-blue-700', icon: ShoppingCart },
    validateur: { label: 'Validateur', classes: 'bg-emerald-50 text-emerald-700', icon: CheckSquare },
} as const;

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });

const deleteUser = async (user: User) => {
    const result = await Swal.fire({
        title: 'Supprimer cet utilisateur ?',
        text: `L'utilisateur "${user.name}" sera definitivement supprime.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280',
        reverseButtons: true,
    });

    if (result.isConfirmed) {
        router.delete(route('admin.users.destroy', user.id));
    }
};

// Sélection multiple + réinitialisation groupée du mot de passe
const selectedIds = ref<number[]>([]);
const isResetModalOpen = ref(false);

const allSelected = computed(
    () => props.users.data.length > 0 && selectedIds.value.length === props.users.data.length,
);

const toggleSelectAll = (checked: boolean) => {
    selectedIds.value = checked ? props.users.data.map((u) => u.id) : [];
};

const toggleUser = (userId: number, checked: boolean) => {
    if (checked) {
        if (!selectedIds.value.includes(userId)) selectedIds.value.push(userId);
    } else {
        selectedIds.value = selectedIds.value.filter((id) => id !== userId);
    }
};

const selectedUsers = computed(() => props.users.data.filter((u) => selectedIds.value.includes(u.id)));

const resetForm = useForm({
    user_ids: [] as number[],
    password: '',
    password_confirmation: '',
    cc_email: '',
});

const openResetModal = () => {
    resetForm.reset();
    resetForm.clearErrors();
    isResetModalOpen.value = true;
};

const closeResetModal = () => {
    isResetModalOpen.value = false;
    resetForm.reset();
    resetForm.clearErrors();
};

const submitReset = (e: Event) => {
    e.preventDefault();

    resetForm.user_ids = selectedIds.value;

    resetForm.post(route('admin.users.bulk-reset-password'), {
        preserveScroll: true,
        onSuccess: () => {
            closeResetModal();
            selectedIds.value = [];
        },
    });
};
</script>

<template>
    <Head title="Utilisateurs" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-4 p-3 sm:gap-6 sm:p-6">
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <h1 class="text-xl font-bold text-foreground sm:text-2xl">Utilisateurs</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        {{ users.total }} utilisateur{{ users.total !== 1 ? 's' : '' }}
                        <span v-if="selectedIds.length > 0">· {{ selectedIds.length }} sélectionné{{ selectedIds.length > 1 ? 's' : '' }}</span>
                    </p>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    <button
                        v-if="selectedIds.length > 0"
                        type="button"
                        @click="openResetModal"
                        class="inline-flex items-center gap-2 rounded-xl border border-input bg-card px-3 py-2.5 text-sm font-semibold text-foreground shadow-sm transition-colors hover:bg-muted sm:px-4"
                    >
                        <KeyRound class="h-4 w-4" />
                        <span class="hidden sm:inline">Réinitialiser le mot de passe</span>
                        <span class="sm:hidden">Réinitialiser</span>
                    </button>
                    <Link
                        :href="route('admin.users.create')"
                        class="inline-flex items-center gap-2 rounded-xl bg-primary px-3 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90 sm:px-4"
                    >
                        <Plus class="h-4 w-4" />
                        <span class="hidden sm:inline">Nouvel utilisateur</span>
                    </Link>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <template v-if="users.data.length > 0">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b bg-muted/30">
                                    <th class="w-10 px-4 py-3.5 sm:px-6">
                                        <Checkbox :model-value="allSelected" @update:model-value="toggleSelectAll" aria-label="Tout sélectionner" />
                                    </th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Utilisateur</th>
                                    <th class="px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Role</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground md:table-cell sm:px-6">Société</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground lg:table-cell sm:px-6">Niveau</th>
                                    <th class="hidden px-4 py-3.5 text-left text-xs font-semibold uppercase tracking-wider text-muted-foreground xl:table-cell sm:px-6">Cree le</th>
                                    <th class="px-4 py-3.5 text-right text-xs font-semibold uppercase tracking-wider text-muted-foreground sm:px-6">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y">
                                <tr
                                    v-for="user in users.data"
                                    :key="user.id"
                                    class="transition-colors hover:bg-muted/20"
                                    :class="{ 'bg-primary/5': selectedIds.includes(user.id) }"
                                >
                                    <td class="px-4 py-4 sm:px-6">
                                        <Checkbox
                                            :model-value="selectedIds.includes(user.id)"
                                            @update:model-value="(checked) => toggleUser(user.id, !!checked)"
                                            :aria-label="`Sélectionner ${user.name}`"
                                        />
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-primary/20 to-primary/40 text-sm font-bold text-primary sm:h-9 sm:w-9">
                                                {{ user.name.charAt(0).toUpperCase() }}
                                            </div>
                                            <div class="min-w-0">
                                                <p class="max-w-[120px] truncate font-medium text-foreground sm:max-w-none">{{ user.name }}</p>
                                                <p class="max-w-[120px] truncate text-xs text-muted-foreground sm:max-w-none">{{ user.email }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <span
                                            v-if="user.role"
                                            class="inline-flex items-center gap-1.5 rounded-full px-2 py-1 text-xs font-medium sm:px-2.5"
                                            :class="roleConfig[user.role.slug as keyof typeof roleConfig]?.classes ?? 'bg-muted text-muted-foreground'"
                                        >
                                            {{ user.role.name }}
                                        </span>
                                        <span v-else class="text-xs text-muted-foreground">-</span>
                                    </td>
                                    <td class="hidden px-4 py-4 md:table-cell sm:px-6">
                                        <span v-if="user.boutique" class="text-sm text-foreground">{{ user.boutique.name }}</span>
                                        <span v-else class="text-xs text-muted-foreground">Groupe</span>
                                    </td>
                                    <td class="hidden px-4 py-4 lg:table-cell sm:px-6">
                                        <span v-if="user.validation_level" class="text-sm text-foreground">
                                            {{ user.validation_level.name }}
                                            <span class="text-xs text-muted-foreground">(N{{ user.validation_level.order }})</span>
                                        </span>
                                        <span v-else class="text-xs text-muted-foreground">-</span>
                                    </td>
                                    <td class="hidden px-4 py-4 text-sm text-muted-foreground xl:table-cell sm:px-6">
                                        {{ formatDate(user.created_at) }}
                                    </td>
                                    <td class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center justify-end gap-1">
                                            <Link
                                                :href="route('admin.users.edit', user.id)"
                                                class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                                            >
                                                <Pencil class="h-4 w-4" />
                                            </Link>
                                            <button
                                                @click="deleteUser(user)"
                                                class="rounded-lg p-2 text-muted-foreground transition-colors hover:bg-red-50 hover:text-red-600"
                                            >
                                                <Trash2 class="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div v-if="users.last_page > 1" class="flex flex-col items-center gap-3 border-t px-4 py-4 sm:flex-row sm:justify-between sm:px-6">
                        <p class="text-sm text-muted-foreground">{{ users.from }}-{{ users.to }} sur {{ users.total }}</p>
                        <div class="flex flex-wrap items-center justify-center gap-1">
                            <Link
                                v-for="link in users.links"
                                :key="link.label"
                                :href="link.url ?? '#'"
                                :class="[
                                    'rounded-lg px-3 py-1.5 text-sm font-medium transition-colors',
                                    link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted',
                                    !link.url ? 'pointer-events-none opacity-40' : '',
                                ]"
                                v-html="link.label"
                            />
                        </div>
                    </div>
                </template>

                <EmptyState
                    v-else
                    :icon="Users"
                    icon-bg="bg-indigo-50"
                    icon-color="text-indigo-500"
                    title="Aucun utilisateur"
                    description="Invitez les membres de votre équipe — demandeurs et validateurs — pour qu'ils puissent accéder à l'application."
                    :action-href="route('admin.users.create')"
                    action-label="Inviter un utilisateur"
                    :bordered="false"
                />
            </div>
        </div>

        <Dialog :open="isResetModalOpen" @update:open="(open) => !open && closeResetModal()">
            <DialogContent>
                <form class="space-y-5" @submit="submitReset">
                    <DialogHeader class="space-y-2">
                        <DialogTitle class="flex items-center gap-2">
                            <KeyRound class="h-5 w-5 text-primary" />
                            Réinitialiser le mot de passe
                        </DialogTitle>
                        <DialogDescription>
                            Un nouveau mot de passe sera envoyé par e-mail à
                            <strong>{{ selectedUsers.length }}</strong> utilisateur{{ selectedUsers.length > 1 ? 's' : '' }} :
                        </DialogDescription>
                    </DialogHeader>

                    <div class="flex flex-wrap gap-1.5 rounded-xl bg-muted/40 p-3">
                        <span
                            v-for="user in selectedUsers"
                            :key="user.id"
                            class="inline-flex items-center rounded-full bg-card px-2.5 py-1 text-xs font-medium text-foreground shadow-sm"
                        >
                            {{ user.name }}
                        </span>
                    </div>

                    <div class="grid gap-2">
                        <Label for="reset-password">Nouveau mot de passe</Label>
                        <Input id="reset-password" type="password" v-model="resetForm.password" placeholder="Nouveau mot de passe" autocomplete="new-password" />
                        <InputError :message="resetForm.errors.password" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="reset-password-confirmation">Confirmer le mot de passe</Label>
                        <Input
                            id="reset-password-confirmation"
                            type="password"
                            v-model="resetForm.password_confirmation"
                            placeholder="Confirmer le mot de passe"
                            autocomplete="new-password"
                        />
                        <InputError :message="resetForm.errors.password_confirmation" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="reset-cc-email">Mettre en copie (optionnel)</Label>
                        <Input id="reset-cc-email" type="email" v-model="resetForm.cc_email" placeholder="exemple@entreprise.com" />
                        <InputError :message="resetForm.errors.cc_email" />
                    </div>

                    <DialogFooter>
                        <DialogClose as-child>
                            <Button type="button" variant="secondary" @click="closeResetModal">Annuler</Button>
                        </DialogClose>
                        <Button type="submit" :disabled="resetForm.processing">
                            {{ resetForm.processing ? 'Envoi en cours…' : 'Réinitialiser et envoyer' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
