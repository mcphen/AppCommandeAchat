<script setup lang="ts">
import { useAppearance } from '@/composables/useAppearance';
import { Check, Monitor, Moon, Sun } from 'lucide-vue-next';

interface Props {
    class?: string;
}

const { class: containerClass = '' } = defineProps<Props>();

const { appearance, updateAppearance } = useAppearance();

const tabs = [
    {
        value: 'light',
        Icon: Sun,
        label: 'Clair',
        description: 'Interface lumineuse',
        activeClass: 'border-amber-300 bg-amber-50 text-amber-900 shadow-sm dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200',
        iconClass: 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
    },
    {
        value: 'dark',
        Icon: Moon,
        label: 'Sombre',
        description: 'Ambiance plus douce',
        activeClass: 'border-indigo-300 bg-indigo-50 text-indigo-900 shadow-sm dark:border-indigo-900 dark:bg-indigo-950/30 dark:text-indigo-200',
        iconClass: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
    },
    {
        value: 'system',
        Icon: Monitor,
        label: 'Systeme',
        description: 'Suit votre appareil',
        activeClass:
            'border-emerald-300 bg-emerald-50 text-emerald-900 shadow-sm dark:border-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200',
        iconClass: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    },
] as const;
</script>

<template>
    <div :class="['grid gap-3 sm:grid-cols-3', containerClass]">
        <button
            v-for="{ value, Icon, label, description, activeClass, iconClass } in tabs"
            :key="value"
            type="button"
            @click="updateAppearance(value)"
            class="flex w-full items-start gap-3 rounded-2xl border px-4 py-4 text-left transition-all"
            :class="
                appearance === value
                    ? activeClass
                    : 'border-border bg-background text-muted-foreground hover:border-primary/20 hover:bg-primary/5 hover:text-foreground'
            "
        >
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl"
                :class="appearance === value ? iconClass : 'bg-primary/10 text-primary'"
            >
                <component :is="Icon" class="h-5 w-5" />
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2">
                    <p class="text-sm font-semibold leading-tight">{{ label }}</p>
                    <Check v-if="appearance === value" class="h-4 w-4 shrink-0" />
                </div>
                <p class="mt-1 text-xs leading-relaxed" :class="appearance === value ? 'text-current/80' : 'text-muted-foreground'">
                    {{ description }}
                </p>
            </div>
        </button>
    </div>
</template>
