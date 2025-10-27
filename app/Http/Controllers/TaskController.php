<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
  // pro cria
  public function store(Request $request){
    if ($request->user()->role !== 'pro') return response()->json(['message'=>'Forbidden'],403);

    $data = $request->validate([
      'patient_id'=>'required|exists:users,id',
      'title'=>'required|string|max:120'
    ]);
    // checar permissão via gate
    $patient = User::findOrFail($data['patient_id']);
    if (!Gate::forUser($request->user())->allows('view-patient', $patient)) {
      return response()->json(['message'=>'Sem acesso ao paciente'], 403);
    }

    $task = Task::create([
      'pro_id'=>$request->user()->id,
      'patient_id'=>$patient->id,
      'title'=>$data['title'],
      'status'=>'active',
    ]);
    return response()->json($task, 201);
  }
  // listagem
  public function index(Request $request){
    $scope = $request->query('scope','mine');
    if ($request->user()->role === 'pro' && $scope==='assigned'){
      return response()->json(
        Task::where('pro_id',$request->user()->id)->orderByDesc('created_at')->paginate(30)
      );
    }
    // paciente vê as dele
    return response()->json(Task::where('patient_id',$request->user()->id)->orderByDesc('created_at')->paginate(30));
  }

  // paciente conclui
  public function markDone(Request $request, Task $task){
    if ($request->user()->id !== $task->patient_id) return response()->json(['message'=>'Forbidden'],403);
    $task->update(['status'=>'done','completed_at'=>now()]);
    return response()->json($task);
  }

  // pro deleta
  public function destroy(Request $request, Task $task){
    if ($request->user()->id !== $task->pro_id) return response()->json(['message'=>'Forbidden'],403);
    $task->delete();
    return response()->json(['message'=>'Tarefa removida']);
  }
}

