<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class LoginUserController extends Controller
{
    public function store(Request $request)
    {

        $data = $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|min:8'
        ]);
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'message' => 'Invalid Credentials'
            ], 401);
        }
        $token = $user->createToken($user->name . '-AuthToken')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'first_change_password' => (bool) $user->first_change_password,
            'id_user'                => $user->id,
        ]);
    }


    public function saveToken(Request $request)
{
    $validated = $request->validate([
        'fcm_token' => ['required', 'string'],
    ]);

    $user = $request->user(); // prende l'utente loggato tramite Bearer Token
    $user->update([
        'fcm_token' => $validated['fcm_token'],
    ]);

    return response()->json(['message' => 'Token salvato con successo']);
}

    public function destroy(Request $request)
    {
        // Cancella solo il token corrente usato da questo dispositivo
        $request->user()->currentAccessToken()->delete();

        // Restituisci uno status 204 No Content
        return response()->noContent();
    }
}
