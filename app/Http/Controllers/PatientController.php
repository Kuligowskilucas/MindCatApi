<?php

namespace App\Http\Controllers;

use App\Services\PatientService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    public function __construct(
        private PatientService $patientService
    ) {}

    public function summary(Request $request, int $id): JsonResponse
    {
        $data = $this->patientService->summary($request->user(), $id);

        return response()->json($data);
    }
}
