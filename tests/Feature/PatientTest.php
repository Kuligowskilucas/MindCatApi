<?php

namespace Tests\Feature;

use App\Models\Feeling;
use App\Models\ProPatientLink;
use App\Models\Task;
use App\Models\User;
use App\Models\UserMoodTracking;
use App\Models\UserProfile;
use Carbon\Carbon;
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function pro_can_get_linked_patient_summary(): void
    {
        [$pro, $patient] = $this->createLinkedProAndPatient();

        UserMoodTracking::create([
            'user_id'     => $patient->id,
            'mood_level'  => 4,
            'thought'     => 'Consegui dar conta da semana.',
            'behavior'    => 'Fiz o exercício que a psicóloga passou.',
            'recorded_at' => now(),
        ]);

        Task::create([
            'pro_id'       => $pro->id,
            'patient_id'   => $patient->id,
            'title'        => 'Respirar',
            'status'       => 'done',
            'completed_at' => now(),
        ]);

        Task::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'title'      => 'Diário',
            'status'     => 'active',
        ]);

        $response = $this->actingAs($pro)->getJson("/api/patients/{$patient->id}/summary");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'patient' => ['id', 'name'],
                'moods'   => [['id', 'mood_level', 'thought', 'behavior', 'recorded_at']],
                'feelings_frequency',
                'range_days',
                'exercises_completed',
            ])
            ->assertJsonPath('moods.0.thought', 'Consegui dar conta da semana.')
            ->assertJsonPath('moods.0.behavior', 'Fiz o exercício que a psicóloga passou.')
            ->assertJsonPath('exercises_completed', 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function mood_outside_the_window_does_not_appear_in_summary(): void
    {
        [$pro, $patient] = $this->createLinkedProAndPatient();

        UserMoodTracking::create([
            'user_id'     => $patient->id,
            'mood_level'  => 2,
            'recorded_at' => Carbon::now()->subDays(40),
        ]);

        $inWindow = UserMoodTracking::create([
            'user_id'     => $patient->id,
            'mood_level'  => 4,
            'recorded_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($pro)->getJson("/api/patients/{$patient->id}/summary");

        $response->assertStatus(200)
            ->assertJsonPath('range_days', 30);

        $ids = collect($response->json('moods'))->pluck('id')->all();
        $this->assertEquals([$inWindow->id], $ids);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function feelings_frequency_counts_correctly_and_ignores_moods_outside_the_window(): void
    {
        [$pro, $patient] = $this->createLinkedProAndPatient();

        $ansioso = Feeling::create(['slug' => 'ansioso', 'label' => 'Ansioso', 'sort_order' => 1]);
        $calmo = Feeling::create(['slug' => 'calmo', 'label' => 'Calmo', 'sort_order' => 10]);

        $moodInWindow1 = UserMoodTracking::create([
            'user_id'     => $patient->id,
            'mood_level'  => 3,
            'recorded_at' => Carbon::now(),
        ]);
        $moodInWindow1->feelings()->attach([$ansioso->id, $calmo->id]);

        $moodInWindow2 = UserMoodTracking::create([
            'user_id'     => $patient->id,
            'mood_level'  => 4,
            'recorded_at' => Carbon::now()->subDays(5),
        ]);
        $moodInWindow2->feelings()->attach([$ansioso->id]);

        $moodOutsideWindow = UserMoodTracking::create([
            'user_id'     => $patient->id,
            'mood_level'  => 1,
            'recorded_at' => Carbon::now()->subDays(40),
        ]);
        $moodOutsideWindow->feelings()->attach([$ansioso->id, $calmo->id]);

        $response = $this->actingAs($pro)->getJson("/api/patients/{$patient->id}/summary");

        $response->assertStatus(200)
            ->assertJsonPath('feelings_frequency.0.slug', 'ansioso')
            ->assertJsonPath('feelings_frequency.0.count', 2)
            ->assertJsonPath('feelings_frequency.1.slug', 'calmo')
            ->assertJsonPath('feelings_frequency.1.count', 1);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function invalid_days_falls_back_to_default(): void
    {
        [$pro, $patient] = $this->createLinkedProAndPatient();

        $response = $this->actingAs($pro)->getJson("/api/patients/{$patient->id}/summary?days=500");

        $response->assertStatus(200)
            ->assertJsonPath('range_days', 30);

        $response = $this->actingAs($pro)->getJson("/api/patients/{$patient->id}/summary?days=abc");

        $response->assertStatus(200)
            ->assertJsonPath('range_days', 30);
    }

    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
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

    #[\PHPUnit\Framework\Attributes\Test]
    public function patient_cannot_access_summary_endpoint(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->getJson("/api/patients/{$patient->id}/summary");

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function summary_requires_authentication(): void
    {
        $this->getJson('/api/patients/1/summary')->assertStatus(401);
    }
}
