<?php

namespace Tests\Feature;

use App\Models\ProPatientLink;
use App\Models\Task;
use App\Models\User;
use App\Models\UserMoodTracking;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientTest extends TestCase
{
    use RefreshDatabase;

    private function createLinkedProAndPatient(): array
    {
        $pro = User::factory()->pro()->create();
        $patient = User::factory()->patient()->create();

        UserProfile::create([
            'user_id'                          => $patient->id,
            'consent_share_with_professional'  => true,
        ]);

        ProPatientLink::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'active'     => true,
        ]);

        return [$pro, $patient];
    }

    /** @test */
    public function pro_can_get_linked_patient_summary(): void
    {
        [$pro, $patient] = $this->createLinkedProAndPatient();

        UserMoodTracking::create([
            'user_id'     => $patient->id,
            'mood_level'  => 4,
            'recorded_at' => now(),
        ]);

        $response = $this->actingAs($pro)->getJson("/api/patients/{$patient->id}/summary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'patient' => ['id', 'name'],
                'moods',
                'exercises_completed',
                'diary',
            ]);
    }

    /** @test */
    public function pro_cannot_get_unlinked_patient_summary(): void
    {
        $pro = User::factory()->pro()->create();
        $patient = User::factory()->patient()->create();
        UserProfile::create([
            'user_id'                          => $patient->id,
            'consent_share_with_professional'  => true,
        ]);

        $response = $this->actingAs($pro)->getJson("/api/patients/{$patient->id}/summary");

        $response->assertStatus(403);
    }

    /** @test */
    public function pro_cannot_get_summary_of_patient_without_consent(): void
    {
        $pro = User::factory()->pro()->create();
        $patient = User::factory()->patient()->create();
        UserProfile::create([
            'user_id'                          => $patient->id,
            'consent_share_with_professional'  => false,
        ]);
        ProPatientLink::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'active'     => true,
        ]);

        $response = $this->actingAs($pro)->getJson("/api/patients/{$patient->id}/summary");

        $response->assertStatus(403);
    }

    /** @test */
    public function patient_cannot_access_summary_endpoint(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->getJson("/api/patients/{$patient->id}/summary");

        $response->assertStatus(403);
    }

    /** @test */
    public function summary_requires_authentication(): void
    {
        $this->getJson('/api/patients/1/summary')->assertStatus(401);
    }
}
