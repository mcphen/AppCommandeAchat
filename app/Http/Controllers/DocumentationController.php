<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;

class DocumentationController extends Controller
{
    public function df()
    {
        $pdf = Pdf::loadView('pdf.documentation-df')
            ->setPaper('a4', 'portrait');

        return $pdf->download('Guide-Utilisateur-DAP-DF.pdf');
    }

    public function facture()
    {
        $pdf = Pdf::loadView('pdf.facture')
            ->setPaper('a4', 'portrait');

        return $pdf->download('Proposition-Facture-AchatPro-DIOUF.pdf');
    }
}
