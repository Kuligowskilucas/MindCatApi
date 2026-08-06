<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class LoggingObservabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_response_carries_a_request_id_header(): void
    {
        $res = $this->getJson('/api/me');

        $res->assertHeader('X-Request-Id');
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/',
            (string) $res->headers->get('X-Request-Id'),
        );
    }

    public function test_sql_logged_with_placeholders_and_no_values_when_enabled(): void
    {
        config(['logging.queries' => true]);
        Log::spy();

        DB::table('users')->where('email', 'secreto@exemplo.com')->get();

        Log::shouldHaveReceived('debug')->withArgs(function (string $message, array $context) {
            return $message === 'query'
                && isset($context['sql'])
                && ! array_key_exists('bindings', $context)
                && ! str_contains($context['sql'], 'secreto@exemplo.com');
        });
    }

    public function test_sql_not_logged_when_disabled(): void
    {
        config(['logging.queries' => false]);
        Log::spy();

        DB::table('users')->get();

        Log::shouldNotHaveReceived('debug', ['query', Mockery::any()]);
    }
}