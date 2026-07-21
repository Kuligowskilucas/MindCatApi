<?php

namespace Tests\Feature;

use App\Models\ProPatientLink;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkTest extends TestCase
{
    use RefreshDatabase;

    private function createProAndConsentedPatient(): array
    {
        $pro = User::factory()->pro()->create();
        $patient = User::factory()->patient()->create();
        UserProfile::create([
            'user_id'                          => $patient->id,
            'consent_share_with_professional'  => true,
        ]);
        return [$pro, $patient];
    }

    // ─── STORE (LINK) ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function pro_can_link_consented_patient(): void
    {
        [$pro, $patient] = $this->createProAndConsentedPatient();

        $response = $this->actingAs($pro)->postJson('/api/links', [
            'patient_id' => $patient->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('pro_patient_links', [
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'active'     => true,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pro_cannot_link_patient_without_consent(): void
    {
        $pro = User::factory()->pro()->create();
        $patient = User::factory()->patient()->create();
        UserProfile::create([
            'user_id'                          => $patient->id,
            'consent_share_with_professional'  => false,
        ]);

        $response = $this->actingAs($pro)->postJson('/api/links', [
            'patient_id' => $patient->id,
        ]);

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pro_cannot_link_another_pro(): void
    {
        $pro1 = User::factory()->pro()->create();
        $pro2 = User::factory()->pro()->create();

        $response = $this->actingAs($pro1)->postJson('/api/links', [
            'patient_id' => $pro2->id,
        ]);

        $response->assertStatus(422);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function patient_cannot_create_link(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->postJson('/api/links', [
            'patient_id' => $patient->id,
        ]);

        $response->assertStatus(403);
    }

    // ─── SEARCH PATIENT ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function pro_can_search_patient_by_email(): void
    {
        $pro = User::factory()->pro()->create();
        $patient = User::factory()->patient()->create(['email' => 'busca@teste.com']);
        UserProfile::create([
            'user_id'                          => $patient->id,
            'consent_share_with_professional'  => true,
        ]);

        $response = $this->actingAs($pro)->getJson('/api/patients/search?email=busca@teste.com');

        $response->assertStatus(200)
            ->assertJson([
                'email'   => 'busca@teste.com',
                'consent' => true,
            ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function search_returns_404_for_nonexistent_patient(): void
    {
        $pro = User::factory()->pro()->create();

        $response = $this->actingAs($pro)->getJson('/api/patients/search?email=naoexiste@teste.com');

        $response->assertStatus(404);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function search_does_not_find_pros(): void
    {
        $pro1 = User::factory()->pro()->create();
        $pro2 = User::factory()->pro()->create(['email' => 'outro@pro.com']);

        $response = $this->actingAs($pro1)->getJson('/api/patients/search?email=outro@pro.com');

        $response->assertStatus(404);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function patient_cannot_search(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->getJson('/api/patients/search?email=x@x.com');

        $response->assertStatus(403);
    }

    // ─── INDEX PATIENTS ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function pro_can_list_linked_patients(): void
    {
        [$pro, $patient] = $this->createProAndConsentedPatient();

        ProPatientLink::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'active'     => true,
        ]);

        $response = $this->actingAs($pro)->getJson('/api/patients');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pro_doesnt_see_inactive_links(): void
    {
        [$pro, $patient] = $this->createProAndConsentedPatient();

        ProPatientLink::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'active'     => false,
        ]);

        $response = $this->actingAs($pro)->getJson('/api/patients');
        $this->assertCount(0, $response->json('data'));
    }

    // ─── DESTROY (UNLINK) ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function pro_can_unlink_patient(): void
    {
        [$pro, $patient] = $this->createProAndConsentedPatient();

        ProPatientLink::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'active'     => true,
        ]);

        $response = $this->actingAs($pro)->deleteJson("/api/links/{$patient->id}");

        $response->assertStatus(200);
        $this->assertDatabaseHas('pro_patient_links', [
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'active'     => false,
        ]);
    }

    // ─── INDEX PROFESSIONALS (PATIENT) ───
    #[\PHPUnit\Framework\Attributes\Test]
    public function patient_can_list_linked_professionals(): void
    {
        [$pro, $patient] = $this->createProAndConsentedPatient();

        ProPatientLink::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'active'     => true,
        ]);

        $response = $this->actingAs($patient)->getJson('/api/my-professionals');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pro_cannot_access_my_professionals(): void
    {
        $pro = User::factory()->pro()->create();

        $response = $this->actingAs($pro)->getJson('/api/my-professionals');

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function links_require_authentication(): void
    {
        $this->postJson('/api/links', [])->assertStatus(401);
        $this->getJson('/api/patients')->assertStatus(401);
        $this->getJson('/api/my-professionals')->assertStatus(401);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function pro_stops_seeing_patient_who_revokes_consent(): void
    {
        [$pro, $patient] = $this->createProAndConsentedPatient();
    
        ProPatientLink::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'active'     => true,
        ]);
    
        $this->assertCount(1, $this->actingAs($pro)->getJson('/api/patients')->json('data'));
    
        // Paciente revoga o consentimento.
        $patient->profile->update(['consent_share_with_professional' => false]);
    
        $this->assertCount(0, $this->actingAs($pro)->getJson('/api/patients')->json('data'));
    }
}
