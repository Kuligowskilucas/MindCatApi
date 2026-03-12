<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PasswordResetController extends Controller
{
    // POST /api/forgot-password
    public function sendCode(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Não revelar se o email existe ou não (segurança)
            return response()->json([
                'message' => 'Se o email estiver cadastrado, você receberá um código.'
            ]);
        }

        // Limpar códigos antigos desse email
        DB::table('password_reset_codes')->where('email', $request->email)->delete();

        // Gerar código de 6 dígitos
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_codes')->insert([
            'email' => $request->email,
            'code' => Hash::make($code),
            'expires_at' => now()->addMinutes(15),
        ]);

        // Enviar email
        Mail::raw(
            "Seu código de recuperação de senha do MindCat é: {$code}\n\nEste código expira em 15 minutos.",
            function ($message) use ($request) {
                $message->to($request->email)
                        ->subject('MindCat - Código de recuperação de senha');
            }
        );

        return response()->json([
            'message' => 'Se o email estiver cadastrado, você receberá um código.'
        ]);
    }

    // POST /api/reset-password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string|size:6',
            'password' => 'required|string|min:6',
        ]);

        $record = DB::table('password_reset_codes')
            ->where('email', $request->email)
            ->first();

        if (!$record) {
            return response()->json(['message' => 'Código inválido ou expirado.'], 422);
        }

        if (now()->greaterThan($record->expires_at)) {
            DB::table('password_reset_codes')->where('email', $request->email)->delete();
            return response()->json(['message' => 'Código expirado. Solicite um novo.'], 422);
        }

        if (!Hash::check($request->code, $record->code)) {
            return response()->json(['message' => 'Código incorreto.'], 422);
        }

        // Atualizar senha
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'Usuário não encontrado.'], 404);
        }

        $user->password = Hash::make($request->password);
        $user->save();

        // Limpar código usado
        DB::table('password_reset_codes')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Senha redefinida com sucesso!']);
    }
}