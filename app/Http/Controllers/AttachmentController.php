<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrderAttachment;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class AttachmentController extends Controller
{
    public function download(PurchaseOrderAttachment $attachment): Response
    {
        $user  = auth()->user();
        $order = $attachment->purchaseOrder;

        // Le demandeur ne peut télécharger que ses propres pièces jointes
        // Les validateurs et admin ont accès à tout
        if (! $user->canValidate() && ! $user->isAdmin()) {
            abort_unless($order->user_id === $user->id, 403);
        }

        abort_unless(Storage::disk('private')->exists($attachment->file_path), 404);

        return Storage::disk('private')->response(
            $attachment->file_path,
            $attachment->file_name,
            ['Content-Type' => 'application/pdf']
        );
    }
}
