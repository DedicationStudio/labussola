<?php

namespace App\Http\Controllers\Api\TypeRequest;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\TypeRequest;
use Illuminate\Http\Request;

class TypeRequestController extends Controller
{
    public function index()
    {

        $tipologia_richiesta = TypeRequest::with(['users' => function ($q) {
            $q->select('users.id', 'users.nome', 'users.cognome')
             ->where('users.n_preventivi_gestibili', '>', 0);
        }])->get(['id', 'nome']);

        $clienti = Customer::all(['id', 'tipo_cliente', 'nome', 'cognome', 'email']);

        return response()->json([
            'tipologia_richiesta' => $tipologia_richiesta,
            'clienti' => $clienti,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|unique:type_requests,nome|max:255',
        ]);

        $type = TypeRequest::create($validated);

        return response()->json([
            'message' => 'Tipo di richiesta creato con successo.',
            'data' => $type
        ], 201);
    }

}
