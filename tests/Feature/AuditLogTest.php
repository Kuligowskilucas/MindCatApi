<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ProfessionalCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function submittedCredential(): ProfessionalCredential
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

    public function test_approving_credential_records_audit_log(): void
    {
        $credential = $this->submittedCredential();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson("/api/admin/credentials/{$credential->id}/approve")
            ->assertStatus(200);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id'       => $admin->id,
            'action'         => 'credential.approved',
            'auditable_type' => ProfessionalCredential::class,
            'auditable_id'   => $credential->id,
        ]);
    }

    public function test_rejecting_credential_records_reason_in_metadata(): void
    {
        $credential = $this->submittedCredential();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson("/api/admin/credentials/{$credential->id}/reject", ['reason' => 'CRP inválido'])
            ->assertStatus(200);

        $log = AuditLog::where('action', 'credential.rejected')->first();

        $this->assertNotNull($log);
        $this->assertSame($admin->id, $log->actor_id);
        $this->assertSame('CRP inválido', $log->metadata['reason']);
    }

    public function test_failed_action_records_no_audit_log(): void
    {
        $credential = $this->submittedCredential();
        $credential->update(['status' => ProfessionalCredential::STATUS_APPROVED]);
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson("/api/admin/credentials/{$credential->id}/approve")
            ->assertStatus(409);

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_admin_can_list_audit_logs(): void
    {
        $credential = $this->submittedCredential();
        $admin = $this->admin();
        $this->actingAs($admin)->postJson("/api/admin/credentials/{$credential->id}/approve");

        $this->actingAs($admin)->getJson('/api/admin/audit-logs')
            ->assertStatus(200)
            ->assertJsonPath('data.0.action', 'credential.approved')
            ->assertJsonPath('data.0.actor.id', $admin->id);
    }

    public function test_non_admin_cannot_list_audit_logs(): void
    {
        $pro = User::factory()->pro()->create();

        $this->actingAs($pro)->getJson('/api/admin/audit-logs')->assertStatus(403);
    }
}
