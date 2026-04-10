<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { type Component } from 'vue';

withDefaults(defineProps<{
    icon: Component;
    iconBg?: string;
    iconColor?: string;
    title: string;
    description?: string;
    actionLabel?: string;
    actionHref?: string;
    bordered?: boolean;
}>(), {
    iconBg: 'bg-muted',
    iconColor: 'text-muted-foreground',
    bordered: true,
});
</script>

<template>
    <div
        class="flex flex-col items-center justify-center px-6 py-16 text-center"
        :class="bordered ? 'rounded-2xl border-2 border-dashed border-border bg-card' : ''"
    >
        <!-- Illustration -->
        <div :class="[iconBg, 'mb-5 flex h-16 w-16 items-center justify-center rounded-2xl shadow-sm']">
            <component :is="icon" :class="[iconColor, 'h-8 w-8']" />
        </div>

        <!-- Text -->
        <h3 class="mb-2 font-semibold text-foreground">{{ title }}</h3>
        <p v-if="description" class="mb-6 max-w-xs text-sm text-muted-foreground leading-relaxed">
            {{ description }}
        </p>

        <!-- Default action -->
        <slot>
            <Link
                v-if="actionHref && actionLabel"
                :href="actionHref"
                class="inline-flex items-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground shadow-sm transition-colors hover:bg-primary/90"
            >
                {{ actionLabel }}
            </Link>
        </slot>
    </div>
</template>
