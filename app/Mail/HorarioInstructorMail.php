<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HorarioInstructorMail extends Mailable
{
    use Queueable, SerializesModels;

    public $horario;

    public function __construct($horario)
    {
        $this->horario = $horario;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Asignación de Horario Semanal — SENA',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.horario',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}