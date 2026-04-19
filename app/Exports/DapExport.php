<?php

namespace App\Exports;

use App\Models\DemandeAutorisationPaiement;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DapExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(protected array $filters) {}

    public function query()
    {
        return DemandeAutorisationPaiement::with([
                'expressionBesoin.user',
                'expressionBesoin.entreprise',
                'budgetAnnuel',
                'validations.niveauValidation',
            ])
            ->when($this->filters['search'] ?? '', function ($q) {
                $s = $this->filters['search'];
                $q->where('reference', 'like', "%{$s}%")
                  ->orWhereHas('expressionBesoin', fn ($sq) =>
                      $sq->where('objet', 'like', "%{$s}%")
                         ->orWhere('beneficiaire', 'like', "%{$s}%")
                  );
            })
            ->when($this->filters['statut'] ?? '', fn ($q) => $q->where('statut', $this->filters['statut']))
            ->when($this->filters['user_id'] ?? '', fn ($q) => $q->whereHas('expressionBesoin', fn ($sq) => $sq->where('user_id', $this->filters['user_id'])))
            ->when($this->filters['entreprise_id'] ?? '', fn ($q) => $q->whereHas('expressionBesoin', fn ($sq) => $sq->where('entreprise_id', $this->filters['entreprise_id'])))
            ->latest();
    }

    public function headings(): array
    {
        return [
            'Référence DAP',
            'Objet',
            'Demandeur',
            'Entreprise',
            'Montant (XOF)',
            'Statut',
            'Budget',
            'Validations',
            'Date création',
        ];
    }

    public function map($dap): array
    {
        $statuts = [
            'en_cours' => 'En cours',
            'validee'  => 'Validée',
            'rejetee'  => 'Rejetée',
            'payee'    => 'Payée',
        ];

        return [
            $dap->reference,
            $dap->expressionBesoin?->objet ?? '-',
            $dap->expressionBesoin?->user?->name ?? '-',
            $dap->expressionBesoin?->entreprise?->nom ?? '-',
            number_format($dap->expressionBesoin?->montant ?? 0, 0, ',', ' '),
            $statuts[$dap->statut] ?? $dap->statut,
            $dap->budgetAnnuel?->libelle ?? '-',
            $dap->validations->map(fn ($v) => $v->niveauValidation?->nom)->filter()->implode(', '),
            $dap->created_at?->format('d/m/Y'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
