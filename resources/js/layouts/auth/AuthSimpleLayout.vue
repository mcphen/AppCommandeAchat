<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage<{
    company: {
        name?: string;
        logoUrl?: string | null;
    };
}>();

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="flex min-h-svh flex-col items-center justify-center gap-6 bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-100 dark:from-slate-950 dark:via-indigo-950 dark:to-slate-900 p-6 md:p-10">
        <div class="w-full max-w-sm">
            <div class="flex flex-col gap-8">
                <div class="flex flex-col items-center gap-4">
                    <Link :href="route('login')" class="flex items-center justify-center">
                        <img
                            v-if="page.props.company?.logoUrl"
                            :src="page.props.company.logoUrl"
                            :alt="page.props.company.name || 'Logo de l’entreprise'"
                            class="block max-h-20 w-auto max-w-[260px] object-contain"
                        />
                        <span v-else class="text-xl font-bold tracking-tight text-foreground">
                            {{ page.props.company?.name || 'AchatPro' }}
                        </span>
                    </Link>
                    <div class="space-y-1.5 text-center">
                        <h1 class="text-xl font-semibold text-foreground">{{ title }}</h1>
                        <p class="text-center text-sm text-muted-foreground">{{ description }}</p>
                    </div>
                </div>
                <div class="rounded-2xl border border-border/60 bg-card/90 p-6 shadow-xl shadow-indigo-100/60 dark:shadow-indigo-950/60 backdrop-blur-sm">
                    <slot />
                </div>
            </div>
        </div>
    </div>
</template>
