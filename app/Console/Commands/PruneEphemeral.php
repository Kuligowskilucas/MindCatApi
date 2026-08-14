<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneEphemeral extends Command
{
    protected $signature = 'mindcat:prune-ephemeral
        {--dry-run : Só conta os registros expirados, sem apagar}';

    protected $description = 'Apaga registros expirados das tabelas efêmeras (códigos de OTP e de reset de senha). Idempotente e re-executável.';

    private const TABLES = [
        'login_otp_codes',
        'password_reset_codes',
    ];

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $cutoff = now();
        $total = 0;

        foreach (self::TABLES as $table) {
            if ($dryRun) {
                $count = DB::table($table)->where('expires_at', '<', $cutoff)->count();
            } else {
                $count = DB::table($table)->where('expires_at', '<', $cutoff)->delete();
            }

            $verb = $dryRun ? 'seriam apagados' : 'apagados';
            $this->info("{$table}: {$count} registro(s) expirado(s) {$verb}.");
            $total += $count;
        }

        $this->info(($dryRun ? '[DRY-RUN] ' : '') . "Total: {$total}.");

        return self::SUCCESS;
    }
}
