<?php

namespace Tests\Feature;

use App\Models\CredentialDocument;
use App\Models\PatientInvite;
use App\Models\ProfessionalCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CredentialFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_role_admin_is_persisted(): void
    {
        // Confirma que a coluna role virou string e aceita 'admin'.
        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertSame('admin', $admin->fresh()->role);
    }

    public function test_pro_has_one_credential_and_approval_helper(): void
    {
        // unverifiedPro(): sem credencial automática, pra criar a nossa aqui.
        $pro = User::factory()->unverifiedPro()->create();

        $credential = ProfessionalCredential::create([
            'user_id'             => $pro->id,
            'crp_number'          => '06/123456',
            'crp_region'          => '06',
            'epsi_registered'     => true,
            'status'              => ProfessionalCredential::STATUS_APPROVED,
            'verification_method' => ProfessionalCredential::METHOD_MANUAL,
            'verified_at'         => now(),
        ]);

        $this->assertTrue($pro->credential->is($credential));
        $this->assertTrue($credential->isApproved());
        $this->assertTrue($credential->epsi_registered); // cast boolean
    }

    public function test_pending_credential_is_not_approved(): void
    {
        $pro = User::factory()->unverifiedPro()->create();

        $credential = ProfessionalCredential::create([
            'user_id' => $pro->id,
            'status'  => ProfessionalCredential::STATUS_PENDING,
        ]);

        $this->assertFalse($credential->isApproved());
    }

    public function test_credential_has_many_documents(): void
    {
        $pro = User::factory()->unverifiedPro()->create();

        $credential = ProfessionalCredential::create([
            'user_id' => $pro->id,
            'status'  => ProfessionalCredential::STATUS_SUBMITTED,
        ]);

        $credential->documents()->create([
            'kind'          => CredentialDocument::KIND_CRP_CARD,
            'storage_path'  => 'credentials/x.pdf',
            'original_name' => 'carteira.pdf',
        ]);

        $this->assertCount(1, $credential->fresh()->documents);
    }

    public function test_patient_invite_usability_and_code_generation(): void
    {
        $patient = User::factory()->patient()->create();

        $active = PatientInvite::create([
            'code'       => PatientInvite::generateCode(),
            'patient_id' => $patient->id,
            'expires_at' => now()->addDay(),
            'status'     => PatientInvite::STATUS_ACTIVE,
        ]);

        $expired = PatientInvite::create([
            'code'       => PatientInvite::generateCode(),
            'patient_id' => $patient->id,
            'expires_at' => now()->subDay(),
            'status'     => PatientInvite::STATUS_ACTIVE,
        ]);

        $this->assertTrue($active->isUsable());
        $this->assertFalse($expired->isUsable());
        $this->assertSame(8, strlen($active->code));
        // Sem caracteres ambíguos (I, O, 0, 1).
        $this->assertMatchesRegularExpression('/^[A-HJ-NP-Z2-9]+$/', $active->code);
    }
}