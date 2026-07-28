<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserAccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly string $plainPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre compte a été créé',
            bcc: ['enockmambou@gmail.com'],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.user-account-created',
            with: [
                'name'         => $this->user->name,
                'email'        => $this->user->email,
                'plainPassword' => $this->plainPassword,
            ],
        );
    }
}
