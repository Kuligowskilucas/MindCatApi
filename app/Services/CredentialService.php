<?php

namespace App\Services;

use App\Models\CredentialDocument;
use App\Models\ProfessionalCredential;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CredentialService
{
    /**
     * Credencial do pro (com documentos). Cria um rascunho 'pending' na
     * primeira consulta, pra o front sempre ter um status pra exibir.
     */
    public function forUser(User $pro): ProfessionalCredential
    {
        return ProfessionalCredential::with('documents')->firstOrCreate(
            ['user_id' => $pro->id],
            ['status' => ProfessionalCredential::STATUS_PENDING]
        );
    }

    /**
     * Submete (ou reenvia) a credencial: grava os dados, guarda os documentos
     * em disco privado e move o status para 'submitted'. Só é permitido a
     * partir de 'pending' ou 'rejected' — nunca em análise ou já aprovado.
     */
    public function submit( User $pro, array $data, UploadedFile $crpDocument, UploadedFile $epsiDocument ): ProfessionalCredential {
        $credential = $this->forUser($pro);

        $blocked = [
            ProfessionalCredential::STATUS_SUBMITTED,
            ProfessionalCredential::STATUS_UNDER_REVIEW,
            ProfessionalCredential::STATUS_APPROVED,
        ];
        if (in_array($credential->status, $blocked, true)) {
            throw new HttpException(409, 'Sua credencial já está em análise ou aprovada.');
        }

        return DB::transaction(function () use ($credential, $pro, $data, $crpDocument, $epsiDocument) {
            $credential->fill([
                'crp_number'       => $data['crp_number'],
                'crp_region'       => $data['crp_region'] ?? null,
                'epsi_registered'  => (bool) $data['epsi_registered'],
                'status'           => ProfessionalCredential::STATUS_SUBMITTED,
                'rejection_reason' => null,
                'submitted_at'     => now(),
            ]);
            $credential->save();

            foreach ($credential->documents as $old) {
                Storage::disk('local')->delete($old->storage_path);
                $old->delete();
            }

            $this->storeDocument($credential, $pro, $crpDocument, CredentialDocument::KIND_CRP_CARD);
            $this->storeDocument($credential, $pro, $epsiDocument, CredentialDocument::KIND_EPSI_PROOF);

            return $credential->load('documents');
        });
    }

    /**
     * Guarda o arquivo no disco PRIVADO (storage/app/credentials/{proId}).
     * Nunca no disco público — o acesso será por URL assinada só pra admin (5c).
     */
    private function storeDocument(
        ProfessionalCredential $credential,
        User $pro,
        UploadedFile $file,
        string $kind
    ): void {
        $path = $file->store("credentials/{$pro->id}", 'local');

        $credential->documents()->create([
            'kind'          => $kind,
            'storage_path'  => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime'          => $file->getClientMimeType(),
            'size'          => $file->getSize(),
        ]);
    }
}