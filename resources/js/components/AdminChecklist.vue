<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    BookOpen, Building2, CheckCircle2, ChevronDown, ChevronUp,
    Circle, Package, PiggyBank, Truck, Users, X, Zap,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface ChecklistStep {
    key: string;
    label: string;
    detail: string;
    done: boolean;
    href: string;
    cta: string;
}

interface Checklist {
    steps: ChecklistStep[];
    completed: number;
    total: number;
    all_done: boolean;
}

const props = defineProps<{ checklist: Checklist }>();

// ─── State ────────────────────────────────────────────────────────────────────

const collapsed  = ref(false);
const dismissing = ref(false);

// Auto-collapse quand tout est fait, mais on garde affiché pour féliciter
const showCongrats = computed(() => props.checklist.all_done);

const percent = computed(() =>
    Math.round((props.checklist.completed / props.checklist.total) * 100)
);

// ─── Icônes par step ─────────────────────────────────────────────────────────

const stepIcon = (key: string) => {
    const map: Record<string, typeof Building2> = {
        boutique:         Building2,
        validation_level: CheckCircle2,
        invite_user:      Users,
        fournisseur:      Truck,
        article:          Package,
        budget:           PiggyBank,
    };
    return map[key] ?? Circle;
};

// ─── Actions ─────────────────────────────────────────────────────────────────

const dismiss = () => {
    dismissing.value = true;
    router.post(route('checklist.dismiss'), {}, {
        preserveScroll: true,
        only: ['checklist'],
        onFinish: () => { dismissing.value = false; },
    });
};
</script>

<template>
    <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">

        <!-- Header -->
        <div
            class="flex items-center gap-3 px-5 py-4 cursor-pointer select-none"
            :class="showCongrats ? 'bg-gradient-to-r from-emerald-50 to-teal-50 border-b border-emerald-100' : 'border-b'"
            @click="collapsed = !collapsed"
        >
            <!-- Icône -->
            <div :class="showCongrats ? 'bg-emerald-100' : 'bg-violet-100'"
                 class="flex h-9 w-9 items-center justify-center rounded-xl shrink-0">
                <Zap v-if="!showCongrats" class="h-4 w-4 text-violet-600" />
                <CheckCircle2 v-else class="h-4 w-4 text-emerald-600" />
            </div>

            <!-- Titre + sous-titre -->
            <div class="flex-1 min-w-0">
                <p class="text-sm font-semibold text-foreground">
                    {{ showCongrats ? 'Configuration terminée 🎉' : 'Configurer AchatPro' }}
                </p>
                <p class="text-xs text-muted-foreground mt-0.5">
                    {{ showCongrats
                        ? 'Votre application est prête à être utilisée par votre équipe.'
                        : `${checklist.completed} / ${checklist.total} étapes complétées` }}
                </p>
            </div>

            <!-- Progress pill + toggle -->
            <div class="flex items-center gap-3 shrink-0">
                <!-- Pourcentage -->
                <span
                    class="rounded-full px-2.5 py-0.5 text-xs font-bold"
                    :class="showCongrats
                        ? 'bg-emerald-100 text-emerald-700'
                        : percent >= 80
                            ? 'bg-amber-100 text-amber-700'
                            : 'bg-violet-100 text-violet-700'"
                >
                    {{ percent }}%
                </span>
                <ChevronUp v-if="!collapsed" class="h-4 w-4 text-muted-foreground" />
                <ChevronDown v-else class="h-4 w-4 text-muted-foreground" />
            </div>
        </div>

        <!-- Barre de progression -->
        <div class="h-1 bg-slate-100">
            <div
                class="h-full transition-all duration-700 ease-out"
                :class="showCongrats ? 'bg-emerald-500' : 'bg-violet-500'"
                :style="{ width: percent + '%' }"
            />
        </div>

        <!-- Corps (collapsible) -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out overflow-hidden"
            enter-from-class="max-h-0 opacity-0"
            enter-to-class="max-h-[800px] opacity-100"
            leave-active-class="transition-all duration-200 ease-in overflow-hidden"
            leave-from-class="max-h-[800px] opacity-100"
            leave-to-class="max-h-0 opacity-0"
        >
            <div v-if="!collapsed">

                <!-- Message félicitations -->
                <div v-if="showCongrats" class="px-5 py-5 flex flex-col gap-4">
                    <div class="flex items-start gap-3 rounded-xl bg-emerald-50 border border-emerald-100 p-4">
                        <BookOpen class="h-4 w-4 text-emerald-600 shrink-0 mt-0.5" />
                        <p class="text-sm text-emerald-800">
                            Toutes les étapes sont complètes. Vos demandeurs peuvent créer des commandes,
                            vos validateurs peuvent les approuver et vos budgets sont sous contrôle.
                        </p>
                    </div>
                    <div class="flex items-center justify-end">
                        <button
                            @click.stop="dismiss"
                            :disabled="dismissing"
                            class="inline-flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 transition-colors disabled:opacity-60"
                        >
                            <CheckCircle2 class="h-4 w-4" />
                            Masquer définitivement
                        </button>
                    </div>
                </div>

                <!-- Liste des étapes -->
                <div v-else class="divide-y">
                    <div
                        v-for="step in checklist.steps"
                        :key="step.key"
                        class="flex items-center gap-4 px-5 py-3.5 transition-colors"
                        :class="step.done ? 'bg-slate-50/50' : 'hover:bg-muted/20'"
                    >
                        <!-- Icône état -->
                        <div class="shrink-0">
                            <div v-if="step.done"
                                 class="flex h-7 w-7 items-center justify-center rounded-full bg-emerald-100">
                                <CheckCircle2 class="h-4 w-4 text-emerald-600" />
                            </div>
                            <div v-else
                                 class="flex h-7 w-7 items-center justify-center rounded-full border-2 border-dashed border-slate-300 bg-white">
                                <component :is="stepIcon(step.key)" class="h-3.5 w-3.5 text-slate-400" />
                            </div>
                        </div>

                        <!-- Label + détail -->
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium"
                               :class="step.done ? 'text-muted-foreground line-through decoration-slate-300' : 'text-foreground'">
                                {{ step.label }}
                            </p>
                            <p v-if="!step.done" class="text-xs text-muted-foreground mt-0.5">
                                {{ step.detail }}
                            </p>
                        </div>

                        <!-- CTA -->
                        <a
                            v-if="!step.done"
                            :href="step.href"
                            class="shrink-0 rounded-lg border border-violet-200 bg-violet-50 px-3 py-1.5 text-xs font-semibold text-violet-700 hover:bg-violet-100 transition-colors whitespace-nowrap"
                        >
                            {{ step.cta }}
                        </a>
                        <span v-else class="shrink-0 rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-700">
                            Fait
                        </span>
                    </div>
                </div>

                <!-- Footer : fermer si pas tout done -->
                <div v-if="!showCongrats" class="flex items-center justify-between border-t px-5 py-3 bg-muted/10">
                    <p class="text-xs text-muted-foreground">
                        Complétez ces étapes pour que votre équipe puisse commencer à travailler.
                    </p>
                    <button
                        @click.stop="dismiss"
                        :disabled="dismissing"
                        class="flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-medium text-muted-foreground hover:bg-muted hover:text-foreground transition-colors disabled:opacity-50"
                    >
                        <X class="h-3.5 w-3.5" />
                        Ne plus afficher
                    </button>
                </div>
            </div>
        </Transition>
    </div>
</template>
