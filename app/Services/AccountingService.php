<?php

namespace App\Services;

use App\Models\AccountingEntry;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReception;
use Illuminate\Support\Collection;

class AccountingService
{
    // Compte fournisseur par défaut (OHADA : 401 = Fournisseurs)
    private const DEFAULT_SUPPLIER_ACCOUNT = '401000';
    private const DEFAULT_SUPPLIER_LABEL   = 'Fournisseurs';

    // Compte de charge par défaut si la catégorie n'a pas de compte configuré
    private const DEFAULT_CHARGE_ACCOUNT = '60500';
    private const DEFAULT_CHARGE_LABEL   = 'Autres achats';

    /**
     * Génère les écritures comptables pour une réception.
     * Date = date de réception, montants = quantités reçues × prix unitaire BC.
     * Idempotent : supprime et recrée les écritures de cette réception à chaque appel.
     */
    public function generateForReception(PurchaseOrderReception $reception): void
    {
        $reception->load(['lines.orderLine.article.category', 'purchaseOrder.fournisseur']);
        $order = $reception->purchaseOrder;

        // Supprimer les éventuelles écritures existantes pour cette réception
        AccountingEntry::where('reception_id', $reception->id)->delete();

        $entryDate = $reception->received_at->toDateString();
        $pieceRef  = $reception->invoice_number ?? ($order->order_number ?? "BC-{$order->id}");

        $entries = [];

        // 1. Écritures DÉBIT par ligne reçue (compte de charge)
        foreach ($reception->lines as $rline) {
            $orderLine = $rline->orderLine;
            if (! $orderLine) continue;

            $qtyReceived = (float) $rline->quantity_received;
            $unitPrice   = (float) ($orderLine->unit_price ?? 0);
            $subtotal    = round($qtyReceived * $unitPrice, 2);

            if ($subtotal <= 0) continue;

            $category     = $orderLine->article?->category;
            $accountCode  = $category?->account_code  ?: self::DEFAULT_CHARGE_ACCOUNT;
            $accountLabel = $category?->account_label ?: ($category?->name ? "Achats - {$category->name}" : self::DEFAULT_CHARGE_LABEL);
            $articleName  = $orderLine->article?->name ?? 'Article divers';

            $entries[] = [
                'purchase_order_id' => $order->id,
                'reception_id'      => $reception->id,
                'entry_date'        => $entryDate,
                'journal_code'      => 'ACH',
                'piece_ref'         => $pieceRef,
                'account_code'      => $accountCode,
                'account_label'     => $accountLabel,
                'aux_code'          => null,
                'aux_label'         => null,
                'entry_label'       => "Réception {$articleName} — {$pieceRef}",
                'debit'             => $subtotal,
                'credit'            => 0,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }

        // 2. Écriture CRÉDIT globale (compte fournisseur 401xxx)
        // Si montant facture saisi → on l'utilise, sinon on prend le cumul des lignes reçues
        $totalCredit = $reception->invoice_amount
            ? (float) $reception->invoice_amount
            : collect($entries)->sum('debit');

        if ($totalCredit > 0) {
            $fournisseur   = $order->fournisseur;
            $supplierCode  = $fournisseur
                ? '401' . str_pad(preg_replace('/\D/', '', $fournisseur->code ?? ''), 3, '0', STR_PAD_LEFT)
                : self::DEFAULT_SUPPLIER_ACCOUNT;
            $supplierLabel = $fournisseur?->name ?? self::DEFAULT_SUPPLIER_LABEL;

            $entries[] = [
                'purchase_order_id' => $order->id,
                'reception_id'      => $reception->id,
                'entry_date'        => $entryDate,
                'journal_code'      => 'ACH',
                'piece_ref'         => $pieceRef,
                'account_code'      => $supplierCode,
                'account_label'     => self::DEFAULT_SUPPLIER_LABEL,
                'aux_code'          => $fournisseur?->code,
                'aux_label'         => $supplierLabel,
                'entry_label'       => "Fournisseur — {$pieceRef}",
                'debit'             => 0,
                'credit'            => $totalCredit,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }

        if (! empty($entries)) {
            AccountingEntry::insert($entries);
        }
    }

    /**
     * Génère les écritures comptables pour un bon de commande confirmé.
     * Supprime les anciennes écritures s'il en existe (re-génération idempotente).
     * @deprecated Utiliser generateForReception() — les écritures sont désormais générées à la réception.
     */
    public function generateForOrder(PurchaseOrder $order): void
    {
        // Charger les relations nécessaires
        $order->load(['lines.article.category', 'fournisseur', 'boutique']);

        // Supprimer les éventuelles écritures existantes (si re-génération)
        AccountingEntry::where('purchase_order_id', $order->id)->delete();

        $entryDate = $order->ordered_at?->toDateString() ?? now()->toDateString();
        $pieceRef  = $order->order_number ?? "BC-{$order->id}";

        $entries = [];

        // 1. Écritures DÉBIT par ligne (compte de charge)
        foreach ($order->lines as $line) {
            $category     = $line->article?->category;
            $accountCode  = $category?->account_code ?: self::DEFAULT_CHARGE_ACCOUNT;
            $accountLabel = $category?->account_label ?: ($category?->name ? "Achats - {$category->name}" : self::DEFAULT_CHARGE_LABEL);
            $subtotal     = round((float) $line->quantity * (float) $line->unit_price, 2);

            if ($subtotal <= 0) {
                continue;
            }

            $articleName = $line->article?->name ?? 'Article divers';
            $entries[] = [
                'purchase_order_id' => $order->id,
                'entry_date'        => $entryDate,
                'journal_code'      => 'ACH',
                'piece_ref'         => $pieceRef,
                'account_code'      => $accountCode,
                'account_label'     => $accountLabel,
                'aux_code'          => null,
                'aux_label'         => null,
                'entry_label'       => "Achat {$articleName} — {$pieceRef}",
                'debit'             => $subtotal,
                'credit'            => 0,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }

        // 2. Écriture CRÉDIT globale (compte fournisseur 401xxx)
        $totalAmount  = (float) $order->amount;
        $fournisseur  = $order->fournisseur;
        $supplierCode = $fournisseur
            ? '401' . str_pad(preg_replace('/\D/', '', $fournisseur->code ?? ''), 3, '0', STR_PAD_LEFT)
            : self::DEFAULT_SUPPLIER_ACCOUNT;
        $supplierLabel = $fournisseur?->name ?? self::DEFAULT_SUPPLIER_LABEL;

        if ($totalAmount > 0) {
            $entries[] = [
                'purchase_order_id' => $order->id,
                'entry_date'        => $entryDate,
                'journal_code'      => 'ACH',
                'piece_ref'         => $pieceRef,
                'account_code'      => $supplierCode,
                'account_label'     => self::DEFAULT_SUPPLIER_LABEL,
                'aux_code'          => $fournisseur?->code,
                'aux_label'         => $supplierLabel,
                'entry_label'       => "Fournisseur — {$pieceRef}",
                'debit'             => 0,
                'credit'            => $totalAmount,
                'created_at'        => now(),
                'updated_at'        => now(),
            ];
        }

        if (! empty($entries)) {
            AccountingEntry::insert($entries);
        }
    }

    /**
     * Exporte les écritures au format FEC (Fichier des Écritures Comptables).
     * Compatible Sage, Odoo, Epegase (import manuel), et la norme SYSCOHADA.
     */
    public function exportFec(Collection $entries): string
    {
        $headers = [
            'JournalCode', 'JournalLib', 'EcritureNum', 'EcritureDate',
            'CompteNum', 'CompteLib', 'CompAuxNum', 'CompAuxLib',
            'PieceRef', 'PieceDate', 'EcritureLib',
            'Debit', 'Credit',
        ];

        $rows = [$this->fecLine($headers)];

        foreach ($entries as $entry) {
            $rows[] = $this->fecLine([
                $entry->journal_code,
                'Journal des achats',
                $entry->piece_ref,
                $entry->entry_date->format('Ymd'),
                $entry->account_code,
                $entry->account_label,
                $entry->aux_code ?? '',
                $entry->aux_label ?? '',
                $entry->piece_ref,
                $entry->entry_date->format('Ymd'),
                $entry->entry_label,
                number_format((float) $entry->debit,  2, ',', ''),
                number_format((float) $entry->credit, 2, ',', ''),
            ]);
        }

        return implode("\n", $rows);
    }

    /**
     * Exporte les écritures en CSV standard (compatible Excel / Epegase / import générique).
     */
    public function exportCsv(Collection $entries): string
    {
        $headers = [
            'N° BC', 'Date', 'Journal', 'Compte', 'Libellé compte',
            'Code aux.', 'Libellé aux.', 'Libellé écriture', 'Débit', 'Crédit',
        ];

        $output = fopen('php://temp', 'r+');
        // BOM UTF-8 pour compatibilité Excel
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $headers, ';');

        foreach ($entries as $entry) {
            fputcsv($output, [
                $entry->piece_ref,
                $entry->entry_date->format('d/m/Y'),
                $entry->journal_code,
                $entry->account_code,
                $entry->account_label,
                $entry->aux_code ?? '',
                $entry->aux_label ?? '',
                $entry->entry_label,
                number_format((float) $entry->debit,  2, ',', ''),
                number_format((float) $entry->credit, 2, ',', ''),
            ], ';');
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    private function fecLine(array $fields): string
    {
        return implode('|', $fields);
    }
}
