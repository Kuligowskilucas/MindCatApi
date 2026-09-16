<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class BackupDatabaseCommandTest extends TestCase
{
    private string $dir;

    /** @var list<string> */
    private array $created = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = storage_path('backups');
        File::ensureDirectoryExists($this->dir);

        config(['mindcat.backup.keep_days' => 7]);
    }

    protected function tearDown(): void
    {
        foreach ($this->created as $file) {
            if (is_file($file)) {
                @unlink($file);
            }
        }

        parent::tearDown();
    }

    private function makeBackup(string $name, int $ageInDays): string
    {
        $path = $this->dir . DIRECTORY_SEPARATOR . $name;
        file_put_contents($path, 'x');
        touch($path, now()->subDays($ageInDays)->getTimestamp());

        $this->created[] = $path;

        return $path;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_builds_a_gzipped_single_transaction_mysqldump_pipeline(): void
    {
        Process::fake();

        $this->artisan('mindcat:backup-database')->assertSuccessful();

        Process::assertRan(function ($process) {
            $command = is_array($process->command)
                ? implode(' ', $process->command)
                : $process->command;

            return str_contains($command, 'mysqldump')
                && str_contains($command, '--defaults-extra-file=')
                && str_contains($command, '--single-transaction')
                && str_contains($command, '--no-tablespaces')
                && str_contains($command, '--quick')
                && str_contains($command, '| gzip >')
                && str_contains($command, 'scheduled-')
                && str_contains($command, '.sql.gz')
                && str_contains($command, 'set -o pipefail');
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_prunes_scheduled_and_pre_deploy_backups_older_than_retention(): void
    {
        Process::fake();

        $old            = $this->makeBackup('scheduled-20200101-030000.sql.gz', 30);
        $recent         = $this->makeBackup('scheduled-20200102-030000.sql.gz', 1);
        $oldPreDeploy   = $this->makeBackup('pre-deploy-20200101-030000.sql.gz', 30);
        $freshPreDeploy = $this->makeBackup('pre-deploy-20200102-030000.sql.gz', 1);

        $this->artisan('mindcat:backup-database')->assertSuccessful();

        $this->assertFileDoesNotExist($old);
        $this->assertFileDoesNotExist($oldPreDeploy);
        $this->assertFileExists($recent);
        $this->assertFileExists($freshPreDeploy);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function retention_zero_prunes_nothing(): void
    {
        Process::fake();

        $old = $this->makeBackup('scheduled-20200101-030000.sql.gz', 30);

        $this->artisan('mindcat:backup-database', ['--keep-days' => 0])->assertSuccessful();

        $this->assertFileExists($old);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function dry_run_neither_dumps_nor_prunes(): void
    {
        Process::fake();

        $old = $this->makeBackup('scheduled-20200101-030000.sql.gz', 30);

        $this->artisan('mindcat:backup-database', ['--dry-run' => true])->assertSuccessful();

        Process::assertNothingRan();
        $this->assertFileExists($old);
    }
}