<?php

namespace App\Http\Controllers\Api\Agents;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class AgentsController extends Controller
{
    public function index(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['error' => 'Utente non autenticato'], 401);
        }
        $agents = User::with('tipo_richieste')
            ->whereHas('role', fn($r) => $r->where('nome', 'agente'))
            ->orderBy('created_at', 'desc') 
            ->get();
        return response()->json($agents);
    }
}
