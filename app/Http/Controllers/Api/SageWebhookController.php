<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Circuit;
use App\Models\Fournisseur;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Project;
use App\Models\Role;
use App\Models\SageWebhookLog;
use App\Models\User;
use App\Models\ValidationLevel;
use App\Notifications\OrderAwaitingSubmissionNotification;
use App\Notifications\OrderMissingDemandeurNotification;
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
            'projet_code'             => ['nullable', 'string', 'max:255'],
            'do_souche'               => ['nullable', 'integer', 'in:0,1'],
            'lignes'                  => ['required', 'array', 'min:1'],
            'lignes.*.article'        => ['required', 'string', 'max:255'],
            'lignes.*.designation'    => ['nullable', 'string', 'max:255'],
            'lignes.*.famille_article'     => ['nullable', 'string', 'max:255'],
            'lignes.*.quantite'       => ['required', 'numeric', 'min:0.01'],
            'lignes.*.prix_unitaire'  => ['required', 'numeric', 'min:0'],
            'lignes.*.taux_tva'            => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lignes.*.remise'              => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lignes.*.unite'                => ['nullable', 'string', 'max:50'],
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
        // DO_Souche cote Sage : 0 = achat matiere, 1 = facture de prestataire. Chaque
        // valeur route vers son propre circuit de validation (cf. Admin > Circuits).
        $circuitCode = (int) ($data['do_souche'] ?? 0) === 1 ? 'prestation' : 'achat';
        $circuitId   = Circuit::where('code', $circuitCode)->value('id');

        if (! $circuitId) {
            throw new RuntimeException("Circuit de validation '{$circuitCode}' introuvable.");
        }

        $firstLevel = ValidationLevel::first_level($circuitId);

        if (! $firstLevel) {
            throw new RuntimeException("Aucun niveau de validation configuré pour le circuit '{$circuitCode}'.");
        }

        $amount = $data['montant_ht'] ?? collect($data['lignes'])
            ->sum(fn ($l) => (float) $l['quantite'] * (float) $l['prix_unitaire']);
        $amountTtc = $data['montant_ttc'] ?? null;

        $userId = isset($data['demandeur'])
            ? $this->findOrCreateDemandeur($data['demandeur'])->id
            : $this->systemUserId();

        $project = isset($data['projet_code']) && trim($data['projet_code']) !== ''
            ? Project::findOrCreateFromCode($data['projet_code'])
            : null;

        // Une commande importee depuis Sage n'est jamais soumise automatiquement au
        // circuit de validation : elle est (re)mise en 'draft' (en attente de completion)
        // tant que le demandeur ne l'a pas soumise lui-meme depuis l'app (avec au moins
        // une piece jointe obligatoire, cf. PurchaseOrderController::submit). Seule
        // exception : si la commande a deja ete soumise (ou a fortiori validee/rejetee),
        // on ne touche pas au statut/niveau en cours pour ne pas effacer la soumission
        // a chaque resynchro Sage (qui peut arriver toutes les 1 a 10 min sur un simple
        // changement de reception, sans rapport avec la validation).
        $hasSubmissionProgress = $existing && $existing->status !== 'draft';
        $targetStatus = $hasSubmissionProgress ? $existing->status : 'draft';

        // Le suivi de livraison (delivery_status, fully_received_at, receptions) est gere
        // exclusivement depuis l'app (reception manuelle par un utilisateur) : le sync Sage
        // ne le deduit plus automatiquement des quantites BL, pour ne jamais afficher une
        // commande "receptionnee" sans qu'une vraie reception ait ete saisie cote app.
        $attributes = [
            'user_id'             => $userId,
            // Le circuit (achat/prestation) n'est reevalue que tant que la commande est
            // en brouillon : une fois entree en validation, on ne le change plus, meme si
            // DO_Souche change cote Sage a la resynchro suivante (cf. current_level_order).
            'circuit_id'          => $targetStatus === 'draft' ? $circuitId : $existing->circuit_id,
            'fournisseur_id'      => $fournisseur->id,
            'title'               => $data['numero'],
            'description'         => "Commande importée automatiquement depuis Sage100 (fournisseur : {$fournisseur->name}).",
            'amount'              => $amount,
            'amount_ttc'          => $amountTtc,
            'status'              => $targetStatus,
            'current_level_order' => $targetStatus === 'draft' ? null : $existing->current_level_order,
            'submitted_at'        => $targetStatus === 'draft' ? null : $existing->submitted_at,
            'order_date'          => $data['date'],
            // Une commande Sage existe deja comme commande reelle passee au fournisseur
            // des sa creation dans Sage (pas de brouillon interne) : ordered_at doit
            // etre rempli, sinon le tableau de bord Receptions ("Commandee le") et son
            // tri par defaut restent casses pour toutes les commandes importees.
            'ordered_at'          => $data['date'],
            'sage_reference'      => $data['numero'],
            'source'              => 'sage',
            'project_code'        => $data['projet_code'] ?? null,
            'project_id'          => $project?->id,
        ];

        $isNewOrder = ! $existing;

        $order = $existing
            ? tap($existing)->update($attributes)
            : PurchaseOrder::create($attributes);

        $order->lines()->delete();

        foreach ($data['lignes'] as $ligne) {
            $article = $this->findOrCreateArticle(
                $ligne['article'],
                $ligne['designation'] ?? null,
                (float) $ligne['prix_unitaire'],
                $ligne['famille_article'] ?? null,
                $ligne['unite'] ?? null,
            );

            // Beaucoup de codes Sage (AR_Ref) sont des codes generiques de depense
            // reutilises par les achats (ex: "CAISCHANT") avec une designation
            // differente a chaque fois. On garde donc TOUJOURS la designation reelle
            // de cette ligne dans `note`, independamment du nom (generique) de
            // l'article lie, pour ne jamais perdre le vrai detail de la commande.
            PurchaseOrderLine::create([
                'purchase_order_id' => $order->id,
                'article_id'        => $article->id,
                'fournisseur_id'    => $fournisseur->id,
                'quantity'          => $ligne['quantite'],
                'unit_price'        => $ligne['prix_unitaire'],
                'note'              => $ligne['designation'] ?? null,
                'vat_rate'          => $ligne['taux_tva'] ?? null,
                'discount_rate'     => $ligne['remise'] ?? null,
                'unit'              => $ligne['unite'] ?? null,
            ]);
        }

        if ($targetStatus === 'draft') {
            // On ne relance pas le mail a chaque resynchro (toutes les 10 min) tant que
            // le demandeur n'a pas soumis : seulement a la toute premiere creation.
            if ($isNewOrder) {
                if (isset($data['demandeur'])) {
                    $order->user->notify(new OrderAwaitingSubmissionNotification($order));
                } else {
                    // Pas de demandeur reel identifie (rattachee au compte systeme) : personne
                    // ne recevra jamais le mail de relance habituel. Sans ca, la commande reste
                    // silencieusement bloquee en brouillon. On alerte les admins directement,
                    // eux seuls pouvant la completer/soumettre a la place du demandeur absent.
                    foreach (User::whereHas('role', fn ($q) => $q->where('slug', 'admin'))->get() as $admin) {
                        $admin->notify(new OrderMissingDemandeurNotification($order));
                    }
                }
            }
        }
        // Pas de notification aux validateurs ici : une commande n'entre en 'pending'
        // que si elle avait deja ete soumise (cf. $hasSubmissionProgress), jamais
        // fraichement via le sync. La notification des validateurs se fait uniquement
        // lors d'une vraie soumission (PurchaseOrderController::submit).

        return $order;
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

    private function findOrCreateArticle(string $sageReference, ?string $designation, float $unitPrice, ?string $familyCode = null, ?string $unit = null): Article
    {
        $article = Article::firstOrCreate(
            ['sage_reference' => $sageReference],
            array_filter([
                'name'        => $designation ?: "Article Sage {$sageReference} (à vérifier)",
                'reference'   => "SAGE-{$sageReference}",
                'unit_price'  => $unitPrice,
                'is_active'   => false,
                'family_code' => $familyCode,
                'unit'        => $unit,
            ], fn ($value) => $value !== null)
        );

        // Comme pour le fournisseur : tant que l'article n'a pas ete valide manuellement
        // (is_active), on peut le completer avec de meilleures donnees Sage arrivees
        // apres coup (ex: famille absente au premier import, presente ensuite).
        if (! $article->wasRecentlyCreated && ! $article->is_active) {
            $updates = [];
            if ($familyCode && $article->family_code !== $familyCode) {
                $updates['family_code'] = $familyCode;
            }
            if ($unit && $article->unit !== $unit) {
                $updates['unit'] = $unit;
            }
            if ($updates) {
                $article->update($updates);
            }
        }

        return $article;
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
