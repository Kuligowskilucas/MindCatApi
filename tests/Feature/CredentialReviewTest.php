<?php

namespace Tests\Feature;

use App\Models\ProfessionalCredential;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CredentialReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mindcat.credential.grace_days' => 7]);
    }

    private function proWithReview(?CarbonInterface $nextReviewAt): User
    {
        $pro = User::factory()->pro()->create();
        $pro->credential->update(['next_review_at' => $nextReviewAt]);

        return $pro;
    }

    private function statusOf(User $pro): string
    {
        return $pro->credential()->first()->status;
    }

    public function test_command_expires_credential_past_grace(): void
    {
        $pro = $this->proWithReview(now()->subDays(8));

        $this->artisan('mindcat:review-credentials')->assertSuccessful();

        $this->assertSame(ProfessionalCredential::STATUS_EXPIRED, $this->statusOf($pro));
    }

    public function test_command_does_not_expire_within_grace(): void
    {
        $pro = $this->proWithReview(now()->subDays(3));

        $this->artisan('mindcat:review-credentials')->assertSuccessful();

        $this->assertSame(ProfessionalCredential::STATUS_APPROVED, $this->statusOf($pro));
    }

    public function test_command_does_not_expire_fresh_approval(): void
    {
        $pro = $this->proWithReview(now()->addYear());

        $this->artisan('mindcat:review-credentials')->assertSuccessful();

        $this->assertSame(ProfessionalCredential::STATUS_APPROVED, $this->statusOf($pro));
    }

    public function test_command_ignores_null_next_review_at(): void
    {
        $pro = User::factory()->pro()->create();

        $this->artisan('mindcat:review-credentials')->assertSuccessful();

        $this->assertSame(ProfessionalCredential::STATUS_APPROVED, $this->statusOf($pro));
    }

    public function test_command_is_idempotent(): void
    {
        $pro = $this->proWithReview(now()->subDays(10));

        $this->artisan('mindcat:review-credentials')->assertSuccessful();
        $this->artisan('mindcat:review-credentials')->assertSuccessful();

        $this->assertSame(ProfessionalCredential::STATUS_EXPIRED, $this->statusOf($pro));
    }

    public function test_dry_run_does_not_write(): void
    {
        $pro = $this->proWithReview(now()->subDays(10));

        $this->artisan('mindcat:review-credentials', ['--dry-run' => true])->assertSuccessful();

        $this->assertSame(ProfessionalCredential::STATUS_APPROVED, $this->statusOf($pro));
    }

    public function test_gate_blocks_after_grace(): void
    {
        $pro = $this->proWithReview(now()->subDays(8));

        $this->actingAs($pro)->getJson('/api/patients')
            ->assertStatus(403)
            ->assertJson(['code' => 'credential_not_approved']);
    }

    public function test_gate_allows_within_grace(): void
    {
        $pro = $this->proWithReview(now()->subDays(3));

        $this->actingAs($pro)->getJson('/api/patients')->assertStatus(200);
    }

    public function test_gate_allows_fresh_approval(): void
    {
        $pro = $this->proWithReview(now()->addYear());

        $this->actingAs($pro)->getJson('/api/patients')->assertStatus(200);
    }

    public function test_gate_allows_null_next_review_at(): void
    {
        $pro = User::factory()->pro()->create();

        $this->actingAs($pro)->getJson('/api/patients')->assertStatus(200);
    }

    public function test_expired_pro_can_resubmit(): void
    {
        Storage::fake('local');

        $pro = $this->proWithReview(now()->subDays(10));
        $pro->credential->update(['status' => ProfessionalCredential::STATUS_EXPIRED]);

        $this->actingAs($pro)->postJson('/api/credentials', [
            'crp_number'      => '06/123456',
            'crp_region'      => '06',
            'epsi_registered' => true,
            'crp_document'    => UploadedFile::fake()->create('crp.pdf', 100, 'application/pdf'),
            'epsi_document'   => UploadedFile::fake()->create('epsi.pdf', 100, 'application/pdf'),
        ])->assertStatus(201)
          ->assertJsonPath('status', ProfessionalCredential::STATUS_SUBMITTED);
    }
}