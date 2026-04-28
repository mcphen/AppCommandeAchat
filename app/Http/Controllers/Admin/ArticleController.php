<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ArticleController extends Controller
{
    public function index(): Response
    {
        $articles = Article::with('category.parent')
            ->withCount('orderLines')
            ->orderBy('name')
            ->get()
            ->map(fn ($a) => [
                'id'               => $a->id,
                'name'             => $a->name,
                'reference'        => $a->reference,
                'description'      => $a->description,
                'unit'             => $a->unit,
                'unit_price'       => $a->unit_price,
                'is_active'        => $a->is_active,
                'nature'           => $a->nature,
                'order_lines_count' => $a->order_lines_count,
                'category'         => $a->category ? [
                    'id'       => $a->category->id,
                    'name'     => $a->category->name,
                    'full_name' => $a->category->full_name,
                ] : null,
            ]);

        return Inertia::render('Admin/Articles/Index', [
            'articles'   => $articles,
            'categories' => $this->getCategoryList(),
            'units'      => $this->getUnits(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Articles/Form', [
            'categories' => $this->getCategoryList(),
            'units'      => $this->getUnits(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name'        => 'required|string|max:255',
            'reference'   => 'nullable|string|max:100|unique:articles,reference',
            'description' => 'nullable|string|max:1000',
            'unit'        => 'required|string|max:50',
            'unit_price'  => 'nullable|numeric|min:0',
            'is_active'   => 'boolean',
            'nature'      => 'required|in:interne,achat',
        ]);

        Article::create($data);

        return redirect()->route('admin.articles.index')
            ->with('success', 'Article créé avec succès.');
    }

    public function edit(Article $article): Response
    {
        return Inertia::render('Admin/Articles/Form', [
            'article'    => $article,
            'categories' => $this->getCategoryList(),
            'units'      => $this->getUnits(),
        ]);
    }

    public function update(Request $request, Article $article): RedirectResponse
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name'        => 'required|string|max:255',
            'reference'   => 'nullable|string|max:100|unique:articles,reference,' . $article->id,
            'description' => 'nullable|string|max:1000',
            'unit'        => 'required|string|max:50',
            'unit_price'  => 'nullable|numeric|min:0',
            'is_active'   => 'boolean',
            'nature'      => 'required|in:interne,achat',
        ]);

        $article->update($data);

        return redirect()->route('admin.articles.index')
            ->with('success', 'Article mis à jour.');
    }

    public function destroy(Article $article): RedirectResponse
    {
        abort_if($article->orderLines()->exists(), 422, 'Cet article est utilisé dans des lignes de commande.');

        $article->delete();

        return redirect()->route('admin.articles.index')
            ->with('success', 'Article supprimé.');
    }

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Articles');

        $headers = ['nom', 'reference', 'categorie', 'description', 'unite', 'prix_unitaire', 'actif', 'nature'];
        $sheet->fromArray($headers, null, 'A1');

        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '7c3aed']],
            'alignment' => ['horizontal' => 'center'],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        $examples = [
            ['Ordinateur portable Dell', 'INF-001', 'Informatique', 'Core i5, 8Go RAM, 256Go SSD', 'pièce', '450000', 'oui', 'achat'],
            ['Ramette papier A4', 'PAP-001', 'Fournitures de bureau', '500 feuilles 80g/m²', 'carton', '12000', 'oui', 'achat'],
            ['Maintenance serveur', 'SRV-MAINT', 'Informatique', '', 'heure', '25000', 'oui', 'achat'],
            ['Prestation interne nettoyage', 'INT-001', '', '', 'forfait', '', 'oui', 'interne'],
        ];
        $sheet->fromArray($examples, null, 'A2');

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Ligne de légende
        $sheet->setCellValue('A7', '// nom: obligatoire');
        $sheet->setCellValue('B7', '// reference: optionnel, unique (si existe → mise à jour)');
        $sheet->setCellValue('C7', '// categorie: nom exact de la catégorie');
        $sheet->setCellValue('E7', '// unite: pièce, kg, litre, mètre, boîte, carton, heure, forfait, lot');
        $sheet->setCellValue('G7', '// actif: oui/non ou 1/0');
        $sheet->setCellValue('H7', '// nature: achat (défaut) ou interne');
        $sheet->getStyle('A7:H7')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF999999'));
        $sheet->getStyle('A7:H7')->getFont()->setItalic(true);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'modele_articles.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importParse(Request $request): JsonResponse
    {
        $request->validate(['file' => 'required|file|max:10240']);

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());

        if (! in_array($ext, ['csv', 'xlsx', 'xls'])) {
            return response()->json(['message' => 'Format non supporté. Utilisez CSV ou Excel (xlsx, xls).'], 422);
        }

        $tempKey = Str::uuid()->toString();
        $relPath = 'temp/articles/' . $tempKey . '.' . $ext;
        Storage::disk('local')->put($relPath, file_get_contents($file->getRealPath()));
        $absPath = Storage::disk('local')->path($relPath);

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

        $headers   = array_values(array_map(fn ($h) => trim((string) $h), $rows[0]));
        $dataRows  = array_slice($rows, 1);
        $totalRows = count($dataRows);

        $existingRefs  = Article::whereNotNull('reference')->pluck('reference')->flip();
        $validUnits    = $this->getUnits();
        $validNatures  = ['interne', 'achat'];
        $preview       = [];

        foreach (array_slice($dataRows, 0, 10) as $i => $row) {
            $firstCell = trim((string) ($row[0] ?? ''));
            if (str_starts_with($firstCell, '//')) continue;

            $nomIdx  = array_search('nom', $headers);
            $refIdx  = array_search('reference', $headers);
            $unitIdx = array_search('unite', $headers);
            $natIdx  = array_search('nature', $headers);

            $nom    = $nomIdx !== false ? trim((string) ($row[$nomIdx] ?? '')) : '';
            $ref    = $refIdx !== false ? trim((string) ($row[$refIdx] ?? '')) : '';
            $unite  = $unitIdx !== false ? trim((string) ($row[$unitIdx] ?? '')) : '';
            $nature = $natIdx !== false ? strtolower(trim((string) ($row[$natIdx] ?? ''))) : '';

            $errors = [];
            if ($nom === '') $errors[] = 'Nom obligatoire';
            if ($unite !== '' && ! in_array($unite, $validUnits)) $errors[] = "Unité \"$unite\" invalide";
            if ($nature !== '' && ! in_array($nature, $validNatures)) $errors[] = "Nature \"$nature\" invalide (achat/interne)";

            $preview[] = [
                'row'    => $i + 2,
                'data'   => $row,
                'exists' => $ref !== '' && $existingRefs->has($ref),
                'errors' => $errors,
            ];
        }

        return response()->json([
            'temp_key'   => $tempKey,
            'extension'  => $ext,
            'headers'    => $headers,
            'preview'    => $preview,
            'total_rows' => $totalRows,
        ]);
    }

    public function importConfirm(Request $request): JsonResponse
    {
        $data = $request->validate([
            'temp_key'  => ['required', 'string', 'regex:/^[a-f0-9\-]{36}$/'],
            'extension' => ['required', 'string', 'in:csv,xlsx,xls'],
        ]);

        $relPath = 'temp/articles/' . $data['temp_key'] . '.' . $data['extension'];

        if (! Storage::disk('local')->exists($relPath)) {
            return response()->json(['message' => 'Fichier temporaire introuvable. Veuillez recommencer.'], 422);
        }

        $absPath = Storage::disk('local')->path($relPath);

        try {
            $spreadsheet = IOFactory::load($absPath);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Impossible de lire le fichier : ' . $e->getMessage()], 422);
        }

        $rows    = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $headers = array_values(array_map(fn ($h) => trim((string) $h), $rows[0]));
        $idx     = fn (string $col): ?int => ($pos = array_search($col, $headers)) !== false ? (int) $pos : null;

        $nomIdx   = $idx('nom');
        $refIdx   = $idx('reference');
        $catIdx   = $idx('categorie');
        $descIdx  = $idx('description');
        $unitIdx  = $idx('unite');
        $prixIdx  = $idx('prix_unitaire');
        $actifIdx = $idx('actif');
        $natIdx   = $idx('nature');

        if ($nomIdx === null) {
            return response()->json(['message' => 'Colonne obligatoire "nom" introuvable.'], 422);
        }

        $validUnits   = $this->getUnits();
        $categories   = Category::all()->keyBy(fn ($c) => strtolower(trim($c->full_name)));
        $parseBool    = fn ($val): bool => in_array(strtolower(trim((string) ($val ?? ''))), ['oui', '1', 'true', 'yes'], true);

        $created = 0;
        $updated = 0;
        $errors  = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $rowNum    = $i + 2;
            $firstCell = trim((string) ($row[0] ?? ''));
            if (str_starts_with($firstCell, '//')) continue;

            $nom = trim((string) ($row[$nomIdx] ?? ''));
            if ($nom === '') {
                $errors[] = ['row' => $rowNum, 'message' => 'Nom vide — ligne ignorée'];
                continue;
            }

            $ref    = $refIdx !== null ? (trim((string) ($row[$refIdx] ?? '')) ?: null) : null;
            $unite  = $unitIdx !== null ? trim((string) ($row[$unitIdx] ?? '')) : '';
            $nature = $natIdx !== null ? strtolower(trim((string) ($row[$natIdx] ?? ''))) : '';

            if ($unite === '' || ! in_array($unite, $validUnits)) {
                $unite = 'pièce';
            }
            if (! in_array($nature, ['interne', 'achat'])) {
                $nature = 'achat';
            }

            $catName   = $catIdx !== null ? strtolower(trim((string) ($row[$catIdx] ?? ''))) : '';
            $catId     = $catName !== '' ? ($categories->get($catName)?->id ?? null) : null;

            $prix = $prixIdx !== null ? ($row[$prixIdx] ?? null) : null;

            $articleData = [
                'name'        => $nom,
                'category_id' => $catId,
                'description' => $descIdx !== null ? (trim((string) ($row[$descIdx] ?? '')) ?: null) : null,
                'unit'        => $unite,
                'unit_price'  => ($prix !== null && is_numeric($prix) && (float) $prix >= 0) ? (float) $prix : null,
                'is_active'   => $actifIdx !== null ? $parseBool($row[$actifIdx]) : true,
                'nature'      => $nature,
            ];

            if ($ref !== null) {
                $exists = Article::where('reference', $ref)->exists();
                Article::updateOrCreate(['reference' => $ref], $articleData);
                $exists ? $updated++ : $created++;
            } else {
                Article::create($articleData);
                $created++;
            }
        }

        Storage::disk('local')->delete($relPath);

        return response()->json(compact('created', 'updated', 'errors'));
    }

    private function getCategoryList(): \Illuminate\Support\Collection
    {
        return Category::with('parent')
            ->where('is_active', true)
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get()
            ->map(fn ($c) => [
                'id'       => $c->id,
                'name'     => $c->name,
                'full_name' => $c->full_name,
                'parent_id' => $c->parent_id,
            ]);
    }

    private function getUnits(): array
    {
        return ['pièce', 'kg', 'litre', 'mètre', 'boîte', 'carton', 'heure', 'forfait', 'lot'];
    }
}
