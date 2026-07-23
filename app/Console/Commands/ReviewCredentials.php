<?php

namespace App\Console\Commands;

use App\Models\ProfessionalCredential;
use Illuminate\Console\Command;

class ReviewCredentials extends Command
{
    protected $signature = 'mindcat:review-credentials
        {--dry-run : Só conta o que venceria, sem gravar nada}';

    protected $description = 'Expira credenciais aprovadas cuja revisão venceu (next_review_at + carência < agora). Idempotente e re-executável.';

    public function handle(): int
    {
        $graceDays = (int) config('mindcat.credential.grace_days');
        $dryRun    = (bool) $this->option('dry-run');

        $threshold = now()->subDays($graceDays);

        $query = ProfessionalCredential::query()
            ->where('status', ProfessionalCredential::STATUS_APPROVED)
            ->whereNotNull('next_review_at')
            ->where('next_review_at', '<', $threshold);

        $total = $query->count();

        if ($total === 0) {
            $this->info('Nada a fazer: nenhuma credencial vencida além da carência.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("[DRY-RUN] {$total} credencial(is) venceria(m) (carência de {$graceDays} dia(s)).");

            return self::SUCCESS;
        }

        $affected = $query->update(['status' => ProfessionalCredential::STATUS_EXPIRED]);

        $this->info("Expiradas: {$affected} (carência de {$graceDays} dia(s)).");

        return self::SUCCESS;
    }
}