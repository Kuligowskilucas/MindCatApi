<?php

namespace App\Http\Controllers;

use App\Http\Requests\Credential\RejectCredentialRequest;
use App\Models\CredentialDocument;
use App\Models\ProfessionalCredential;
use App\Services\AdminCredentialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AdminCredentialController extends Controller
{
    public function __construct(
        private AdminCredentialService $service
    ) {}

    /** Fila de análise (default: submitted). Paginada. */
    public function index(Request $request): JsonResponse
    {
        $status = $request->query('status', ProfessionalCredential::STATUS_SUBMITTED);

        return response()->json($this->service->queue($status));
    }

    /** Detalhe + URLs assinadas temporárias de cada documento. */
    public function show(ProfessionalCredential $credential): JsonResponse
    {
        return response()->json($this->service->detail($credential));
    }

    public function approve(Request $request, ProfessionalCredential $credential): JsonResponse
    {
        return response()->json(
            $this->service->approve($credential, $request->user())
        );
    }

    public function reject(RejectCredentialRequest $request, ProfessionalCredential $credential): JsonResponse
    {
        return response()->json(
            $this->service->reject($credential, $request->user(), $request->validated()['reason'])
        );
    }

    /**
     * Serve o documento do disco privado. A rota é protegida por assinatura
     * temporária (`signed`), emitida só dentro do show (que é admin-only).
     */
    public function document(CredentialDocument $document): StreamedResponse
    {
        return $this->service->streamDocument($document);
    }
}