<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function updatePreventivi(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'n_preventivi_gestibili' => ['required', 'integer', 'min:0']
        ]);

        $user = $request->user(); // prende l'utente loggato
        $user->update($data);

        return response()->json($user);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'cognome' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'telefono' => 'nullable|string|max:20',
        ]);

        $user->update($data);

        return response()->json($user);
    }
}
