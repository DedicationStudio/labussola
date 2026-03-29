<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class DeleteUserController extends Controller
{
    public function destroy($id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json([
                    'message' => 'Utente non trovato'
                ], 404);
            }
            $user->preventives()->delete();
            //$user->richieste_assegnate()->delete();
            foreach ($user->richieste_assegnate as $req) {
            $req->assegnata_da = null; // se vuoi tenerle in vita
            $req->save();

            // oppure $req->delete(); se devono sparire insieme all'utente
        }
            $user->tipo_richieste()->detach();
            $user->richieste_ricevute()->detach();
            $user->richieste_gestite()->detach();
            $user->notifications()->detach();

             $user->notificationMute()?->delete();

            foreach ($user->richieste_assegnate as $req) {
                $req->assegnata_da = null;
                $req->save();
            }

            $user->delete();
            return response()->json([
                'message' => 'Utente eliminato con successo'
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Errore durante l\'eliminazione',
                'error'   => $e->getMessage() // utile in debug
            ], 500);
        }
    }
}
