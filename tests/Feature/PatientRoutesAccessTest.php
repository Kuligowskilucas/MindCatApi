<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PatientRoutesAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function patientOnlyRoutes(): array
    {
        $routes = [
            'POST /diary'                  => ['post', '/api/diary'],
            'POST /diary/list'             => ['post', '/api/diary/list'],
            'DELETE /diary/{id}'           => ['delete', '/api/diary/999'],
            'POST /moods'                  => ['post', '/api/moods'],
            'GET /moods'                   => ['get', '/api/moods'],
            'DELETE /moods/{id}'           => ['delete', '/api/moods/999'],
            'PUT /profile/diary-password'  => ['put', '/api/profile/diary-password'],
            'PATCH /tasks/{task}/done'     => ['patch', '/api/tasks/{task}/done'],
        ];

        $cases = [];
        foreach (['pro', 'admin'] as $role) {
            foreach ($routes as $label => [$method, $uri]) {
                $cases["{$role} {$label}"] = [$role, $method, $uri];
            }
        }

        return $cases;
    }

    #[Test]
    #[DataProvider('patientOnlyRoutes')]
    public function non_patient_roles_are_forbidden(string $role, string $method, string $uri): void
    {
        $user = $role === 'pro'
            ? User::factory()->pro()->create()
            : User::factory()->create(['role' => 'admin']);

        if (str_contains($uri, '{task}')) {
            $task = Task::create([
                'pro_id'     => User::factory()->pro()->create()->id,
                'patient_id' => User::factory()->patient()->create()->id,
                'title'      => 'Tarefa',
                'status'     => 'active',
            ]);
            $uri = str_replace('{task}', (string) $task->id, $uri);
        }

        $this->actingAs($user)->json($method, $uri)->assertStatus(403);
    }
}
