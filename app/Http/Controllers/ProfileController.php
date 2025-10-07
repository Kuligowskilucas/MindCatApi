<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    
    public function show(Request $request)
    {
        return response()->json($request->user()->profile()->firstOrCreate([]));
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'consent_share_with_professional' => 'sometimes|boolean',
        ]);

        $profile = $request->user()->profile()->firstOrCreate([]);
        $profile->fill($data)->save();

        return response()->json($profile);
    }

    public function setDiaryPassword(Request $request)
    {
        $request->validate([
            'current_password' => 'sometimes|string',
            'new_password' => 'required|string|min:8|',
        ]);

        $profile = $request->user()->profile()->firstOrCreate([]);
        if($profile->diary_password_hash){
            if(!$request->filled('current_password') || !Hash::check($request->current_password, $profile->diary_password_hash)){
                return response()->json(['message' => 'Senha atual inválida.'], 403);
            }
        }
        $profile->diary_password_hash = Hash::make($request->new_password);
        $profile->save();
        return response()->json(['message' => 'Senha do diário atualizada com sucesso.']);
    }
}
