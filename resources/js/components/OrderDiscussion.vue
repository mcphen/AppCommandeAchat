<script setup lang="ts">
import { type OrderComment, type PurchaseOrder, type User } from '@/types';
import { useForm } from '@inertiajs/vue3';
import {
    MessageSquare, Paperclip, Send, X, Download,
    RefreshCw, AlertCircle, Trash2,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

const props = defineProps<{
    order: PurchaseOrder;
    authUser: User;
    canRequestRevision?: boolean; // validateur sur une commande pending
    canComment?: boolean;         // false si commande finalisée
}>();

const formatDate = (d: string) =>
    new Date(d).toLocaleDateString('fr-FR', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

const formatSize = (bytes: number) => {
    if (bytes >= 1048576) return `${(bytes / 1048576).toFixed(1)} MB`;
    return `${Math.round(bytes / 1024)} KB`;
};

// ---- Form ----
const form = useForm({
    content: '',
    type: 'comment' as 'comment' | 'revision_request',
    attachment: null as File | null,
});

const fileInput = ref<HTMLInputElement | null>(null);
const fileName = ref<string | null>(null);

const onFileChange = (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0] ?? null;
    form.attachment = file;
    fileName.value  = file?.name ?? null;
};

const clearFile = () => {
    form.attachment = null;
    fileName.value  = null;
    if (fileInput.value) fileInput.value.value = '';
};

const setType = (t: 'comment' | 'revision_request') => {
    form.type = t;
};

const submit = () => {
    form.post(route('order-comments.store', props.order.uuid), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.reset('content', 'attachment');
            form.type = 'comment';
            fileName.value = null;
            if (fileInput.value) fileInput.value.value = '';
        },
    });
};

const destroy = (comment: OrderComment) => {
    if (! confirm('Supprimer ce message ?')) return;
    useForm({}).delete(route('order-comments.destroy', { purchase_order: props.order.uuid, comment: comment.id }), {
        preserveScroll: true,
    });
};

const isMe = (comment: OrderComment) => comment.user_id === props.authUser.id;

const isInteractive = computed(() =>
    props.canComment !== false &&
    !['approved', 'rejected'].includes(props.order.status)
);
</script>

