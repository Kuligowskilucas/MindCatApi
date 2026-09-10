<?php

namespace App\Http\Controllers;

use App\Models\Feeling;
use Illuminate\Http\JsonResponse;

class FeelingController extends Controller
{
    public function index(): JsonResponse
    {
        $feelings = Feeling::orderBy('sort_order')
            ->get(['id', 'slug', 'label']);

        return response()->json($feelings);
    }
}
