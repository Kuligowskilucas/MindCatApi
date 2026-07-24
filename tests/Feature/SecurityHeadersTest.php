<?php

namespace Tests\Feature;

use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\Test]
    public function security_headers_are_present_on_responses(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'no-referrer');
        $response->assertHeader('Content-Security-Policy', "frame-ancestors 'none'");
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function hsts_is_absent_over_plain_http(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertHeaderMissing('Strict-Transport-Security');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function forwarded_proto_from_trusted_proxy_marks_request_secure(): void
    {
        $response = $this->getJson('/api/me', ['X-Forwarded-Proto' => 'https']);

        $response->assertHeader(
            'Strict-Transport-Security',
            'max-age=31536000; includeSubDomains'
        );
    }
}