<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type PaginatedData, type Project, type ReceptionTransfer } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, Boxes, CalendarDays, HardHat, PackageCheck, RotateCcw, Search, SlidersHorizontal } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';


type AvailableReception = {
    id: number;
    received_at: string;
    purchase_order: { id: number; order_number?: string | null; title: string };
    lines: { id: number; label: string; unit: string; quantity_received: number; quantity_transferred: number; quantity_available: number }[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Réceptions', href: '/receptions' },
    { title: 'Transferts chantiers', href: '#' },
];

const props = defineProps<{
    transfers: PaginatedData<ReceptionTransfer>;
    projects: Pick<Project, 'id' | 'code' | 'name'>[];
    availableReceptions: AvailableReception[];
    filters: { project_id: string; date_from: string; date_to: string; search: string };
    stats: { transfers: number; projects: number; transferred_quantity: number; pending_quantity: number };
}>();

const localFilters = ref({ ...props.filters });
let timer: ReturnType<typeof setTimeout>;
watch(() => localFilters.value.search, () => {
    clearTimeout(timer);
    timer = setTimeout(applyFilters, 350);
});
watch([
    () => localFilters.value.project_id,
    () => localFilters.value.date_from,
    () => localFilters.value.date_to,
], applyFilters);

function applyFilters() {
    router.get(route('transfers.index'), localFilters.value, { preserveState: true, replace: true });
}
function clearFilters() {
    localFilters.value = { project_id: '', date_from: '', date_to: '', search: '' };
}
const hasFilters = computed(() => Object.values(localFilters.value).some(Boolean));
const formatQty = (value: string | number) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(Number(value));
const formatDate = (value: string) => new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
const orderOf = (transfer: ReceptionTransfer) => (transfer as any).reception?.purchase_order;
const transferLines = (transfer: ReceptionTransfer) => transfer.lines ?? [];

const showCreateForm = ref(false);
const transferForm = ref({
    reception_id: '',
    project_id: '',
    transferred_at: new Date().toISOString().slice(0, 10),
    reference: '',
    notes: '',
    lines: [] as { reception_line_id: number; label: string; unit: string; max: number; quantity_transferred: number }[],
});
const selectedReception = computed(() =>
    props.availableReceptions.find(item => String(item.id) === transferForm.value.reception_id)
);
watch(() => transferForm.value.reception_id, () => {
    transferForm.value.lines = (selectedReception.value?.lines ?? []).map(line => ({
        reception_line_id: line.id,
        label: line.label,
        unit: line.unit,
        max: line.quantity_available,
        quantity_transferred: line.quantity_available,
    }));
});
const submitTransfer = () => {
    const reception = selectedReception.value;
    if (!reception) return;
    router.post(route('purchase-orders.receptions.transfers.store', {
        purchase_order: reception.purchase_order.id,
        reception: reception.id,
    }), {
        project_id: transferForm.value.project_id,
        transferred_at: transferForm.value.transferred_at,
        reference: transferForm.value.reference || null,
        notes: transferForm.value.notes || null,
        lines: transferForm.value.lines
            .filter(line => Number(line.quantity_transferred) > 0)
            .map(line => ({ reception_line_id: line.reception_line_id, quantity_transferred: line.quantity_transferred })),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            showCreateForm.value = false;
            transferForm.value.reception_id = '';
            transferForm.value.project_id = '';
            transferForm.value.reference = '';
            transferForm.value.notes = '';
            transferForm.value.lines = [];
        },
    });
};
</script>

