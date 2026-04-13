<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { MonitorCog, MoonStar, Palette, SunMedium } from 'lucide-vue-next';

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Apparence',
        href: '/settings/appearance',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Apparence" />

        <SettingsLayout>
            <div class="flex flex-col gap-5">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-foreground">Apparence de l'interface</h2>
                        <p class="text-sm text-muted-foreground">Choisissez le theme d'affichage qui vous convient le mieux.</p>
                    </div>

                    <div
                        class="inline-flex items-center gap-2 self-start rounded-xl border border-primary/20 bg-primary/5 px-4 py-2 text-sm font-semibold text-primary"
                    >
                        <Palette class="h-4 w-4" />
                        Preferences visuelles
                    </div>
                </div>

                <section class="rounded-2xl border bg-card p-5 shadow-sm">
                    <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                        <div class="flex items-center gap-4">
                            <div class="flex h-16 w-16 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                                <MonitorCog class="h-8 w-8" />
                            </div>

                            <div class="min-w-0">
                                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">Theme de l'application</p>
                                <h3 class="mt-1 text-xl font-semibold text-foreground">Personnaliser l'affichage</h3>
                                <p class="mt-1 text-sm text-muted-foreground">
                                    Basculez entre mode clair, sombre ou suivez automatiquement le systeme.
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 lg:w-[560px]">
                            <div class="rounded-2xl border border-amber-100 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/30">
                                <div class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-300">
                                    <SunMedium class="h-4 w-4" />
                                    Clair
                                </div>
                                <p class="mt-3 text-sm font-semibold text-foreground">Interface lumineuse et nette</p>
                            </div>

                            <div class="rounded-2xl border border-indigo-100 bg-indigo-50 p-4 dark:border-indigo-900 dark:bg-indigo-950/30">
                                <div
                                    class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-indigo-700 dark:text-indigo-300"
                                >
                                    <MoonStar class="h-4 w-4" />
                                    Sombre
                                </div>
                                <p class="mt-3 text-sm font-semibold text-foreground">Confortable pour les environnements peu eclaires</p>
                            </div>

                            <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/30">
                                <div
                                    class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-300"
                                >
                                    <MonitorCog class="h-4 w-4" />
                                    Systeme
                                </div>
                                <p class="mt-3 text-sm font-semibold text-foreground">Suit automatiquement les preferences de l'appareil</p>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border bg-card p-6 shadow-sm">
                    <div class="flex flex-col gap-5">
                        <div>
                            <HeadingSmall
                                title="Choisir un theme"
                                description="Selectionnez votre mode d'affichage prefere. Le changement est applique immediatement."
                            />
                        </div>

                        <div class="rounded-2xl border border-primary/20 bg-primary/5 p-4">
                            <AppearanceTabs class="w-full" />
                        </div>

                        <div class="rounded-2xl border bg-muted/20 p-4">
                            <p class="text-sm font-semibold text-foreground">A propos du mode systeme</p>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Si vous choisissez <span class="font-medium text-foreground">Systeme</span>, l'application suivra automatiquement le
                                theme configure sur votre ordinateur ou votre navigateur.
                            </p>
                        </div>
                    </div>
                </section>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
