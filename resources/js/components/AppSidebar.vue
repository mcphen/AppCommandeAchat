<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import NotificationPanel from '@/components/NotificationPanel.vue';
import {
    Sidebar, SidebarContent, SidebarFooter, SidebarHeader,
    SidebarMenu, SidebarMenuButton, SidebarMenuItem,
    SidebarGroup, SidebarGroupLabel, SidebarGroupContent,
    SidebarSeparator, SidebarRail,
} from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard, FileText, CheckSquare, CreditCard,
    Users, Settings, Shield, Building2, ClipboardList,
    PiggyBank, SlidersHorizontal, UserCheck, Layers, Scale,
} from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value?.role?.slug);
const niveauSlug = computed(() => user.value?.niveau_validation?.slug);

const isActive = (href: string) => page.url.startsWith(href);

const mainNav = computed(() => {
    const items: { title: string; href: string; icon: any; key: string }[] = [
        { title: 'Tableau de bord', href: route('dashboard'), icon: LayoutDashboard, key: 'dashboard' },
    ];

    if (role.value === 'employe' || role.value === 'admin') {
        items.push({
            title: 'Mes expressions de besoin',
            href: route('expressions-besoin.index'),
            icon: FileText,
            key: 'expressions-besoin',
        });
    }

    if (role.value === 'validateur' || role.value === 'admin') {
        if (niveauSlug.value === 'compta' || role.value === 'admin') {
            items.push({
                title: 'Comptabilité',
                href: route('compta.index'),
                icon: ClipboardList,
                key: 'compta',
            });
        }

        if (niveauSlug.value === 'df') {
            items.push({
                title: 'DAP en attente',
                href: route('validations-dap.index'),
                icon: CheckSquare,
                key: 'validations-dap',
            });
            items.push({
                title: 'Toutes les DAP',
                href: route('validations-dap.toutes'),
                icon: Layers,
                key: 'validations-dap-toutes',
            });
        } else {
            items.push({
                title: 'Validations DAP',
                href: route('validations-dap.index'),
                icon: CheckSquare,
                key: 'validations-dap',
            });
        }

        items.push({
            title: 'Paiements',
            href: route('paiements.index'),
            icon: CreditCard,
            key: 'paiements',
        });
    }

    return items;
});

const arbitrageNav = computed(() => {
    const isMembre = user.value?.is_membre_comite ?? false;
    if (role.value !== 'admin' && !isMembre) return [];
    return [
        { title: 'Sessions d\'arbitrage', href: route('arbitrage.sessions.index'), icon: Scale, key: 'arbitrage-sessions' },
    ];
});

