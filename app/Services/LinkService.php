<?php

namespace App\Services;

use App\Models\ProPatientLink;
use App\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LinkService
{
    public function store(User $pro, int $patientId): ProPatientLink
    {
        $patient = User::findOrFail($patientId);

        if ($patient->role !== 'patient') {
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

    public function indexPatients(User $pro)
    {
        return $pro->patients()->paginate(30);
    }

    public function indexProfessionals(User $patient)
    {
        return $patient->professionals()
            ->select('users.id', 'users.name', 'users.email')
            ->paginate(30);
    }

    public function destroy(User $pro, int $patientId): void
    {
        ProPatientLink::where('pro_id', $pro->id)
            ->where('patient_id', $patientId)
            ->update(['active' => false]);
    }

    public function searchPatient(string $email): array
    {
        $patient = User::where('email', $email)
            ->where('role', 'patient')
            ->first();

        if (!$patient) {
            throw new HttpException(404, 'Paciente não encontrado.');
        }

        return [
            'id'      => $patient->id,
            'name'    => $patient->name,
            'email'   => $patient->email,
            'consent' => (bool) optional($patient->profile)->consent_share_with_professional,
        ];
    }
}
