<?php

namespace Tests\Feature;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function up_returns_ok_when_database_is_reachable(): void
    {
        $this->getJson('/up')
            ->assertOk()
            ->assertExactJson(['status' => 'ok']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function up_returns_503_when_database_is_unreachable(): void
    {
        config(['database.default' => 'connection_that_does_not_exist']);

        $this->getJson('/up')
            ->assertStatus(503)
            ->assertExactJson(['status' => 'error']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function up_is_public_and_needs_no_authentication(): void
    {
        $this->getJson('/up')->assertOk();
    }
}