<?php

namespace App\Http\Controllers;

use App\Http\Requests\Credential\StoreCredentialRequest;
use App\Models\ProfessionalCredential;
use App\Services\CredentialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CredentialController extends Controller
{
    public function __construct(
        private CredentialService $credentialService
    ) {}

    /** Status da própria credencial (cria rascunho pending se não existir). */
    public function me(Request $request): JsonResponse
    {
        $credential = $this->credentialService->forUser($request->user());

        return response()->json($credential);
    }

    /** Primeira submissão: pending → submitted. */
    public function store(StoreCredentialRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['council'] = ProfessionalCredential::COUNCIL_BY_PROFESSION[$data['profession']];

        $credential = $this->credentialService->submit(
            $request->user(),
            $data,
            $request->file('registration_document'),
            $request->file('epsi_document'),
        );

        return response()->json($credential, 201);
    }
}