<template>
    <Head title="Transferts chantiers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-3 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Transferts vers les chantiers</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Affectation et traçabilité des articles réceptionnés vers leurs chantiers de destination.</p>
                </div>
                <button class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700" @click="showCreateForm = !showCreateForm">
                    {{ showCreateForm ? 'Fermer' : 'Nouveau transfert' }}
                </button>
            </div>

            <div v-if="showCreateForm" class="rounded-2xl border border-amber-200 bg-amber-50/40 p-5 shadow-sm">
                <div class="mb-4"><h2 class="font-semibold text-foreground">Nouveau transfert</h2><p class="text-xs text-muted-foreground">Choisissez une réception, puis affectez ses quantités disponibles à un chantier.</p></div>
                <div v-if="availableReceptions.length" class="space-y-4">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                        <label class="text-xs font-medium text-muted-foreground xl:col-span-2">Réception source
                            <select v-model="transferForm.reception_id" class="mt-1 h-10 w-full rounded-xl border bg-white px-3 text-sm">
                                <option value="" disabled>Sélectionner un BC réceptionné…</option>
                                <option v-for="reception in availableReceptions" :key="reception.id" :value="String(reception.id)">
                                    {{ reception.purchase_order.order_number || reception.purchase_order.title }} — reçue le {{ formatDate(reception.received_at) }}
                                </option>
                            </select>
                        </label>
                        <label class="text-xs font-medium text-muted-foreground">Chantier destination
                            <select v-model="transferForm.project_id" class="mt-1 h-10 w-full rounded-xl border bg-white px-3 text-sm">
                                <option value="" disabled>Sélectionner…</option>
                                <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.code }} — {{ project.name }}</option>
                            </select>
                        </label>
                        <label class="text-xs font-medium text-muted-foreground">Date
                            <input v-model="transferForm.transferred_at" type="date" class="mt-1 h-10 w-full rounded-xl border bg-white px-3 text-sm" />
                        </label>
                    </div>
                    <div v-if="selectedReception" class="rounded-xl border bg-white p-4">
                        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">Articles disponibles</p>
                        <div class="space-y-2">
                            <div v-for="line in transferForm.lines" :key="line.reception_line_id" class="grid grid-cols-[1fr_auto_auto] items-center gap-3">
                                <span class="truncate text-sm">{{ line.label }}</span>
                                <input v-model.number="line.quantity_transferred" type="number" min="0" :max="line.max" step="0.01" class="h-9 w-28 rounded-lg border px-2 text-right text-sm" />
                                <span class="w-28 text-xs text-muted-foreground">/ {{ formatQty(line.max) }} {{ line.unit }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="text-xs font-medium text-muted-foreground">Référence du bon de sortie
                            <input v-model="transferForm.reference" type="text" maxlength="100" placeholder="Ex. BS-2026-001" class="mt-1 h-10 w-full rounded-xl border bg-white px-3 text-sm" />
                        </label>
                        <label class="text-xs font-medium text-muted-foreground">Notes
                            <input v-model="transferForm.notes" type="text" maxlength="1000" class="mt-1 h-10 w-full rounded-xl border bg-white px-3 text-sm" />
                        </label>
                    </div>
                    <div class="flex gap-2">
                        <button :disabled="!selectedReception || !transferForm.project_id || !transferForm.lines.some(line => Number(line.quantity_transferred) > 0)" class="rounded-xl bg-amber-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50" @click="submitTransfer">Enregistrer le transfert</button>
                        <button class="rounded-xl border bg-white px-4 py-2 text-sm" @click="showCreateForm = false">Annuler</button>
                    </div>
                </div>
                <EmptyState v-else :icon="PackageCheck" icon-bg="bg-emerald-50" icon-color="text-emerald-500" title="Tout est déjà affecté" description="Aucune quantité réceptionnée ne reste à transférer vers un chantier." />
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div v-for="card in [
                    { label: 'Mouvements enregistrés', value: stats.transfers, icon: PackageCheck, color: 'text-blue-600', bg: 'bg-blue-100' },
                    { label: 'Chantiers servis', value: stats.projects, icon: HardHat, color: 'text-amber-600', bg: 'bg-amber-100' },
                    { label: 'Quantité transférée', value: formatQty(stats.transferred_quantity), icon: Boxes, color: 'text-emerald-600', bg: 'bg-emerald-100' },
                    { label: 'Quantité restant à affecter', value: formatQty(stats.pending_quantity), icon: PackageCheck, color: 'text-orange-600', bg: 'bg-orange-100' },
                ]" :key="card.label" class="flex items-center gap-4 rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl" :class="card.bg">
                        <component :is="card.icon" class="h-6 w-6" :class="card.color" />
                    </div>
                    <div><p class="text-2xl font-bold">{{ card.value }}</p><p class="text-xs text-muted-foreground">{{ card.label }}</p></div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-56 flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input v-model="localFilters.search" type="search" placeholder="BC, référence, chantier…" class="w-full rounded-xl border bg-background py-2 pl-9 pr-3 text-sm focus:ring-2 focus:ring-primary/30" />
                </div>
                <SlidersHorizontal class="h-4 w-4 text-muted-foreground" />
                <select v-model="localFilters.project_id" class="rounded-xl border bg-background px-3 py-2 text-sm">
                    <option value="">Tous les chantiers</option>
                    <option v-for="project in projects" :key="project.id" :value="String(project.id)">{{ project.code }} — {{ project.name }}</option>
                </select>
                <label class="flex items-center gap-2 rounded-xl border bg-background px-3 py-2 text-sm text-muted-foreground">
                    <CalendarDays class="h-4 w-4" /><input v-model="localFilters.date_from" type="date" class="bg-transparent outline-none" />
                </label>
                <label class="flex items-center gap-2 rounded-xl border bg-background px-3 py-2 text-sm text-muted-foreground">
                    <CalendarDays class="h-4 w-4" /><input v-model="localFilters.date_to" type="date" class="bg-transparent outline-none" />
                </label>
                <button v-if="hasFilters" class="flex items-center gap-1 rounded-xl border px-3 py-2 text-sm text-muted-foreground hover:bg-muted" @click="clearFilters">
                    <RotateCcw class="h-4 w-4" /> Réinitialiser
                </button>
            </div>

            <div v-if="transfers.data.length" class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead><tr class="border-b bg-muted/30 text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <th class="px-5 py-3">Date / Référence</th><th class="px-5 py-3">Bon de commande</th>
                            <th class="px-5 py-3">Chantier</th><th class="px-5 py-3">Articles transférés</th>
                            <th class="px-5 py-3">Effectué par</th><th class="px-5 py-3 text-right">Action</th>
                        </tr></thead>
                        <tbody class="divide-y">
                            <tr v-for="transfer in transfers.data" :key="transfer.id" class="hover:bg-muted/20">
                                <td class="px-5 py-4"><p class="font-semibold">{{ formatDate(transfer.transferred_at) }}</p><p class="text-xs text-muted-foreground">{{ transfer.reference || 'Sans référence' }}</p></td>
                                <td class="px-5 py-4"><p class="font-mono text-xs font-bold text-blue-700">{{ orderOf(transfer)?.order_number || '—' }}</p><p class="max-w-52 truncate text-xs text-muted-foreground">{{ orderOf(transfer)?.title }}</p></td>
                                <td class="px-5 py-4"><span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"><HardHat class="h-3 w-3" />{{ transfer.project?.name }}</span></td>
                                <td class="px-5 py-4"><div class="space-y-1"><div v-for="line in transferLines(transfer)" :key="line.id" class="flex max-w-72 justify-between gap-4 text-xs"><span class="truncate">{{ (line as any).reception_line?.order_line?.article?.name || 'Article' }}</span><strong>× {{ formatQty(line.quantity_transferred) }}</strong></div></div></td>
                                <td class="px-5 py-4 text-xs text-muted-foreground">{{ transfer.actor?.name || '—' }}</td>
                                <td class="px-5 py-4 text-right"><Link v-if="orderOf(transfer)" :href="route('purchase-orders.show', orderOf(transfer).id)" class="inline-flex items-center gap-1 rounded-lg border px-3 py-2 text-xs font-semibold hover:bg-muted">Voir le BC <ArrowRight class="h-3.5 w-3.5" /></Link></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <EmptyState v-else :icon="HardHat" icon-bg="bg-amber-50" icon-color="text-amber-500" title="Aucun transfert trouvé" description="Les transferts enregistrés depuis les réceptions apparaîtront ici." />

            <div v-if="transfers.last_page > 1" class="flex justify-center gap-1">
                <template v-for="link in transfers.links" :key="link.label">
                    <Link v-if="link.url" :href="link.url" class="rounded-lg px-3 py-1.5 text-sm" :class="link.active ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted'" v-html="link.label" />
                    <span v-else class="px-3 py-1.5 text-sm text-muted-foreground opacity-40" v-html="link.label" />
                </template>
            </div>
        </div>
    </AppLayout>
</template>
