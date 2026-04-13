<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PasswordResetService
{
    private const TABLE = 'password_reset_codes';
    private const MAX_ATTEMPTS = 5;
    private const EXPIRY_MINUTES = 15;

    public function sendCode(string $email): void
    {
        $user = User::where('email', $email)->first();

        if (!$user) {
            return; // não revelar se o email existe
        }

        DB::table(self::TABLE)->where('email', $email)->delete();

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table(self::TABLE)->insert([
            'email'      => $email,
            'code'       => Hash::make($code),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        Mail::raw(
            "Seu código de recuperação de senha do MindCat é: {$code}\n\nEste código expira em " . self::EXPIRY_MINUTES . " minutos.",
            function ($message) use ($email) {
                $message->to($email)->subject('MindCat - Código de recuperação de senha');
            }
        );
    }

    public function resetPassword(string $email, string $code, string $newPassword): void
    {
        $record = DB::table(self::TABLE)->where('email', $email)->first();

        if (!$record) {
            throw new HttpException(422, 'Código inválido ou expirado.');
        }

        if (now()->greaterThan($record->expires_at)) {
            DB::table(self::TABLE)->where('email', $email)->delete();
            throw new HttpException(422, 'Código expirado. Solicite um novo.');
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            DB::table(self::TABLE)->where('email', $email)->delete();
            throw new HttpException(429, 'Muitas tentativas. Solicite um novo código.');
        }

        if (!Hash::check($code, $record->code)) {
            DB::table(self::TABLE)->where('email', $email)->increment('attempts');
            throw new HttpException(422, 'Código incorreto.');
        }

        $user = User::where('email', $email)->firstOrFail();
        $user->password = Hash::make($newPassword);
        $user->save();

        DB::table(self::TABLE)->where('email', $email)->delete();
    }
}
