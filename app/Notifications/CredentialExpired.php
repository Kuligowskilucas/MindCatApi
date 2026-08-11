<?php

namespace App\Notifications;

use App\Models\ProfessionalCredential;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CredentialExpired extends Notification
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

        return (new MailMessage)
            ->subject('Sua credencial expirou — MindCat')
            ->greeting("Olá, {$notifiable->name}!")
            ->line('O prazo de revisão da sua credencial profissional venceu e ela expirou.')
            ->line('Seu acesso clínico está suspenso até que você reenvie os documentos para revalidação.')
            ->action('Revalidar credencial', $url)
            ->line('Assim que a nova análise for aprovada, seu acesso volta automaticamente.')
            ->salutation("Atenciosamente,\nEquipe MindCat");
    }
}
