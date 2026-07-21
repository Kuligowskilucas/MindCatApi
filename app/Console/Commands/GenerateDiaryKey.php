<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateDiaryKey extends Command
{
    protected $signature = 'mindcat:diary-key {--raw : Imprime apenas a chave, sem instruções}';

    protected $description = 'Gera uma chave dedicada para a criptografia do diário (não grava no .env).';

    public function handle(): int
    {
        $key = 'base64:' . base64_encode(random_bytes(32));

        if ($this->option('raw')) {
            $this->line($key);

            return self::SUCCESS;
        }

        $this->newLine();
        $this->info('Chave dedicada do diário gerada (NÃO gravada em disco):');
        $this->newLine();
        $this->line("  MINDCAT_DIARY_KEY={$key}");
        $this->newLine();
        $this->warn('Cole você mesmo no .env — de propósito a chave não é gravada automaticamente.');
        $this->newLine();
        $this->line('Regras de ouro:');
        $this->line('  • Faça backup desta chave SEPARADO do backup do banco.');
        $this->line('  • Perder a chave = perder todo diário cifrado com ela.');
        $this->line('  • Ao rotacionar: mova a chave atual para MINDCAT_DIARY_PREVIOUS_KEYS');
        $this->line('    ANTES de definir a nova aqui, ou o conteúdo antigo fica ilegível.');
        $this->newLine();

        return self::SUCCESS;
    }
}