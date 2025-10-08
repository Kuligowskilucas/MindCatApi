<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserMoodTracking;
use Carbon\Carbon;
use Illuminate\Http\Request;

class MoodController extends Controller
{
    
    public function store(Request $request)
    {
        $data = $request->validate([
            'mood_level' => 'required|integer|min:1|max:10',
            'mood_description' => 'nullable|string|max:255',
            'recorded_at' => 'nullable|date',
        ]);

        $user = $request->user();
        $day = Carbon::parse($data['recorded_at'] ?? now())->startOfDay();

        $exists = UserMoodTracking::where('user_id',$user->id)->whereBetween('recorded_at', [$day, (clone $day)->endOfDay()])->exists();

        if ($exists) {
            return response()->json(['message' => 'O humor ja foi registrado hoje.'], 409);
        }

        $row = UserMoodTracking::create([
            'user_id'=>$user->id,
            'mood_level'=>$data['mood_level'],
            'mood_description'=>$data['mood_description'] ?? null,
            'recorded_at'=>$data['recorded_at'] ?? now(),
        ]);

        return response()->json($row, 201);
    }

    public function index(Request $request)
    {
        $request->validate(['from'=>'nullable|date','to'=>'nullable|date']);
        $q = UserMoodTracking::where('user_id',$request->user()->id)->orderByDesc('recorded_at');
        if($request->filled('from')){
            $q->where('recorded_at','>=',$request->from);
        }
        if($request->filled('to')){
            $q->where('recorded_at','<=',$request->to);
        }
        return response()->json($q->paginate(30));
    }
}
