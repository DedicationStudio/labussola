<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::all();

        return response()->json($customers);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_cliente'    => 'required|in:azienda,privato,scuola',
            'nome'            => 'nullable|string|max:255',
            'cognome'         => 'nullable|string|max:255',
            'genere'         => 'nullable|string|max:255',
            'ragione_sociale' => 'nullable|string|max:255',
            'piva_cf'         => 'nullable|string|max:50',
            'indirizzo'       => 'nullable|string|max:255',
            'citta'           => 'nullable|string|max:255',
            'cap'             => 'nullable|string|max:20',
            'provincia'       => 'nullable|string|max:255',
            'stato'           => 'nullable|string|max:255',
            'email'           => 'nullable|email|max:255|unique:customers,email',
            'telefono'        => 'nullable|string|max:50',
        ]);

        $customer = Customer::create($validated);

        return response()->json($customer, 201);
    }
}
