<script setup lang="ts">
import { type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight, BookOpen, BarChart2, Building2, CheckCircle2,
    ClipboardCheck, Package, PiggyBank, Receipt, Send,
    ShoppingCart, UserCheck, Users, X, Zap,
} from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref } from 'vue';

// ─── Data ────────────────────────────────────────────────────────────────────

const page  = usePage<SharedData>();
const role  = computed(() => page.props.auth.user?.role?.slug);
const show  = computed(() => page.props.show_onboarding);
const name  = computed(() => page.props.auth.user?.name?.split(' ')[0] ?? 'vous');

// ─── Slides par rôle ─────────────────────────────────────────────────────────

interface Slide {
    icon: typeof ShoppingCart;
    iconBg: string;
    iconColor: string;
    title: string;
    body: string;
    tip?: string;
    action?: { label: string; href: string };
}

const slidesAdmin: Slide[] = [
    {
        icon: Zap,
        iconBg: 'bg-violet-100',
        iconColor: 'text-violet-600',
        title: `Bienvenue, ${name.value} 👋`,
        body: 'Vous êtes administrateur d\'AchatPro. Vous contrôlez l\'intégralité du cycle d\'achat : configuration, validation, budgets et comptabilité.',
        tip: 'Ce guide vous prendra moins de 2 minutes.',
    },
    {
        icon: Building2,
        iconBg: 'bg-blue-100',
        iconColor: 'text-blue-600',
        title: 'Configurez votre organisation',
        body: 'Commencez par créer vos boutiques ou points de vente, puis invitez vos utilisateurs et assignez-leur un rôle (demandeur, validateur).',
        tip: 'Menu Administration → Boutiques, puis Utilisateurs.',
        action: { label: 'Gérer les boutiques', href: '/admin/boutiques' },
    },
    {
        icon: CheckCircle2,
        iconBg: 'bg-emerald-100',
        iconColor: 'text-emerald-600',
        title: 'Définissez le circuit de validation',
        body: 'Configurez les niveaux d\'approbation dans l\'ordre souhaité. Chaque niveau est affecté à un ou plusieurs validateurs. Les commandes passeront par chaque niveau dans l\'ordre.',
        tip: 'Menu Administration → Niveaux de validation.',
        action: { label: 'Configurer les niveaux', href: '/admin/validation-levels' },
    },
    {
        icon: PiggyBank,
        iconBg: 'bg-amber-100',
        iconColor: 'text-amber-600',
        title: 'Pilotez vos budgets',
        body: 'Définissez des enveloppes budgétaires par boutique, par catégorie ou les deux. AchatPro vous alerte automatiquement à 80% de consommation et bloque les dépassements.',
        tip: 'Menu Administration → Budgets.',
        action: { label: 'Créer un budget', href: '/admin/budgets/create' },
    },
    {
        icon: BarChart2,
        iconBg: 'bg-indigo-100',
        iconColor: 'text-indigo-600',
        title: 'Analytique & comptabilité',
        body: 'Suivez les dépenses par boutique, les délais de validation, le top fournisseurs. À chaque BC confirmé, les écritures OHADA sont générées automatiquement et exportables vers Sage, Odoo ou Epegase.',
        tip: 'Menu Analytique et menu Comptabilité dans la barre latérale.',
        action: { label: 'Voir le tableau analytique', href: '/analytics' },
    },
];

const slidesValidateur: Slide[] = [
    {
        icon: Zap,
        iconBg: 'bg-emerald-100',
        iconColor: 'text-emerald-600',
        title: `Bienvenue, ${name.value} 👋`,
        body: 'Vous êtes validateur dans le circuit d\'approbation. Les demandes d\'achat passent par vous avant d\'être confirmées. Votre décision est tracée et horodatée.',
        tip: 'Ce guide vous prendra moins de 2 minutes.',
    },
    {
        icon: ClipboardCheck,
        iconBg: 'bg-blue-100',
        iconColor: 'text-blue-600',
        title: 'Vos commandes en attente',
        body: 'Dans le menu "Validations", vous voyez toutes les commandes qui attendent votre décision à votre niveau. Vous recevez aussi une notification email et in-app à chaque nouvelle demande.',
        action: { label: 'Voir les validations', href: '/validations' },
    },
    {
        icon: CheckCircle2,
        iconBg: 'bg-emerald-100',
        iconColor: 'text-emerald-600',
        title: 'Approuver, refuser ou réviser',
        body: 'Sur chaque commande, vous pouvez :\n• Approuver → la commande passe au niveau suivant\n• Refuser avec un motif → le demandeur est notifié\n• Demander une révision → la commande est suspendue sans être rejetée',
        tip: 'Vous pouvez aussi échanger directement avec le demandeur via la discussion.',
    },
    {
        icon: UserCheck,
        iconBg: 'bg-orange-100',
        iconColor: 'text-orange-600',
        title: 'Vous partez en déplacement ?',
        body: 'Déléguez temporairement vos droits de validation à un collègue. La délégation est datée et intégralement tracée dans l\'audit. Aucune commande ne restera bloquée.',
        tip: 'Menu Délégations dans la barre latérale.',
        action: { label: 'Gérer mes délégations', href: '/delegations' },
    },
];

