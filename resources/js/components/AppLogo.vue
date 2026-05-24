<script setup lang="ts">
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    class?: string;
}

defineProps<Props>();

const page = usePage<SharedData>();
const company = computed(() => page.props.company);
const logoSrc = computed(() => company.value?.logoUrl ?? '/logo_scn.jpg');
const companyName = computed(() => company.value?.name ?? 'SCN');
</script>

<template>
    <img :src="logoSrc" :alt="companyName" class="size-8 rounded-full object-cover ring-2 ring-primary/20 shadow-sm shrink-0" />
    <div class="ml-1 grid flex-1 text-left text-sm">
        <span class="mb-0.5 truncate font-semibold leading-none">{{ companyName }}</span>
    </div>
</template>
