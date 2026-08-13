<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()
            ->with('actor:id,name,email')
            ->orderByDesc('created_at');

        if ($action = $request->query('action')) {
            $query->where('action', $action);
        }

        return response()->json($query->paginate(50));
    }
}
