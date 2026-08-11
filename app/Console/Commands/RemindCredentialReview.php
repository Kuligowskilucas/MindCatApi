<?php

namespace App\Console\Commands;

use App\Models\ProfessionalCredential;
use App\Notifications\CredentialReviewReminder;
use Illuminate\Console\Command;

class RemindCredentialReview extends Command
{
    protected $signature = 'mindcat:remind-credential-review
        {--dry-run : Só conta quem receberia aviso, sem enviar nem gravar}';

    protected $description = 'Avisa por e-mail os profissionais cuja credencial vence dentro da janela de lembrete, uma vez por ciclo. Idempotente e re-executável.';

    public function handle(): int
    {
        $reminderDays = (int) config('mindcat.credential.reminder_days');
        $dryRun       = (bool) $this->option('dry-run');

        $windowEnd = now()->addDays($reminderDays);

        $credentials = ProfessionalCredential::query()
            ->where('status', ProfessionalCredential::STATUS_APPROVED)
            ->whereNotNull('next_review_at')
            ->whereNull('review_reminder_sent_at')
            ->where('next_review_at', '>', now())
            ->where('next_review_at', '<=', $windowEnd)
            ->get();

        $total = $credentials->count();

        if ($total === 0) {
            $this->info('Nada a fazer: nenhuma credencial na janela de aviso.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->info("[DRY-RUN] {$total} credencial(is) receberia(m) aviso (janela de {$reminderDays} dia(s)).");

            return self::SUCCESS;
        }

        foreach ($credentials as $credential) {
            $credential->user?->notify(new CredentialReviewReminder($credential));
            $credential->update(['review_reminder_sent_at' => now()]);
        }

        $this->info("Avisos enviados: {$total} (janela de {$reminderDays} dia(s)).");

        return self::SUCCESS;
    }
}
