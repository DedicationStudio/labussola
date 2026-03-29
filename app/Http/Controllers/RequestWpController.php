<?php

namespace App\Http\Controllers;

use App\Models\RequestWp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RequestWpController extends Controller
{
    public function store(Request $request)
    {
        // Log sicuro per debug
        Log::info('DEBUG - Dati ricevuti', [
            'all' => $request->all(),
            'content_type' => $request->header('Content-Type'),
            'method' => $request->method()
        ]);
//test
        try {
            // Recupera i dati
            $fields = $request->input('form_fields', []);
            
            // Se form_fields è vuoto, usa tutti i dati
            if (empty($fields)) {
                $fields = $request->all();
            }

            Log::info('Fields estratti', ['fields' => $fields]);

            if (empty($fields)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nessun dato ricevuto',
                ], 400);
            }

            // Validazione flessibile
            $data = validator($fields, [
                'nome' => 'nullable|string|max:255',
                'cognome' => 'nullable|string|max:255',
                'email' => 'required|email',
                'telefono' => 'nullable|string|max:50',
                'messaggio' => 'nullable|string',
            ])->validate();

            // Salvataggio
            $reqWp = RequestWp::create([
                'nome' => $data['nome'] ?? null,
                'cognome' => $data['cognome'] ?? null,
                'email' => $data['email'],
                'telefono' => $data['telefono'] ?? null,
                'messaggio' => $data['messaggio'] ?? null,
            ]);

            Log::info('Richiesta salvata', ['id' => $reqWp->id]);

            return response()->json([
                'success' => true,
                'message' => 'Richiesta salvata con successo',
                'id' => $reqWp->id
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Errore validazione', ['errors' => $e->errors()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Errore validazione',
                'errors' => $e->errors()
            ], 422);

        } catch (\Throwable $e) {
            Log::error('Errore webhook', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Errore: ' . $e->getMessage(),
            ], 500);
        }
    }
}