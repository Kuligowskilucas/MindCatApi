<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\UserActivity;

class ExerciseController extends Controller
{
  public function complete(Request $request){
    $data = $request->validate([
      'type' => 'required|in:breathing',
      'duration_seconds' => 'required|integer|min:30'
    ]);
    $row = UserActivity::create([
      'user_id'=>$request->user()->id,
      'activity_name'=>$data['type'],
      'is_completed'=>1,
      'completed_at'=>now(),
    ]);
    return response()->json($row, 201);
  }

  public function history(Request $request){
    $request->validate(['from'=>'nullable|date','to'=>'nullable|date']);
    $q = UserActivity::where('user_id',$request->user()->id)->where('is_completed',1)->orderByDesc('completed_at');
    if ($request->filled('from')) $q->where('completed_at','>=',$request->from);
    if ($request->filled('to')) $q->where('completed_at','<=',$request->to);
    return response()->json($q->paginate(30));
  }
}

