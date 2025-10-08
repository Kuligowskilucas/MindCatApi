<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{

    public function me(Request $request)
    {
        $user = $request->user()->load(['profile']);
        if ($user->profile) {
            unset($user->profile->diary_password_hash);
        }
        return response()->json($user);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'password' => 'sometimes|string|min:6',
        ]);

        if($request->has('name')){
            $user->name = $request->name;
        }

        if($request->has('email')){
            $user->email = $request->email;
        }

        if($request->has('password')){
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'message' => 'Usuário atualizado com sucesso!',
            'user' => $user,
        ], 200);
    }

    public function destroy(Request $request)
    {
        $user = request()->user();
        
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Usuário deletado com sucesso!',
        ], 200);
    }
}
