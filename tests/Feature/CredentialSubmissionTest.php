<?php

namespace Tests\Feature;

use App\Models\CredentialDocument;
use App\Models\ProfessionalCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CredentialSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function psychologistPayload(array $overrides = []): array
    {
        return array_merge([
            'profession'            => ProfessionalCredential::PROFESSION_PSYCHOLOGIST,
            'registration_number'   => '06/123456',
            'registration_region'   => '06',
            'epsi_registered'       => true,
            'registration_document' => UploadedFile::fake()->create('crp.pdf', 100, 'application/pdf'),
            'epsi_document'         => UploadedFile::fake()->create('epsi.pdf', 100, 'application/pdf'),
        ], $overrides);
    }

    private function psychiatristPayload(array $overrides = []): array
    {
        return array_merge([
            'profession'            => ProfessionalCredential::PROFESSION_PSYCHIATRIST,
            'registration_number'   => '123456',
            'registration_region'   => 'RJ',
            'rqe_number'            => '98765',
            'registration_document' => UploadedFile::fake()->create('crm.pdf', 100, 'application/pdf'),
        ], $overrides);
    }

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

    public function test_credentials_me_creates_pending_draft(): void
    {
        $pro = User::factory()->unverifiedPro()->create();

        $this->actingAs($pro)->getJson('/api/credentials/me')
            ->assertStatus(200)
            ->assertJsonPath('status', ProfessionalCredential::STATUS_PENDING);
    }

    public function test_psychologist_submits_with_crp_council_and_both_documents(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $this->actingAs($pro)->postJson('/api/credentials', $this->psychologistPayload())
            ->assertStatus(201)
            ->assertJsonPath('status', ProfessionalCredential::STATUS_SUBMITTED)
            ->assertJsonPath('profession', ProfessionalCredential::PROFESSION_PSYCHOLOGIST)
            ->assertJsonPath('council', ProfessionalCredential::COUNCIL_CRP)
            ->assertJsonPath('registration_number', '06/123456')
            ->assertJsonPath('registration_region', '06');

        $credential = ProfessionalCredential::where('user_id', $pro->id)->first();
        $this->assertTrue($credential->epsi_registered);
        $this->assertEqualsCanonicalizing(
            [CredentialDocument::KIND_CRP_CARD, CredentialDocument::KIND_EPSI_PROOF],
            $credential->documents->pluck('kind')->all()
        );

        foreach ($credential->documents as $doc) {
            $this->assertTrue(Storage::disk('local')->exists($doc->storage_path));
        }
    }

    public function test_psychiatrist_submits_with_crm_council_rqe_and_no_epsi(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $this->actingAs($pro)->postJson('/api/credentials', $this->psychiatristPayload())
            ->assertStatus(201)
            ->assertJsonPath('council', ProfessionalCredential::COUNCIL_CRM)
            ->assertJsonPath('rqe_number', '98765');

        $credential = ProfessionalCredential::where('user_id', $pro->id)->first();
        $this->assertFalse($credential->epsi_registered);
        $this->assertSame(
            [CredentialDocument::KIND_CRM_CARD],
            $credential->documents->pluck('kind')->all()
        );
    }

    public function test_psychiatrist_can_submit_without_rqe(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $this->actingAs($pro)->postJson('/api/credentials', $this->psychiatristPayload(['rqe_number' => null]))
            ->assertStatus(201)
            ->assertJsonPath('rqe_number', null);
    }

    public function test_council_is_derived_from_profession_not_from_input(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $this->actingAs($pro)->postJson('/api/credentials', $this->psychiatristPayload(['council' => 'CRP']))
            ->assertStatus(201)
            ->assertJsonPath('council', ProfessionalCredential::COUNCIL_CRM);
    }

    public function test_profession_is_required_and_must_be_known(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $payload = $this->psychologistPayload();
        unset($payload['profession']);

        $this->actingAs($pro)->postJson('/api/credentials', $payload)
            ->assertStatus(422)->assertJsonValidationErrors('profession');

        $this->actingAs($pro)->postJson('/api/credentials', $this->psychologistPayload(['profession' => 'neurologist']))
            ->assertStatus(422)->assertJsonValidationErrors('profession');
    }

    public function test_registration_number_and_region_are_required(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $this->actingAs($pro)->postJson('/api/credentials', $this->psychologistPayload([
            'registration_number' => '',
            'registration_region' => '',
        ]))->assertStatus(422)->assertJsonValidationErrors(['registration_number', 'registration_region']);
    }

    public function test_registration_number_format_is_validated(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $this->actingAs($pro)->postJson('/api/credentials', $this->psychologistPayload([
            'registration_number' => str_repeat('1', 21),
        ]))->assertStatus(422)->assertJsonValidationErrors('registration_number');

        $this->actingAs($pro)->postJson('/api/credentials', $this->psychologistPayload([
            'registration_number' => '<script>',
        ]))->assertStatus(422)->assertJsonValidationErrors('registration_number');
    }

    public function test_crp_accepts_two_digit_regions_from_01_to_24(): void
    {
        Storage::fake('local');

        foreach (['01', '06', '10', '24'] as $region) {
            $pro = User::factory()->unverifiedPro()->create();

            $this->actingAs($pro)->postJson('/api/credentials', $this->psychologistPayload(['registration_region' => $region]))
                ->assertStatus(201)
                ->assertJsonPath('registration_region', $region);
        }
    }

    public function test_crp_rejects_invalid_regions(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        foreach (['00', '25', '6', '006', 'SP', '6a'] as $region) {
            $this->actingAs($pro)->postJson('/api/credentials', $this->psychologistPayload(['registration_region' => $region]))
                ->assertStatus(422)
                ->assertJsonValidationErrors('registration_region');
        }
    }

    public function test_crm_accepts_uppercase_uf(): void
    {
        Storage::fake('local');

        foreach (['SP', 'DF', 'TO'] as $uf) {
            $pro = User::factory()->unverifiedPro()->create();

            $this->actingAs($pro)->postJson('/api/credentials', $this->psychiatristPayload(['registration_region' => $uf]))
                ->assertStatus(201)
                ->assertJsonPath('registration_region', $uf);
        }
    }

    public function test_crm_rejects_region_that_is_not_an_uppercase_uf(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        foreach (['sp', 'XX', '06', 'SPA'] as $region) {
            $this->actingAs($pro)->postJson('/api/credentials', $this->psychiatristPayload(['registration_region' => $region]))
                ->assertStatus(422)
                ->assertJsonValidationErrors('registration_region');
        }
    }

    public function test_psychologist_cannot_send_rqe(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $this->actingAs($pro)->postJson('/api/credentials', $this->psychologistPayload(['rqe_number' => '98765']))
            ->assertStatus(422)->assertJsonValidationErrors('rqe_number');
    }

    public function test_psychologist_requires_epsi_confirmation_and_document(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $payload = $this->psychologistPayload();
        unset($payload['epsi_registered'], $payload['epsi_document']);

        $this->actingAs($pro)->postJson('/api/credentials', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['epsi_registered', 'epsi_document']);
    }

    public function test_psychiatrist_cannot_send_epsi_fields(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $this->actingAs($pro)->postJson('/api/credentials', $this->psychiatristPayload([
            'epsi_registered' => true,
            'epsi_document'   => UploadedFile::fake()->create('epsi.pdf', 100, 'application/pdf'),
        ]))->assertStatus(422)->assertJsonValidationErrors(['epsi_registered', 'epsi_document']);
    }

    public function test_submission_requires_registration_document(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $payload = $this->psychiatristPayload();
        unset($payload['registration_document']);

        $this->actingAs($pro)->postJson('/api/credentials', $payload)
            ->assertStatus(422)->assertJsonValidationErrors('registration_document');
    }

    public function test_submitting_does_not_immediately_grant_clinical_access(): void
    {
        Storage::fake('local');
        $pro = User::factory()->unverifiedPro()->create();

        $this->actingAs($pro)->postJson('/api/credentials', $this->psychologistPayload())
            ->assertStatus(201);

        $this->actingAs($pro)->getJson('/api/patients')->assertStatus(403);
    }

    public function test_cannot_submit_when_already_approved(): void
    {
        Storage::fake('local');
        $pro = User::factory()->pro()->create();

        $this->actingAs($pro)->postJson('/api/credentials', $this->psychologistPayload())
            ->assertStatus(409);
    }
}