const slidesDemandeur: Slide[] = [
    {
        icon: Zap,
        iconBg: 'bg-sky-100',
        iconColor: 'text-sky-600',
        title: `Bienvenue, ${name.value} 👋`,
        body: 'Vous pouvez créer vos demandes d\'achat directement depuis AchatPro. Elles suivent automatiquement le circuit de validation configuré par votre administrateur.',
        tip: 'Ce guide vous prendra moins de 2 minutes.',
    },
    {
        icon: ShoppingCart,
        iconBg: 'bg-blue-100',
        iconColor: 'text-blue-600',
        title: 'Créez votre première commande',
        body: 'Sélectionnez les articles dans le catalogue, choisissez le fournisseur, indiquez les quantités. Le montant total est calculé automatiquement. Joignez vos devis si nécessaire.',
        tip: 'Vous pouvez sauvegarder en brouillon avant de soumettre.',
        action: { label: 'Créer une commande', href: '/purchase-orders/create' },
    },
    {
        icon: Send,
        iconBg: 'bg-indigo-100',
        iconColor: 'text-indigo-600',
        title: 'Suivez vos commandes en temps réel',
        body: 'Dans "Mes commandes", vous voyez le statut de chaque demande : brouillon, en attente, approuvée, refusée. Vous pouvez suivre qui a validé à chaque niveau et lire les commentaires des validateurs.',
        action: { label: 'Mes commandes', href: '/purchase-orders' },
    },
    {
        icon: Package,
        iconBg: 'bg-orange-100',
        iconColor: 'text-orange-600',
        title: 'Réceptionnez les livraisons',
        body: 'Quand la marchandise arrive, enregistrez les quantités reçues ligne par ligne. Vous pouvez faire plusieurs réceptions partielles et rattacher la facture fournisseur directement depuis la page de commande.',
        tip: 'Menu Réceptions dans la barre latérale.',
        action: { label: 'Tableau des réceptions', href: '/receptions' },
    },
];

const slides = computed<Slide[]>(() => {
    if (role.value === 'admin')      return slidesAdmin;
    if (role.value === 'validateur') return slidesValidateur;
    return slidesDemandeur;
});

// ─── Navigation ───────────────────────────────────────────────────────────────

const current   = ref(0);
const leaving   = ref(false);
const direction = ref<'forward' | 'back'>('forward');

const isLast = computed(() => current.value === slides.value.length - 1);
const isFirst = computed(() => current.value === 0);

const goTo = (index: number) => {
    if (index === current.value) return;
    direction.value = index > current.value ? 'forward' : 'back';
    leaving.value   = true;
    setTimeout(() => {
        current.value = index;
        leaving.value = false;
    }, 200);
};

const next = () => { if (!isLast.value) goTo(current.value + 1); else complete(); };
const prev = () => { if (!isFirst.value) goTo(current.value - 1); };

// ─── Complétion ───────────────────────────────────────────────────────────────

const completing = ref(false);

const complete = () => {
    if (completing.value) return;
    completing.value = true;
    router.post(route('onboarding.complete'), {}, {
        preserveScroll: true,
        onFinish: () => { completing.value = false; },
    });
};

// ─── Keyboard ────────────────────────────────────────────────────────────────

const onKey = (e: KeyboardEvent) => {
    if (!show.value) return;
    if (e.key === 'Escape')     complete();
    if (e.key === 'ArrowRight' || e.key === 'Enter') next();
    if (e.key === 'ArrowLeft')  prev();
};

