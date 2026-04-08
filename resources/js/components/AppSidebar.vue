<script setup lang="ts">
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar, SidebarContent, SidebarFooter, SidebarHeader,
    SidebarMenu, SidebarMenuButton, SidebarMenuItem,
    SidebarGroup, SidebarGroupLabel, SidebarGroupContent,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import {
    LayoutDashboard, ShoppingCart, CheckSquare,
    Users, Settings, ChevronRight, Shield,
} from 'lucide-vue-next';
import { computed } from 'vue';

const page = usePage<SharedData>();
const user = computed(() => page.props.auth.user);
const role = computed(() => user.value?.role?.slug);

const isActive = (href: string) => page.url.startsWith(href);

const mainNav = computed(() => {
    const items = [
        { title: 'Tableau de bord', href: route('dashboard'), icon: LayoutDashboard, key: 'dashboard' },
    ];

    if (role.value === 'demandeur' || role.value === 'admin') {
        items.push({
            title: 'Mes commandes',
            href: route('purchase-orders.index'),
            icon: ShoppingCart,
            key: 'purchase-orders',
        });
    }

    if (role.value === 'validateur' || role.value === 'admin') {
        items.push({
            title: 'Validations',
            href: route('validations.index'),
            icon: CheckSquare,
            key: 'validations',
        });
    }

    return items;
});

const adminNav = computed(() => {
    if (role.value !== 'admin') return [];
    return [
        { title: 'Utilisateurs', href: route('admin.users.index'), icon: Users, key: 'admin-users' },
        { title: 'Niveaux de validation', href: route('admin.validation-levels.index'), icon: Settings, key: 'admin-levels' },
    ];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="border-b border-sidebar-border/40 pb-3">
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="route('dashboard')" class="flex items-center gap-3">
                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-sidebar-primary/20 text-sidebar-primary shadow-inner border border-sidebar-primary/30">
                                <ShoppingCart class="h-4 w-4" />
                            </div>
                            <div class="flex flex-col leading-tight">
                                <span class="font-bold text-sm text-sidebar-foreground tracking-wide">AchatPro</span>
                                <span class="text-[11px] text-sidebar-foreground/40 font-medium">Gestion des commandes</span>
                            </div>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent class="gap-0 pt-2">
            <SidebarGroup class="px-2">
                <SidebarGroupLabel class="text-[11px] font-medium uppercase tracking-wider text-sidebar-foreground/40 px-2 mb-1">
                    Navigation
                </SidebarGroupLabel>
                <SidebarGroupContent>
                    <SidebarMenu>
                        <SidebarMenuItem v-for="item in mainNav" :key="item.key">
                            <SidebarMenuButton
                                as-child
                                :is-active="isActive(item.href)"
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

            <template v-if="adminNav.length > 0">
                <SidebarSeparator class="mx-4 my-2" />
                <SidebarGroup class="px-2">
                    <SidebarGroupLabel class="text-[11px] font-medium uppercase tracking-wider text-sidebar-foreground/40 px-2 mb-1 flex items-center gap-1.5">
                        <Shield class="h-3 w-3" />
                        Administration
                    </SidebarGroupLabel>
                    <SidebarGroupContent>
                        <SidebarMenu>
                            <SidebarMenuItem v-for="item in adminNav" :key="item.key">
                                <SidebarMenuButton
                                    as-child
                                    :is-active="isActive(item.href)"
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
            <!-- Badge rôle -->
            <div class="px-3 pb-1">
                <div class="flex items-center gap-2 rounded-lg bg-sidebar-accent px-3 py-2">
                    <div
                        class="h-2 w-2 rounded-full shrink-0 shadow-sm"
                        :class="{
                            'bg-violet-400 shadow-violet-400/50': role === 'admin',
                            'bg-sky-400 shadow-sky-400/50': role === 'demandeur',
                            'bg-emerald-400 shadow-emerald-400/50': role === 'validateur',
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
    </Sidebar>
    <slot />
</template>
