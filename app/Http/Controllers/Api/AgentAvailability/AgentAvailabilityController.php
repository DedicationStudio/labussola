<?php

namespace App\Http\Controllers\Api\AgentAvailability;

use App\Http\Controllers\Controller;
use App\Models\AgentAvailability;
use App\Models\CustomNotification;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Notifications\PersonalNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Exception\MessagingException;
use Throwable;
use Illuminate\Support\Facades\Notification as NotificationFacade;


class AgentAvailabilityController extends Controller
{
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

       /*  if ($user && $user->hasRole('admin')) {

            // Admin: tutti gli eventi
            
        } else {
            // Agente: solo i propri
            $availabilities = AgentAvailability::with('user', 'quote_request')
                ->where('user_id', $user->id)
                ->orderBy('created_at', 'desc') 
                ->get();
        } */

                $availabilities = AgentAvailability::with('user', 'quote_request')
            ->orderBy('created_at', 'desc') 
            ->get();

        return response()->json($availabilities);
    }
    //singolo evento sul calendario
    public function show(AgentAvailability $agentAvailability)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        if (!$user->isAdmin() && $agentAvailability->user_id !== $user->id) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        return response()->json($agentAvailability);
    }

    public function store(Request $request)
    {

        $data = $request->validate([
            'quote_request_id' => 'nullable|exists:quote_requests,id',
            'stato' => 'required|in:disponibile,non_disponibile',
            'n_giorni_preventivo' => 'nullable|integer',
            'motivazione_rifiuto' => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'note' => 'nullable|string',
        ]);
        $data['user_id'] = Auth::id();        // salvataggio
        $availability = AgentAvailability::create($data);

         $scadenza = now()->addDays($data['n_giorni_preventivo']);
            
          


        $availability->quote_request()->update([
            'stato_richiesta' => 'risposta pervenuta',
            'scadenza' => $scadenza,
        ]);
        $quoteRequest = QuoteRequest::findOrFail($data['quote_request_id']);

        $titolo = 'Disponibilità Agente';
        $body = $data['stato'] === 'disponibile'
            ? "Un agente è disponibile per la richiesta: " . $quoteRequest->oggetto . "."
            : "Un agente ha rifiutato la richiesta: " . $quoteRequest->oggetto . ".";




        $quoteRequest = QuoteRequest::findOrFail($data['quote_request_id']);

        $adminIds = User::query()
            ->whereHas('role', fn($q) => $q->whereIn('nome', ['admin', 'superadmin']))
            ->where('id', '!=', $request->user()->id) // escludi l'agente stesso
            ->pluck('id')
            ->all();
        //utente creatore della richiesta
        $creator_req = $quoteRequest->created_by;

        $usersIds = $adminIds;

        //se l'agente crea la richiesta e non è nell'array adminIds e non è uguale a quello loggato, certi che il creatore non sia lo stesso agente che sta rispondendo
        if ($creator_req && !in_array($creator_req, $adminIds) && $creator_req !== $request->user->id) {
            $usersIds[] = $creator_req;
        }


        //  Crea anche la notifica "database" per Filament
        foreach (User::whereIn('id', $usersIds)->get() as $receiver) {
            $receiver->notify(new PersonalNotification(
                title: $titolo,
                body: $body,
            ));
        }

         NotificationFacade::send(
                    $usersIds,
                    new PersonalNotification(
                        title: $titolo,
                        body: $body,
                        type: 'quote_request',
                    )
                );

                \Filament\Notifications\Notification::make()
                    ->title($titolo)
                    ->body($body)
                    ->success()
                    ->sendToDatabase($usersIds);


        $tokens = User::query()
            ->whereIn('id', $adminIds)
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->filter()
            ->values()
            ->all();

        if (!empty($tokens)) {
            // 6) Inizializza Firebase UNA sola volta
            $credentialsPath = base_path(config('services.firebase.credentials', env('FIREBASE_CREDENTIALS')));
            $messaging = (new Factory)
                ->withServiceAccount($credentialsPath)
                ->createMessaging();

            // 7) Messaggio base (stesso per tutti)
            $base = CloudMessage::fromArray([
                'notification' => [
                    'title' => $titolo,
                    'body' => $body,
                ],
                'data' => [
                    'type' => 'agent_availability',
                    'agent_availability_id' => (string) $availability->id,
                    'quote_request_id' => (string) $data['quote_request_id'],
                    'title' => $titolo,
                    'body' => $body,
                ],
                'android' => [
                    'priority' => 'high',
                    'notification' => [
                        'channel_id' => 'high_importance_channel',
                    ],
                ],
                'apns' => [
                'headers' => [
                    'apns-priority' => '10', // 10 = immediata
                ],
                'payload' => [
                    'aps' => [
                        'alert' => [
                            'title' => $titolo,
                            'body' => $body,
                        ],
                        'sound' => 'default', // Usa il suono di sistema
                    ],
                ],
            ],
            ]);

            // 8) Invia in chunk (max 500 token/richiesta)
            foreach (array_chunk($tokens, 500) as $batch) {
                try {
                    $messaging->sendMulticast($base, $batch);
                } catch (MessagingException | Throwable $e) {
                    report($e);
                }
            }
        }
        return response()->json([
            'message' => 'Disponibilità salvata correttamente',
            'data' => $availability
        ], 200);
    }


   public function update(Request $request, $id)
{
    $validated = $request->validate([
        'user_id' => 'required|exists:users,id',
        'quote_request_id' => 'required|exists:quote_requests,id',
        'stato' => 'required|in:disponibile,non_disponibile',
        'n_giorni_preventivo' => 'nullable|integer|min:1',
        'motivazione_rifiuto' => 'nullable|string|max:255',
    ]);

    $availability = AgentAvailability::findOrFail($id);

    // sicurezza: solo l'agente che ha creato la disponibilità può modificarla
    if ($availability->user_id !== $request->user()->id) {
        return response()->json(['error' => 'Non autorizzato'], 403);
    }

    $availability->update($validated);

    // Se lo stato della richiesta è "assegnata" E l'agente ha inserito i giorni
    $quoteRequest = $availability->quote_request;
    
    if ($quoteRequest) {
        $statoRichiesta = $quoteRequest->stato_richiesta;
        
        // Gestione Enum
        if (is_object($statoRichiesta)) {
            $statoString = $statoRichiesta->value;
        } else {
            $statoString = $statoRichiesta;
        }
        
        if (strtolower($statoString) === 'assegnata' && isset($validated['n_giorni_preventivo'])) {
            // Calcola la scadenza in base ai giorni del preventivo
            $scadenza = now()->addDays($validated['n_giorni_preventivo']);
            
            $quoteRequest->update([
                'stato_richiesta' => 'in lavorazione',
                'scadenza' => $scadenza,
            ]);

            // NOTIFICHE AGLI ADMIN
            $adminIds = [];
            
            if ($quoteRequest->assegnata_da) {
                $adminIds[] = $quoteRequest->assegnata_da;
            }
            
            if ($quoteRequest->created_by && $quoteRequest->created_by !== $quoteRequest->assegnata_da) {
                $adminIds[] = $quoteRequest->created_by;
            }

            if (!empty($adminIds)) {
                $admins = User::whereIn('id', array_unique($adminIds))->get();
                
                $titleNotifica = 'Richiesta Confermata';
                $bodyNotifica = "L'agente ha confermato {$validated['n_giorni_preventivo']} giorni per la richiesta: {$quoteRequest->oggetto}. Scadenza: {$scadenza->format('d/m/Y')}";

                // Notifiche database
                foreach ($admins as $admin) {
                    $admin->notify(new PersonalNotification(
                        title: $titleNotifica,
                        body: $bodyNotifica,
                        type: 'quote_request',
                    ));
                }

                \Filament\Notifications\Notification::make()
                    ->title($titleNotifica)
                    ->body($bodyNotifica)
                    ->success()
                    ->sendToDatabase($admins);

                // FCM
                $tokens = $admins
                    ->filter(fn($admin) => $admin->fcm_token && !$admin->hasMuteNotifications())
                    ->pluck('fcm_token')
                    ->filter()
                    ->values()
                    ->all();

                if (!empty($tokens)) {
                    $credentialsPath = base_path(config('services.firebase.credentials', env('FIREBASE_CREDENTIALS')));
                    $messaging = (new Factory)
                        ->withServiceAccount($credentialsPath)
                        ->createMessaging();

                    $base = CloudMessage::fromArray([
                        'notification' => [
                            'title' => $titleNotifica,
                            'body' => $bodyNotifica,
                        ],
                        'data' => [
                            'type' => 'quote_request',
                            'quote_request_id' => (string) $quoteRequest->id,
                            'title' => $titleNotifica,
                            'body' => $bodyNotifica,
                        ],
                        'android' => [
                            'priority' => 'high',
                            'notification' => [
                                'channel_id' => 'high_importance_channel',
                                'sound' => 'default'
                            ],
                        ],
                        'apns' => [
                            'headers' => ['apns-priority' => '10'],
                            'payload' => [
                                'aps' => [
                                    'alert' => [
                                        'title' => $titleNotifica,
                                        'body' => $bodyNotifica,
                                    ],
                                    'sound' => 'default',
                                ],
                            ],
                        ],
                    ]);

                    foreach (array_chunk($tokens, 500) as $batch) {
                        try {
                            $messaging->sendMulticast($base, $batch);
                        } catch (MessagingException | Throwable $e) {
                            report($e);
                        }
                    }
                }
            }
        }
    }

    return response()->json([
        'message' => 'Disponibilità aggiornata correttamente',
        'data' => $availability->fresh()
    ], 200);
}


    public function destroy($id)
    {
        $agentAvailability = AgentAvailability::findOrFail($id);
        $user = Auth::user();

        // controlla se non è admin o superadmin, e non è l’autore dell’evento
        if (
            !$user->hasRole('admin') &&
            !$user->hasRole('superadmin') &&
            $agentAvailability->user_id !== $user->id
        ) {
            return response()->json(['error' => 'Non autorizzato'], 403);
        }

        $agentAvailability->delete();

        return response()->json(['message' => 'Evento eliminato con successo']);
    }




    public function updateFromCalendar(Request $request, $id)
    {
        $availability = AgentAvailability::findOrFail($id);

        $data = $request->validate([
            'stato' => 'required|in:disponibile,non_disponibile',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i',
            'note' => 'nullable|string|max:255',
        ]);

        $availability->update($data);

        return response()->json([
            'message' => 'Disponibilità aggiornata dal calendario con successo',
            'data' => $availability->fresh(),
        ], 200);
    }

    public function storeFromCalendar(Request $request)
    {
        try {
            $data = $request->validate([
                'stato' => 'required|in:disponibile,non_disponibile',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
                'start_time' => 'nullable|date_format:H:i',
                'end_time' => 'nullable|date_format:H:i',
                'note' => 'nullable|string|max:255',
            ]);

            $data['user_id'] = Auth::id();

            $availability = AgentAvailability::create($data);

            return response()->json([
                'message' => 'Disponibilità salvata dal calendario con successo',
                'data' => $availability->fresh(),
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'error' => 'Errore durante il salvataggio della disponibilità',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

}
