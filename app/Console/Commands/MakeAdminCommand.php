<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

/**
 * Cria (ou promove) um admin do MindCat. Admin não sai do registro público
 * (RegisterRequest só aceita patient|pro), então este é o caminho de produção.
 *
 * Uso:
 *   php artisan mindcat:make-admin
 *   php artisan mindcat:make-admin --name="Fulano" --email=fulano@x.com --password=Segredo123
 */
class MakeAdminCommand extends Command
{
    protected $signature = 'mindcat:make-admin
                            {--name= : Nome do admin}
                            {--email= : Email do admin}
                            {--password= : Senha (deixe vazio para digitar de forma oculta)}';

    protected $description = 'Cria ou promove um usuário admin do MindCat.';

    public function handle(): int
    {
        $name     = $this->option('name') ?: $this->ask('Nome');
        $email    = $this->option('email') ?: $this->ask('Email');
        $password = $this->option('password') ?: $this->secret('Senha');

        $validator = Validator::make(
            compact('name', 'email', 'password'),
            [
                'name'     => 'required|string|max:255',
                'email'    => 'required|email',
                'password' => 'required|string|min:8',
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }
            return self::FAILURE;
        }

        $user = User::withTrashed()->firstWhere('email', $email);

        if ($user) {
            if ($user->trashed()) {
                $user->restore();
            }
            $user->update([
                'name'     => $name,
                'role'     => 'admin',
                'password' => Hash::make($password),
            ]);
            $this->info("Usuário {$email} promovido a admin.");
            return self::SUCCESS;
        }

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
            'role'     => 'admin',
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->info("Admin {$email} criado.");
        return self::SUCCESS;
    }
}