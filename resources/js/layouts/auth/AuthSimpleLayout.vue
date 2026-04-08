<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ShoppingCart } from 'lucide-vue-next';

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
                    <Link :href="route('login')" class="flex flex-col items-center gap-3 font-medium">
                        <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary shadow-lg shadow-primary/30">
                            <ShoppingCart class="h-7 w-7 text-white" />
                        </div>
                        <span class="text-xl font-bold tracking-tight text-foreground">AchatPro</span>
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
