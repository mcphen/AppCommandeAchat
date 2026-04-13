<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { KeyRound, Palette, Settings2, UserRound } from 'lucide-vue-next';

type SettingsNavItem = {
    title: string;
    description: string;
    href: string;
    icon: typeof UserRound;
};

const sidebarNavItems: SettingsNavItem[] = [
    {
        title: 'Profil',
        description: 'Informations personnelles',
        href: '/settings/profile',
        icon: UserRound,
    },
    {
        title: 'Mot de passe',
        description: 'Securite du compte',
        href: '/settings/password',
        icon: KeyRound,
    },
    {
        title: 'Apparence',
        description: 'Theme et preferences',
        href: '/settings/appearance',
        icon: Palette,
    },
];

const currentPath = window.location.pathname;
</script>

<template>
    <div class="flex w-full flex-col gap-5 p-3 sm:gap-6 sm:p-6">
        <div class="flex items-start gap-4">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                <Settings2 class="h-5 w-5" />
            </div>
            <div>
                <h1 class="text-2xl font-bold text-foreground">Parametres du compte</h1>
                <p class="text-sm text-muted-foreground">Gerez votre profil, votre securite et vos preferences d'utilisation.</p>
            </div>
        </div>

        <div class="flex w-full flex-col gap-5 xl:flex-row xl:items-start">
            <aside class="w-full xl:sticky xl:top-6 xl:w-80">
                <div class="rounded-2xl border bg-card p-2 shadow-sm">
                    <nav class="grid gap-2 sm:grid-cols-3 xl:grid-cols-1">
                        <Link
                            v-for="item in sidebarNavItems"
                            :key="item.href"
                            :href="item.href"
                            class="flex items-start gap-3 rounded-xl border px-4 py-3 text-left transition-all"
                            :class="
                                currentPath === item.href
                                    ? 'border-primary/30 bg-primary text-primary-foreground shadow-sm'
                                    : 'border-transparent bg-background text-muted-foreground hover:border-primary/20 hover:bg-primary/5 hover:text-foreground'
                            "
                        >
                            <div
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl"
                                :class="currentPath === item.href ? 'bg-white/15 text-primary-foreground' : 'bg-primary/10 text-primary'"
                            >
                                <component :is="item.icon" class="h-4 w-4" />
                            </div>

                            <div class="min-w-0">
                                <p class="text-sm font-semibold leading-tight">{{ item.title }}</p>
                                <p
                                    class="mt-1 text-xs leading-relaxed"
                                    :class="currentPath === item.href ? 'text-primary-foreground/80' : 'text-muted-foreground'"
                                >
                                    {{ item.description }}
                                </p>
                            </div>
                        </Link>
                    </nav>
                </div>
            </aside>

            <div class="min-w-0 flex-1">
                <section class="flex w-full flex-col gap-6">
                    <slot />
                </section>
            </div>
        </div>
    </div>
</template>
