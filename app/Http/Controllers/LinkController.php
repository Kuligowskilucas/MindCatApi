<?php

namespace App\Http\Controllers;

use App\Services\AuditLogger;
use App\Services\LinkService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LinkController extends Controller
{
    public function __construct(
        private LinkService $linkService,
        private AuditLogger $audit
    ) {}

    public function indexPatients(Request $request): JsonResponse
    {
        $patients = $this->linkService->indexPatients($request->user());

        return response()->json($patients);
    }

    public function destroy(Request $request, int $patientId): JsonResponse
    {
        $this->linkService->destroy($request->user(), $patientId);

        return response()->json([
            'message' => 'Vínculo removido.',
        ]);
    }

    public function indexProfessionals(Request $request): JsonResponse
    {
        return response()->json([
            'data' => $this->linkService->indexProfessionals($request->user()),
        ]);
    }

    public function destroyProfessional(Request $request, int $proId): JsonResponse
    {
        $link = $this->linkService->destroyForPatient($request->user(), $proId);

        if (!$link) {
            throw new HttpException(404, 'Vínculo não encontrado.');
        }

        $this->audit->record(
            $request->user(),
            'link.deactivated_by_patient',
            $link,
            ['pro_id' => $proId],
            $request->ip()
        );

        return response()->json([
            'message' => 'Vínculo removido.',
        ]);
    }
}