const adminNav = computed(() => {
    if (role.value !== 'admin') return [];
    return [
        { title: 'Groupes',             href: route('admin.groupes.index'),            icon: Layers,           key: 'admin-groupes' },
        { title: 'Entreprises',         href: route('admin.entreprises.index'),        icon: Building2,        key: 'admin-entreprises' },
        { title: 'Utilisateurs',        href: route('admin.users.index'),              icon: Users,            key: 'admin-users' },
        { title: 'Niveaux & Seuils',    href: route('admin.niveaux-validation.index'), icon: SlidersHorizontal, key: 'admin-niveaux' },
        { title: 'Validateurs',         href: route('admin.validateurs.index'),        icon: UserCheck,        key: 'admin-validateurs' },
        { title: 'Budgets annuels',     href: route('admin.budgets-annuels.index'),    icon: PiggyBank,        key: 'admin-budgets' },
        { title: 'Comités d\'arbitrage', href: route('admin.comites-arbitrage.index'), icon: Scale,            key: 'admin-comites' },
    ];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="sidebar">
        <SidebarHeader class="border-b border-sidebar-border/40 pb-3">
            <div class="flex items-center justify-between gap-2 px-1 py-1 group-data-[collapsible=icon]:justify-center">
                <Link :href="route('dashboard')" class="flex min-w-0 items-center gap-3">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-sidebar-primary/30 bg-gradient-to-br from-sidebar-primary/30 to-cyan-300/20 text-sidebar-primary shadow-[0_10px_20px_hsl(var(--sidebar-primary)/0.18)]">
                        <CreditCard class="h-4 w-4" />
                    </div>
                    <div class="flex min-w-0 flex-col leading-tight group-data-[collapsible=icon]:hidden">
                        <span class="font-extrabold text-[15px] text-sidebar-foreground tracking-tight">AutoPaiement</span>
                        <span class="text-[11px] text-sidebar-foreground/55 font-medium">Finance Workflow Platform</span>
                    </div>
                </Link>
                <div class="group-data-[collapsible=icon]:hidden">
                    <NotificationPanel />
                </div>
            </div>
        </SidebarHeader>

        <SidebarContent class="gap-0 pt-2">
            <SidebarGroup class="px-2 pt-1">
                <SidebarGroupLabel class="px-2 mb-1 text-[11px] font-semibold uppercase tracking-wider text-sidebar-foreground/45">
                    Navigation
                </SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in mainNav" :key="item.key">
                            <SidebarMenuButton
                                as-child
                                :is-active="isActive(item.href)"
                                :tooltip="item.title"
                                class="group relative h-9 rounded-lg transition-all"
                            >
                                <Link :href="item.href" class="flex items-center gap-3">
                                    <component :is="item.icon" class="h-4 w-4 shrink-0" />
                                    <span class="font-medium text-sm">{{ item.title }}</span>
                                </Link>
                            </SidebarMenuButton>
                        </SidebarMenuItem>
                    </SidebarMenu>
                </SidebarGroupContent>
            </SidebarGroup>

            <template v-if="arbitrageNav.length > 0">
                <SidebarSeparator class="mx-4 my-2" />
                <SidebarGroup class="px-2">
                    <SidebarGroupLabel class="px-2 mb-1 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-sidebar-foreground/45">
                        <Scale class="h-3 w-3" />
                        Arbitrage
                    </SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem v-for="item in arbitrageNav" :key="item.key">
                                <SidebarMenuButton
                                    as-child
                                    :is-active="isActive(item.href)"
                                    :tooltip="item.title"
                                    class="h-9 rounded-lg transition-all"
                                >
                                    <Link :href="item.href" class="flex items-center gap-3">
                                        <component :is="item.icon" class="h-4 w-4 shrink-0" />
                                        <span class="font-medium text-sm">{{ item.title }}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </template>

            <template v-if="adminNav.length > 0">
                <SidebarSeparator class="mx-4 my-2" />
                <SidebarGroup class="px-2">
                    <SidebarGroupLabel class="px-2 mb-1 flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-sidebar-foreground/45">
                        <Shield class="h-3 w-3" />
                        Administration
                    </SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem v-for="item in adminNav" :key="item.key">
                                <SidebarMenuButton
                                    as-child
                                    :is-active="isActive(item.href)"
                                    :tooltip="item.title"
                                    class="h-9 rounded-lg transition-all"
                                >
                                    <Link :href="item.href" class="flex items-center gap-3">
                                        <component :is="item.icon" class="h-4 w-4 shrink-0" />
                                        <span class="font-medium text-sm">{{ item.title }}</span>
                                    </Link>
                                </SidebarMenuButton>
                            </SidebarMenuItem>
                        </SidebarMenu>
                    </SidebarGroupContent>
                </SidebarGroup>
            </template>
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border/40 pt-2">
            <div class="px-3 pb-1 group-data-[collapsible=icon]:hidden">
                <div class="flex items-center gap-2 rounded-xl border border-sidebar-border/70 bg-sidebar-accent/70 px-3 py-2.5">
                    <div
                        class="h-2 w-2 rounded-full shrink-0 shadow-sm"
                        :class="{
                            'bg-violet-400 shadow-violet-400/50': role === 'admin',
                            'bg-sky-400 shadow-sky-400/50': role === 'employe',
                            'bg-emerald-400 shadow-emerald-400/50': role === 'validateur',
                        }"
                    />
                    <span class="text-xs text-sidebar-foreground/70 font-medium">
                        {{ user?.role?.name ?? 'Sans rôle' }}
                    </span>
                    <template v-if="user?.niveau_validation">
                        <span class="text-sidebar-foreground/30 text-xs">·</span>
                        <span class="text-xs text-sidebar-foreground/70">{{ user.niveau_validation.nom }}</span>
                    </template>
                </div>
            </div>
            <NavUser />
        </SidebarFooter>
        <SidebarRail />
    </Sidebar>
    <slot />
</template>
