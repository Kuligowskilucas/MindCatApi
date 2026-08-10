<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class BackupDatabase extends Command
{
    protected $signature = 'mindcat:backup-database
        {--keep-days= : Dias de retenção (sobrescreve config; 0 = não poda nada)}
        {--dry-run : Mostra o dump e a poda que fariam, sem gravar nem apagar}';

    protected $description = 'Faz dump gzipado do MySQL em storage/backups e poda backups agendados além da retenção. Idempotente e re-executável.';

    public function handle(): int
    {
        $connection = 'mysql';

        $database = (string) config("database.connections.{$connection}.database");
        $username = (string) config("database.connections.{$connection}.username");
        $password = (string) config("database.connections.{$connection}.password");
        $host     = (string) config("database.connections.{$connection}.host");
        $port     = (string) config("database.connections.{$connection}.port");

        $keepDays = $this->option('keep-days') !== null
            ? max(0, (int) $this->option('keep-days'))
            : (int) config('mindcat.backup.keep_days');

        $dryRun = (bool) $this->option('dry-run');

        $dir = storage_path('backups');
        File::ensureDirectoryExists($dir);

        $path = $dir . DIRECTORY_SEPARATOR . 'scheduled-' . now()->format('Ymd-His') . '.sql.gz';

        $pruned = $this->prune($dir, $keepDays, $dryRun);

        if ($dryRun) {
            $this->info("[DRY-RUN] Dump de '{$database}' iria para {$path}.");
            $this->info("[DRY-RUN] Retenção {$keepDays} dia(s): {$pruned} arquivo(s) agendado(s) seriam apagados.");

            return self::SUCCESS;
        }

        $cnf = tempnam(sys_get_temp_dir(), 'mindcat-backup-');

        if ($cnf === false) {
            $this->error('Não foi possível criar o arquivo temporário de credenciais.');

            return self::FAILURE;
        }

        @chmod($cnf, 0600);
        file_put_contents(
            $cnf,
            "[client]\nhost={$host}\nport={$port}\nuser={$username}\npassword={$password}\n"
        );

        try {
            $pipeline = sprintf(
                'mysqldump --defaults-extra-file=%s --single-transaction --no-tablespaces --quick %s | gzip > %s',
                escapeshellarg($cnf),
                escapeshellarg($database),
                escapeshellarg($path)
            );

            $result = Process::run(['bash', '-c', 'set -o pipefail; ' . $pipeline]);
        } finally {
            @unlink($cnf);
        }

        if (! $result->successful()) {
            if (is_file($path)) {
                @unlink($path);
            }

            $stderr = trim($result->errorOutput());
            $this->error('Falha no mysqldump: ' . ($stderr !== '' ? $stderr : 'erro desconhecido.'));

            return self::FAILURE;
        }

        $size = is_file($path) ? File::size($path) : 0;

        $this->info("Backup criado: {$path} (" . number_format($size) . ' bytes).');
        $this->info("Retenção {$keepDays} dia(s): {$pruned} arquivo(s) antigo(s) apagado(s).");

        return self::SUCCESS;
    }

    private function prune(string $dir, int $keepDays, bool $dryRun): int
    {
        if ($keepDays <= 0) {
            return 0;
        }

        $cutoff = now()->subDays($keepDays)->getTimestamp();
        $count  = 0;

        foreach (glob($dir . DIRECTORY_SEPARATOR . 'scheduled-*.sql.gz') ?: [] as $file) {
            if (filemtime($file) < $cutoff) {
                $count++;

                if (! $dryRun) {
                    @unlink($file);
                }
            }
        }

        return $count;
    }
}