<?php

// A executer avec : php artisan tinker --execute="require 'scripts/check-bc4207.php';"
// sur le serveur de PRODUCTION (achats.construcsen.com), pas en local.

$po = \App\Models\PurchaseOrder::find(1488);

if (! $po) {
    echo "Commande introuvable (id=1488). Verifie l'ID exact dans l'URL.\n";
    return;
}

echo "=== Etat actuel de la commande ===\n";
echo "id: {$po->id}\n";
echo "title: {$po->title}\n";
echo "status: {$po->status}\n";
echo "current_level_order: {$po->current_level_order}\n";
echo "submitted_at: {$po->submitted_at}\n";
echo "circuit_id: {$po->circuit_id}\n";
echo "sage_reference: {$po->sage_reference}\n";
echo "updated_at: {$po->updated_at}\n";
echo "created_at: {$po->created_at}\n";

echo "\n=== Notifications 'commande soumise' envoyees pour cette commande (preuve d'un clic sur Soumettre) ===\n";
$notifs = \DB::table('notifications')
    ->where('type', \App\Notifications\OrderSubmittedNotification::class)
    ->whereRaw("JSON_EXTRACT(data, '$.order_id') = ?", [$po->id])
    ->orderBy('created_at')
    ->get(['notifiable_id', 'created_at', 'read_at', 'data']);

foreach ($notifs as $n) {
    echo "- envoyee a user #{$n->notifiable_id} le {$n->created_at} (lue: " . ($n->read_at ?? 'non') . ")\n";
}
if ($notifs->isEmpty()) {
    echo "Aucune notification trouvee : le bouton 'Soumettre' n'a peut-etre jamais ete clique avec succes.\n";
}

echo "\n=== Historique des resynchros Sage pour cette piece (sage_reference) ===\n";
$logs = \App\Models\SageWebhookLog::where('sage_reference', $po->sage_reference)
    ->orderBy('created_at')
    ->get(['status', 'created_at', 'error_message']);

foreach ($logs as $l) {
    echo "- {$l->created_at} : {$l->status}" . ($l->error_message ? " ({$l->error_message})" : '') . "\n";
}

echo "\n=== Logs de validation (approbations/rejets) ===\n";
$vlogs = $po->validationLogs()->orderBy('created_at')->get(['action', 'created_at', 'user_id']);
foreach ($vlogs as $v) {
    echo "- {$v->created_at} : {$v->action} par user #{$v->user_id}\n";
}
if ($vlogs->isEmpty()) {
    echo "(aucun)\n";
}
