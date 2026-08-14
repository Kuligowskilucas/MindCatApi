<?php

namespace App\Http\Controllers;

use App\Http\Requests\User\ExportDataRequest;
use App\Services\DataExportService;
use Illuminate\Http\JsonResponse;

class DataExportController extends Controller
{
    public function __construct(
        private DataExportService $service
    ) {}

    public function export(ExportDataRequest $request): JsonResponse
    {
        $data = $this->service->export(
            $request->user(),
            $request->validated()['diary_password'] ?? null
        );

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="mindcat-meus-dados.json"');
    }
}
