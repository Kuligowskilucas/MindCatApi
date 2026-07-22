<?php

namespace App\Services;

use App\Models\PatientInvite;
use App\Models\ProPatientLink;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class InviteService
{
    private const MAX_CODE_ATTEMPTS = 8;

    public function __construct(
        private LinkService $linkService
    ) {}

    public function activeForPatient(User $patient): ?PatientInvite
    {
        $invite = PatientInvite::where('patient_id', $patient->id)
            ->where('status', PatientInvite::STATUS_ACTIVE)
            ->latest()
            ->first();

        if ($invite && ! $invite->isUsable()) {
            $invite->update(['status' => PatientInvite::STATUS_EXPIRED]);

            return null;
        }

        return $invite;
    }

    public function generate(User $patient): PatientInvite
    {
        if (! optional($patient->profile)->consent_share_with_professional) {
            throw new HttpException(403, 'Ative o consentimento de compartilhamento antes de gerar um convite.');
        }

        $ttl = (int) config('mindcat.invite.ttl_hours', 72);
        $length = (int) config('mindcat.invite.code_length', 8);

        return DB::transaction(function () use ($patient, $ttl, $length) {
            PatientInvite::where('patient_id', $patient->id)
                ->where('status', PatientInvite::STATUS_ACTIVE)
                ->update(['status' => PatientInvite::STATUS_REVOKED]);

            for ($attempt = 0; $attempt < self::MAX_CODE_ATTEMPTS; $attempt++) {
                try {
                    return PatientInvite::create([
                        'code'       => PatientInvite::generateCode($length),
                        'patient_id' => $patient->id,
                        'expires_at' => now()->addHours($ttl),
                        'status'     => PatientInvite::STATUS_ACTIVE,
                    ]);
                } catch (QueryException $e) {
                    if ($this->isUniqueViolation($e)) {
                        continue;
                    }

                    throw $e;
                }
            }

            throw new HttpException(500, 'Não foi possível gerar um código único. Tente novamente.');
        });
    }

    public function revokeActive(User $patient): void
    {
        PatientInvite::where('patient_id', $patient->id)
            ->where('status', PatientInvite::STATUS_ACTIVE)
            ->update(['status' => PatientInvite::STATUS_REVOKED]);
    }

    public function redeem(User $pro, string $code): ProPatientLink
    {
        return DB::transaction(function () use ($pro, $code) {
            $invite = PatientInvite::where('code', $code)
                ->lockForUpdate()
                ->first();

            if (! $invite || ! $invite->isUsable()) {
                throw new HttpException(422, 'Código de convite inválido ou expirado.');
            }

            $link = $this->linkService->store($pro, $invite->patient_id);

            $invite->update([
                'used_at'        => now(),
                'used_by_pro_id' => $pro->id,
                'status'         => PatientInvite::STATUS_USED,
            ]);

            return $link;
        });
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return $e->getCode() === '23000';
    }
}