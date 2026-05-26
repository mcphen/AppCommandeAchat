<?php

namespace App\Notifications\Concerns;

use Illuminate\Notifications\Messages\MailMessage;

trait CcAdmin
{
    protected function withAdminCc(MailMessage $message): MailMessage
    {
        $cc = config('mail.admin_cc');

        return $cc ? $message->cc($cc) : $message;
    }
}
