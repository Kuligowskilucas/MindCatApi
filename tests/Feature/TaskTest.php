<?php

namespace Tests\Feature;

use App\Models\ProPatientLink;
use App\Models\Task;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
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

    // ─── STORE (PRO) ───

    /** @test */
    public function pro_can_create_task_for_linked_patient(): void
    {
        [$pro, $patient] = $this->createLinkedProAndPatient();

        $response = $this->actingAs($pro)->postJson('/api/tasks', [
            'patient_id' => $patient->id,
            'title'      => 'Exercício de respiração',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'title'  => 'Exercício de respiração',
                'status' => 'active',
            ]);
    }

    /** @test */
    public function pro_cannot_create_task_for_unlinked_patient(): void
    {
        $pro = User::factory()->pro()->create();
        $patient = User::factory()->patient()->create();
        UserProfile::create([
            'user_id'                          => $patient->id,
            'consent_share_with_professional'  => true,
        ]);

        $response = $this->actingAs($pro)->postJson('/api/tasks', [
            'patient_id' => $patient->id,
            'title'      => 'Tarefa',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function patient_cannot_create_task(): void
    {
        $patient = User::factory()->patient()->create();

        $response = $this->actingAs($patient)->postJson('/api/tasks', [
            'patient_id' => $patient->id,
            'title'      => 'Tarefa',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function task_title_is_required(): void
    {
        [$pro, $patient] = $this->createLinkedProAndPatient();

        $response = $this->actingAs($pro)->postJson('/api/tasks', [
            'patient_id' => $patient->id,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function task_title_max_120_chars(): void
    {
        [$pro, $patient] = $this->createLinkedProAndPatient();

        $response = $this->actingAs($pro)->postJson('/api/tasks', [
            'patient_id' => $patient->id,
            'title'      => str_repeat('a', 121),
        ]);

        $response->assertStatus(422);
    }

    // ─── INDEX ───

    /** @test */
    public function patient_sees_own_tasks(): void
    {
        [$pro, $patient] = $this->createLinkedProAndPatient();

        Task::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'title'      => 'Minha tarefa',
            'status'     => 'active',
        ]);

        $response = $this->actingAs($patient)->getJson('/api/tasks');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function pro_sees_assigned_tasks(): void
    {
        [$pro, $patient] = $this->createLinkedProAndPatient();

        Task::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'title'      => 'Tarefa criada',
            'status'     => 'active',
        ]);

        $response = $this->actingAs($pro)->getJson('/api/tasks?scope=assigned');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
    }

    /** @test */
    public function patient_doesnt_see_other_patients_tasks(): void
    {
        [$pro, $patient1] = $this->createLinkedProAndPatient();
        $patient2 = User::factory()->patient()->create();

        Task::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient1->id,
            'title'      => 'Tarefa do outro',
            'status'     => 'active',
        ]);

        $response = $this->actingAs($patient2)->getJson('/api/tasks');
        $this->assertCount(0, $response->json('data'));
    }

    // ─── MARK DONE (PATIENT) ───

    /** @test */
    public function patient_can_mark_own_task_done(): void
    {
        [$pro, $patient] = $this->createLinkedProAndPatient();

        $task = Task::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'title'      => 'Tarefa',
            'status'     => 'active',
        ]);

        $response = $this->actingAs($patient)->patchJson("/api/tasks/{$task->id}/done");

        $response->assertStatus(200)
            ->assertJson(['status' => 'done']);

        $this->assertNotNull($response->json('completed_at'));
    }

    /** @test */
    public function patient_cannot_mark_other_patients_task(): void
    {
        [$pro, $patient1] = $this->createLinkedProAndPatient();
        $patient2 = User::factory()->patient()->create();

        $task = Task::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient1->id,
            'title'      => 'Do outro',
            'status'     => 'active',
        ]);

        $response = $this->actingAs($patient2)->patchJson("/api/tasks/{$task->id}/done");
        $response->assertStatus(403);
    }

    // ─── DESTROY (PRO) ───

    /** @test */
    public function pro_can_delete_own_task(): void
    {
        [$pro, $patient] = $this->createLinkedProAndPatient();

        $task = Task::create([
            'pro_id'     => $pro->id,
            'patient_id' => $patient->id,
            'title'      => 'Deletar',
            'status'     => 'active',
        ]);

        $response = $this->actingAs($pro)->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('tasks', ['id' => $task->id]);
    }

    /** @test */
    public function pro_cannot_delete_other_pros_task(): void
    {
        [$pro1, $patient] = $this->createLinkedProAndPatient();
        $pro2 = User::factory()->pro()->create();

        $task = Task::create([
            'pro_id'     => $pro1->id,
            'patient_id' => $patient->id,
            'title'      => 'Do outro pro',
            'status'     => 'active',
        ]);

        $response = $this->actingAs($pro2)->deleteJson("/api/tasks/{$task->id}");
        $response->assertStatus(403);
    }

    /** @test */
    public function tasks_require_authentication(): void
    {
        $this->getJson('/api/tasks')->assertStatus(401);
        $this->postJson('/api/tasks', [])->assertStatus(401);
    }
}
