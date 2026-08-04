<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\URL;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(!app()->isProduction());

        VerifyEmail::createUrlUsing(function ($notifiable) {
            $apiUrl = URL::temporarySignedRoute(
                'verification.verify',
                now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id'   => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );

            $query = parse_url($apiUrl, PHP_URL_QUERY);

            return rtrim((string) config('mindcat.frontend_url'), '/')
                . '/verificar-email/' . $notifiable->getKey()
                . '/' . sha1($notifiable->getEmailForVerification())
                . '?' . $query;
        });

        VerifyEmail::toMailUsing(function ($notifiable, string $url) {
            return (new MailMessage)
                ->subject('Confirme seu e-mail — MindCat')
                ->greeting('Olá!')
                ->line('Obrigado por criar sua conta no MindCat. Confirme seu e-mail para ativar o acesso.')
                ->action('Confirmar e-mail', $url)
                ->line('O link expira em 60 minutos. Se você não criou esta conta, ignore este e-mail.');
        });
    }
}