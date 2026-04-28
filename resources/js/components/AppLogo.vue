<script setup lang="ts">
interface Props {
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <img src="/logo_scn.jpg" alt="SCN" class="size-8 rounded-full object-cover ring-2 ring-primary/20 shadow-sm shrink-0" />
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate font-semibold leading-none">SCN</span>
    </div>
</template>
