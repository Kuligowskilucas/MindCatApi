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
        $days = (int) $request->query('days', 30);

        if ($days < 7 || $days > 90) {
            $days = 30;
        }

        $data = $this->patientService->summary($request->user(), $id, $days);

        return response()->json($data);
    }
}
