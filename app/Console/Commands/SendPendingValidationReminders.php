<?php

namespace App\Console\Commands;

use App\Models\PurchaseOrder;
use App\Models\ValidationLevel;
use App\Notifications\PendingValidationDigestNotification;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SendPendingValidationReminders extends Command
{
    protected $signature = 'validation:send-reminders {--dry-run : Affiche les relances sans envoyer de mail}';

    protected $description = 'Envoie un digest par direction pour les validations en attente depuis au moins 3 jours ouvrés';

    public function handle(): int
    {
        $orders = PurchaseOrder::query()
            ->where('status', 'pending')
            ->whereNotNull('submitted_at')
            ->whereNotNull('current_level_order')
            ->with(['circuit', 'fournisseur'])
            ->get();

        $levels = ValidationLevel::query()
            ->with('validators')
            ->get()
            ->keyBy(fn (ValidationLevel $level) => "{$level->circuit_id}:{$level->order}");

        $eligible = $orders->map(function (PurchaseOrder $order) use ($levels) {
            $level = $levels->get("{$order->circuit_id}:{$order->current_level_order}");

            if (! $level) {
                return null;
            }

            $enteredAt = DB::table('validation_logs')
                ->where('purchase_order_id', $order->id)
                ->where('action', 'approved')
                ->max('created_at');

            $enteredAt = $enteredAt ? now()->parse($enteredAt) : $order->submitted_at;
            $waitingDays = $this->businessDaysBetween($enteredAt, now());

            if ($waitingDays < 3) {
                return null;
            }

            $lastSentAt = DB::table('validation_reminder_logs')
                ->where('purchase_order_id', $order->id)
                ->where('validation_level_id', $level->id)
                ->max('sent_at');

            if ($lastSentAt && $this->businessDaysBetween(now()->parse($lastSentAt), now()) < 3) {
                return null;
            }

            return compact('order', 'level', 'waitingDays');
        })->filter()->groupBy(fn (array $item) => $item['level']->id);

        if ($eligible->isEmpty()) {
            $this->info('Aucune relance de validation à envoyer.');

            return self::SUCCESS;
        }

        foreach ($eligible as $items) {
            $this->sendLevelDigest($items);
        }

        return self::SUCCESS;
    }

    private function sendLevelDigest(Collection $items): void
    {
        $level = $items->first()['level'];
        $emails = $level->validators
            ->pluck('email')
            ->filter()
            ->map(fn (string $email) => mb_strtolower(trim($email)))
            ->unique()
            ->values();

        if ($emails->isEmpty()) {
            $this->warn("{$level->name} : aucun destinataire, digest ignoré.");

            return;
        }

        $this->info("{$level->name} : {$items->count()} commande(s), {$emails->count()} destinataire(s).");

        if ($this->option('dry-run')) {
            return;
        }

        $orders = $items->map(fn (array $item) => [
            'title' => $item['order']->order_number ?: $item['order']->title,
            'amount' => (float) ($item['order']->amount_ttc ?: $item['order']->amount),
            'supplier' => $item['order']->fournisseur?->name ?? 'Fournisseur non renseigné',
            'days' => $item['waitingDays'],
        ])->all();

        Notification::route('mail', $emails->all())
            ->notify(new PendingValidationDigestNotification($level->name, $orders));

        $sentAt = now();

        DB::table('validation_reminder_logs')->insert($items->map(fn (array $item) => [
            'purchase_order_id' => $item['order']->id,
            'validation_level_id' => $level->id,
            'sent_at' => $sentAt,
            'waiting_business_days' => $item['waitingDays'],
            'recipients_count' => $emails->count(),
            'created_at' => $sentAt,
            'updated_at' => $sentAt,
        ])->all());
    }

    private function businessDaysBetween(CarbonInterface $start, CarbonInterface $end): int
    {
        $cursor = $start->copy()->startOfDay();
        $lastDay = $end->copy()->startOfDay();
        $days = 0;

        while ($cursor->lt($lastDay)) {
            $cursor->addDay();

            if ($cursor->isWeekday()) {
                $days++;
            }
        }

        return $days;
    }
}
