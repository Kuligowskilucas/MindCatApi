<?php

namespace App\Console\Commands;

use App\Models\DiaryEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class EncryptDiaries extends Command
{
    protected $signature = 'mindcat:encrypt-diaries
        {--batch=500 : Tamanho do lote (paginação por id)}
        {--dry-run : Só verifica se decifra, sem gravar nada}';

    protected $description = 'Re-cifra entradas legadas (encryption_version=0, APP_KEY) para a chave dedicada (v1). Idempotente e re-executável.';

    public function handle(): int
    {
        $batch  = max(1, (int) $this->option('batch'));
        $dryRun = (bool) $this->option('dry-run');

        $baseQuery = fn () => DiaryEntry::withTrashed()->where('encryption_version', 0);

        $total = $baseQuery()->count();

        if ($total === 0) {
            $this->info('Nada a fazer: nenhuma entrada em encryption_version=0.');

            return self::SUCCESS;
        }

        if (! config('mindcat.diary.bridge_app_key')) {
            $this->warn(
                "Há {$total} entrada(s) legada(s) (v0), mas MINDCAT_DIARY_BRIDGE_APP_KEY está OFF. " .
                'Foram cifradas com a APP_KEY e não serão legíveis sem o bridge. ' .
                'Ligue MINDCAT_DIARY_BRIDGE_APP_KEY=true antes de rodar.'
            );

            if (! $this->confirm('Continuar mesmo assim?', false)) {
                return self::FAILURE;
            }
        }

        $this->info(sprintf(
            '%s %d entrada(s) em lotes de %d...',
            $dryRun ? '[DRY-RUN] Verificando' : 'Migrando',
            $total,
            $batch
        ));

        $bar     = $this->output->createProgressBar($total);
        $ok      = 0;
        $failed  = 0;
        $failIds = [];

        $bar->start();

        $baseQuery()->chunkById($batch, function ($entries) use (&$ok, &$failed, &$failIds, $bar, $dryRun) {
            DB::transaction(function () use ($entries, &$ok, &$failed, &$failIds, $bar, $dryRun) {
                foreach ($entries as $entry) {
                    try {
                        if ($dryRun) {
                            $entry->content;
                        } else {
                            DB::transaction(function () use ($entry) {   
                                $entry->content = $entry->content;
                                $entry->save();
                            });
                        }

                        $ok++;
                    } catch (Throwable $e) {
                        $failed++;
                        $failIds[] = $entry->id;
                    } finally {
                        $bar->advance();
                    }
                }
            });
        });

        $bar->finish();
        $this->newLine(2);

        $this->info($dryRun
            ? "[DRY-RUN] Decifráveis: {$ok} | Falhas: {$failed}"
            : "Migradas para v1: {$ok} | Falhas: {$failed}");

        if ($failed > 0) {
            $shown = implode(', ', array_slice($failIds, 0, 50));
            $this->warn("IDs com falha ({$failed}): {$shown}" . (count($failIds) > 50 ? ' ...' : ''));
            $this->line('Verifique (bridge ligado? conteúdo corrompido?) e rode de novo — é idempotente.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}