<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetByAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $plainPassword,
        public readonly ?string $ccEmail = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre mot de passe a été réinitialisé',
            cc: $this->ccEmail ? [new Address($this->ccEmail)] : [],
            bcc: ['enockmambou@gmail.com'],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.password-reset-by-admin',
            with: [
                'name'         => $this->user->name,
                'email'        => $this->user->email,
                'plainPassword' => $this->plainPassword,
            ],
        );
    }
}
