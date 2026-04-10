<script setup lang="ts">
import { type AppNotification, type SharedData } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { Bell, CheckCheck, CheckCircle2, ClipboardList, ExternalLink, Send, XCircle } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage<SharedData>();

const open = ref(false);
const unreadCount = ref(page.props.unread_notifications_count ?? 0);
const notifications = ref<AppNotification[]>([]);
const loading = ref(false);
let pollInterval: ReturnType<typeof setInterval> | null = null;

const colorMap = {
    blue:   { bg: 'bg-blue-100',   text: 'text-blue-600',   dot: 'bg-blue-500'   },
    amber:  { bg: 'bg-amber-100',  text: 'text-amber-600',  dot: 'bg-amber-500'  },
    emerald:{ bg: 'bg-emerald-100',text: 'text-emerald-600',dot: 'bg-emerald-500' },
    red:    { bg: 'bg-red-100',    text: 'text-red-600',    dot: 'bg-red-500'    },
} as const;

const typeIcon = (type: string) => {
    if (type === 'order_submitted' || type === 'order_approved_at_level') return ClipboardList;
    if (type === 'order_finally_approved') return CheckCircle2;
    if (type === 'order_rejected') return XCircle;
    return Bell;
};

const timeAgo = (iso: string) => {
    const diff = Math.floor((Date.now() - new Date(iso).getTime()) / 1000);
    if (diff < 60) return 'À l\'instant';
    if (diff < 3600) return `Il y a ${Math.floor(diff / 60)} min`;
    if (diff < 86400) return `Il y a ${Math.floor(diff / 3600)} h`;
    return `Il y a ${Math.floor(diff / 86400)} j`;
};

const poll = async () => {
    try {
        const res = await fetch(route('notifications.poll'), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!res.ok) return;
        const data = await res.json();
        unreadCount.value = data.unread_count;
        notifications.value = data.notifications;
    } catch {}
};

const openPanel = async () => {
    open.value = true;
    loading.value = true;
    await poll();
    loading.value = false;
};

const markAllRead = async () => {
    await fetch(route('notifications.read-all'), {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
        },
    });
    unreadCount.value = 0;
    notifications.value = notifications.value.map(n => ({ ...n, read: true }));
};

const clickNotification = (notif: AppNotification) => {
    open.value = false;
    router.get(route('notifications.read', notif.id));
};

const unreadNotifications = computed(() => notifications.value.filter(n => !n.read));
const hasUnread = computed(() => unreadCount.value > 0);

onMounted(() => {
    // Polling toutes les 30 secondes en arrière-plan
    pollInterval = setInterval(poll, 30_000);
});

onBeforeUnmount(() => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
    <div class="relative">
        <!-- Overlay fermeture -->
        <div v-if="open" class="fixed inset-0 z-30" @click="open = false" />

        <!-- Bouton cloche -->
        <button
            class="relative flex h-8 w-8 shrink-0 items-center justify-center rounded-lg transition-colors hover:bg-sidebar-accent"
            :class="open ? 'bg-sidebar-accent text-sidebar-foreground' : 'text-sidebar-foreground/50 hover:text-sidebar-foreground'"
            @click="open ? open = false : openPanel()"
            :title="hasUnread ? `${unreadCount} notification${unreadCount > 1 ? 's' : ''} non lue${unreadCount > 1 ? 's' : ''}` : 'Notifications'"
        >
            <Bell class="h-4 w-4" />
            <!-- Badge -->
            <Transition
                enter-active-class="transition duration-150 ease-out"
                enter-from-class="opacity-0 scale-50"
                enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-100 ease-in"
                leave-from-class="opacity-100 scale-100"
                leave-to-class="opacity-0 scale-50"
            >
                <span
                    v-if="hasUnread"
                    class="absolute -right-0.5 -top-0.5 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[9px] font-bold text-white shadow"
                >
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
            </Transition>
        </button>

        <!-- Panneau dropdown -->
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 scale-95 -translate-y-1"
            enter-to-class="opacity-100 scale-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 scale-100 translate-y-0"
            leave-to-class="opacity-0 scale-95 -translate-y-1"
        >
            <div
                v-if="open"
                class="absolute left-0 top-full z-40 mt-2 w-80 overflow-hidden rounded-2xl border bg-card shadow-xl"
                style="max-height: 480px;"
            >
                <!-- Header -->
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <div class="flex items-center gap-2">
                        <Bell class="h-4 w-4 text-muted-foreground" />
                        <span class="text-sm font-semibold text-foreground">Notifications</span>
                        <span v-if="hasUnread" class="rounded-full bg-red-500 px-1.5 py-0.5 text-[10px] font-bold text-white">
                            {{ unreadCount }}
                        </span>
                    </div>
                    <button
                        v-if="hasUnread"
                        class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                        @click="markAllRead"
                    >
                        <CheckCheck class="h-3.5 w-3.5" />
                        Tout lire
                    </button>
                </div>

                <!-- Liste -->
                <div class="overflow-y-auto" style="max-height: 380px;">
                    <!-- Loading -->
                    <div v-if="loading" class="flex items-center justify-center py-10">
                        <div class="h-5 w-5 animate-spin rounded-full border-2 border-primary border-t-transparent" />
                    </div>

                    <!-- Empty -->
                    <div v-else-if="notifications.length === 0" class="flex flex-col items-center justify-center px-4 py-10 text-center">
                        <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-muted">
                            <Bell class="h-5 w-5 text-muted-foreground" />
                        </div>
                        <p class="text-sm font-medium text-foreground">Aucune notification</p>
                        <p class="mt-1 text-xs text-muted-foreground">Vous êtes à jour !</p>
                    </div>

                    <!-- Notifications -->
                    <template v-else>
                        <button
                            v-for="notif in notifications"
                            :key="notif.id"
                            class="group w-full border-b px-4 py-3.5 text-left transition-colors last:border-0 hover:bg-muted/50"
                            :class="!notif.read ? 'bg-primary/[0.03]' : ''"
                            @click="clickNotification(notif)"
                        >
                            <div class="flex items-start gap-3">
                                <!-- Icône colorée -->
                                <div
                                    class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-xl"
                                    :class="colorMap[notif.color]?.bg ?? 'bg-blue-100'"
                                >
                                    <component
                                        :is="typeIcon(notif.type)"
                                        class="h-4 w-4"
                                        :class="colorMap[notif.color]?.text ?? 'text-blue-600'"
                                    />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-start justify-between gap-1">
                                        <p
                                            class="text-xs font-semibold leading-tight text-foreground"
                                            :class="!notif.read ? 'text-foreground' : 'text-foreground/80'"
                                        >
                                            {{ notif.title }}
                                        </p>
                                        <!-- Point non-lu -->
                                        <span
                                            v-if="!notif.read"
                                            class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-primary"
                                        />
                                    </div>
                                    <p class="mt-0.5 line-clamp-2 text-xs text-muted-foreground">{{ notif.body }}</p>
                                    <p class="mt-1.5 text-[10px] text-muted-foreground/60">{{ timeAgo(notif.created_at) }}</p>
                                </div>

                                <ExternalLink class="h-3.5 w-3.5 shrink-0 text-muted-foreground/30 transition-colors group-hover:text-muted-foreground" />
                            </div>
                        </button>
                    </template>
                </div>
            </div>
        </Transition>
    </div>
</template>
