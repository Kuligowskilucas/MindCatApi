<?php

namespace App\Services;

use App\Models\DiaryEntry;
use App\Models\ProfessionalCredential;
use App\Models\ProPatientLink;
use App\Models\Task;
use App\Models\User;
use App\Models\UserMoodTracking;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService
{
    public function getProfile(User $user): User
    {
        $user->load('profile');

        if ($user->profile) {
            $user->profile->makeHidden('diary_password_hash');
        }

        return $user;
    }

    public function update(User $user, array $data): User
    {
        $passwordChanged = false;

        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        if (isset($data['email'])) {
            $user->email = $data['email'];
        }

        if (isset($data['password'])) {
            $user->password = Hash::make($data['password']);
            $passwordChanged = true;
        }

        $user->save();

        // Trocar a senha derruba as outras sessões/tokens.
        if ($passwordChanged) {
            $current = $user->currentAccessToken();

            $user->tokens()
                ->when(
                    $current && isset($current->id),
                    fn ($q) => $q->where('id', '!=', $current->id)
                )
                ->delete();
        }

        return $user;
    }

    /**
     * Exclusão de conta.
     *
     * Diário, humor e tarefas do paciente são apagados de verdade — o usuário
     * pediu. A tarefa carrega texto clínico escrito sobre a pessoa e segue
     * presa ao patient_id, então anonimizar o usuário não anonimiza a tarefa.
     * O usuário é anonimizado e vira lápide soft-deleted; sob a LGPD (art. 12),
     * dado anonimizado deixa de ser dado pessoal, então a linha pode permanecer
     * para manter a integridade referencial.
     */
    public function destroy(User $user): void
    {
        $credentialDocumentPaths = [];

        DB::transaction(function () use ($user, &$credentialDocumentPaths) {
            DiaryEntry::withTrashed()
                ->where('user_id', $user->id)
                ->forceDelete();

            UserMoodTracking::withTrashed()
                ->where('user_id', $user->id)
                ->forceDelete();

            Task::withTrashed()
                ->where('patient_id', $user->id)
                ->forceDelete();

            ProPatientLink::where('patient_id', $user->id)->update(['active' => false]);
            ProPatientLink::where('pro_id', $user->id)->update(['active' => false]);

            $credential = ProfessionalCredential::withTrashed()
                ->where('user_id', $user->id)
                ->first();

            if ($credential) {
                $credentialDocumentPaths = $credential->documents
                    ->pluck('storage_path')
                    ->filter()
                    ->all();

                $credential->documents()->delete();
                $credential->forceDelete();
            }

            if ($user->profile) {
                $user->profile->forceFill([
                    'diary_password_hash'             => null,
                    'consent_share_with_professional' => false,
                ])->save();
            }

            $user->tokens()->delete();

            $user->forceFill([
                'name'     => 'Usuário removido',
                'email'    => 'deleted_' . $user->id . '_' . Str::random(8) . '@mindcat.invalid',
                'password' => Hash::make(Str::random(40)),
            ])->save();

            $user->delete();
        });

        foreach ($credentialDocumentPaths as $path) {
            Storage::disk('local')->delete($path);
        }
    }
}