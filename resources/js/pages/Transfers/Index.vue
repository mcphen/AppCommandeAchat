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
    lines: { id: number; label: string; article_reference?: string | null; line_note?: string | null; unit: string; quantity_received: number; quantity_transferred: number; quantity_available: number }[];
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
    filters: { project_id: string; date_from: string; date_to: string; search: string; status: string; user_id: string; reception_id: string };
    users: {id:number;name:string}[];
    receptionOptions: any[];
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
    () => localFilters.value.status,
    () => localFilters.value.user_id,
    () => localFilters.value.reception_id,
], applyFilters);

function applyFilters() {
    router.get(route('transfers.index'), localFilters.value, { preserveState: true, replace: true });
}
function clearFilters() {
    localFilters.value = { project_id: '', date_from: '', date_to: '', search: '', status: '', user_id: '', reception_id: '' };
}
const hasFilters = computed(() => Object.values(localFilters.value).some(Boolean));
const formatQty = (value: string | number) => new Intl.NumberFormat('fr-FR', { maximumFractionDigits: 2 }).format(Number(value));
const formatDate = (value: string) => new Date(value).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric' });
const orderOf = (transfer: ReceptionTransfer) => (transfer as any).reception?.purchase_order;
const transferLines = (transfer: ReceptionTransfer) => transfer.lines ?? [];

const showCreateForm = ref(false);
const receptionQuery = ref('');
const selectedReception = ref<AvailableReception | null>(null);
const showProjectCreator = ref(false);
const newProject = ref({ code: '', name: '' });
const submitting = ref(false);

type Allocation = {
    project_id: string;
    project_query: string;
    transferred_at: string;
    reference: string;
    notes: string;
    lines: { reception_line_id: number; label: string; article_reference?: string | null; line_note?: string | null; unit: string; max: number; quantity_transferred: number }[];
};
const allocations = ref<Allocation[]>([]);
const receptionLabel = (item: AvailableReception) => `${item.purchase_order.order_number || item.purchase_order.title} — reçue le ${formatDate(item.received_at)}`;
const projectLabel = (project: Pick<Project, 'id' | 'code' | 'name'>) => `${project.code} — ${project.name}`;

function selectReceptionFromQuery() {
    const found = props.availableReceptions.find(item => receptionLabel(item) === receptionQuery.value);
    selectedReception.value = found ?? null;
    allocations.value = found ? [makeAllocation()] : [];
}
function makeAllocation(): Allocation {
    return {
        project_id: '', project_query: '', transferred_at: new Date().toISOString().slice(0, 10), reference: '', notes: '',
        lines: (selectedReception.value?.lines ?? []).map(line => ({
            reception_line_id: line.id, label: line.label, article_reference: line.article_reference, line_note: line.line_note,
            unit: line.unit, max: line.quantity_available, quantity_transferred: 0,
        })),
    };
}
function addAllocation() { allocations.value.push(makeAllocation()); }
function resolveProject(allocation: Allocation) {
    const found = props.projects.find(project => projectLabel(project) === allocation.project_query);
    allocation.project_id = found ? String(found.id) : '';
}
const batchIsValid = computed(() => selectedReception.value && allocations.value.length > 0 && allocations.value.every(item =>
    item.project_id && item.lines.some(line => Number(line.quantity_transferred) > 0)
));
function createProject() {
    if (!newProject.value.code.trim() || !newProject.value.name.trim()) return;
    router.post(route('transfers.projects.store'), newProject.value, {
        preserveScroll: true,
        onSuccess: () => { showProjectCreator.value = false; newProject.value = { code: '', name: '' }; },
    });
}
function submitTransfer() {
    if (!selectedReception.value || !batchIsValid.value) return;
    submitting.value = true;
    router.post(route('transfers.batch.store'), {
        reception_id: selectedReception.value.id,
        transfers: allocations.value.map(item => ({
            project_id: item.project_id, transferred_at: item.transferred_at,
            reference: item.reference || null, notes: item.notes || null,
            lines: item.lines.filter(line => Number(line.quantity_transferred) > 0).map(line => ({
                reception_line_id: line.reception_line_id, quantity_transferred: line.quantity_transferred,
            })),
        })),
    }, {
        preserveScroll: true,
        onSuccess: () => { showCreateForm.value = false; receptionQuery.value = ''; selectedReception.value = null; allocations.value = []; },
        onFinish: () => { submitting.value = false; },
    });
}
</script>

