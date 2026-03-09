<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\ProPatientLink;
use App\Models\User;

class LinkController extends Controller
{
  public function store(Request $request){
    $this->authorizeRole($request->user(), 'pro');

    $data = $request->validate(['patient_id'=>'required|exists:users,id']);
    $patient = User::findOrFail($data['patient_id']);
    if ($patient->role !== 'patient') return response()->json(['message'=>'Usuário não é paciente'], 422);

    if (!optional($patient->profile)->consent_share_with_professional) {
      return response()->json(['message'=>'Paciente sem consentimento'], 403);
    }

    $link = ProPatientLink::updateOrCreate(
      ['pro_id'=>$request->user()->id,'patient_id'=>$patient->id],
      ['active'=>true]
    );
    return response()->json($link, 201);
  }

  public function indexPatients(Request $request){
    $this->authorizeRole($request->user(), 'pro');
    return response()->json($request->user()->patients()->paginate(30));
  }

  public function destroy(Request $request, $patientId){
    $this->authorizeRole($request->user(), 'pro');
    ProPatientLink::where('pro_id',$request->user()->id)->where('patient_id',$patientId)->update(['active'=>false]);
    return response()->json(['message'=>'Vínculo removido']);
  }

  private function authorizeRole($user, $role){
    if ($user->role !== $role) abort(403);
  }

  public function searchPatient(Request $request)
{
    $this->authorizeRole($request->user(), 'pro');

    $request->validate(['email' => 'required|email']);

    $patient = User::where('email', $request->email)
                    ->where('role', 'patient')
                    ->first();

    if (!$patient) {
        return response()->json(['message' => 'Paciente não encontrado.'], 404);
    }

    return response()->json([
        'id' => $patient->id,
        'name' => $patient->name,
        'email' => $patient->email,
        'consent' => optional($patient->profile)->consent_share_with_professional ?? false,
    ]);
}
}

