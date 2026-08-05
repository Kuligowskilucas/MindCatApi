<?php

namespace Tests\Feature;

use App\Models\ProfessionalCredential;
use App\Models\User;
use App\Notifications\CredentialApproved;
use App\Notifications\CredentialRejected;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CredentialNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function submittedCredentialForNewPro(): ProfessionalCredential
    {
        $pro = User::factory()->unverifiedPro()->create();

        return ProfessionalCredential::create([
            'user_id'         => $pro->id,
            'crp_number'      => '06/123456',
            'crp_region'      => '06',
            'epsi_registered' => true,
            'status'          => ProfessionalCredential::STATUS_SUBMITTED,
            'submitted_at'    => now(),
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    public function test_approving_notifies_the_professional(): void
    {
        Notification::fake();
        $credential = $this->submittedCredentialForNewPro();

        $this->actingAs($this->admin())
            ->postJson("/api/admin/credentials/{$credential->id}/approve")
            ->assertStatus(200);

        Notification::assertSentTo($credential->user, CredentialApproved::class);
        Notification::assertNotSentTo($credential->user, CredentialRejected::class);
    }

    public function test_rejecting_notifies_the_professional_with_the_reason(): void
    {
        Notification::fake();
        $credential = $this->submittedCredentialForNewPro();

        $this->actingAs($this->admin())
            ->postJson("/api/admin/credentials/{$credential->id}/reject", [
                'reason' => 'Documento ilegível.',
            ])
            ->assertStatus(200);

        Notification::assertSentTo(
            $credential->user,
            CredentialRejected::class,
            function (CredentialRejected $notification) use ($credential) {
                $mail = $notification->toMail($credential->user);

                return collect($mail->introLines)
                    ->contains(fn (string $line) => str_contains($line, 'Documento ilegível.'));
            }
        );
        Notification::assertNotSentTo($credential->user, CredentialApproved::class);
    }

    public function test_non_decidable_credential_sends_nothing(): void
    {
        Notification::fake();
        $credential = $this->submittedCredentialForNewPro();
        $credential->update(['status' => ProfessionalCredential::STATUS_APPROVED]);

        $this->actingAs($this->admin())
            ->postJson("/api/admin/credentials/{$credential->id}/approve")
            ->assertStatus(409);

        Notification::assertNothingSent();
    }
}