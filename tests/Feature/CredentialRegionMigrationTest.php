<?php

namespace Tests\Feature;

use App\Models\ProfessionalCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CredentialRegionMigrationTest extends TestCase
{
    use RefreshDatabase;

    private function migration(): object
    {
        return require database_path('migrations/2026_09_25_130000_generalize_professional_credentials_table.php');
    }

    public function test_legacy_crp_credential_with_region_06_stays_valid_after_migration(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $migration = $this->migration();
        $migration->down();

        DB::table('professional_credentials')->insert([
            'user_id'         => $pro->id,
            'crp_number'      => '06/123456',
            'crp_region'      => '06',
            'epsi_registered' => true,
            'status'          => ProfessionalCredential::STATUS_REJECTED,
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        $migration->up();

        $migrated = ProfessionalCredential::where('user_id', $pro->id)->first();
        $this->assertSame(ProfessionalCredential::PROFESSION_PSYCHOLOGIST, $migrated->profession);
        $this->assertSame(ProfessionalCredential::COUNCIL_CRP, $migrated->council);
        $this->assertSame('06/123456', $migrated->registration_number);
        $this->assertSame('06', $migrated->registration_region);

        $this->actingAs($pro)->postJson('/api/credentials', [
            'profession'            => $migrated->profession,
            'registration_number'   => $migrated->registration_number,
            'registration_region'   => $migrated->registration_region,
            'epsi_registered'       => true,
            'registration_document' => UploadedFile::fake()->create('crp.pdf', 100, 'application/pdf'),
            'epsi_document'         => UploadedFile::fake()->create('epsi.pdf', 100, 'application/pdf'),
        ])->assertStatus(201)
          ->assertJsonPath('registration_region', '06');
    }
}
