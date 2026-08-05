<?php

namespace App\Notifications;

use App\Models\ProfessionalCredential;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CredentialApproved extends Notification
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
        $url = rtrim((string) config('mindcat.frontend_url'), '/') . '/pro';

        return (new MailMessage)
            ->subject('Sua credencial foi aprovada — MindCat')
            ->greeting("Olá, {$notifiable->name}!")
            ->line('Sua credencial profissional foi validada pela nossa equipe.')
            ->line('Seu acesso clínico está liberado: você já pode acompanhar pacientes e tarefas no MindCat.')
            ->action('Acessar o painel', $url)
            ->salutation("Atenciosamente,\nEquipe MindCat");
    }
}