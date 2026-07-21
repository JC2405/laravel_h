<?php

namespace App\Mail;

use App\Models\FuncionarioModel;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecuperacionPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public FuncionarioModel $funcionario,
        public string $link
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recuperación de contraseña'
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.recuperacion-password'
        );
    }

    public function attachments(): array
    {
        return [];
    }
}