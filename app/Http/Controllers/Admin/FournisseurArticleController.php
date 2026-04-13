<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Fournisseur;
use App\Models\FournisseurArticle;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FournisseurArticleController extends Controller
{
    /**
     * Retourne la liste des prix fournisseurs pour un article donné.
     * Utilisé par le comparateur de prix sur la page de création de commande.
     */
    public function compareByArticle(Article $article): JsonResponse
    {
        $prix = FournisseurArticle::with('fournisseur')
            ->where('article_id', $article->id)
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('valide_jusqu_au')->orWhere('valide_jusqu_au', '>=', now()))
            ->orderBy('unit_price')
            ->get()
            ->map(fn (FournisseurArticle $fa) => [
                'fournisseur_article_id'  => $fa->id,
                'fournisseur_id'          => $fa->fournisseur_id,
                'fournisseur_name'        => $fa->fournisseur->name,
                'fournisseur_is_approved' => $fa->fournisseur->is_approved,
                'unit_price'              => (float) $fa->unit_price,
                'reference_fournisseur'   => $fa->reference_fournisseur,
                'delai_livraison_jours'   => $fa->delai_livraison_jours,
                'valide_jusqu_au'         => $fa->valide_jusqu_au?->toDateString(),
            ]);

        return response()->json($prix);
    }

    /**
     * Liste les entrées du catalogue pour un fournisseur donné.
     */
    public function index(Fournisseur $fournisseur): JsonResponse
    {
        $catalogue = FournisseurArticle::with('article.category')
            ->where('fournisseur_id', $fournisseur->id)
            ->orderBy('is_active', 'desc')
            ->orderBy('unit_price')
            ->get()
            ->map(fn (FournisseurArticle $fa) => [
                'id'                    => $fa->id,
                'article_id'            => $fa->article_id,
                'article_name'          => $fa->article->name,
                'article_reference'     => $fa->article->reference,
                'article_unit'          => $fa->article->unit,
                'category_name'         => $fa->article->category?->full_name,
                'unit_price'            => (float) $fa->unit_price,
                'reference_fournisseur' => $fa->reference_fournisseur,
                'delai_livraison_jours' => $fa->delai_livraison_jours,
                'valide_jusqu_au'       => $fa->valide_jusqu_au?->toDateString(),
                'notes'                 => $fa->notes,
                'is_active'             => $fa->is_active,
            ]);

        return response()->json($catalogue);
    }

    /**
     * Ajoute ou met à jour un tarif pour un fournisseur/article.
     */
    public function store(Request $request, Fournisseur $fournisseur): JsonResponse
    {
        $data = $request->validate([
            'article_id'            => 'required|exists:articles,id',
            'unit_price'            => 'required|numeric|min:0',
            'reference_fournisseur' => 'nullable|string|max:100',
            'delai_livraison_jours' => 'nullable|integer|min:0|max:3650',
            'valide_jusqu_au'       => 'nullable|date|after_or_equal:today',
            'notes'                 => 'nullable|string|max:500',
            'is_active'             => 'boolean',
        ]);

        $fa = FournisseurArticle::updateOrCreate(
            ['fournisseur_id' => $fournisseur->id, 'article_id' => $data['article_id']],
            array_merge($data, ['fournisseur_id' => $fournisseur->id])
        );

        return response()->json([
            'id'                    => $fa->id,
            'article_id'            => $fa->article_id,
            'article_name'          => $fa->article->name,
            'article_reference'     => $fa->article->reference,
            'article_unit'          => $fa->article->unit,
            'unit_price'            => (float) $fa->unit_price,
            'reference_fournisseur' => $fa->reference_fournisseur,
            'delai_livraison_jours' => $fa->delai_livraison_jours,
            'valide_jusqu_au'       => $fa->valide_jusqu_au?->toDateString(),
            'notes'                 => $fa->notes,
            'is_active'             => $fa->is_active,
        ], 201);
    }

    /**
     * Met à jour un tarif existant.
     */
    public function update(Request $request, Fournisseur $fournisseur, FournisseurArticle $tarif): JsonResponse
    {
        abort_if($tarif->fournisseur_id !== $fournisseur->id, 403);

        $data = $request->validate([
            'unit_price'            => 'required|numeric|min:0',
            'reference_fournisseur' => 'nullable|string|max:100',
            'delai_livraison_jours' => 'nullable|integer|min:0|max:3650',
            'valide_jusqu_au'       => 'nullable|date',
            'notes'                 => 'nullable|string|max:500',
            'is_active'             => 'boolean',
        ]);

        $tarif->update($data);

        return response()->json([
            'id'                    => $tarif->id,
            'article_id'            => $tarif->article_id,
            'article_name'          => $tarif->article->name,
            'unit_price'            => (float) $tarif->unit_price,
            'reference_fournisseur' => $tarif->reference_fournisseur,
            'delai_livraison_jours' => $tarif->delai_livraison_jours,
            'valide_jusqu_au'       => $tarif->valide_jusqu_au?->toDateString(),
            'notes'                 => $tarif->notes,
            'is_active'             => $tarif->is_active,
        ]);
    }

    /**
     * Supprime un tarif.
     */
    public function destroy(Fournisseur $fournisseur, FournisseurArticle $tarif): JsonResponse
    {
        abort_if($tarif->fournisseur_id !== $fournisseur->id, 403);

        $tarif->delete();

        return response()->json(null, 204);
    }

    /**
     * Télécharge le modèle CSV vierge.
     */
    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF"); // BOM UTF-8 pour Excel
            fputcsv($handle, ['reference_article', 'prix', 'reference_fournisseur', 'delai_livraison_jours', 'valide_jusqu_au', 'notes'], ';');
            fputcsv($handle, ['REF001', '1500', 'FNS-REF-001', '7', '2026-12-31', 'Exemple de note'], ';');
            fputcsv($handle, ['REF002', '2500', 'FNS-REF-002', '14', '', ''], ';');
            fclose($handle);
        }, 'modele_catalogue_fournisseur.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Analyse un fichier CSV/Excel uploadé et retourne les en-têtes + aperçu.
     */
    public function importParse(Request $request, Fournisseur $fournisseur): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:10240']);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, ['csv', 'xlsx', 'xls', 'txt'])) {
            return response()->json(['message' => 'Format non supporté. Utilisez CSV ou Excel (xlsx, xls).'], 422);
        }

        $tempKey  = Str::uuid()->toString();
        $relPath  = 'temp/catalogue/' . $tempKey . '.' . $ext;
        Storage::disk('local')->put($relPath, file_get_contents($file->getRealPath()));
        $absPath  = Storage::disk('local')->path($relPath);

        try {
            $spreadsheet = IOFactory::load($absPath);
        } catch (\Exception $e) {
            Storage::disk('local')->delete($relPath);
            return response()->json(['message' => 'Impossible de lire le fichier : ' . $e->getMessage()], 422);
        }

        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (empty($rows)) {
            Storage::disk('local')->delete($relPath);
            return response()->json(['message' => 'Fichier vide.'], 422);
        }

        $headers  = array_values(array_map(fn ($h) => trim((string) $h), $rows[0]));
        $preview  = array_slice($rows, 1, 5);
        $totalRows = count($rows) - 1;

        return response()->json([
            'temp_key'   => $tempKey,
            'extension'  => $ext,
            'headers'    => $headers,
            'preview'    => $preview,
            'total_rows' => $totalRows,
        ]);
    }

    /**
     * Confirme l'import avec le mapping de colonnes et insère les tarifs en base.
     */
    public function importConfirm(Request $request, Fournisseur $fournisseur): JsonResponse
    {
        $data = $request->validate([
            'temp_key'                      => ['required', 'string', 'regex:/^[a-f0-9\-]{36}$/'],
            'extension'                     => ['required', 'string', 'in:csv,xlsx,xls,txt'],
            'mapping.reference_article'     => ['required', 'string'],
            'mapping.prix'                  => ['required', 'string'],
            'mapping.reference_fournisseur' => ['nullable', 'string'],
            'mapping.delai_livraison_jours' => ['nullable', 'string'],
            'mapping.valide_jusqu_au'       => ['nullable', 'string'],
            'mapping.notes'                 => ['nullable', 'string'],
        ]);

        $relPath = 'temp/catalogue/' . $data['temp_key'] . '.' . $data['extension'];

        if (! Storage::disk('local')->exists($relPath)) {
            return response()->json(['message' => 'Fichier temporaire introuvable. Veuillez recommencer l\'import.'], 422);
        }

        $absPath = Storage::disk('local')->path($relPath);

        try {
            $spreadsheet = IOFactory::load($absPath);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Impossible de lire le fichier : ' . $e->getMessage()], 422);
        }

        $rows    = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $headers = array_values(array_map(fn ($h) => trim((string) $h), $rows[0]));
        $mapping = $data['mapping'];

        $idx = fn (string $col): ?int => ($pos = array_search($col, $headers)) !== false ? (int) $pos : null;

        $refArticleIdx = $idx($mapping['reference_article']);
        $prixIdx       = $idx($mapping['prix']);
        $refFnsIdx     = ! empty($mapping['reference_fournisseur']) ? $idx($mapping['reference_fournisseur']) : null;
        $delaiIdx      = ! empty($mapping['delai_livraison_jours']) ? $idx($mapping['delai_livraison_jours']) : null;
        $validiteIdx   = ! empty($mapping['valide_jusqu_au']) ? $idx($mapping['valide_jusqu_au']) : null;
        $notesIdx      = ! empty($mapping['notes']) ? $idx($mapping['notes']) : null;

        if ($refArticleIdx === null || $prixIdx === null) {
            return response()->json(['message' => 'Colonnes obligatoires introuvables dans le fichier.'], 422);
        }

        $articles  = Article::all()->keyBy('reference');
        $imported  = 0;
        $updated   = 0;
        $errors    = [];
        $dataRows  = array_slice($rows, 1);

        foreach ($dataRows as $i => $row) {
            $rowNum     = $i + 2;
            $refArticle = trim((string) ($row[$refArticleIdx] ?? ''));
            $prix       = $row[$prixIdx] ?? null;

            if ($refArticle === '') {
                $errors[] = ['row' => $rowNum, 'message' => 'Référence article vide — ligne ignorée'];
                continue;
            }

            if (! is_numeric($prix) || (float) $prix < 0) {
                $errors[] = ['row' => $rowNum, 'message' => "Prix invalide (\"$prix\") pour ref. \"$refArticle\""];
                continue;
            }

            $article = $articles->get($refArticle);
            if (! $article) {
                $errors[] = ['row' => $rowNum, 'message' => "Référence article inconnue : \"$refArticle\""];
                continue;
            }

            $exists = FournisseurArticle::where('fournisseur_id', $fournisseur->id)
                ->where('article_id', $article->id)
                ->exists();

            $faData = [
                'fournisseur_id'        => $fournisseur->id,
                'unit_price'            => (float) $prix,
                'reference_fournisseur' => $refFnsIdx !== null ? (trim((string) ($row[$refFnsIdx] ?? '')) ?: null) : null,
                'delai_livraison_jours' => ($delaiIdx !== null && is_numeric($row[$delaiIdx] ?? '')) ? (int) $row[$delaiIdx] : null,
                'is_active'             => true,
            ];

            if ($validiteIdx !== null && ! empty(trim((string) ($row[$validiteIdx] ?? '')))) {
                try {
                    $faData['valide_jusqu_au'] = Carbon::parse((string) $row[$validiteIdx])->toDateString();
                } catch (\Exception) { /* date invalide, on ignore */ }
            }

            if ($notesIdx !== null && ! empty(trim((string) ($row[$notesIdx] ?? '')))) {
                $faData['notes'] = trim((string) $row[$notesIdx]);
            }

            FournisseurArticle::updateOrCreate(
                ['fournisseur_id' => $fournisseur->id, 'article_id' => $article->id],
                $faData,
            );

            $exists ? $updated++ : $imported++;
        }

        Storage::disk('local')->delete($relPath);

        return response()->json(compact('imported', 'updated', 'errors'));
    }
}
