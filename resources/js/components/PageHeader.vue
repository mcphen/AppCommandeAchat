<script setup lang="ts">
interface Props {
    title: string;
    subtitle?: string;
    eyebrow?: string;
    compactMobile?: boolean;
}

withDefaults(defineProps<Props>(), {
    compactMobile: true,
});
</script>

<template>
    <header
        class="relative overflow-hidden rounded-2xl border bg-card md:px-6 md:py-6"
        :class="compactMobile ? 'px-4 py-4' : 'px-5 py-5'"
    >
        <div class="pointer-events-none absolute -right-16 -top-16 h-48 w-48 rounded-full bg-primary/10 blur-3xl" />
        <div class="pointer-events-none absolute -bottom-20 left-0 h-44 w-44 rounded-full bg-cyan-300/10 blur-3xl" />

        <div
            class="relative flex flex-col md:flex-row md:items-start md:justify-between"
            :class="compactMobile ? 'gap-3' : 'gap-4'"
        >
            <div class="min-w-0">
                <p v-if="eyebrow" class="mb-1.5 text-xs font-semibold uppercase tracking-[0.12em] text-muted-foreground">
                    {{ eyebrow }}
                </p>
                <h1
                    class="font-extrabold text-foreground md:text-[30px] md:leading-9"
                    :class="compactMobile ? 'text-xl leading-7' : 'text-2xl'"
                >
                    {{ title }}
                </h1>
                <p v-if="subtitle" class="text-muted-foreground md:text-[15px]" :class="compactMobile ? 'mt-1 text-xs' : 'mt-1.5 text-sm'">
                    {{ subtitle }}
                </p>
            </div>

            <div class="flex shrink-0 flex-wrap items-center gap-2">
                <slot name="actions" />
            </div>
        </div>
    </header>
</template>
