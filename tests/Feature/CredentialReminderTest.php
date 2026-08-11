<?php

namespace Tests\Feature;

use App\Models\ProfessionalCredential;
use App\Models\User;
use App\Notifications\CredentialReviewReminder;
use App\Services\AdminCredentialService;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CredentialReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'mindcat.credential.grace_days'    => 7,
            'mindcat.credential.reminder_days' => 14,
        ]);
    }

    private function proWithReview(?CarbonInterface $nextReviewAt): User
    {
        $pro = User::factory()->pro()->create();
        $pro->credential->update(['next_review_at' => $nextReviewAt]);

        return $pro;
    }

    public function test_reminds_pro_whose_review_is_within_window(): void
    {
        Notification::fake();

        $pro = $this->proWithReview(now()->addDays(10));

        $this->artisan('mindcat:remind-credential-review')->assertSuccessful();

        Notification::assertSentTo($pro, CredentialReviewReminder::class);
        $this->assertNotNull($pro->credential->fresh()->review_reminder_sent_at);
    }

    public function test_does_not_remind_twice(): void
    {
        Notification::fake();

        $this->proWithReview(now()->addDays(10));

        $this->artisan('mindcat:remind-credential-review')->assertSuccessful();

        Notification::fake();
        $this->artisan('mindcat:remind-credential-review')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_remind_outside_window(): void
    {
        Notification::fake();

        $this->proWithReview(now()->addDays(60));

        $this->artisan('mindcat:remind-credential-review')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_does_not_remind_already_overdue(): void
    {
        Notification::fake();

        $this->proWithReview(now()->subDays(1));

        $this->artisan('mindcat:remind-credential-review')->assertSuccessful();

        Notification::assertNothingSent();
    }

    public function test_reapproval_resets_the_reminder_flag(): void
    {
        Notification::fake();

        $pro = User::factory()->pro()->create();
        $pro->credential->update([
            'status'                  => ProfessionalCredential::STATUS_SUBMITTED,
            'review_reminder_sent_at' => now()->subMonths(11),
        ]);

        $admin = User::factory()->create(['role' => 'admin']);

        app(AdminCredentialService::class)->approve($pro->credential->fresh(), $admin);

        $this->assertNull($pro->credential->fresh()->review_reminder_sent_at);
    }
}
