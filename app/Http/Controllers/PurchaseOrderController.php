<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePurchaseOrderRequest;
use App\Http\Requests\UpdatePurchaseOrderRequest;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderAttachment;
use App\Models\ValidationLevel;
use App\Notifications\OrderSubmittedNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PurchaseOrderController extends Controller
{
    public function index(): Response
    {
        $orders = PurchaseOrder::where('user_id', auth()->id())
            ->with('attachments')
            ->latest()
            ->paginate(10);

        return Inertia::render('PurchaseOrders/Index', [
            'orders' => $orders,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('PurchaseOrders/Create');
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $order = PurchaseOrder::create([
                'user_id'     => auth()->id(),
                'title'       => $request->title,
                'description' => $request->description,
                'amount'      => $request->amount,
                'status'      => 'draft',
            ]);

            $this->storeAttachments($order, $request);
        });

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Commande créée avec succès.');
    }

    public function show(PurchaseOrder $purchaseOrder): Response
    {
        $this->authorizeView($purchaseOrder);

        $purchaseOrder->load([
            'attachments',
            'validationLogs.validationLevel',
            'validationLogs.user',
        ]);

        $levels = ValidationLevel::orderBy('order')->get();

        return Inertia::render('PurchaseOrders/Show', [
            'order'  => $purchaseOrder,
            'levels' => $levels,
        ]);
    }

    public function edit(PurchaseOrder $purchaseOrder): Response
    {
        abort_unless($purchaseOrder->isEditableBy(auth()->user()), 403);

        $purchaseOrder->load('attachments');

        return Inertia::render('PurchaseOrders/Edit', [
            'order' => $purchaseOrder,
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        DB::transaction(function () use ($request, $purchaseOrder) {
            $purchaseOrder->update([
                'title'       => $request->title,
                'description' => $request->description,
                'amount'      => $request->amount,
            ]);

            // Supprimer les pièces jointes marquées pour suppression
            if ($request->deleted_attachment_ids) {
                $toDelete = $purchaseOrder->attachments()
                    ->whereIn('id', $request->deleted_attachment_ids)
                    ->get();

                foreach ($toDelete as $attachment) {
                    Storage::disk('private')->delete($attachment->file_path);
                    $attachment->delete();
                }
            }

            $this->storeAttachments($purchaseOrder, $request);
        });

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Commande mise à jour.');
    }

    public function submit(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless(
            $purchaseOrder->user_id === auth()->id() && in_array($purchaseOrder->status, ['draft', 'rejected']),
            403
        );

        $firstLevel = ValidationLevel::first_level();

        abort_if(! $firstLevel, 422, 'Aucun niveau de validation configuré.');

        $purchaseOrder->update([
            'status'              => 'pending',
            'current_level_order' => $firstLevel->order,
            'submitted_at'        => now(),
        ]);

        // Notifier les validateurs du premier niveau
        $validators = $firstLevel->validators;
        foreach ($validators as $validator) {
            $validator->notify(new OrderSubmittedNotification($purchaseOrder, $firstLevel));
        }

        return redirect()->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Commande soumise à validation.');
    }

    public function destroy(PurchaseOrder $purchaseOrder): RedirectResponse
    {
        abort_unless($purchaseOrder->isEditableBy(auth()->user()), 403);

        foreach ($purchaseOrder->attachments as $attachment) {
            Storage::disk('private')->delete($attachment->file_path);
        }

        $purchaseOrder->delete();

        return redirect()->route('purchase-orders.index')
            ->with('success', 'Commande supprimée.');
    }

    private function storeAttachments(PurchaseOrder $order, $request): void
    {
        if (! $request->hasFile('attachments')) {
            return;
        }

        foreach ($request->file('attachments') as $file) {
            $path = $file->store("attachments/{$order->id}", 'private');

            PurchaseOrderAttachment::create([
                'purchase_order_id' => $order->id,
                'file_path'         => $path,
                'file_name'         => $file->getClientOriginalName(),
                'file_size'         => $file->getSize(),
            ]);
        }
    }

    private function authorizeView(PurchaseOrder $order): void
    {
        $user = auth()->user();

        if ($user->isAdmin() || $user->canValidate()) {
            return;
        }

        abort_unless($order->user_id === $user->id, 403);
    }
}
