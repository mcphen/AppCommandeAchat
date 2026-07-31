<?php

namespace App\Http\Controllers;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function store(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        $this->authorizeEdit($purchaseOrder, $request->user());

        $data = $request->validate([
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:10240', 'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png'],
        ]);

        foreach ($data['files'] as $file) {
            $path = $file->store("purchase-orders/{$purchaseOrder->id}", 'private');

            PurchaseOrderAttachment::create([
                'purchase_order_id' => $purchaseOrder->id,
                'file_path'         => $path,
                'file_name'         => $file->getClientOriginalName(),
                'file_size'         => $file->getSize(),
            ]);
        }

        return back()->with('success', 'Pièce(s) jointe(s) ajoutée(s).');
    }

    public function destroy(Request $request, PurchaseOrderAttachment $attachment): RedirectResponse
    {
        $order = $attachment->purchaseOrder;
        $this->authorizeEdit($order, $request->user());

        Storage::disk('private')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('success', 'Pièce jointe supprimée.');
    }

    private function authorizeEdit(PurchaseOrder $order, $user): void
    {
        abort_unless($user->isAdmin() || $order->user_id === $user->id, 403);
        abort_unless($order->status === 'draft', 422, 'Les pièces jointes ne peuvent être modifiées qu\'avant soumission de la commande.');
    }
}
