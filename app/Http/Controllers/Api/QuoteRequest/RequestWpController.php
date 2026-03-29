<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RequestWp;
use Illuminate\Http\Request;

class RequestWpController extends Controller
{
    /**
     * GET /api/request-wps
     * Restituisce tutte le richieste provenienti da WP.
     */
    public function index()
    {
        $requests = RequestWp::orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $requests,
        ], 200);
    }

    /**
     * DELETE /api/request-wps/{id}
     * Elimina una richiesta specifica.
     */
    public function destroy($id)
    {
        $requestWp = RequestWp::find($id);

        if (!$requestWp) {
            return response()->json([
                'success' => false,
                'message' => 'Richiesta non trovata',
            ], 404);
        }

        $requestWp->delete();

        return response()->json([
            'success' => true,
            'message' => 'Richiesta eliminata con successo',
        ], 200);
    }
}
