<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import Tooltip from '@/components/ui/tooltip/Tooltip.vue';
import TooltipContent from '@/components/ui/tooltip/TooltipContent.vue';
import TooltipTrigger from '@/components/ui/tooltip/TooltipTrigger.vue';
import { cn } from '@/lib/utils';
import { PanelLeft, PanelLeftClose } from 'lucide-vue-next';
import { computed, type HTMLAttributes } from 'vue';
import { useSidebar } from './utils';

const props = defineProps<{
    class?: HTMLAttributes['class'];
}>();

const { toggleSidebar, state } = useSidebar();

const isExpanded = computed(() => state.value === 'expanded');
const label = computed(() => isExpanded.value ? 'Réduire le menu' : 'Ouvrir le menu');
</script>

<template>
    <Tooltip>
        <TooltipTrigger as-child>
            <Button
                data-sidebar="trigger"
                variant="outline"
                size="icon"
                :class="cn(
                    'h-8 w-8 shrink-0 border border-border/60 bg-background/80 shadow-sm',
                    'hover:bg-accent hover:border-border transition-all duration-150',
                    props.class
                )"
                @click="toggleSidebar"
            >
                <PanelLeftClose v-if="isExpanded" class="h-4 w-4 text-foreground/70" />
                <PanelLeft v-else class="h-4 w-4 text-foreground/70" />
                <span class="sr-only">{{ label }}</span>
            </Button>
        </TooltipTrigger>
        <TooltipContent side="right" :delay-duration="300">
            {{ label }}
        </TooltipContent>
    </Tooltip>
</template>
