<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use Illuminate\Support\Facades\DB;

class BudgetService
{
    /**
     * Calcule la consommation d'un budget.
     * Retourne: amount, consumed, in_validation, engaged, available, percent_consumed, percent_engaged, is_exceeded, is_warning
     */
    public function computeConsumption(Budget $budget): array
    {
        $amount = (float) $budget->amount;

        $consumed     = $this->sumForStatuses($budget, ['approved']);
        $inValidation = $this->sumForStatuses($budget, ['pending']);
        $engaged      = $consumed + $inValidation;

        $available       = max(0, $amount - $engaged);
        $percentConsumed = $amount > 0 ? min(100, round($consumed / $amount * 100, 1)) : 0;
        $percentEngaged  = $amount > 0 ? min(100, round($engaged / $amount * 100, 1)) : 0;

        return [
            'amount'           => $amount,
            'consumed'         => $consumed,
            'in_validation'    => $inValidation,
            'engaged'          => $engaged,
            'available'        => $available,
            'percent_consumed' => $percentConsumed,
            'percent_engaged'  => $percentEngaged,
            'is_exceeded'      => $engaged > $amount,
            'is_warning'       => !($engaged > $amount) && $engaged > $amount * 0.8,
        ];
    }

    /**
     * Vérifie si une commande, si approuvée, dépasserait des budgets.
     * Retourne les budgets en dépassement ou en alerte.
     */
    public function checkOrderAgainstBudgets(PurchaseOrder $purchaseOrder): array
    {
        $warnings = [];

        $budgets = Budget::where(function ($q) use ($purchaseOrder) {
                $q->where('boutique_id', $purchaseOrder->boutique_id)
                  ->orWhereNull('boutique_id');
            })
            ->where('year', now()->year)
            ->where(function ($q) {
                $q->where('month', now()->month)->orWhereNull('month');
            })
            ->with(['boutique', 'category'])
            ->get();

        foreach ($budgets as $budget) {
            $consumption = $this->computeConsumption($budget);

            if ($consumption['is_exceeded'] || $consumption['is_warning']) {
                $warnings[] = array_merge($consumption, [
                    'budget_id' => $budget->id,
                    'boutique'  => $budget->boutique?->name ?? 'Toutes boutiques',
                    'category'  => $budget->category?->name ?? 'Toutes catégories',
                    'period'    => $budget->period_label,
                ]);
            }
        }

        return $warnings;
    }

    /**
     * Retourne les budgets en alerte ou dépassés pour le dashboard.
     */
    public function getAlertBudgets(int $year, ?int $limit = null): \Illuminate\Support\Collection
    {
        $query = Budget::with(['boutique', 'category'])
            ->where('year', $year)
            ->get()
            ->map(fn ($b) => array_merge(
                ['id' => $b->id, 'boutique' => $b->boutique ? ['name' => $b->boutique->name, 'code' => $b->boutique->code] : null, 'category' => $b->category ? ['name' => $b->category->name] : null, 'period' => $b->period_label],
                $this->computeConsumption($b)
            ))
            ->sortByDesc('percent_engaged')
            ->values();

        return $limit ? $query->take($limit) : $query;
    }

    private function sumForStatuses(Budget $budget, array $statuses): float
    {
        if ($budget->category_id !== null) {
            // Calcul au niveau des lignes de commande (par catégorie d'article)
            $total = PurchaseOrderLine::query()
                ->whereHas('article', fn ($q) => $q->where('category_id', $budget->category_id))
                ->whereHas('purchaseOrder', function ($q) use ($budget, $statuses) {
                    $q->whereIn('status', $statuses);

                    if ($budget->boutique_id !== null) {
                        $q->where('boutique_id', $budget->boutique_id);
                    }

                    $this->applyPeriodFilter($q, $budget);
                })
                ->sum(DB::raw('quantity * unit_price'));

            return (float) ($total ?? 0);
        }

        // Calcul au niveau de la commande entière (budget boutique global)
        $query = PurchaseOrder::whereIn('status', $statuses);

        if ($budget->boutique_id !== null) {
            $query->where('boutique_id', $budget->boutique_id);
        }

        $this->applyPeriodFilter($query, $budget);

        return (float) ($query->sum('amount') ?? 0);
    }

    private function applyPeriodFilter($query, Budget $budget): void
    {
        $query->whereYear('created_at', $budget->year);

        if ($budget->month !== null) {
            $query->whereMonth('created_at', $budget->month);
        }
    }
}
