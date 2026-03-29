<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ChangePasswordUserController extends Controller
{
    public function update(Request $request): JsonResponse
    {
        // Utente autenticato via Sanctum
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Non autenticato'], 401);
        }

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed'],
        ]);

        #Match The Old Password
        if (!Hash::check($request->current_password, $user->password)) {
            return  response()->json([
                'message' => 'La password corrente è errata'
            ], 400);
        }


        $user->password = Hash::make($validated['password']);
        $user->first_change_password = false;
        $user->save();

        return response()->json([
            'message' => 'Password cambiata con successo'
        ], 200);
    }
}
