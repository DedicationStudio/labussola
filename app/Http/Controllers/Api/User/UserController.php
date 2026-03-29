<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getAgenti()
    {
        $users = User::with('tipo_richieste')
        ->whereHas('role', function ($q) {
            $q->where('nome', 'agente');
        })->get();

        return response()->json($users);
    }
}
