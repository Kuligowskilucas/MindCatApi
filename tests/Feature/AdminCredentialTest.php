<?php

namespace Tests\Feature;

use App\Models\CredentialDocument;
use App\Models\ProfessionalCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AdminCredentialTest extends TestCase
{
    use RefreshDatabase;

    /** Cria um pro novo (sem credencial automática) com uma credencial 'submitted'. */
    private function submittedCredentialForNewPro(): ProfessionalCredential
    {
        $pro = User::factory()->unverifiedPro()->create();

        return ProfessionalCredential::create([
            'user_id'         => $pro->id,
            'crp_number'      => '06/123456',
            'crp_region'      => '06',
            'epsi_registered' => true,
            'status'          => ProfessionalCredential::STATUS_SUBMITTED,
            'submitted_at'    => now(),
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_admin_can_list_pending_queue(): void
    {
        $this->submittedCredentialForNewPro();

        $this->actingAs($this->admin())->getJson('/api/admin/credentials')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => [['id', 'status', 'user' => ['id', 'name', 'email']]]])
            ->assertJsonPath('data.0.status', ProfessionalCredential::STATUS_SUBMITTED);
    }

    public function test_non_admin_cannot_access_admin_routes(): void
    {
        $credential = $this->submittedCredentialForNewPro();
        $pro = User::factory()->pro()->create();
        $patient = User::factory()->patient()->create();

        $this->actingAs($pro)->getJson('/api/admin/credentials')->assertStatus(403);
        $this->actingAs($patient)->getJson('/api/admin/credentials')->assertStatus(403);
        $this->actingAs($pro)
            ->postJson("/api/admin/credentials/{$credential->id}/approve")
            ->assertStatus(403);
    }

    public function test_admin_can_approve_and_it_unlocks_clinical_access(): void
    {
        $credential = $this->submittedCredentialForNewPro();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson("/api/admin/credentials/{$credential->id}/approve")
            ->assertStatus(200)
            ->assertJsonPath('status', ProfessionalCredential::STATUS_APPROVED)
            ->assertJsonPath('verified_by', $admin->id);

        // Auditoria + agendamento de revisão gravados.
        $fresh = $credential->fresh();
        $this->assertNotNull($fresh->verified_at);
        $this->assertNotNull($fresh->next_review_at);
        $this->assertSame(ProfessionalCredential::METHOD_MANUAL, $fresh->verification_method);

        // O gate da 5b agora libera o pro nas rotas clínicas.
        $this->actingAs($credential->user)->getJson('/api/patients')->assertStatus(200);
    }

    public function test_admin_can_reject_with_reason_and_pro_stays_blocked(): void
    {
        $credential = $this->submittedCredentialForNewPro();

        $this->actingAs($this->admin())
            ->postJson("/api/admin/credentials/{$credential->id}/reject", [
                'reason' => 'Documento ilegível.',
            ])
            ->assertStatus(200)
            ->assertJsonPath('status', ProfessionalCredential::STATUS_REJECTED)
            ->assertJsonPath('rejection_reason', 'Documento ilegível.');

        $this->actingAs($credential->user->fresh())
            ->getJson('/api/patients')->assertStatus(403);
    }

    public function test_reject_requires_reason(): void
    {
        $credential = $this->submittedCredentialForNewPro();

        $this->actingAs($this->admin())
            ->postJson("/api/admin/credentials/{$credential->id}/reject", [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('reason');
    }

    public function test_cannot_decide_credential_not_awaiting_review(): void
    {
        $credential = $this->submittedCredentialForNewPro();
        $credential->update(['status' => ProfessionalCredential::STATUS_APPROVED]);

        $this->actingAs($this->admin())
            ->postJson("/api/admin/credentials/{$credential->id}/approve")
            ->assertStatus(409);
    }

    public function test_show_returns_signed_document_urls(): void
    {
        $credential = $this->submittedCredentialForNewPro();
        $credential->documents()->create([
            'kind'          => CredentialDocument::KIND_CRP_CARD,
            'storage_path'  => 'credentials/x.pdf',
            'original_name' => 'carteira.pdf',
        ]);

        $response = $this->actingAs($this->admin())
            ->getJson("/api/admin/credentials/{$credential->id}")
            ->assertStatus(200)
            ->assertJsonStructure([
                'credential' => ['id', 'status', 'crp_number'],
                'documents'  => [['id', 'kind', 'original_name', 'url']],
            ]);

        $this->assertStringContainsString('signature=', $response->json('documents.0.url'));
    }

    public function test_document_route_rejects_missing_signature(): void
    {
        $credential = $this->submittedCredentialForNewPro();
        $doc = $credential->documents()->create([
            'kind'          => CredentialDocument::KIND_CRP_CARD,
            'storage_path'  => 'credentials/x.pdf',
            'original_name' => 'carteira.pdf',
        ]);

        // Sem assinatura → 403 do middleware `signed`.
        $this->get("/api/admin/credential-documents/{$doc->id}")->assertStatus(403);
    }

    public function test_document_route_serves_file_with_valid_signature(): void
    {
        Storage::fake('local');
        $credential = $this->submittedCredentialForNewPro();

        $path = UploadedFile::fake()->create('crp.pdf', 100, 'application/pdf')
            ->store('credentials/' . $credential->user_id, 'local');

        $doc = $credential->documents()->create([
            'kind'          => CredentialDocument::KIND_CRP_CARD,
            'storage_path'  => $path,
            'original_name' => 'crp.pdf',
            'mime'          => 'application/pdf',
        ]);

        $url = URL::temporarySignedRoute(
            'admin.credential-document',
            now()->addMinutes(5),
            ['document' => $doc->id]
        );

        $this->get($url)->assertStatus(200);
    }
}