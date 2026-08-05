<?php

namespace App\Services;

use App\Models\CredentialDocument;
use App\Models\ProfessionalCredential;
use App\Models\User;
use App\Notifications\CredentialApproved;
use App\Notifications\CredentialRejected;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminCredentialService
{
    /** Minutos de validade da URL assinada de cada documento. */
    private const DOCUMENT_URL_TTL = 5;

    /** Fila de credenciais por status (default: submitted). Paginada. */
    public function queue(string $status): LengthAwarePaginator
    {
        return ProfessionalCredential::with('user:id,name,email')
            ->where('status', $status)
            ->orderBy('submitted_at')
            ->paginate(30);
    }

    /**
     * Detalhe da credencial + URLs assinadas temporárias dos documentos.
     * A URL aponta pra rota `admin.credential-document`, válida por poucos
     * minutos; o disco continua privado.
     */
    public function detail(ProfessionalCredential $credential): array
    {
        $credential->load(['user:id,name,email', 'documents']);

        $documents = $credential->documents->map(fn (CredentialDocument $doc) => [
            'id'            => $doc->id,
            'kind'          => $doc->kind,
            'original_name' => $doc->original_name,
            'mime'          => $doc->mime,
            'size'          => $doc->size,
            'url'           => URL::temporarySignedRoute(
                'admin.credential-document',
                now()->addMinutes(self::DOCUMENT_URL_TTL),
                ['document' => $doc->id]
            ),
        ]);

        return [
            'credential' => $credential->makeHidden('documents'),
            'documents'  => $documents,
        ];
    }

    /** Aprova: registra auditoria (quem/quando/método/fonte) e agenda revisão. */
    public function approve(ProfessionalCredential $credential, User $admin): ProfessionalCredential
    {
        $this->assertDecidable($credential);

        $credential->update([
            'status'              => ProfessionalCredential::STATUS_APPROVED,
            'rejection_reason'    => null,
            'verification_method' => ProfessionalCredential::METHOD_MANUAL,
            'verification_source' => 'admin_review',
            'verified_by'         => $admin->id,
            'verified_at'         => now(),
            'next_review_at'      => now()->addYear(),
        ]);

        $credential->user->notify(new CredentialApproved($credential));

        return $credential->fresh();
    }

    /** Recusa com motivo. O pro pode reenviar depois (fluxo da Fase 5b). */
    public function reject(ProfessionalCredential $credential, User $admin, string $reason): ProfessionalCredential
    {
        $this->assertDecidable($credential);

        $credential->update([
            'status'              => ProfessionalCredential::STATUS_REJECTED,
            'rejection_reason'    => $reason,
            'verification_method' => ProfessionalCredential::METHOD_MANUAL,
            'verification_source' => 'admin_review',
            'verified_by'         => $admin->id,
            'verified_at'         => now(),
            'next_review_at'      => null,
        ]);

        $credential->user->notify(new CredentialRejected($credential));

        return $credential->fresh();
    }

    /** Stream do arquivo do disco privado (a rota já validou a assinatura). */
    public function streamDocument(CredentialDocument $document): StreamedResponse
    {
        $disk = Storage::disk('local');

        if (!$disk->exists($document->storage_path)) {
            throw new HttpException(404, 'Documento não encontrado.');
        }

        return $disk->response($document->storage_path, $document->original_name, [
            'Content-Type' => $document->mime ?? 'application/octet-stream',
        ]);
    }

    /** Só é possível decidir credencial que está aguardando análise. */
    private function assertDecidable(ProfessionalCredential $credential): void
    {
        $decidable = [
            ProfessionalCredential::STATUS_SUBMITTED,
            ProfessionalCredential::STATUS_UNDER_REVIEW,
        ];

        if (!in_array($credential->status, $decidable, true)) {
            throw new HttpException(409, 'Esta credencial não está aguardando análise.');
        }
    }
}