<template>
    <Head title="Transferts chantiers" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="transfers-page flex flex-col gap-6 p-3 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold text-foreground">Transferts vers les chantiers</h1>
                    <p class="mt-1 text-sm text-muted-foreground">Affectation et traçabilité des articles réceptionnés vers leurs chantiers de destination.</p>
                </div>
                <div class="flex gap-2">
                    <a :href="route('transfers.export', localFilters)" class="rounded-xl border bg-white px-4 py-2.5 text-sm font-semibold text-slate-800">Export Excel/CSV</a>
                    <Link :href="route('transfers.create')" class="rounded-xl bg-amber-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-amber-700">
                        Nouveau transfert
                    </Link>
                </div>
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
                    <div><p class="text-2xl font-bold text-slate-950">{{ card.value }}</p><p class="text-xs text-slate-600">{{ card.label }}</p></div>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <div class="relative min-w-56 flex-1">
                    <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <input v-model="localFilters.search" type="search" placeholder="N° BT, BC, référence, chantier…" class="w-full rounded-xl border bg-background py-2 pl-9 pr-3 text-sm focus:ring-2 focus:ring-primary/30" />
                </div>
                <SlidersHorizontal class="h-4 w-4 text-muted-foreground" /><select v-model="localFilters.status" class="rounded-xl border px-3 py-2 text-sm"><option value="">Tous les statuts</option><option value="draft">Brouillon</option><option value="confirmed">Confirmé</option><option value="cancelled">Annulé</option></select><select v-model="localFilters.user_id" class="rounded-xl border px-3 py-2 text-sm"><option value="">Tous les utilisateurs</option><option v-for="user in users" :key="user.id" :value="String(user.id)">{{user.name}}</option></select><select v-model="localFilters.reception_id" class="rounded-xl border px-3 py-2 text-sm"><option value="">Toutes les réceptions</option><option v-for="r in receptionOptions" :key="r.id" :value="String(r.id)">{{r.purchase_order?.order_number||r.purchase_order?.title}}</option></select>
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
                                <td class="px-5 py-4"><span class="mb-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" :class="transfer.status==='confirmed'?'bg-emerald-100 text-emerald-700':transfer.status==='cancelled'?'bg-red-100 text-red-700':'bg-amber-100 text-amber-700'">{{transfer.status==='confirmed'?'Confirmé':transfer.status==='cancelled'?'Annulé':'Brouillon'}}</span><p class="font-mono text-xs font-bold text-amber-700">{{ transfer.transfer_number }}</p><p class="font-semibold">{{ formatDate(transfer.transferred_at) }}</p><p class="text-xs text-muted-foreground">{{ transfer.reference || 'Sans référence' }}</p></td>
                                <td class="px-5 py-4"><span class="mb-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase" :class="transfer.status==='confirmed'?'bg-emerald-100 text-emerald-700':transfer.status==='cancelled'?'bg-red-100 text-red-700':'bg-amber-100 text-amber-700'">{{transfer.status==='confirmed'?'Confirmé':transfer.status==='cancelled'?'Annulé':'Brouillon'}}</span><p class="font-mono text-xs font-bold text-blue-700">{{ orderOf(transfer)?.order_number || '—' }}</p><p class="max-w-52 truncate text-xs text-muted-foreground">{{ orderOf(transfer)?.title }}</p></td>
                                <td class="px-5 py-4"><span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700"><HardHat class="h-3 w-3" />{{ transfer.project?.name }}</span></td>
                                <td class="px-5 py-4"><div class="space-y-1"><div v-for="line in transferLines(transfer)" :key="line.id" class="flex max-w-72 justify-between gap-4 text-xs"><span class="truncate">{{ (line as any).reception_line?.order_line?.article?.name || 'Article' }}</span><strong>× {{ formatQty(line.quantity_transferred) }}</strong></div></div></td>
                                <td class="px-5 py-4 text-xs text-muted-foreground">{{ transfer.actor?.name || '—' }}</td>
                                <td class="px-5 py-4 text-right"><div class="flex justify-end gap-2"><Link v-if="transfer.status==='draft'" :href="route('transfers.edit', transfer.id)" class="rounded-lg border border-amber-500 px-3 py-2 text-xs font-semibold text-amber-700">Reprendre</Link><Link :href="route('transfers.show', transfer.id)" class="rounded-lg border px-3 py-2 text-xs font-semibold hover:bg-muted">Détail</Link><a :href="route('transfers.pdf', transfer.id)" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white">PDF</a></div></td>
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
<style scoped>
.transfers-page input,
.transfers-page select {
    background-color: #fff !important;
    color: #0f172a !important;
    opacity: 1;
}

.transfers-page input::placeholder {
    color: #64748b !important;
    opacity: 1;
}

.transfers-page select option {
    background-color: #fff;
    color: #0f172a;
}
</style>