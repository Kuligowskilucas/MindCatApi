<?php

namespace Tests\Feature;

use App\Models\ProfessionalCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CredentialSubmissionTest extends TestCase
{
    use RefreshDatabase;

    // ─── Gate pro-verified ───

    public function test_unverified_pro_is_blocked_from_clinical_routes(): void
    {
        $pro = User::factory()->unverifiedPro()->create();

        $this->actingAs($pro)->getJson('/api/patients')
            ->assertStatus(403)
            ->assertJson(['code' => 'credential_not_approved']);
    }

    public function test_verified_pro_passes_clinical_routes(): void
    {
        // pro() já cria credencial aprovada por padrão nos testes.
        $pro = User::factory()->pro()->create();

        $this->actingAs($pro)->getJson('/api/patients')->assertStatus(200);
    }

    public function test_patient_cannot_access_credential_routes(): void
    {
        $patient = User::factory()->patient()->create();

        $this->actingAs($patient)->getJson('/api/credentials/me')->assertStatus(403);
    }

    // ─── Submissão ───

    public function test_credentials_me_creates_pending_draft(): void
    {
        $pro = User::factory()->unverifiedPro()->create();

        $this->actingAs($pro)->getJson('/api/credentials/me')
            ->assertStatus(200)
            ->assertJsonPath('status', ProfessionalCredential::STATUS_PENDING);
    }

    public function test_pro_can_submit_credential_with_documents(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $response = $this->actingAs($pro)->postJson('/api/credentials', [
            'crp_number'      => '06/123456',
            'crp_region'      => '06',
            'epsi_registered' => true,
            'crp_document'    => UploadedFile::fake()->create('crp.pdf', 100, 'application/pdf'),
            'epsi_document'   => UploadedFile::fake()->create('epsi.pdf', 100, 'application/pdf'),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('status', ProfessionalCredential::STATUS_SUBMITTED);

        $credential = ProfessionalCredential::where('user_id', $pro->id)->first();
        $this->assertNotNull($credential);
        $this->assertCount(2, $credential->documents);

        foreach ($credential->documents as $doc) {
            $this->assertTrue(Storage::disk('local')->exists($doc->storage_path));
        }
    }

    public function test_submission_requires_both_documents(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $this->actingAs($pro)->postJson('/api/credentials', [
            'crp_number'      => '06/123456',
            'epsi_registered' => true,
            'crp_document'    => UploadedFile::fake()->create('crp.pdf', 100, 'application/pdf'),
            // sem epsi_document
        ])->assertStatus(422)->assertJsonValidationErrors('epsi_document');
    }

    public function test_submitting_does_not_immediately_grant_clinical_access(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $this->actingAs($pro)->postJson('/api/credentials', [
            'crp_number'      => '06/123456',
            'epsi_registered' => true,
            'crp_document'    => UploadedFile::fake()->create('crp.pdf', 100, 'application/pdf'),
            'epsi_document'   => UploadedFile::fake()->create('epsi.pdf', 100, 'application/pdf'),
        ])->assertStatus(201);

        // Submeteu, mas continua 'submitted' (não 'approved') → gate ainda barra.
        $this->actingAs($pro)->getJson('/api/patients')->assertStatus(403);
    }

    public function test_cannot_submit_when_already_approved(): void
    {
        Storage::fake('local');
        $pro = User::factory()->pro()->create(); // já aprovado

        $this->actingAs($pro)->postJson('/api/credentials', [
            'crp_number'      => '06/123456',
            'epsi_registered' => true,
            'crp_document'    => UploadedFile::fake()->create('crp.pdf', 100, 'application/pdf'),
            'epsi_document'   => UploadedFile::fake()->create('epsi.pdf', 100, 'application/pdf'),
        ])->assertStatus(409);
    }
}