onMounted(()  => window.addEventListener('keydown', onKey));
onUnmounted(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show"
                class="fixed inset-0 z-[100] flex items-center justify-center p-4"
                style="background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(6px);"
            >
                <!-- Card -->
                <div class="relative w-full max-w-lg">

                    <!-- Bouton fermer (skip) -->
                    <button
                        @click="complete"
                        class="absolute -top-3 -right-3 z-10 flex h-8 w-8 items-center justify-center rounded-full bg-white shadow-lg text-slate-400 hover:text-slate-700 transition-colors"
                        title="Passer le guide (Échap)"
                    >
                        <X class="h-4 w-4" />
                    </button>

                    <!-- Slide card -->
                    <Transition
                        :enter-active-class="`transition duration-200 ease-out`"
                        :enter-from-class="direction === 'forward' ? 'opacity-0 translate-x-6' : 'opacity-0 -translate-x-6'"
                        enter-to-class="opacity-100 translate-x-0"
                        :leave-active-class="`transition duration-150 ease-in absolute inset-0`"
                        leave-from-class="opacity-100 translate-x-0"
                        :leave-to-class="direction === 'forward' ? 'opacity-0 -translate-x-6' : 'opacity-0 translate-x-6'"
                    >
                        <div
                            :key="current"
                            class="rounded-3xl bg-white shadow-2xl overflow-hidden"
                        >
                            <!-- Bande supérieure colorée selon le rôle -->
                            <div
                                class="h-1.5 w-full"
                                :class="{
                                    'bg-gradient-to-r from-violet-500 to-purple-400': role === 'admin',
                                    'bg-gradient-to-r from-emerald-500 to-teal-400':  role === 'validateur',
                                    'bg-gradient-to-r from-sky-500 to-blue-400':      role === 'demandeur' || !role,
                                }"
                            />

                            <!-- Corps -->
                            <div class="px-8 py-8">

                                <!-- Icône -->
                                <div class="mb-6 flex items-center gap-4">
                                    <div :class="[slides[current].iconBg, 'flex h-14 w-14 items-center justify-center rounded-2xl shrink-0']">
                                        <component :is="slides[current].icon" :class="[slides[current].iconColor, 'h-7 w-7']" />
                                    </div>
                                    <!-- Numéro d'étape -->
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-widest text-slate-400">
                                            Étape {{ current + 1 }} sur {{ slides.length }}
                                        </p>
                                        <h2 class="text-xl font-bold text-slate-900 mt-0.5">
                                            {{ slides[current].title }}
                                        </h2>
                                    </div>
                                </div>

                                <!-- Corps texte -->
                                <p class="text-slate-600 leading-relaxed whitespace-pre-line text-sm">
                                    {{ slides[current].body }}
                                </p>

                                <!-- Tip -->
                                <div v-if="slides[current].tip" class="mt-4 flex items-start gap-2 rounded-xl bg-slate-50 border border-slate-100 px-4 py-3">
                                    <BookOpen class="h-4 w-4 text-slate-400 shrink-0 mt-0.5" />
                                    <p class="text-xs text-slate-500">{{ slides[current].tip }}</p>
                                </div>

                                <!-- Action optionnelle -->
                                <a
                                    v-if="slides[current].action"
                                    :href="slides[current].action!.href"
                                    @click="complete"
                                    class="mt-4 inline-flex items-center gap-1.5 text-xs font-semibold underline underline-offset-2"
                                    :class="{
                                        'text-violet-600 hover:text-violet-800': role === 'admin',
                                        'text-emerald-600 hover:text-emerald-800': role === 'validateur',
                                        'text-sky-600 hover:text-sky-800': role === 'demandeur' || !role,
                                    }"
                                >
                                    {{ slides[current].action!.label }}
                                    <ArrowRight class="h-3 w-3" />
                                </a>
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-between px-8 pb-7">

                                <!-- Progress dots -->
                                <div class="flex items-center gap-2">
                                    <button
                                        v-for="(_, i) in slides"
                                        :key="i"
                                        @click="goTo(i)"
                                        class="rounded-full transition-all duration-200"
                                        :class="i === current
                                            ? 'w-5 h-2 ' + (role === 'admin' ? 'bg-violet-500' : role === 'validateur' ? 'bg-emerald-500' : 'bg-sky-500')
                                            : 'w-2 h-2 bg-slate-200 hover:bg-slate-300'"
                                    />
                                </div>

                                <!-- Boutons nav -->
                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="!isFirst"
                                        @click="prev"
                                        class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 transition-colors"
                                    >
                                        Précédent
                                    </button>
                                    <button
                                        @click="next"
                                        :disabled="completing"
                                        class="inline-flex items-center gap-2 rounded-xl px-5 py-2 text-sm font-semibold text-white shadow-sm transition-all disabled:opacity-60"
                                        :class="{
                                            'bg-violet-600 hover:bg-violet-700': role === 'admin',
                                            'bg-emerald-600 hover:bg-emerald-700': role === 'validateur',
                                            'bg-sky-600 hover:bg-sky-700': role === 'demandeur' || !role,
                                        }"
                                    >
                                        <template v-if="isLast">
                                            <CheckCircle2 class="h-4 w-4" />
                                            C'est parti !
                                        </template>
                                        <template v-else>
                                            Suivant
                                            <ArrowRight class="h-4 w-4" />
                                        </template>
                                    </button>
                                </div>
                            </div>

                            <!-- Hint clavier (discret) -->
                            <div class="border-t border-slate-50 px-8 py-3 flex items-center justify-center gap-3">
                                <span class="text-[11px] text-slate-300 flex items-center gap-1">
                                    <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono text-slate-400">←→</kbd>
                                    naviguer
                                </span>
                                <span class="text-[11px] text-slate-300 flex items-center gap-1">
                                    <kbd class="rounded border border-slate-200 bg-slate-50 px-1.5 py-0.5 text-[10px] font-mono text-slate-400">Échap</kbd>
                                    passer
                                </span>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
