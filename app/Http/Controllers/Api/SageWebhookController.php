<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Fournisseur;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\SageWebhookLog;
use App\Models\User;
use App\Models\ValidationLevel;
use App\Notifications\OrderSubmittedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class SageWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'numero'                  => ['required', 'string', 'max:255'],
            'date'                    => ['required', 'date'],
            'tiers'                   => ['required', 'string', 'max:255'],
            'montant_ht'              => ['nullable', 'numeric', 'min:0'],
            'montant_ttc'             => ['nullable', 'numeric', 'min:0'],
            'lignes'                  => ['required', 'array', 'min:1'],
            'lignes.*.article'        => ['required', 'string', 'max:255'],
            'lignes.*.designation'    => ['nullable', 'string', 'max:255'],
            'lignes.*.quantite'       => ['required', 'numeric', 'min:0.01'],
            'lignes.*.prix_unitaire'  => ['required', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            $this->log($request->input('numero'), null, 'rejected', $validator->errors()->toJson(), $request->all());

            return response()->json(['message' => 'Payload invalide.', 'errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        try {
            $order = DB::transaction(fn () => $this->upsertOrder($data));
        } catch (RuntimeException $e) {
            $this->log($data['numero'], null, 'rejected', $e->getMessage(), $request->all());

            return response()->json(['message' => $e->getMessage()], 409);
        }

        $this->log($data['numero'], $order->id, 'success', null, $request->all());

        return response()->json([
            'id'             => $order->id,
            'sage_reference' => $order->sage_reference,
            'status'         => $order->status,
        ], 201);
    }

    private function upsertOrder(array $data): PurchaseOrder
    {
        $existing = PurchaseOrder::where('sage_reference', $data['numero'])->first();

        if ($existing && $existing->status === 'approved') {
            throw new RuntimeException("La commande {$data['numero']} est déjà approuvée et ne peut plus être modifiée.");
        }

        $fournisseur = $this->findOrCreateFournisseur($data['tiers']);
        $firstLevel  = ValidationLevel::first_level();

        if (! $firstLevel) {
            throw new RuntimeException('Aucun niveau de validation configuré.');
        }

        $amount = $data['montant_ht'] ?? collect($data['lignes'])
            ->sum(fn ($l) => (float) $l['quantite'] * (float) $l['prix_unitaire']);

        $attributes = [
            'user_id'             => $this->systemUserId(),
            'fournisseur_id'      => $fournisseur->id,
            'title'               => "Commande Sage {$data['numero']}",
            'description'         => "Commande importée automatiquement depuis Sage100 (fournisseur : {$data['tiers']}).",
            'amount'              => $amount,
            'status'              => 'pending',
            'current_level_order' => $firstLevel->order,
            'submitted_at'        => now(),
            'order_date'          => $data['date'],
            'sage_reference'      => $data['numero'],
            'source'              => 'sage',
        ];

        $isNewOrReopened = ! $existing || $existing->status !== 'pending';

        $order = $existing
            ? tap($existing)->update($attributes)
            : PurchaseOrder::create($attributes);

        $order->lines()->delete();

        foreach ($data['lignes'] as $ligne) {
            $article = $this->findOrCreateArticle($ligne['article'], $ligne['designation'] ?? null, (float) $ligne['prix_unitaire']);

            PurchaseOrderLine::create([
                'purchase_order_id' => $order->id,
                'article_id'        => $article->id,
                'fournisseur_id'    => $fournisseur->id,
                'quantity'          => $ligne['quantite'],
                'unit_price'        => $ligne['prix_unitaire'],
            ]);
        }

        if ($isNewOrReopened) {
            foreach ($firstLevel->validators as $validator) {
                $validator->notify(new OrderSubmittedNotification($order, $firstLevel));
            }
        }

        return $order;
    }

    private function findOrCreateFournisseur(string $sageCode): Fournisseur
    {
        return Fournisseur::firstOrCreate(
            ['sage_code' => $sageCode],
            [
                'name'        => "Fournisseur Sage {$sageCode} (à vérifier)",
                'code'        => "SAGE-{$sageCode}",
                'is_active'   => true,
                'is_approved' => false,
            ]
        );
    }

    private function findOrCreateArticle(string $sageReference, ?string $designation, float $unitPrice): Article
    {
        return Article::firstOrCreate(
            ['sage_reference' => $sageReference],
            [
                'name'       => $designation ?: "Article Sage {$sageReference} (à vérifier)",
                'reference'  => "SAGE-{$sageReference}",
                'unit_price' => $unitPrice,
                'is_active'  => false,
            ]
        );
    }

    private function systemUserId(): int
    {
        return User::where('email', 'sage100@system.local')->value('id');
    }

    private function log(?string $reference, ?int $orderId, string $status, ?string $error, array $payload): void
    {
        SageWebhookLog::create([
            'sage_reference'     => $reference,
            'purchase_order_id'  => $orderId,
            'status'             => $status,
            'error_message'      => $error,
            'payload'            => $payload,
        ]);
    }
}
