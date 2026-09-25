<?php

namespace App\Services;

use App\Models\ProfessionalCredential;
use App\Models\ProPatientLink;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LinkService
{
    public function store(User $pro, int $patientId): ProPatientLink
    {
        $patient = User::findOrFail($patientId);

        if (!$patient->isPatient()) {
            throw new HttpException(422, 'Usuário não é paciente.');
        }

        if (!optional($patient->profile)->consent_share_with_professional) {
            throw new HttpException(403, 'Paciente sem consentimento.');
        }

        return ProPatientLink::updateOrCreate(
            ['pro_id' => $pro->id, 'patient_id' => $patient->id],
            ['active' => true]
        );
    }

    /**
     * Consentimento é verificado na LEITURA, não só no vínculo.
     * Se o paciente revoga, ele some da lista imediatamente.
     */
    public function indexPatients(User $pro)
    {
        return $pro->patients()
            ->whereHas('profile', fn ($q) => $q->where('consent_share_with_professional', true))
            ->paginate(30);
    }

    public function destroy(User $pro, int $patientId): void
    {
        $this->deactivate($pro->id, $patientId);
    }

    /** Pros com vínculo ativo, só com o selo público: sem email nem número de registro. */
    public function indexProfessionals(User $patient): array
    {
        return $patient->professionals()
            ->with('credential')
            ->orderBy('users.name')
            ->get()
            ->map(fn (User $pro) => [
                'id'    => $pro->id,
                'name'  => $pro->name,
                'badge' => $pro->credential?->publicBadge() ?? ProfessionalCredential::BADGE_UNVERIFIED,
            ])
            ->all();
    }

    /** Escopado pelo próprio paciente: um vínculo de outro paciente nunca é encontrado. */
    public function destroyForPatient(User $patient, int $proId): ?ProPatientLink
    {
        $link = ProPatientLink::where('pro_id', $proId)
            ->where('patient_id', $patient->id)
            ->where('active', true)
            ->first();

        if ($link) {
            $this->deactivate($proId, $patient->id);
        }

        return $link;
    }

    private function deactivate(int $proId, int $patientId): void
    {
        ProPatientLink::where('pro_id', $proId)
            ->where('patient_id', $patientId)
            ->update(['active' => false]);
    }
}