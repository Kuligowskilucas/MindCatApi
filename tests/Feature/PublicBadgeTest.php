<?php

namespace Tests\Feature;

use App\Models\ProfessionalCredential;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PublicBadgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['mindcat.credential.grace_days' => 7]);
    }

    private function credential(array $attributes): ProfessionalCredential
    {
        $pro = User::factory()->pro()->create();
        $pro->credential->update($attributes);

        return $pro->credential->fresh();
    }

    #[Test]
    public function active_psychologist_is_verified_psicologo(): void
    {
        $credential = $this->credential([]);

        $this->assertSame(['verified' => true, 'label' => 'Psicólogo(a)'], $credential->publicBadge());
    }

    #[Test]
    public function active_psychiatrist_with_rqe_is_verified_psiquiatra(): void
    {
        $credential = $this->credential([
            'profession' => ProfessionalCredential::PROFESSION_PSYCHIATRIST,
            'council'    => ProfessionalCredential::COUNCIL_CRM,
            'rqe_number' => '98765',
        ]);

        $this->assertSame(['verified' => true, 'label' => 'Psiquiatra'], $credential->publicBadge());
    }

    #[Test]
    public function active_psychiatrist_without_rqe_is_verified_medico(): void
    {
        foreach ([null, '', '   '] as $rqe) {
            $credential = $this->credential([
                'profession' => ProfessionalCredential::PROFESSION_PSYCHIATRIST,
                'council'    => ProfessionalCredential::COUNCIL_CRM,
                'rqe_number' => $rqe,
            ]);

            $this->assertSame(['verified' => true, 'label' => 'Médico(a)'], $credential->publicBadge());
        }
    }

    public static function inactiveStatuses(): array
    {
        return [
            'pending'      => [ProfessionalCredential::STATUS_PENDING],
            'submitted'    => [ProfessionalCredential::STATUS_SUBMITTED],
            'under_review' => [ProfessionalCredential::STATUS_UNDER_REVIEW],
            'rejected'     => [ProfessionalCredential::STATUS_REJECTED],
            'suspended'    => [ProfessionalCredential::STATUS_SUSPENDED],
            'expired'      => [ProfessionalCredential::STATUS_EXPIRED],
        ];
    }

    #[Test]
    #[DataProvider('inactiveStatuses')]
    public function non_approved_credential_is_unverified_profissional(string $status): void
    {
        foreach (ProfessionalCredential::PROFESSIONS as $profession) {
            $credential = $this->credential([
                'status'     => $status,
                'profession' => $profession,
                'rqe_number' => '98765',
            ]);

            $this->assertFalse($credential->isActive());
            $this->assertSame(ProfessionalCredential::BADGE_UNVERIFIED, $credential->publicBadge());
        }
    }

    #[Test]
    public function approved_credential_past_grace_is_unverified_profissional(): void
    {
        $credential = $this->credential(['next_review_at' => now()->subDays(8)]);

        $this->assertFalse($credential->isActive());
        $this->assertSame(ProfessionalCredential::BADGE_UNVERIFIED, $credential->publicBadge());
    }

    #[Test]
    public function approved_credential_on_last_grace_day_is_still_active(): void
    {
        $this->freezeSecond();
        $credential = $this->credential(['next_review_at' => now()->subDays(7)]);

        $this->assertTrue($credential->isActive());
        $this->assertTrue($credential->publicBadge()['verified']);
    }

    #[Test]
    public function approved_credential_without_profession_is_unverified_profissional(): void
    {
        $credential = $this->credential(['profession' => null, 'council' => null]);

        $this->assertTrue($credential->isActive());
        $this->assertSame(ProfessionalCredential::BADGE_UNVERIFIED, $credential->publicBadge());
    }

    #[Test]
    public function badge_never_exposes_registration_data(): void
    {
        $credential = $this->credential([
            'profession' => ProfessionalCredential::PROFESSION_PSYCHIATRIST,
            'rqe_number' => '98765',
        ]);

        $this->assertSame(['verified', 'label'], array_keys($credential->publicBadge()));
    }
}
