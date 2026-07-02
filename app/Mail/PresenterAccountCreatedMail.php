<?php

namespace App\Mail;

use App\Models\Presenter;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PresenterAccountCreatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Presenter $presenter,
        public string $plainPassword,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Akun BONUSKU Presenter Anda Telah Dibuat',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.presenter-account-created',
            with: [
                'presenterName' => $this->presenter->name,
                'email' => $this->presenter->email,
                'plainPassword' => $this->plainPassword,
                'loginUrl' => url('/login'),
                'appName' => 'BONUSKU',
                'institutionName' => 'STIKES Gunung Sari',
            ],
        );
    }
}
