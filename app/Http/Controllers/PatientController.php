<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UserMoodTracking;
use App\Models\UserActivity;
use Illuminate\Support\Facades\Gate;


class PatientController extends Controller
{
  public function summary(Request $request, $patientId){
    $pro = $request->user();
    $patient = User::findOrFail($patientId);

    if (!Gate::forUser($pro)->allows('view-patient', $patient)) {
      return response()->json(['message'=>'Forbidden'], 403);
    }

    return response()->json([
      'patient' => ['id'=>$patient->id,'name'=>$patient->name],
      'moods'   => UserMoodTracking::where('user_id',$patient->id)->orderByDesc('recorded_at')->limit(14)->get(),
      'exercises_completed' => UserActivity::where('user_id',$patient->id)->where('is_completed',1)->count(),
      // diário: só metadados
      'diary' => $patient->diaryEntries()->select('id','created_at')->orderByDesc('created_at')->limit(10)->get(),
    ]);
  }
}
