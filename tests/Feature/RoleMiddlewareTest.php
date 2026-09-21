<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function allows_user_with_matching_role(): void
    {
        $pro = User::factory()->pro()->create();

        $response = $this->actingAs($pro)->getJson('/api/credentials/me');

        $response->assertStatus(200);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function denies_user_with_other_role(): void
    {
        $patient = User::factory()->create(['role' => 'patient']);

        $response = $this->actingAs($patient)->getJson('/api/credentials/me');

        $response->assertStatus(403);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function denies_unknown_role_value_instead_of_erroring(): void
    {
        $user = User::factory()->make(['role' => 'pro']);
        $user->setRawAttributes(array_merge($user->getAttributes(), ['role' => 'superadmin']));
        $user->save();

        $response = $this->actingAs($user)->getJson('/api/credentials/me');

        $response->assertStatus(403);
    }
}
