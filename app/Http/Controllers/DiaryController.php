<?php

namespace App\Http\Controllers;
use App\Models\DiaryEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class DiaryController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
        ]);

        $entry = DiaryEntry::create([
            'user_id' => $request->user()->id,
            'content' => $request->content,
        ]);

        return response()->json([
            'message' => 'Entrada criada com sucesso!',
            'entry' => $entry,
        ], 201);
    }

    public function index(Request $request)
    {
        $request->validate([
            'diary_password' => 'required|string',
        ]);

        $user = $request->user();
        $hash = optional($user->profile)->diary_password_hash;

        if (!$hash || !Hash::check($request->diary_password, $hash)) {
            return response()->json(['message' => 'Senha do diário inválida'], 403);
        }

        $entries = DiaryEntry::where('user_id', $user->id)
            ->latest('created_at')
            ->get(); // se quiser paginação: ->paginate(20);

        return response()->json($entries);
    }

    public function destroy(Request $request, $id)
    {
        $request->validate([
            'diary_password' => 'required|string',
        ]);

        $user = $request->user();
        $hash = optional($user->profile)->diary_password_hash;

        if (!$hash || !Hash::check($request->diary_password, $hash)) {
            return response()->json(['message' => 'Senha do diário inválida'], 403);
        }

        $entry = DiaryEntry::where('user_id', $user->id)->findOrFail($id);
        $entry->delete();

        return response()->json(['message' => 'Entrada deletada com sucesso!']);
    }
}
