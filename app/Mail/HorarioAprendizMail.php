<?php

namespace App\Mail;


use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class HorarioAprendizMail extends Mailable
{
    use SerializesModels;

   
    public function __construct(
        public array $horario,
        public $aprendiz,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Asignación de Horario Semanal — SENA',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.horario-aprendiz',
            with: [
                'horario' => $this->horario,
                'aprendiz' => $this->aprendiz,
            ],
        );
    }
}