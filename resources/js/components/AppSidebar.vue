<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import NotificationPanel from '@/components/NotificationPanel.vue';
import {
    Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarRail,
} from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard, CheckSquare, ShoppingCart,
    Users, Settings, ChevronRight, Shield, Building2, ClipboardList,
    FolderTree, Truck, Package, ClipboardCheck, PiggyBank,
    BarChart2, UserCheck, BookOpen, SlidersHorizontal,
    Banknote, Wallet, HandCoins, UserCircle, History, Tag,
    ChevronDown, Landmark, CreditCard, MessageCircle,
} from 'lucide-vue-next';
import { computed, onMounted, ref, watch } from 'vue';

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value?.role?.slug);
const pendingValidations = computed(() => page.props.pending_validations_count ?? 0);
const pendingDd = computed(() => page.props.pending_dd_count ?? 0);

// Badge par clé de groupe
const groupBadge = (key: string): number => {
    if (key === 'validations') return pendingValidations.value;
    if (key === 'decaissements') return pendingDd.value;
    return 0;
};

const isActive = (href: string) => {
    try {
        const path = new URL(href).pathname;
        return page.url.startsWith(path);
    } catch {
        return page.url.startsWith(href);
    }
};

type NavLink = { type: 'link'; title: string; href: string; icon: any };
type NavSection = { type: 'section'; title: string };
type NavEntry = NavLink | NavSection;
type NavGroup = { key: string; title: string; icon: any; items: NavEntry[] };

const isNavLink = (item: NavEntry): item is NavLink => item.type === 'link';

const navGroups = computed<NavGroup[]>(() => {
    const r = role.value;
    const groups: NavGroup[] = [];

    /* ── Commandes d'achat ── */
    const cmdItems: NavEntry[] = [];
    if (r === 'demandeur' || r === 'validateur' || r === 'admin')
        cmdItems.push({ type: 'link', title: "Commandes d'achat", href: route('purchase-orders.index'), icon: ShoppingCart });
    if (r === 'demandeur' || r === 'admin')
        cmdItems.push({ type: 'link', title: 'Réceptions', href: route('receptions.index'), icon: ClipboardCheck });
    if (cmdItems.length)
        groups.push({ key: 'commandes', title: "Commandes d'achat", icon: ShoppingCart, items: cmdItems });

    /* ── Décaissements ── */
    const decItems: NavEntry[] = [];
    if (r === 'demandeur' || r === 'validateur' || r === 'admin')
        decItems.push({ type: 'link', title: 'Demandes décaissement', href: route('disbursement-requests.index'), icon: Banknote });
    if (r === 'admin')
        decItems.push({ type: 'link', title: 'Natures opérations', href: route('admin.nature-operations.index'), icon: Tag });
    if (r === 'validateur' || r === 'admin') {
        decItems.push({ type: 'link', title: 'Validations DD', href: route('disbursement-validations.index'), icon: CheckSquare });
        decItems.push({ type: 'link', title: 'Historique DD', href: route('disbursement-validations.history'), icon: History });
    }
    if (decItems.length)
        groups.push({ key: 'decaissements', title: 'Décaissements', icon: Banknote, items: decItems });

    /* ── Paiements ── */
    if (r === 'caissier' || r === 'admin')
        groups.push({ key: 'paiements', title: 'Paiements', icon: CreditCard, items: [
            { type: 'link', title: 'Payer une demande DD', href: route('caissier.demandes.index'), icon: Wallet },
            { type: 'link', title: 'Payer une commande',   href: route('decaissements.index'),     icon: CreditCard },
        ]});

    /* ── Validations ── */
    const valItems: NavEntry[] = [];
    if (r === 'validateur' || r === 'admin') {
        valItems.push({ type: 'link', title: 'Validations', href: route('validations.index'), icon: CheckSquare });
        valItems.push({ type: 'link', title: 'Historique validations', href: route('validations.history'), icon: History });
        valItems.push({ type: 'link', title: 'Délégations', href: route('delegations.index'), icon: UserCheck });
        valItems.push({ type: 'link', title: 'Audit & Historique', href: route('audit.index'), icon: ClipboardList });
    }
    if (valItems.length)
        groups.push({ key: 'validations', title: 'Validations', icon: CheckSquare, items: valItems });

    /* ── Caisse & Prêts ── */
    const caisseItems: NavItem[] = [];
    if (r === 'caissier' || r === 'admin') {
        caisseItems.push({ title: 'Caisse Épargne', href: route('caisse.index'), icon: Landmark });
        caisseItems.push({ type: 'link', title: 'Prêts', href: route('caisse.prets.index'), icon: HandCoins });
    }
    if (r === 'validateur' || r === 'admin')
        caisseItems.push({ type: 'link', title: 'Validations Prêts', href: route('pret-validations.index'), icon: HandCoins });
    if (r === 'agent')
        caisseItems.push({ type: 'link', title: 'Mon Compte', href: route('mon-compte.index'), icon: UserCircle });
    if (caisseItems.length)
        groups.push({ key: 'caisse', title: 'Caisse & Prêts', icon: Landmark, items: caisseItems });

    /* ── Analytique (admin) ── */
    if (r === 'admin')
        groups.push({ key: 'analytique', title: 'Analytique', icon: BarChart2, items: [
            { type: 'link', title: 'Tableaux analytiques', href: route('analytics.index'), icon: BarChart2 },
        ]});

    /* ── Catalogue achats (admin) ── */
    if (r === 'admin')
        groups.push({ key: 'catalogue', title: 'Catalogue achats', icon: Package, items: [
            { type: 'link', title: 'Catégories',   href: route('admin.categories.index'),   icon: FolderTree },
            { type: 'link', title: 'Articles',     href: route('admin.articles.index'),     icon: Package },
            { type: 'link', title: 'Fournisseurs', href: route('admin.fournisseurs.index'), icon: Truck },
        ]});

    /* ── Administration (admin) ── */
    if (r === 'admin')
        groups.push({ key: 'admin', title: 'Administration', icon: Shield, items: [
            { type: 'link', title: 'Agents',                href: route('admin.agents.index'),            icon: UserCircle },
            { type: 'link', title: 'Boutiques',             href: route('admin.boutiques.index'),         icon: Building2 },
            { type: 'link', title: 'Utilisateurs',          href: route('admin.users.index'),             icon: Users },
            { type: 'link', title: 'Niveaux de validation', href: route('admin.validation-levels.index'), icon: Settings },
            { type: 'link', title: 'Budgets',               href: route('admin.budgets.index'),           icon: PiggyBank },
            { type: 'link', title: 'Comptabilité',          href: route('admin.accounting.index'),        icon: BookOpen },
            { type: 'link', title: 'Notifications WhatsApp', href: route('admin.notifications.whatsapp'),   icon: MessageCircle },
            { type: 'link', title: 'Configuration',         href: route('admin.settings.index'),          icon: SlidersHorizontal },
        ]});

    return groups;
});

