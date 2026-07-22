<?php

namespace Tests\Feature;

use App\Models\PatientInvite;
use App\Models\ProPatientLink;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InviteTest extends TestCase
{
    use RefreshDatabase;

    private function patientWithConsent(bool $consent = true): User
    {
        $patient = User::factory()->patient()->create();

        UserProfile::create([
            'user_id'                         => $patient->id,
            'consent_share_with_professional' => $consent,
        ]);

        return $patient;
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function patient_can_generate_invite(): void
    {
        $patient = $this->patientWithConsent();

        $response = $this->actingAs($patient)->postJson('/api/invites');

        $response->assertStatus(201)
            ->assertJsonStructure(['code', 'expires_at']);

        $this->assertDatabaseHas('patient_invites', [
            'patient_id' => $patient->id,
            'status'     => PatientInvite::STATUS_ACTIVE,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function generating_invite_requires_consent(): void
    {
        $patient = $this->patientWithConsent(false);

        $response = $this->actingAs($patient)->postJson('/api/invites');

        $response->assertStatus(403);

        $this->assertDatabaseCount('patient_invites', 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function generating_invite_revokes_previous_active(): void
    {
        $patient = $this->patientWithConsent();

        $first = $this->actingAs($patient)->postJson('/api/invites')->json('code');
        $second = $this->actingAs($patient)->postJson('/api/invites')->json('code');

        $this->assertNotSame($first, $second);

        $this->assertDatabaseHas('patient_invites', [
            'code'   => $first,
            'status' => PatientInvite::STATUS_REVOKED,
        ]);
        $this->assertSame(
            1,
            PatientInvite::where('patient_id', $patient->id)
                ->where('status', PatientInvite::STATUS_ACTIVE)
                ->count()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function patient_can_list_active_invite(): void
    {
        $patient = $this->patientWithConsent();
        $invite = PatientInvite::factory()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($patient)->getJson('/api/invites');

        $response->assertStatus(200)
            ->assertJson(['invite' => ['code' => $invite->code]]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function list_returns_null_when_no_active_invite(): void
    {
        $patient = $this->patientWithConsent();

        $response = $this->actingAs($patient)->getJson('/api/invites');

        $response->assertStatus(200)
            ->assertExactJson(['invite' => null]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function listing_lazily_expires_a_stale_invite(): void
    {
        $patient = $this->patientWithConsent();
        $invite = PatientInvite::factory()->expired()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($patient)->getJson('/api/invites');

        $response->assertStatus(200)
            ->assertExactJson(['invite' => null]);

        $this->assertDatabaseHas('patient_invites', [
            'id'     => $invite->id,
            'status' => PatientInvite::STATUS_EXPIRED,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function patient_can_revoke_active_invite(): void
    {
        $patient = $this->patientWithConsent();
        $invite = PatientInvite::factory()->create(['patient_id' => $patient->id]);

        $this->actingAs($patient)->deleteJson('/api/invites')->assertStatus(200);

        $this->assertDatabaseHas('patient_invites', [
            'id'     => $invite->id,
            'status' => PatientInvite::STATUS_REVOKED,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pro_cannot_generate_invite(): void
    {
        $pro = User::factory()->pro()->create();

        $this->actingAs($pro)->postJson('/api/invites')->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pro_can_redeem_invite_and_create_link(): void
    {
        $pro = User::factory()->pro()->create();
        $patient = $this->patientWithConsent();
        $invite = PatientInvite::factory()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($pro)
            ->postJson('/api/invites/redeem', ['code' => $invite->code]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('pro_patient_links', [
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'active'     => true,
        ]);

        $invite->refresh();
        $this->assertSame(PatientInvite::STATUS_USED, $invite->status);
        $this->assertSame($pro->id, $invite->used_by_pro_id);
        $this->assertNotNull($invite->used_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function redeem_normalizes_case_and_whitespace(): void
    {
        $pro = User::factory()->pro()->create();
        $patient = $this->patientWithConsent();
        $invite = PatientInvite::factory()->create(['patient_id' => $patient->id]);

        $response = $this->actingAs($pro)->postJson('/api/invites/redeem', [
            'code' => '  ' . strtolower($invite->code) . '  ',
        ]);

        $response->assertStatus(201);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function redeem_fails_for_invalid_code(): void
    {
        $pro = User::factory()->pro()->create();

        $this->actingAs($pro)
            ->postJson('/api/invites/redeem', ['code' => 'ABCDEFGH'])
            ->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function redeem_fails_for_expired_code(): void
    {
        $pro = User::factory()->pro()->create();
        $patient = $this->patientWithConsent();
        $invite = PatientInvite::factory()->expired()->create(['patient_id' => $patient->id]);

        $this->actingAs($pro)
            ->postJson('/api/invites/redeem', ['code' => $invite->code])
            ->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function redeem_fails_for_used_code(): void
    {
        $pro = User::factory()->pro()->create();
        $patient = $this->patientWithConsent();
        $invite = PatientInvite::factory()->used()->create(['patient_id' => $patient->id]);

        $this->actingAs($pro)
            ->postJson('/api/invites/redeem', ['code' => $invite->code])
            ->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function redeem_fails_for_revoked_code(): void
    {
        $pro = User::factory()->pro()->create();
        $patient = $this->patientWithConsent();
        $invite = PatientInvite::factory()->revoked()->create(['patient_id' => $patient->id]);

        $this->actingAs($pro)
            ->postJson('/api/invites/redeem', ['code' => $invite->code])
            ->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function redeeming_twice_does_not_create_a_second_link(): void
    {
        $pro = User::factory()->pro()->create();
        $patient = $this->patientWithConsent();
        $invite = PatientInvite::factory()->create(['patient_id' => $patient->id]);

        $this->actingAs($pro)
            ->postJson('/api/invites/redeem', ['code' => $invite->code])
            ->assertStatus(201);

        $this->actingAs($pro)
            ->postJson('/api/invites/redeem', ['code' => $invite->code])
            ->assertStatus(422);

        $this->assertSame(
            1,
            ProPatientLink::where('pro_id', $pro->id)
                ->where('patient_id', $patient->id)
                ->count()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function redeem_fails_when_consent_revoked_after_generation(): void
    {
        $pro = User::factory()->pro()->create();
        $patient = $this->patientWithConsent();
        $invite = PatientInvite::factory()->create(['patient_id' => $patient->id]);

        $patient->profile->update(['consent_share_with_professional' => false]);

        $this->actingAs($pro)
            ->postJson('/api/invites/redeem', ['code' => $invite->code])
            ->assertStatus(403);

        $invite->refresh();
        $this->assertSame(PatientInvite::STATUS_ACTIVE, $invite->status);
        $this->assertNull($invite->used_at);
        $this->assertDatabaseCount('pro_patient_links', 0);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function redeem_reactivates_a_previously_unlinked_patient(): void
    {
        $pro = User::factory()->pro()->create();
        $patient = $this->patientWithConsent();

        ProPatientLink::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'active'     => false,
        ]);

        $invite = PatientInvite::factory()->create(['patient_id' => $patient->id]);

        $this->actingAs($pro)
            ->postJson('/api/invites/redeem', ['code' => $invite->code])
            ->assertStatus(201);

        $this->assertDatabaseHas('pro_patient_links', [
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'active'     => true,
        ]);
        $this->assertSame(
            1,
            ProPatientLink::where('pro_id', $pro->id)
                ->where('patient_id', $patient->id)
                ->count()
        );
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function unverified_pro_cannot_redeem(): void
    {
        $pro = User::factory()->unverifiedPro()->create();
        $patient = $this->patientWithConsent();
        $invite = PatientInvite::factory()->create(['patient_id' => $patient->id]);

        $this->actingAs($pro)
            ->postJson('/api/invites/redeem', ['code' => $invite->code])
            ->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function patient_cannot_redeem(): void
    {
        $patient = $this->patientWithConsent();

        $this->actingAs($patient)
            ->postJson('/api/invites/redeem', ['code' => 'ABCDEFGH'])
            ->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function redeem_is_rate_limited(): void
    {
        $pro = User::factory()->pro()->create();

        for ($i = 0; $i < 6; $i++) {
            $this->actingAs($pro)
                ->postJson('/api/invites/redeem', ['code' => 'ZZZZZZZZ'])
                ->assertStatus(422);
        }

        $this->actingAs($pro)
            ->postJson('/api/invites/redeem', ['code' => 'ZZZZZZZZ'])
            ->assertStatus(429);
    }
}