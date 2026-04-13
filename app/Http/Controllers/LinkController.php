<?php

namespace App\Http\Controllers;

use App\Http\Requests\Link\SearchPatientRequest;
use App\Http\Requests\Link\StoreLinkRequest;
use App\Services\LinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LinkController extends Controller
{
    public function __construct(
        private LinkService $linkService
    ) {}

    public function store(StoreLinkRequest $request): JsonResponse
    {
        $link = $this->linkService->store(
            $request->user(),
            $request->validated()['patient_id']
        );

        return response()->json($link, 201);
    }

    public function indexPatients(Request $request): JsonResponse
    {
        $patients = $this->linkService->indexPatients($request->user());

        return response()->json($patients);
    }

    public function indexProfessionals(Request $request): JsonResponse
    {
        if ($request->user()->role !== 'patient') {
            abort(403);
        }

        $professionals = $this->linkService->indexProfessionals($request->user());

        return response()->json($professionals);
    }

    public function destroy(Request $request, int $patientId): JsonResponse
    {
        $this->linkService->destroy($request->user(), $patientId);

        return response()->json([
            'message' => 'Vínculo removido.',
        ]);
    }

    public function searchPatient(SearchPatientRequest $request): JsonResponse
    {
        $result = $this->linkService->searchPatient(
            $request->validated()['email']
        );

        return response()->json($result);
    }
}
