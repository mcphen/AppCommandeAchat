<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Fournisseur;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\PurchaseOrderReception;
use App\Models\PurchaseOrderReceptionLine;
use App\Models\Role;
use App\Models\SageWebhookLog;
use App\Models\User;
use App\Models\ValidationLevel;
use App\Notifications\OrderSubmittedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class SageWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'numero'                  => ['required', 'string', 'max:255'],
            'date'                    => ['required', 'date'],
            'tiers'                   => ['required', 'string', 'max:255'],
            'tiers_nom'               => ['nullable', 'string', 'max:255'],
            'tiers_adresse'           => ['nullable', 'string', 'max:255'],
            'tiers_ville'             => ['nullable', 'string', 'max:255'],
            'tiers_telephone'         => ['nullable', 'string', 'max:255'],
            'tiers_email'             => ['nullable', 'string', 'max:255'],
            'tiers_siret'             => ['nullable', 'string', 'max:255'],
            'montant_ht'              => ['nullable', 'numeric', 'min:0'],
            'montant_ttc'             => ['nullable', 'numeric', 'min:0'],
            'cloture'                 => ['nullable', 'boolean'],
            'lignes'                  => ['required', 'array', 'min:1'],
            'lignes.*.article'        => ['required', 'string', 'max:255'],
            'lignes.*.designation'    => ['nullable', 'string', 'max:255'],
            'lignes.*.quantite'       => ['required', 'numeric', 'min:0.01'],
            'lignes.*.prix_unitaire'  => ['required', 'numeric', 'min:0'],
            'lignes.*.quantite_livree'     => ['nullable', 'numeric', 'min:0'],
            'lignes.*.date_livraison'      => ['nullable', 'date'],
            'lignes.*.piece_bl'            => ['nullable', 'string', 'max:255'],
            'demandeur'                    => ['nullable', 'array'],
            'demandeur.code'               => ['required_with:demandeur', 'string', 'max:255'],
            'demandeur.nom'                => ['nullable', 'string', 'max:255'],
            'demandeur.email'              => ['nullable', 'string', 'max:255'],
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

        $fournisseur = $this->findOrCreateFournisseur($data['tiers'], [
            'name'    => $data['tiers_nom'] ?? null,
            'address' => $data['tiers_adresse'] ?? null,
            'city'    => $data['tiers_ville'] ?? null,
            'phone'   => $data['tiers_telephone'] ?? null,
            'email'   => $data['tiers_email'] ?? null,
            'siret'   => $data['tiers_siret'] ?? null,
        ]);
        $firstLevel  = ValidationLevel::first_level();

        if (! $firstLevel) {
            throw new RuntimeException('Aucun niveau de validation configuré.');
        }

        $amount = $data['montant_ht'] ?? collect($data['lignes'])
            ->sum(fn ($l) => (float) $l['quantite'] * (float) $l['prix_unitaire']);

        $userId = isset($data['demandeur'])
            ? $this->findOrCreateDemandeur($data['demandeur'])->id
            : $this->systemUserId();

        [$deliveryStatus, $fullyReceivedAt] = $this->resolveDeliveryStatus($data['lignes']);

        // Une commande deja cloturee cote Sage (DO_Cloture), OU deja receptionnee
        // entierement, est forcement passee par tout le circuit d'approbation dans la
        // vraie vie (on ne receptionne pas une commande jamais validee) - meme si
        // DO_Cloture n'est pas encore coche cote Sage. Elle n'a pas besoin de repasser
        // par le circuit de validation interne, elle est importee directement comme
        // approuvee.
        $isCloture = (bool) ($data['cloture'] ?? false) || $deliveryStatus === 'received';

        $attributes = [
            'user_id'             => $userId,
            'fournisseur_id'      => $fournisseur->id,
            'title'               => $data['numero'],
            'description'         => "Commande importée automatiquement depuis Sage100 (fournisseur : {$fournisseur->name}).",
            'amount'              => $amount,
            'status'              => $isCloture ? 'approved' : 'pending',
            'current_level_order' => $isCloture ? null : $firstLevel->order,
            'submitted_at'        => now(),
            'order_date'          => $data['date'],
            // Une commande Sage existe deja comme commande reelle passee au fournisseur
            // des sa creation dans Sage (pas de brouillon interne) : ordered_at doit
            // etre rempli, sinon le tableau de bord Receptions ("Commandee le") et son
            // tri par defaut restent casses pour toutes les commandes importees.
            'ordered_at'          => $data['date'],
            'sage_reference'      => $data['numero'],
            'source'              => 'sage',
            'delivery_status'     => $deliveryStatus,
            'fully_received_at'   => $fullyReceivedAt,
        ];

        $isNewOrReopened = ! $isCloture && (! $existing || $existing->status !== 'pending');

        $order = $existing
            ? tap($existing)->update($attributes)
            : PurchaseOrder::create($attributes);

        $order->lines()->delete();
        // Les receptions liees aux anciennes lignes ont deja disparu via cascadeOnDelete
        // sur purchase_order_line_id. On nettoie aussi l'entete de reception qu'on avait
        // eventuellement cree lors d'un sync precedent, pour ne pas en accumuler un
        // nouveau vide/orphelin a chaque resynchronisation.
        $order->receptions()->where('notes', 'like', self::SAGE_RECEPTION_MARKER . '%')->delete();

        $createdLines = [];

        foreach ($data['lignes'] as $ligne) {
            $article = $this->findOrCreateArticle($ligne['article'], $ligne['designation'] ?? null, (float) $ligne['prix_unitaire']);

            // Beaucoup de codes Sage (AR_Ref) sont des codes generiques de depense
            // reutilises par les achats (ex: "CAISCHANT") avec une designation
            // differente a chaque fois. On garde donc TOUJOURS la designation reelle
            // de cette ligne dans `note`, independamment du nom (generique) de
            // l'article lie, pour ne jamais perdre le vrai detail de la commande.
            $line = PurchaseOrderLine::create([
                'purchase_order_id' => $order->id,
                'article_id'        => $article->id,
                'fournisseur_id'    => $fournisseur->id,
                'quantity'          => $ligne['quantite'],
                'unit_price'        => $ligne['prix_unitaire'],
                'note'              => $ligne['designation'] ?? null,
            ]);

            $createdLines[] = ['line' => $line, 'data' => $ligne];
        }

        $this->syncReception($order, $createdLines, $deliveryStatus);

        if ($isNewOrReopened) {
            foreach ($firstLevel->validators as $validator) {
                $validator->notify(new OrderSubmittedNotification($order, $firstLevel));
            }
        }

        return $order;
    }

    private const SAGE_RECEPTION_MARKER = '[Import Sage100]';

    /**
     * Persiste le detail de reception (quantite livree par ligne), pas seulement le
     * badge de synthese sur la commande, pour que la barre de progression par ligne
     * dans l'app reste coherente avec ce qui a reellement ete livre dans Sage.
     *
     * @param  array<int, array{line: PurchaseOrderLine, data: array}>  $createdLines
     */
    private function syncReception(PurchaseOrder $order, array $createdLines, ?string $deliveryStatus): void
    {
        if (! $deliveryStatus) {
            return;
        }

        $livrees = array_filter($createdLines, fn ($item) => (float) ($item['data']['quantite_livree'] ?? 0) > 0);

        if (! $livrees) {
            return;
        }

        $dates = collect($livrees)->pluck('data.date_livraison')->filter()->map(fn ($d) => \Carbon\Carbon::parse($d));
        $receivedAt = $dates->max() ?? now();

        $pieces = collect($livrees)->pluck('data.piece_bl')->filter()->unique()->implode(', ');

        $reception = PurchaseOrderReception::create([
            'purchase_order_id' => $order->id,
            'received_by'       => $this->systemUserId(),
            'received_at'       => $receivedAt,
            'type'              => $deliveryStatus === 'received' ? 'complete' : 'partial',
            'notes'             => self::SAGE_RECEPTION_MARKER . ($pieces ? " BL: {$pieces}" : ''),
        ]);

        foreach ($livrees as $item) {
            PurchaseOrderReceptionLine::create([
                'reception_id'           => $reception->id,
                'purchase_order_line_id' => $item['line']->id,
                'quantity_received'      => (float) $item['data']['quantite_livree'],
            ]);
        }
    }

    /**
     * Deduit le statut de livraison a partir des quantites livrees Sage (DL_QteBL)
     * comparees aux quantites commandees (DL_Qte), pour refleter ce qui a deja ete
     * livre dans la vraie vie plutot que de tout afficher comme "non livre".
     *
     * @return array{0: ?string, 1: ?\Illuminate\Support\Carbon}
     */
    private function resolveDeliveryStatus(array $lignes): array
    {
        $totalQuantite = collect($lignes)->sum(fn ($l) => (float) $l['quantite']);
        $totalLivree   = collect($lignes)->sum(fn ($l) => (float) ($l['quantite_livree'] ?? 0));

        if ($totalLivree <= 0) {
            return [null, null];
        }

        // La vraie date de livraison Sage (DL_DateBL), pas la date d'import : sinon
        // "Receptionnee le" affiche toujours la date du jour du sync au lieu de la
        // vraie date de reception.
        $dateLivraison = collect($lignes)
            ->pluck('date_livraison')
            ->filter()
            ->map(fn ($d) => \Carbon\Carbon::parse($d))
            ->max() ?? now();

        if ($totalLivree >= $totalQuantite) {
            return ['received', $dateLivraison];
        }

        return ['partially_received', null];
    }

    private function findOrCreateFournisseur(string $sageCode, array $details): Fournisseur
    {
        $fournisseur = Fournisseur::firstOrCreate(
            ['sage_code' => $sageCode],
            [
                'name'        => $details['name'] ?: $sageCode,
                'code'        => "SAGE-{$sageCode}",
                'email'       => $details['email'],
                'phone'       => $details['phone'],
                'address'     => $details['address'],
                'city'        => $details['city'],
                'siret'       => $details['siret'],
                'is_active'   => true,
                'is_approved' => false,
            ]
        );

        // Tient a jour les coordonnees des fournisseurs pas encore valides par un admin
        // (une fois approuve, on ne touche plus a ses donnees : un admin a pu les corriger).
        if (! $fournisseur->wasRecentlyCreated && ! $fournisseur->is_approved) {
            $updates = [];
            foreach ($details as $field => $value) {
                if ($value && $fournisseur->{$field} !== $value) {
                    $updates[$field] = $value;
                }
            }
            if ($updates) {
                $fournisseur->update($updates);
            }
        }

        return $fournisseur;
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

    private function findOrCreateDemandeur(array $demandeur): User
    {
        $code = trim($demandeur['code']);

        return User::firstOrCreate(
            ['sage_collaborateur_code' => $code],
            [
                'name'     => ($demandeur['nom'] ?? null) ?: "Collaborateur Sage {$code}",
                'email'    => ($demandeur['email'] ?? null) ?: "collaborateur-{$code}@sage.local",
                'password' => Hash::make(Str::random(40)),
                'role_id'  => Role::where('slug', 'demandeur')->value('id'),
            ]
        );
    }

    /**
     * Compte systeme auquel rattacher les commandes sans demandeur Sage identifie.
     * Auto-reparateur : le recree s'il a ete supprime par erreur, plutot que de
     * planter toutes les commandes sans demandeur (deja arrive en prod).
     */
    private function systemUserId(): int
    {
        return User::firstOrCreate(
            ['email' => 'sage100@system.local'],
            [
                'name'     => 'Sage100 (import automatique)',
                'password' => Hash::make(Str::random(40)),
                'role_id'  => null,
            ]
        )->id;
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
