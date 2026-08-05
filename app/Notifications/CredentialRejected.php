<?php

namespace App\Notifications;

use App\Models\ProfessionalCredential;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CredentialRejected extends Notification
{
    public function __construct(
        public ProfessionalCredential $credential
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('mindcat.frontend_url'), '/') . '/pro/verificacao';

        $mail = (new MailMessage)
            ->subject('Sua credencial não foi aprovada — MindCat')
            ->greeting("Olá, {$notifiable->name}!")
            ->line('Analisamos sua credencial profissional e, desta vez, ela não foi aprovada.');

        if (filled($this->credential->rejection_reason)) {
            $mail->line("Motivo: {$this->credential->rejection_reason}");
        }

        return $mail
            ->line('Você pode corrigir os pontos apontados e reenviar seus documentos para uma nova análise.')
            ->action('Reenviar documentos', $url)
            ->line('Assim que reenviar, faremos uma nova avaliação.');
    }
}