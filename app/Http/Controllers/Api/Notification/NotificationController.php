<?php

namespace App\Http\Controllers\Api\Notification;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     *  Elenco notifiche utente autenticato.
     */


    public function index(Request $request)
{
    $user = $request->user();

    $notifications = $user->customNotifications()
        ->orderBy('is_read')
        ->orderByDesc('created_at')
        ->get()
        ->transform(function (Notification $n) {
            return [
                'id'         => $n->id,
                'data'      => $n->data,
                'is_read'    => (bool) $n->is_read,
                'created_at' => optional($n->created_at)->toISOString(),
                'read_at'    => optional($n->read_at)->toISOString(),
            ];
        });

    return response()->json($notifications);
}


    /**
     *  Segna una notifica come letta.
     */
    public function markAsRead(Request $request, $id)
    {
        $user = $request->user();

        $notification = $user->customNotifications()->findOrFail($id);

        $notification->update([
            'is_read' => true,
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'Notifica segnata come letta']);
    }

    /**
     *  Segna tutte le notifiche come lette.
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->user();

        $user->customNotifications()
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        return response()->json(['message' => 'Tutte le notifiche segnate come lette']);
    }

    /**
     *  Cancella tutte le notifiche dell’utente.
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        $user->customNotifications()->delete();

        return response()->json(['message' => 'Notifiche rimosse con successo']);
    }
}
