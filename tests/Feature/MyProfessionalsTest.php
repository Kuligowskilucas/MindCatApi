<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\ProfessionalCredential;
use App\Models\ProPatientLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MyProfessionalsTest extends TestCase
{
    use RefreshDatabase;

    private function link(User $pro, User $patient, bool $active = true): ProPatientLink
    {
        return ProPatientLink::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'active'     => $active,
        ]);
    }

    #[Test]
    public function patient_lists_active_professionals_with_badge_and_without_email(): void
    {
        $patient = User::factory()->patient()->create();

        $psychologist = User::factory()->pro()->create(['name' => 'Ana']);
        $psychiatrist = User::factory()->pro()->create(['name' => 'Bruno']);
        $psychiatrist->credential->update([
            'profession' => ProfessionalCredential::PROFESSION_PSYCHIATRIST,
            'council'    => ProfessionalCredential::COUNCIL_CRM,
            'rqe_number' => '98765',
        ]);
        $unverified = User::factory()->unverifiedPro()->create(['name' => 'Carla']);

        $this->link($psychologist, $patient);
        $this->link($psychiatrist, $patient);
        $this->link($unverified, $patient);

        $response = $this->actingAs($patient)->getJson('/api/my-professionals')
            ->assertStatus(200)
            ->assertExactJson([
                'data' => [
                    ['id' => $psychologist->id, 'name' => 'Ana', 'badge' => ['verified' => true, 'label' => 'Psicólogo(a)']],
                    ['id' => $psychiatrist->id, 'name' => 'Bruno', 'badge' => ['verified' => true, 'label' => 'Psiquiatra']],
                    ['id' => $unverified->id, 'name' => 'Carla', 'badge' => ['verified' => false, 'label' => 'Profissional']],
                ],
            ]);

        $body = $response->getContent();
        $this->assertStringNotContainsString($psychologist->email, $body);
        $this->assertStringNotContainsString('06/000000', $body);
    }

    #[Test]
    public function listing_excludes_inactive_links_and_other_patients_links(): void
    {
        $patient = User::factory()->patient()->create();
        $other = User::factory()->patient()->create();

        $inactive = User::factory()->pro()->create();
        $othersPro = User::factory()->pro()->create();

        $this->link($inactive, $patient, active: false);
        $this->link($othersPro, $other);

        $this->actingAs($patient)->getJson('/api/my-professionals')
            ->assertStatus(200)
            ->assertExactJson(['data' => []]);
    }

    #[Test]
    public function patient_can_unlink_professional_and_it_is_audited(): void
    {
        $patient = User::factory()->patient()->create();
        $pro = User::factory()->pro()->create();
        $link = $this->link($pro, $patient);

        $this->actingAs($patient)->deleteJson("/api/my-professionals/{$pro->id}")
            ->assertStatus(200);

        $this->assertFalse((bool) $link->fresh()->active);

        $this->assertDatabaseHas('audit_logs', [
            'actor_id'       => $patient->id,
            'action'         => 'link.deactivated_by_patient',
            'auditable_type' => ProPatientLink::class,
            'auditable_id'   => $link->id,
        ]);

        $this->actingAs($patient)->getJson('/api/my-professionals')
            ->assertExactJson(['data' => []]);

        $this->actingAs($pro)->getJson('/api/patients')
            ->assertJsonCount(0, 'data');
    }

    #[Test]
    public function patient_cannot_unlink_another_patients_link(): void
    {
        $patient = User::factory()->patient()->create();
        $other = User::factory()->patient()->create();
        $pro = User::factory()->pro()->create();
        $othersLink = $this->link($pro, $other);

        $this->actingAs($patient)->deleteJson("/api/my-professionals/{$pro->id}")
            ->assertStatus(404);

        $this->assertTrue((bool) $othersLink->fresh()->active);
        $this->assertSame(0, AuditLog::where('action', 'link.deactivated_by_patient')->count());
    }

    #[Test]
    public function unlinking_unknown_or_inactive_link_returns_404_without_audit(): void
    {
        $patient = User::factory()->patient()->create();
        $pro = User::factory()->pro()->create();
        $this->link($pro, $patient, active: false);

        $this->actingAs($patient)->deleteJson("/api/my-professionals/{$pro->id}")->assertStatus(404);
        $this->actingAs($patient)->deleteJson('/api/my-professionals/999999')->assertStatus(404);
        $this->actingAs($patient)->deleteJson('/api/my-professionals/abc')->assertStatus(404);

        $this->assertSame(0, AuditLog::where('action', 'link.deactivated_by_patient')->count());
    }

    #[Test]
    public function pro_and_admin_cannot_use_my_professionals(): void
    {
        $pro = User::factory()->pro()->create();
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([$pro, $admin] as $user) {
            $this->actingAs($user)->getJson('/api/my-professionals')->assertStatus(403);
            $this->actingAs($user)->deleteJson("/api/my-professionals/{$pro->id}")->assertStatus(403);
        }
    }

    #[Test]
    public function my_professionals_requires_authentication(): void
    {
        $this->getJson('/api/my-professionals')->assertStatus(401);
        $this->deleteJson('/api/my-professionals/1')->assertStatus(401);
    }
}
