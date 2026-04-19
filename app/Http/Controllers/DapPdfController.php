<?php

namespace App\Http\Controllers;

use App\Models\DemandeAutorisationPaiement;
use Barryvdh\DomPDF\Facade\Pdf;

class DapPdfController extends Controller
{
    public function download(DemandeAutorisationPaiement $dap)
    {
        $dap->load([
            'expressionBesoin.user',
            'expressionBesoin.entreprise.groupe',
            'expressionBesoin.attachments',
            'budgetAnnuel',
            'validations.niveauValidation',
            'validations.validateur',
            'paiement',
            'createdBy',
        ]);

        $pdf = Pdf::loadView('pdf.dap', compact('dap'))
            ->setPaper('a4', 'portrait');

        $filename = 'DAP-' . $dap->reference . '.pdf';

        return $pdf->download($filename);
    }
}
