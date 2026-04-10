<?php

namespace App\Http\Controllers;

use App\Models\OrderComment;
use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use App\Notifications\OrderCommentAddedNotification;
use App\Notifications\OrderRevisionRequestedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class OrderCommentController extends Controller
{
    public function store(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $user = $request->user();

        $this->authorizeComment($purchaseOrder, $user);

        $data = $request->validate([
            'content'    => ['required', 'string', 'max:2000'],
            'type'       => ['required', 'in:comment,revision_request'],
            'attachment' => ['nullable', 'file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);

        // Seuls les validateurs/admin peuvent demander une révision
        if ($data['type'] === 'revision_request') {
            abort_unless($user->canValidate(), 403, 'Seul un validateur peut demander une révision.');
            abort_unless($purchaseOrder->isPending(), 422, 'Impossible de demander une révision sur cette commande.');
        }

        DB::transaction(function () use ($request, $purchaseOrder, $user, $data) {
            $attachmentPath = null;
            $attachmentName = null;
            $attachmentSize = null;

            if ($request->hasFile('attachment')) {
                $file           = $request->file('attachment');
                $attachmentPath = $file->store("comments/{$purchaseOrder->id}", 'private');
                $attachmentName = $file->getClientOriginalName();
                $attachmentSize = $file->getSize();
            }

            $comment = OrderComment::create([
                'purchase_order_id' => $purchaseOrder->id,
                'user_id'           => $user->id,
                'type'              => $data['type'],
                'content'           => $data['content'],
                'attachment_path'   => $attachmentPath,
                'attachment_name'   => $attachmentName,
                'attachment_size'   => $attachmentSize,
            ]);

            if ($data['type'] === 'revision_request') {
                $purchaseOrder->update(['status' => 'needs_revision']);

                // Notifier le demandeur
                $purchaseOrder->user->notify(
                    new OrderRevisionRequestedNotification($purchaseOrder, $comment->load('user'))
                );
            } else {
                // Notifier les parties impliquées (sauf l'auteur du commentaire)
                $this->notifyParties($purchaseOrder, $comment->load('user'), $user->id);
            }
        });

        return back()->with('success', 'Message envoyé.');
    }

    public function download(PurchaseOrder $purchaseOrder, OrderComment $comment): \Illuminate\Http\Response
    {
        abort_unless($comment->purchase_order_id === $purchaseOrder->id, 404);
        abort_unless($comment->hasAttachment(), 404);

        $user = auth()->user();
        abort_unless(
            $user->isAdmin() || $user->canValidate() || $purchaseOrder->user_id === $user->id,
            403
        );

        abort_unless(Storage::disk('private')->exists($comment->attachment_path), 404);

        return Storage::disk('private')->response(
            $comment->attachment_path,
            $comment->attachment_name
        );
    }

    public function destroy(PurchaseOrder $purchaseOrder, OrderComment $comment): RedirectResponse
    {
        abort_unless($comment->purchase_order_id === $purchaseOrder->id, 404);
        abort_unless(auth()->id() === $comment->user_id || auth()->user()->isAdmin(), 403);
        abort_unless(! $comment->isRevisionRequest(), 422, 'Impossible de supprimer une demande de révision.');

        $comment->deleteAttachment();
        $comment->delete();

        return back()->with('success', 'Message supprimé.');
    }

    private function authorizeComment(PurchaseOrder $purchaseOrder, $user): void
    {
        // Admin et validateurs : toujours autorisés si la commande est visible
        if ($user->isAdmin() || $user->canValidate()) return;

        // Demandeur : uniquement sur ses propres commandes
        abort_unless($purchaseOrder->user_id === $user->id, 403);

        // La commande doit être dans un état commentable
        abort_unless(
            in_array($purchaseOrder->status, ['pending', 'needs_revision']),
            422,
            'Les commentaires ne sont pas disponibles pour cette commande.'
        );
    }

    private function notifyParties(PurchaseOrder $purchaseOrder, OrderComment $comment, int $authorId): void
    {
        $notified = collect();

        // Demandeur (si ce n'est pas lui l'auteur)
        if ($purchaseOrder->user_id !== $authorId) {
            $purchaseOrder->user->notify(new OrderCommentAddedNotification($purchaseOrder, $comment));
            $notified->push($purchaseOrder->user_id);
        }

        // Validateurs du niveau courant (si la commande est en pending)
        if ($purchaseOrder->isPending() && $purchaseOrder->current_level_order) {
            $level = ValidationLevel::where('order', $purchaseOrder->current_level_order)->first();
            foreach ($level?->validators ?? [] as $validator) {
                if ($validator->id !== $authorId && ! $notified->contains($validator->id)) {
                    $validator->notify(new OrderCommentAddedNotification($purchaseOrder, $comment));
                    $notified->push($validator->id);
                }
            }
        }
    }
}
