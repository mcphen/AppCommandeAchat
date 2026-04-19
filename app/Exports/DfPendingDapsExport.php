<?php

namespace App\Exports;

use App\Models\DemandeAutorisationPaiement;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DfPendingDapsExport implements FromQuery, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    public function __construct(
        protected int $niveauValidationId,
        protected float $seuil,
        protected array $filters
    ) {}

    public function query()
    {
        $periode = $this->filters['periode'] ?? 'this_month';
        $entrepriseId = $this->filters['entreprise_id'] ?? null;
        $dateDebutInput = $this->filters['date_debut'] ?? null;
        $dateFinInput = $this->filters['date_fin'] ?? null;

        $dateFrom = match ($periode) {
            'last_30_days' => now()->subDays(30)->startOfDay(),
            'year_to_date' => now()->startOfYear(),
            'custom' => $dateDebutInput ? Carbon::parse($dateDebutInput)->startOfDay() : now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        $dateTo = match ($periode) {
            'custom' => $dateFinInput ? Carbon::parse($dateFinInput)->endOfDay() : now()->endOfDay(),
            default => now()->endOfDay(),
        };

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        return DemandeAutorisationPaiement::query()
            ->with(['expressionBesoin.user', 'expressionBesoin.entreprise', 'budgetAnnuel'])
            ->where('statut', DemandeAutorisationPaiement::STATUT_EN_COURS)
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->whereHas('expressionBesoin', function ($q) use ($entrepriseId) {
                $q->where('montant', '>=', $this->seuil);
                if ($entrepriseId) {
                    $q->where('entreprise_id', $entrepriseId);
                }
            })
            ->whereDoesntHave('validations', fn ($q) => $q->where('niveau_validation_id', $this->niveauValidationId))
            ->orderBy('created_at');
    }

    public function headings(): array
    {
        return [
            'Reference DAP',
            'Date creation',
            'Objet',
            'Entreprise',
            'Demandeur',
            'Montant',
            'Budget',
            'Statut',
        ];
    }

    public function map($dap): array
    {
        return [
            $dap->reference,
            optional($dap->created_at)->format('d/m/Y'),
            $dap->expressionBesoin?->objet ?? '-',
            $dap->expressionBesoin?->entreprise?->nom ?? '-',
            $dap->expressionBesoin?->user?->name ?? '-',
            number_format((float) ($dap->expressionBesoin?->montant ?? 0), 0, ',', ' '),
            $dap->budgetAnnuel?->libelle ?? '-',
            'En cours',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
