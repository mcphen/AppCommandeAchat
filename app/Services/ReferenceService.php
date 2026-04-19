<?php

namespace App\Services;

use App\Models\DemandeAutorisationPaiement;
use App\Models\ExpressionBesoin;

class ReferenceService
{
    public function generateEB(): string
    {
        $year  = now()->year;
        $count = ExpressionBesoin::whereYear('created_at', $year)->count() + 1;
        return sprintf('EB-%d-%04d', $year, $count);
    }

    public function generateDAP(): string
    {
        $year  = now()->year;
        $count = DemandeAutorisationPaiement::whereYear('created_at', $year)->count() + 1;
        return sprintf('DAP-%d-%04d', $year, $count);
    }
}