<template>
    <div class="rounded-2xl border bg-card shadow-sm overflow-hidden">
        <!-- Header -->
        <div class="flex items-center gap-2 border-b px-5 py-3.5 bg-muted/30">
            <MessageSquare class="h-4 w-4 text-indigo-500" />
            <h3 class="font-semibold text-foreground text-sm">Discussion</h3>
            <span class="ml-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                {{ order.comments?.length ?? 0 }}
            </span>
        </div>

        <!-- Thread -->
        <div class="divide-y divide-border">
            <div v-if="!order.comments?.length" class="px-5 py-8 text-center text-sm text-muted-foreground">
                Aucun message pour l'instant. Lancez la discussion !
            </div>

            <div
                v-for="comment in order.comments"
                :key="comment.id"
                class="px-5 py-4"
                :class="comment.type === 'revision_request' ? 'bg-indigo-50/60' : ''"
            >
                <!-- Demande de révision badge -->
                <div v-if="comment.type === 'revision_request'"
                     class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                    <RefreshCw class="h-3 w-3" />
                    Demande de révision
                </div>

                <div class="flex items-start gap-3">
                    <!-- Avatar -->
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold uppercase"
                         :class="isMe(comment) ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-600'">
                        {{ comment.user?.name?.charAt(0) }}
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-baseline gap-2 flex-wrap mb-1">
                            <span class="text-sm font-semibold text-foreground">{{ comment.user?.name }}</span>
                            <span class="text-xs text-muted-foreground">{{ formatDate(comment.created_at) }}</span>
                            <span v-if="isMe(comment)" class="text-xs text-indigo-500 font-medium">Vous</span>
                        </div>

                        <p class="text-sm text-foreground whitespace-pre-wrap leading-relaxed">{{ comment.content }}</p>

                        <!-- Pièce jointe -->
                        <a v-if="comment.attachment_name"
                           :href="route('order-comments.download', { purchase_order: order.uuid, comment: comment.id })"
                           target="_blank"
                           class="mt-2 inline-flex items-center gap-1.5 rounded-lg border bg-background px-3 py-1.5 text-xs font-medium text-foreground transition hover:bg-muted">
                            <Paperclip class="h-3.5 w-3.5 text-muted-foreground" />
                            {{ comment.attachment_name }}
                            <span v-if="comment.attachment_size" class="text-muted-foreground">
                                ({{ formatSize(comment.attachment_size) }})
                            </span>
                            <Download class="h-3 w-3 text-muted-foreground" />
                        </a>
                    </div>

                    <!-- Supprimer (auteur ou admin, pas les revision_request) -->
                    <button
                        v-if="isMe(comment) && comment.type !== 'revision_request'"
                        @click="destroy(comment)"
                        class="shrink-0 rounded p-1 text-muted-foreground/50 transition hover:bg-red-50 hover:text-red-500"
                        title="Supprimer"
                    >
                        <Trash2 class="h-3.5 w-3.5" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Formulaire -->
        <div v-if="isInteractive" class="border-t bg-muted/20 px-5 py-4">
            <!-- Sélection type (validateur seulement) -->
            <div v-if="canRequestRevision" class="mb-3 flex gap-2">
                <button
                    @click="setType('comment')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                    :class="form.type === 'comment'
                        ? 'bg-indigo-600 text-white'
                        : 'border text-muted-foreground hover:bg-muted'"
                >
                    <MessageSquare class="inline h-3.5 w-3.5 mr-1" />
                    Commentaire
                </button>
                <button
                    @click="setType('revision_request')"
                    class="rounded-lg px-3 py-1.5 text-xs font-medium transition"
                    :class="form.type === 'revision_request'
                        ? 'bg-indigo-600 text-white'
                        : 'border text-muted-foreground hover:bg-muted'"
                >
                    <RefreshCw class="inline h-3.5 w-3.5 mr-1" />
                    Demander une révision
                </button>
            </div>

            <!-- Avertissement révision -->
            <div v-if="form.type === 'revision_request'"
                 class="mb-3 flex items-start gap-2 rounded-lg bg-amber-50 border border-amber-200 px-3 py-2 text-xs text-amber-800">
                <AlertCircle class="mt-0.5 h-3.5 w-3.5 shrink-0 text-amber-500" />
                La commande passera en statut <strong>Révision demandée</strong>. Le demandeur pourra la modifier et re-soumettre.
            </div>

            <!-- Zone de texte -->
            <textarea
                v-model="form.content"
                rows="3"
                :placeholder="form.type === 'revision_request'
                    ? 'Expliquez ce qui doit être modifié…'
                    : 'Votre message…'"
                class="w-full rounded-xl border bg-background px-3 py-2.5 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-indigo-500 placeholder:text-muted-foreground"
            />
            <p v-if="form.errors.content" class="mt-1 text-xs text-red-500">{{ form.errors.content }}</p>

            <!-- Footer : fichier + envoyer -->
            <div class="mt-2 flex items-center justify-between gap-3 flex-wrap">
                <div class="flex items-center gap-2">
                    <label class="cursor-pointer inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-medium text-muted-foreground transition hover:bg-muted">
                        <Paperclip class="h-3.5 w-3.5" />
                        Joindre un fichier
                        <input ref="fileInput" type="file" class="hidden"
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
                               @change="onFileChange" />
                    </label>
                    <div v-if="fileName" class="flex items-center gap-1 rounded-lg bg-indigo-50 px-2 py-1 text-xs text-indigo-700">
                        <Paperclip class="h-3 w-3" />
                        <span class="max-w-[160px] truncate">{{ fileName }}</span>
                        <button @click="clearFile" class="ml-0.5 hover:text-red-500"><X class="h-3 w-3" /></button>
                    </div>
                </div>

                <button
                    @click="submit"
                    :disabled="form.processing || !form.content.trim()"
                    class="inline-flex items-center gap-1.5 rounded-xl px-4 py-2 text-sm font-semibold text-white transition disabled:opacity-50"
                    :class="form.type === 'revision_request'
                        ? 'bg-amber-500 hover:bg-amber-600'
                        : 'bg-indigo-600 hover:bg-indigo-700'"
                >
                    <RefreshCw v-if="form.type === 'revision_request'" class="h-3.5 w-3.5" />
                    <Send v-else class="h-3.5 w-3.5" />
                    {{ form.type === 'revision_request' ? 'Demander révision' : 'Envoyer' }}
                </button>
            </div>
        </div>

        <!-- Commande finalisée -->
        <div v-else-if="['approved','rejected'].includes(order.status)"
             class="border-t bg-muted/20 px-5 py-3 text-center text-xs text-muted-foreground">
            La discussion est fermée — commande {{ order.status === 'approved' ? 'approuvée' : 'refusée' }}.
        </div>
    </div>
</template>
