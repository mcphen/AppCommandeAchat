<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import OnboardingGuide from '@/components/OnboardingGuide.vue';
import type { BreadcrumbItemType, SharedData } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { CheckCircle, XCircle, X } from 'lucide-vue-next';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage<SharedData>();
const flash = computed(() => page.props.flash);
const showFlash = ref(false);

watch(
    () => flash.value,
    (val) => {
        if (val?.success || val?.error) {
            showFlash.value = true;
            setTimeout(() => { showFlash.value = false; }, 4500);
        }
    },
    { immediate: true, deep: true }
);
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />

            <!-- Flash notifications -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-y-[-8px] opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div v-if="showFlash && (flash?.success || flash?.error)" class="px-6 pt-4">
                    <div
                        class="flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium shadow-sm"
                        :class="flash?.success
                            ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                            : 'bg-red-50 text-red-800 border border-red-200'"
                    >
                        <CheckCircle v-if="flash?.success" class="h-4 w-4 shrink-0 text-emerald-500" />
                        <XCircle v-else class="h-4 w-4 shrink-0 text-red-500" />
                        <span class="flex-1">{{ flash?.success ?? flash?.error }}</span>
                        <button @click="showFlash = false" class="ml-auto rounded-md p-0.5 opacity-60 hover:opacity-100">
                            <X class="h-3.5 w-3.5" />
                        </button>
                    </div>
                </div>
            </Transition>

            <slot />
        </AppContent>
    </AppShell>

    <!-- Guide premier login — monté globalement, géré par OnboardingGuide -->
    <OnboardingGuide />
</template>
