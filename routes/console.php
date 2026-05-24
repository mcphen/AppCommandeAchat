<?php

use App\Models\DisbursementRequest;
use App\Models\PurchaseOrder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('references:backfill-boutique-code {--dry-run : Simule sans sauvegarder en base}', function () {
    $dryRun = (bool) $this->option('dry-run');

    $sanitizeBoutiqueCode = function (?string $code, ?int $fallbackId): string {
        $raw = $code ?? ($fallbackId !== null ? (string) $fallbackId : 'NA');
        $sanitized = Str::upper((string) preg_replace('/[^A-Za-z0-9]/', '', $raw));

        return $sanitized !== '' ? $sanitized : 'NA';
    };

    $rewriteReference = function (string $reference, string $prefix, string $boutiqueCode): ?string {
        $pattern = '/^' . preg_quote($prefix, '/') . '-(?:[A-Z0-9]+-)?(\d{4})-(\d{5})$/';
        if (! preg_match($pattern, $reference, $matches)) {
            return null;
        }

        return $prefix . '-' . $boutiqueCode . '-' . $matches[1] . '-' . $matches[2];
    };

    $summary = [
        'purchase_orders' => ['updated' => 0, 'unchanged' => 0, 'invalid' => 0, 'collisions' => 0],
        'disbursement_requests' => ['updated' => 0, 'unchanged' => 0, 'invalid' => 0, 'collisions' => 0],
    ];

    PurchaseOrder::withTrashed()
        ->with('boutique:id,code')
        ->orderBy('id')
        ->chunkById(200, function ($orders) use ($dryRun, &$summary, $sanitizeBoutiqueCode, $rewriteReference) {
            foreach ($orders as $order) {
                $current = (string) $order->reference;
                $boutiqueCode = $sanitizeBoutiqueCode($order->boutique?->code, $order->boutique_id);
                $newReference = $rewriteReference($current, 'DA', $boutiqueCode);

                if ($newReference === null) {
                    $summary['purchase_orders']['invalid']++;
                    continue;
                }

                if ($newReference === $current) {
                    $summary['purchase_orders']['unchanged']++;
                    continue;
                }

                $collision = PurchaseOrder::withTrashed()
                    ->where('reference', $newReference)
                    ->where('id', '!=', $order->id)
                    ->exists();

                if ($collision) {
                    $summary['purchase_orders']['collisions']++;
                    $this->warn('Collision PurchaseOrder ID ' . $order->id . ' -> ' . $newReference);
                    continue;
                }

                if (! $dryRun) {
                    $order->reference = $newReference;
                    $order->saveQuietly();
                }

                $summary['purchase_orders']['updated']++;
            }
        });

    DisbursementRequest::withTrashed()
        ->with('boutique:id,code')
        ->orderBy('id')
        ->chunkById(200, function ($requests) use ($dryRun, &$summary, $sanitizeBoutiqueCode, $rewriteReference) {
            foreach ($requests as $request) {
                $current = (string) $request->reference;
                $boutiqueCode = $sanitizeBoutiqueCode($request->boutique?->code, $request->boutique_id);
                $newReference = $rewriteReference($current, 'DD', $boutiqueCode);

                if ($newReference === null) {
                    $summary['disbursement_requests']['invalid']++;
                    continue;
                }

                if ($newReference === $current) {
                    $summary['disbursement_requests']['unchanged']++;
                    continue;
                }

                $collision = DisbursementRequest::withTrashed()
                    ->where('reference', $newReference)
                    ->where('id', '!=', $request->id)
                    ->exists();

                if ($collision) {
                    $summary['disbursement_requests']['collisions']++;
                    $this->warn('Collision DisbursementRequest ID ' . $request->id . ' -> ' . $newReference);
                    continue;
                }

                if (! $dryRun) {
                    $request->reference = $newReference;
                    $request->saveQuietly();
                }

                $summary['disbursement_requests']['updated']++;
            }
        });

    $this->info('Backfill terminé' . ($dryRun ? ' (simulation)' : '') . '.');
    $this->line('PurchaseOrder: updated=' . $summary['purchase_orders']['updated']
        . ', unchanged=' . $summary['purchase_orders']['unchanged']
        . ', invalid=' . $summary['purchase_orders']['invalid']
        . ', collisions=' . $summary['purchase_orders']['collisions']);
    $this->line('DisbursementRequest: updated=' . $summary['disbursement_requests']['updated']
        . ', unchanged=' . $summary['disbursement_requests']['unchanged']
        . ', invalid=' . $summary['disbursement_requests']['invalid']
        . ', collisions=' . $summary['disbursement_requests']['collisions']);
})->purpose('Ajoute le code boutique dans les references DA/DD existantes');