/* ── Accordion ── */
const openGroup = ref<string | null>(null);

const toggleGroup = (key: string) => {
    openGroup.value = openGroup.value === key ? null : key;
};

const autoOpenGroup = () => {
    for (const group of navGroups.value) {
        if (group.items.some(item => isNavLink(item) && isActive(item.href))) {
            openGroup.value = group.key;
            return;
        }
    }
};

const isGroupActive = (group: NavGroup) => group.items.some(item => isNavLink(item) && isActive(item.href));

onMounted(autoOpenGroup);
watch(() => page.url, autoOpenGroup);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <!-- Header -->
        <SidebarHeader class="border-b border-sidebar-border/40 pb-3">
            <div class="flex items-center justify-between gap-2 px-1 py-1 group-data-[collapsible=icon]:justify-center">
                <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-3">
                    <img
                        :src="page.props.company?.logoUrl ?? '/logo_scn.jpg'"
                        :alt="page.props.company?.name ?? 'SCN'"
                        class="h-9 w-9 shrink-0 rounded-full object-cover ring-2 ring-sidebar-primary/30 shadow-sm"
                    />
                    <div class="flex min-w-0 flex-col leading-tight group-data-[collapsible=icon]:hidden">
                        <span class="font-bold text-sm text-sidebar-foreground tracking-wide">{{ page.props.company?.name ?? 'SCN' }}</span>
                        <span class="text-[11px] text-sidebar-foreground/40 font-medium">Gestion des commandes</span>
                    </div>
                </Link>
                <div class="group-data-[collapsible=icon]:hidden">
                    <NotificationPanel />
                </div>
            </div>
        </SidebarHeader>

        <SidebarContent class="gap-0 pt-2 pb-2 overflow-y-auto">
            <div class="px-2">
                <!-- Tableau de bord -->
                <Link
                    :href="route('dashboard')"
                    class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-2"
                    :class="isActive(route('dashboard'))
                        ? 'bg-amber-500/15 text-amber-600 font-semibold dark:bg-amber-400/15 dark:text-amber-400'
                        : 'text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-foreground'"
                >
                    <LayoutDashboard
                        class="h-4 w-4 shrink-0"
                        :class="isActive(route('dashboard')) ? 'text-amber-600 dark:text-amber-400' : ''"
                    />
                    <span class="group-data-[collapsible=icon]:hidden">Tableau de bord</span>
                </Link>

                <!-- Groupes accordéon -->
                <div
                    v-for="group in navGroups"
                    :key="group.key"
                    class="mt-0.5"
                >
                    <!-- Trigger du groupe -->
                    <button
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-all group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-2"
                        :class="isGroupActive(group)
                            ? 'text-amber-600 font-semibold dark:text-amber-400'
                            : 'text-sidebar-foreground/70 hover:bg-sidebar-accent hover:text-sidebar-foreground'"
                        @click="toggleGroup(group.key)"
                    >
                        <component
                            :is="group.icon"
                            class="h-4 w-4 shrink-0"
                            :class="isGroupActive(group) ? 'text-amber-600 dark:text-amber-400' : ''"
                        />
                        <span class="flex-1 truncate text-left group-data-[collapsible=icon]:hidden">{{ group.title }}</span>
                        <span
                            v-if="groupBadge(group.key) > 0"
                            class="mr-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1.5 text-[10px] font-bold text-white group-data-[collapsible=icon]:hidden"
                        >{{ groupBadge(group.key) > 99 ? '99+' : groupBadge(group.key) }}</span>
                        <ChevronDown
                            class="ml-auto h-3.5 w-3.5 shrink-0 transition-transform duration-200 group-data-[collapsible=icon]:hidden"
                            :class="openGroup === group.key ? 'rotate-180' : ''"
                        />
                    </button>

                    <!-- Sous-items -->
                    <Transition
                        enter-active-class="transition-all duration-200 ease-out"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition-all duration-150 ease-in"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-1"
                    >
                        <div
                            v-if="openGroup === group.key"
                            class="ml-3 mt-0.5 border-l border-sidebar-border/50 pl-2 group-data-[collapsible=icon]:hidden"
                        >
                            <template
                                v-for="item in group.items"
                                :key="isNavLink(item) ? item.href : `section-${group.key}-${item.title}`"
                            >
                                <div
                                    v-if="!isNavLink(item)"
                                    class="px-3 pb-1 pt-3 text-[11px] font-semibold uppercase tracking-[0.18em] text-sidebar-foreground/35 first:pt-1"
                                >
                                    {{ item.title }}
                                </div>
                                <Link
                                    v-else
                                    :href="item.href"
                                    class="flex items-center gap-3 rounded-lg px-3 py-1.5 text-sm transition-all"
                                    :class="isActive(item.href)
                                        ? 'bg-amber-500/15 text-amber-600 font-semibold dark:bg-amber-400/15 dark:text-amber-400'
                                        : 'text-sidebar-foreground/60 hover:bg-sidebar-accent hover:text-sidebar-foreground'"
                                >
                                    <component
                                        :is="item.icon"
                                        class="h-3.5 w-3.5 shrink-0"
                                        :class="isActive(item.href) ? 'text-amber-600 dark:text-amber-400' : ''"
                                    />
                                    <span>{{ item.title }}</span>
                                    <span
                                        v-if="isActive(item.href)"
                                        class="ml-auto h-1.5 w-1.5 rounded-full bg-amber-500 dark:bg-amber-400"
                                    />
                                </Link>
                            </template>
                        </div>
                    </Transition>
                </div>
            </div>
        </SidebarContent>

        <!-- Footer -->
        <SidebarFooter class="border-t border-sidebar-border/40 pt-2">
            <div class="px-3 pb-1 group-data-[collapsible=icon]:hidden">
                <div class="flex items-center gap-2 rounded-lg bg-sidebar-accent px-3 py-2">
                    <div
                        class="h-2 w-2 rounded-full shrink-0 shadow-sm"
                        :class="{
                            'bg-violet-400 shadow-violet-400/50': role === 'admin',
                            'bg-sky-400 shadow-sky-400/50': role === 'demandeur',
                            'bg-emerald-400 shadow-emerald-400/50': role === 'validateur',
                            'bg-amber-400 shadow-amber-400/50': role === 'caissier',
                            'bg-teal-400 shadow-teal-400/50': role === 'agent',
                        }"
                    />
                    <span class="text-xs text-sidebar-foreground/70 font-medium">
                        {{ user?.role?.name ?? 'Sans rôle' }}
                    </span>
                    <template v-if="user?.validation_level">
                        <ChevronRight class="h-3 w-3 text-sidebar-foreground/30" />
                        <span class="text-xs text-sidebar-foreground/70">{{ user.validation_level.name }}</span>
                    </template>
                </div>
            </div>
            <NavUser />
        </SidebarFooter>
        <SidebarRail />
    </Sidebar>
    <slot />
</template>
