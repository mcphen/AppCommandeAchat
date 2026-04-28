<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Fournisseur;
use App\Models\PurchaseOrder;
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

class FournisseurController extends Controller
{
    public function index(): Response
    {
        $fournisseurs = Fournisseur::withCount('orderLines')
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Fournisseurs/Index', [
            'fournisseurs' => $fournisseurs,
        ]);
    }

    public function show(Fournisseur $fournisseur): Response
    {
        // Commandes liées directement (fournisseur au niveau commande)
        // + commandes liées via les lignes — union dédoublonnée
        $orderIds = PurchaseOrder::where('fournisseur_id', $fournisseur->id)
            ->pluck('id')
            ->merge(
                PurchaseOrder::whereHas(
                    'lines',
                    fn ($q) => $q->where('fournisseur_id', $fournisseur->id)
                )->pluck('id')
            )
            ->unique();

        $orders = PurchaseOrder::with(['user', 'boutique'])
            ->withSum(
                ['lines as lines_amount' => fn ($q) => $q->where('fournisseur_id', $fournisseur->id)],
                \Illuminate\Support\Facades\DB::raw('quantity * unit_price')
            )
            ->whereIn('id', $orderIds)
            ->latest()
            ->get();

        $stats = [
            'total'           => $orders->count(),
            'approved'        => $orders->where('status', 'approved')->count(),
            'pending'         => $orders->where('status', 'pending')->count(),
            'rejected'        => $orders->where('status', 'rejected')->count(),
            'budget_approved' => (int) $orders->where('status', 'approved')->sum('amount'),
            'budget_pending'  => (int) $orders->where('status', 'pending')->sum('amount'),
        ];

        $articles = Article::with('category')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return Inertia::render('Admin/Fournisseurs/Show', [
            'fournisseur' => $fournisseur,
            'orders'      => $orders,
            'stats'       => $stats,
            'articles'    => $articles,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Fournisseurs/Form');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:fournisseurs,code',
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:500',
            'city'        => 'nullable|string|max:100',
            'is_active'   => 'boolean',
            'is_approved' => 'boolean',
        ]);

        Fournisseur::create($data);

        return redirect()->route('admin.fournisseurs.index')
            ->with('success', 'Fournisseur créé avec succès.');
    }

    public function edit(Fournisseur $fournisseur): Response
    {
        return Inertia::render('Admin/Fournisseurs/Form', [
            'fournisseur' => $fournisseur,
        ]);
    }

    public function update(Request $request, Fournisseur $fournisseur): RedirectResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'code'        => 'required|string|max:50|unique:fournisseurs,code,' . $fournisseur->id,
            'email'       => 'nullable|email|max:255',
            'phone'       => 'nullable|string|max:50',
            'address'     => 'nullable|string|max:500',
            'city'        => 'nullable|string|max:100',
            'is_active'   => 'boolean',
            'is_approved' => 'boolean',
        ]);

        $fournisseur->update($data);

        return redirect()->route('admin.fournisseurs.index')
            ->with('success', 'Fournisseur mis à jour.');
    }

    public function destroy(Fournisseur $fournisseur): RedirectResponse
    {
        abort_if($fournisseur->orderLines()->exists(), 422, 'Ce fournisseur est utilisé dans des lignes de commande.');

        $fournisseur->delete();

        return redirect()->route('admin.fournisseurs.index')
            ->with('success', 'Fournisseur supprimé.');
    }

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Fournisseurs');

        $headers = ['nom', 'code', 'email', 'telephone', 'adresse', 'ville', 'actif', 'homologue'];
        $sheet->fromArray($headers, null, 'A1');

        // Style en-têtes
        $headerStyle = [
            'font'      => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => 'solid', 'startColor' => ['rgb' => '1e40af']],
            'alignment' => ['horizontal' => 'center'],
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // Exemples
        $examples = [
            ['Société Dupont & Fils', 'FRN001', 'contact@dupont.fr', '+33 1 23 45 67 89', '12 rue de la Paix', 'Paris', 'oui', 'oui'],
            ['SARL Matériaux Pro', 'FRN002', 'info@mat-pro.com', '+33 4 56 78 90 12', 'ZI Les Pins', 'Lyon', 'oui', 'non'],
            ['Global Supplies SARL', 'FRN003', '', '', '', 'Marseille', 'oui', 'non'],
        ];
        $sheet->fromArray($examples, null, 'A2');

        // Largeurs colonnes
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Commentaires dans la ligne 6
        $sheet->setCellValue('A6', '// nom: obligatoire');
        $sheet->setCellValue('B6', '// code: obligatoire, unique');
        $sheet->setCellValue('G6', '// actif: oui/non ou 1/0');
        $sheet->setCellValue('H6', '// homologue: oui/non ou 1/0');
        $sheet->getStyle('A6:H6')->getFont()->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FF999999'));
        $sheet->getStyle('A6:H6')->getFont()->setItalic(true);

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, 'modele_fournisseurs.xlsx', [
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
        $relPath = 'temp/fournisseurs/' . $tempKey . '.' . $ext;
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

        // Prévisualisation avec validation partielle (10 premières lignes)
        $existingCodes = Fournisseur::pluck('code')->flip();
        $preview       = [];

        foreach (array_slice($dataRows, 0, 10) as $i => $row) {
            $nom  = trim((string) ($row[array_search('nom', $headers)] ?? ''));
            $code = trim((string) ($row[array_search('code', $headers)] ?? ''));

            $errors = [];
            if ($nom === '')  $errors[] = 'Nom obligatoire';
            if ($code === '') $errors[] = 'Code obligatoire';

            $preview[] = [
                'row'    => $i + 2,
                'data'   => $row,
                'exists' => $code !== '' && $existingCodes->has($code),
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

        $relPath = 'temp/fournisseurs/' . $data['temp_key'] . '.' . $data['extension'];

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

        $nomIdx       = $idx('nom');
        $codeIdx      = $idx('code');
        $emailIdx     = $idx('email');
        $phoneIdx     = $idx('telephone');
        $adresseIdx   = $idx('adresse');
        $villeIdx     = $idx('ville');
        $actifIdx     = $idx('actif');
        $homologueIdx = $idx('homologue');

        if ($nomIdx === null || $codeIdx === null) {
            return response()->json(['message' => 'Colonnes obligatoires "nom" et "code" introuvables.'], 422);
        }

        $parseBool = function ($val): bool {
            $v = strtolower(trim((string) ($val ?? '')));
            return in_array($v, ['oui', '1', 'true', 'yes'], true);
        };

        $created = 0;
        $updated = 0;
        $errors  = [];

        foreach (array_slice($rows, 1) as $i => $row) {
            $rowNum = $i + 2;

            // Ignorer lignes de commentaire (commencent par //)
            $firstCell = trim((string) ($row[0] ?? ''));
            if (str_starts_with($firstCell, '//')) continue;

            $nom  = trim((string) ($row[$nomIdx] ?? ''));
            $code = trim((string) ($row[$codeIdx] ?? ''));

            if ($nom === '') {
                $errors[] = ['row' => $rowNum, 'message' => 'Nom vide — ligne ignorée'];
                continue;
            }
            if ($code === '') {
                $errors[] = ['row' => $rowNum, 'message' => 'Code vide — ligne ignorée'];
                continue;
            }

            $fournisseurData = [
                'name'        => $nom,
                'email'       => $emailIdx !== null ? (trim((string) ($row[$emailIdx] ?? '')) ?: null) : null,
                'phone'       => $phoneIdx !== null ? (trim((string) ($row[$phoneIdx] ?? '')) ?: null) : null,
                'address'     => $adresseIdx !== null ? (trim((string) ($row[$adresseIdx] ?? '')) ?: null) : null,
                'city'        => $villeIdx !== null ? (trim((string) ($row[$villeIdx] ?? '')) ?: null) : null,
                'is_active'   => $actifIdx !== null ? $parseBool($row[$actifIdx]) : true,
                'is_approved' => $homologueIdx !== null ? $parseBool($row[$homologueIdx]) : false,
            ];

            $exists = Fournisseur::where('code', $code)->exists();

            Fournisseur::updateOrCreate(['code' => $code], $fournisseurData);

            $exists ? $updated++ : $created++;
        }

        Storage::disk('local')->delete($relPath);

        return response()->json(compact('created', 'updated', 'errors'));
    }
}
