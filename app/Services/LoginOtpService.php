<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LoginOtpService
{
    private const TABLE = 'login_otp_codes';
    private const MAX_ATTEMPTS = 5;
    private const EXPIRY_MINUTES = 10;

    public function start(User $user): string
    {
        DB::table(self::TABLE)->where('user_id', $user->id)->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $challenge = Str::random(64);

        DB::table(self::TABLE)->insert([
            'user_id'    => $user->id,
            'challenge'  => $challenge,
            'code'       => Hash::make($code),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        $this->sendCode($user->email, $code);

        return $challenge;
    }

    public function verify(string $challenge, string $code): User
    {
        $record = DB::table(self::TABLE)->where('challenge', $challenge)->first();

        if (!$record) {
            throw new HttpException(422, 'Código inválido ou expirado.');
        }

        if (now()->greaterThan($record->expires_at)) {
            DB::table(self::TABLE)->where('challenge', $challenge)->delete();
            throw new HttpException(422, 'Código expirado. Entre novamente.');
        }

        if ($record->attempts >= self::MAX_ATTEMPTS) {
            DB::table(self::TABLE)->where('challenge', $challenge)->delete();
            throw new HttpException(429, 'Muitas tentativas. Entre novamente.');
        }

        if (!Hash::check($code, $record->code)) {
            DB::table(self::TABLE)->where('challenge', $challenge)->increment('attempts');
            throw new HttpException(422, 'Código incorreto.');
        }

        $user = User::find($record->user_id);

        if (!$user) {
            DB::table(self::TABLE)->where('challenge', $challenge)->delete();
            throw new HttpException(422, 'Sessão de verificação inválida. Entre novamente.');
        }

        DB::table(self::TABLE)->where('challenge', $challenge)->delete();

        return $user;
    }

    public function resend(string $challenge): void
    {
        $record = DB::table(self::TABLE)->where('challenge', $challenge)->first();

        if (!$record || now()->greaterThan($record->expires_at)) {
            throw new HttpException(422, 'Sessão de verificação expirada. Entre novamente.');
        }

        $user = User::find($record->user_id);

        if (!$user) {
            DB::table(self::TABLE)->where('challenge', $challenge)->delete();
            throw new HttpException(422, 'Sessão de verificação inválida. Entre novamente.');
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table(self::TABLE)->where('challenge', $challenge)->update([
            'code'       => Hash::make($code),
            'attempts'   => 0,
            'expires_at' => now()->addMinutes(self::EXPIRY_MINUTES),
        ]);

        $this->sendCode($user->email, $code);
    }

    private function sendCode(string $email, string $code): void
    {
        Mail::raw(
            "Seu código de acesso ao MindCat é: {$code}\n\nEste código expira em " . self::EXPIRY_MINUTES . " minutos. Se você não tentou entrar, ignore este e-mail e troque sua senha.",
            function ($message) use ($email) {
                $message->to($email)->subject('MindCat - Código de verificação de acesso');
            }
        );
    }
}