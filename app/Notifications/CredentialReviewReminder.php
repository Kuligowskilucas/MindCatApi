<?php

namespace App\Notifications;

use App\Models\ProfessionalCredential;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CredentialReviewReminder extends Notification
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
        $url     = rtrim((string) config('mindcat.frontend_url'), '/') . '/pro/verificacao';
        $dueDate = $this->credential->next_review_at?->format('d/m/Y');

        return (new MailMessage)
            ->subject('Sua credencial precisa ser revalidada em breve — MindCat')
            ->greeting("Olá, {$notifiable->name}!")
            ->line("A validade da sua credencial profissional vence em {$dueDate}.")
            ->line('Reenvie seus documentos antes dessa data para não perder o acesso clínico.')
            ->action('Revalidar credencial', $url)
            ->line('Se a nova análise for aprovada a tempo, seu acesso continua sem interrupção.')
            ->salutation("Atenciosamente,\nEquipe MindCat");
    }
}
