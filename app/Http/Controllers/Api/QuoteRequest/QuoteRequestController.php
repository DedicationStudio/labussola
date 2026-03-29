<?php

namespace App\Http\Controllers\Api\QuoteRequest;

use App\Filament\Resources\QuoteRequestResource;
use App\Http\Controllers\Controller;
use App\Models\AgentAvailability;
use App\Models\Customer;
use App\Models\CustomNotification;
use App\Models\QuoteRequest;
use App\Models\Preventive;
use App\Models\TypeRequest;
use App\Models\User;
use App\Notifications\PersonalNotification;
use Filament\Notifications\Actions\Action;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Exception\MessagingException;
use Throwable;
use Illuminate\Support\Facades\Notification as NotificationFacade;



class QuoteRequestController extends Controller
{
    public function show($id) //schermata home agente
    {
        $user = User::findOrFail($id);


        $competenceId = $user->tipo_richieste()->pluck('type_request_id');



        $totali = AgentAvailability::where('user_id', $user->id)
            ->where('stato', 'disponibile')
            ->count();

        $evase = AgentAvailability::where('user_id', $user->id)
            ->where('stato', 'disponibile')
            ->whereHas('quote_request', function ($q) {
                $q->where('stato_richiesta', 'evasa');
            })
            ->count();

        $percentuale = $totali > 0 ? round(($evase / $totali) * 100) : 0;

        $quoteRequests = QuoteRequest::with(['customer', 'disponibilita.user'])
            ->orderByRaw("
            CASE
                WHEN (
                    " . collect($competenceId)
                    ->map(fn($typeId) => "JSON_CONTAINS(tipo_richieste, '[$typeId]')")
                    ->join(' OR ') . "
                    OR created_by = ?
                )
                THEN 1 ELSE 0 END DESC
        ", [$id])
            ->orderBy('id', 'desc')
            ->paginate(5);

        $totalRequests = $quoteRequests->total();


        $data = $quoteRequests->map(function ($req) {
            //test-prev
            return [
                'id' => $req->id,
                'oggetto' => $req->oggetto,
                'stato_richiesta' => $req->stato_richiesta,
                'motivazione_archivio' => $req->motivazione_archivio,
                'customer_id' => $req->customer_id,
                'sono_gia_disponibile' => $req->sono_gia_disponibile,
                'nome_cliente' => $req->customer?->nome,
                'cognome_cliente' => $req->customer?->cognome,
                'data_ricezione_richiesta' => $req->data_ricezione_richiesta,
                'meta_viaggio' => $req->meta_viaggio,
                'scadenza' => $req->scadenza,
                'note' => $req->note,
                'tipo_richieste' => collect($req->tipo_richieste)
                    ->filter()
                    ->map(function ($typeId) {
                        $type = \App\Models\TypeRequest::find($typeId);
                        return $type
                            ? ['id' => $type->id, 'nome' => $type->nome]
                            : null;
                    })
                    ->filter()
                    ->values(),


                'availability_response' => $req->disponibilita
                    ->sortByDesc('created_at')
                    ->values()
                    ->filter()
                    ->map(
                        function ($agente) use ($req) {
                            return [
                                'id' => $agente->id,
                                'quote_request_id' => $req->id,
                                'user_id' => $agente->user_id,
                                'stato' => $agente->stato,
                                'n_giorni_preventivo' => $agente->n_giorni_preventivo,
                                'motivazione_rifiuto' => $agente->motivazione_rifiuto,
                                'created_at' => $agente->created_at->toDateTimeString(),
                            ];
                        }
                    ),
            ];
        });


        //preventivi
        $preventivi = Preventive::with(['customer', 'quote_request'])
            ->orderByRaw("
        CASE
            WHEN created_by = ? THEN 1
            ELSE 0
        END DESC
    ", [$user->id])
            ->orderBy('id', 'desc')
            ->paginate(5);

        $totalPrev = $preventivi->total();
        $total_preventivi = Preventive::where(function ($query) use ($user) {
            $query->whereHas('quote_request.agenti_gestori', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
                ->orWhere('created_by', $user->id);
        })->count();

        $preventivi_evasi = Preventive::where(function ($query) use ($user) {
            $query->whereHas('quote_request.agenti_gestori', function ($q) use ($user) {
                $q->where('users.id', $user->id);
            })
                ->orWhere('created_by', $user->id);
        })
            ->where('stato', '!=','bozza')
            ->count();


        $percentPreventivi = $total_preventivi > 0
            ? intval(($preventivi_evasi / $total_preventivi) * 100)
            : 0;


        return response()->json([
            'user' => $user,
            'data' => $data,
            'summary' => [ //array associativo (chiavi → valori) quindi in app sarà "summary":{"richieste_evase":0,"richieste_gestibili":1,"label":"0 su 1","percentuale":0}}
                'richieste_evase' => $evase,
                'richieste_gestibili' => $totali,
                'richieste_totali' => $totalRequests,
                'label' => "$evase su " . $totali,
                'percentuale' => $totali > 0 ? $percentuale : 0,

                /*  //preventivi

                'preventivi_evasi'    => $preventivi_evasi,
                'preventivi_totali'   => $total_preventivi,
                'percentuale_preventivi' => $percentPreventivi, */
            ],
            'preventivi' => $preventivi->map(function ($p) {
                return [
                    'id' => $p->id,
                    'stato' => $p->stato,
                    'customer' => $p->customer?->nome . ' ' . $p->customer?->cognome,
                    'quote_request_id' => $p->quote_request_id,
                    'meta' => $p->meta_viaggio,
                    'data_preventivo' => $p->data_preventivo,
                    'date_expiration' => $p->date_expiration,
                    'prezzo_per_persona' => $p->prezzo_per_persona,
                    'numero_persone' => $p->numero_persone,
                    'cod_alfa' => $p->cod_alfa,
                    
                ];
            }),
            'summary_preventivi' => [
                'preventivi_evasi' => $preventivi_evasi,
                'preventivi_tot' => $totalPrev,
                'preventivi_totali' => $total_preventivi,
                'percentuale_preventivi' => $percentPreventivi,
            ],

        ]);
    }


    public function index() //schermata home amministratore
    {
        $user = Auth::user(); // l’utente loggato tramite Sanctum

    $agents = User::whereHas('role', fn($r) => $r->where('nome', 'agente'))
        ->with('tipo_richieste')
        ->get();

        $totali = QuoteRequest::count();
        $evase = QuoteRequest::where('stato_richiesta', 'evasa')->count();
        $inevase = QuoteRequest::whereIn('stato_richiesta', ['inviata', 'in lavorazione'])->count();

        $percentuale = $totali > 0 ? round(($evase / $totali) * 100) : 0;

        $quoteRequests = QuoteRequest::with(['customer', 'disponibilita.user'])
            ->orderBy('id', 'desc')
            ->paginate(5);


        $data = $quoteRequests->map(function ($req) {
            $tipoIds = $req->tipo_richieste ?? [];

            return [
                'id' => $req->id,
                'oggetto' => $req->oggetto,
                'stato_richiesta' => $req->stato_richiesta,
                'motivazione_archivio' => $req->motivazione_archivio,
                'customer_id' => $req->customer_id,
                'sono_gia_disponibile' => $req->sono_gia_disponibile,
                'nome_cliente' => $req->customer?->nome,
                'cognome_cliente' => $req->customer?->cognome,
                'data_ricezione_richiesta' => $req->data_ricezione_richiesta,
                'meta_viaggio' => $req->meta_viaggio,
                'scadenza' => $req->scadenza,
                'note' => $req->note,
                'tipo_richieste' => collect($tipoIds)
                    ->filter()
                    ->map(function ($typeId) {
                        $type = \App\Models\TypeRequest::find($typeId);
                        return $type
                            ? ['id' => $type->id, 'nome' => $type->nome]
                            : null;
                    })
                    ->filter()
                    ->values()
                    ->toArray(),

                'availability_response' => $req->disponibilita->isNotEmpty()
                    ? $req->disponibilita
                        ->sortByDesc('created_at')
                        ->values()
                        ->filter()
                        ->map(
                            function ($agente) use ($req) {
                                return [
                                    'id' => $agente->id,
                                    'quote_request_id' => $req->id,
                                    'user_id' => $agente->user_id,
                                    'stato' => $agente->stato,
                                    'n_giorni_preventivo' => $agente->n_giorni_preventivo,
                                    'motivazione_rifiuto' => $agente->motivazione_rifiuto,
                                    'created_at' => $agente->created_at->toDateTimeString(),
                                ];
                            }
                        ) : [],
            ];
        });


        //prev
        $preventivi = Preventive::with(['customer', 'quote_request'])->orderBy('id', 'desc')
            ->paginate(5);
        $total_preventivi = Preventive::count();
        $preventivi_evasi = Preventive::where('stato', '!=','bozza')->count();
        $preventivi_inevasi = $total_preventivi - $preventivi_evasi;
        $percentPreventivi = $total_preventivi > 0 ? intval(($preventivi_evasi / $total_preventivi) * 100) : 0;

        return response()->json([
            'user' => $user,
            'data' => $data,
            'summary' => [ //array associativo (chiavi → valori) quindi in app sarà "summary":{"richieste_evase":0,"richieste_gestibili":1,"label":"0 su 1","percentuale":0}}
                'richieste_totali' => $totali,
                'richieste_evase' => $evase,
                'richieste_inevase' => $inevase,
                'label' => "$evase su $totali",
                'percentuale' => $percentuale,
            ],
            'agents' => $agents,
            'preventivi' => $preventivi->map(function ($p) {
                return [
                    'id' => $p->id,
                    'stato' => $p->stato,
                    'created_by' => $p->creator?->nome . ' ' . $p->creator?->cognome,
                    'customer' => $p->customer?->nome . ' ' . $p->customer?->cognome,
                    'meta' => $p->meta_viaggio,
                    'quote_request_id' => $p->quote_request_id,
                    'data_preventivo' => $p->data_preventivo,
                    'date_expiration' => $p->date_expiration,
                    'prezzo_per_persona' => $p->prezzo_per_persona,
                    'numero_persone' => $p->numero_persone,
                    'cod_alfa' => $p->cod_alfa,
                    'allego_file' => $p->allego_file,
                    'file_preventivo' => $p->file_preventivo
                ];
            }),
            'summary_preventivi' => [
                'preventivi_evasi' => $preventivi_evasi,
                'preventivi_totali' => $total_preventivi,
                'percentuale_preventivi' => $percentPreventivi,
            ],
        ]);
    }


    public function archiviate(Request $request)
    {
        try {
            $user = $request->user();

            $quoteRequests = QuoteRequest::with(['customer'])
                ->where('stato_richiesta', 'archiviata')
                ->orderBy('id', 'desc')
                ->paginate(5);

            $data = $quoteRequests->map(function ($req) {
                return [
                    'id' => $req->id,
                    'oggetto' => $req->oggetto ?? '',
                    'stato_richiesta' => $req->stato_richiesta,
                    'motivazione_archivio' => $req->motivazione_archivio,
                    'customer_id' => $req->customer_id,
                    'sono_gia_disponibile' => $req->sono_gia_disponibile ?? false,
                    'nome_cliente' => $req->customer?->nome ?? '',
                    'cognome_cliente' => $req->customer?->cognome ?? '',
                    'data_ricezione_richiesta' => $req->data_ricezione_richiesta ?? null,
                    'meta_viaggio' => $req->meta_viaggio ?? '',
                    'scadenza' => $req->scadenza ?? null,
                    'note' => $req->note ?? '',
                    'tipo_richieste' => is_array($req->tipo_richieste)
                        ? collect($req->tipo_richieste)
                            ->map(function ($typeId) {
                                $type = \App\Models\TypeRequest::find($typeId);
                                return $type ? ['id' => $type->id, 'nome' => $type->nome] : null;
                            })
                            ->filter()
                            ->values()
                        : [],
                    'availability_response' => [],
                ];
            });

            return response()->json([
                'user' => $user,
                'data' => $data,
                'summary' => [
                    'richieste_totali' => $quoteRequests->total(),
                    'richieste_evase' => 0,
                    'richieste_gestibili' => 0,
                    'label' => '0 su 0',
                    'percentuale' => 0,
                ],
                'preventivi' => [],
                'summary_preventivi' => [
                    'preventivi_evasi' => 0,
                    'preventivi_tot' => 0,
                    'preventivi_totali' => 0,
                    'percentuale_preventivi' => 0,
                ],
            ]);

        } catch (\Exception $e) {
            \Log::error('Errore richieste archiviate', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function edit($id)
    {
        $req = QuoteRequest::with(['customer', 'disponibilita.user'])
            ->findOrFail($id);


        // tutte le tipologie
        $typeRequests = TypeRequest::with([
            'users' => function ($u) {
                $u->where('n_preventivi_gestibili', '>', 0);
            }
        ])->get();


        $creataDaUser = null;
        if ($req->created_by) {
            $creataDaUser = User::find($req->created_by);
        }

        $assegnataDaUser = null;
        if ($req->assegnata_da) {
            $assegnataDaUser = User::find($req->assegnata_da);
        }
        $creatorAvailability = $req->disponibilita
            ->where('user_id', $req->created_by)
            ->first();

        // tutti i clienti
        $customers = Customer::all();

        $admins = User::whereHas('role', fn($r) => $r->where('nome', 'superadmin')
            ->orWhere('nome', 'admin'))->get();

        $tipoIds = $req->tipo_richieste ?? [];

        return response()->json([
            'request' => [
                'id' => $req->id,
                'oggetto' => $req->oggetto,
                'customer_id' => $req->customer_id,
                'type_request_id' => $req->type_request_id,
                'created_by' => $req->created_by,
                'creata_da_user' => $creataDaUser ? [
                    'id' => $creataDaUser->id,
                    'nome' => $creataDaUser->nome,
                    'cognome' => $creataDaUser->cognome,
                ] : null,
                'nome_cliente' => $req->customer?->nome,
                'cognome_cliente' => $req->customer?->cognome,
                'nome_tipologia' => $req->tipo_richiesta?->nome,
                'stato_richiesta' => $req->stato_richiesta,
                'motivazione_archivio' => $req->motivazione_archivio,
                'email_cliente' => $req->email_cliente,
                'sono_gia_disponibile' => $req->sono_gia_disponibile,
                'cliente_gestito_da_me' => $req->cliente_gestito_da_me,
                'n_giorni_preventivo' => $creatorAvailability?->n_giorni_preventivo,
                'data_ricezione_richiesta' => $req->data_ricezione_richiesta,
                'meta_viaggio' => $req->meta_viaggio,
                'scadenza' => $req->scadenza,
                'note' => $req->note,
                'tipo_richieste' => collect($tipoIds)
                    ->filter()
                    ->map(function ($typeId) {
                        $type = \App\Models\TypeRequest::find($typeId);
                        return $type
                            ? ['id' => $type->id, 'nome' => $type->nome]
                            : null;
                    })
                    ->filter()
                    ->values()
                    ->toArray(),
                'assegnata_da' => $req->user?->id,
                'assegnata_da_user' => $assegnataDaUser ? [
                    'id' => $assegnataDaUser->id,
                    'nome' => $assegnataDaUser->nome,
                    'cognome' => $assegnataDaUser->cognome,
                ] : null,
                'gestita_da' => $req->agenti_gestori->map(fn($a) => [
                    'id' => $a->id,
                    'nome' => $a->nome,
                    'cognome' => $a->cognome,
                    'email' => $a->email,
                ]),
                'data_assegnazione' => $req->data_assegnazione,
                'agenti_inviati' => $req->agenti_inviati->pluck('id'),
                'agenti_info' => $req->agenti_inviati->map(fn($u) => [
                    'id' => $u->id,
                    'nome' => $u->nome,
                    'cognome' => $u->cognome,
                    'email' => $u->email,
                    'availability_response' => $req->disponibilita
                        ->sortByDesc('created_at')
                        ->values()
                        ->filter()
                        ->map(
                            function ($agente) use ($req) {
                                return [
                                    'id' => $agente->id,
                                    'quote_request_id' => $req->id,
                                    'user_id' => $agente->user_id,
                                    'stato' => $agente->stato,
                                    'n_giorni_preventivo' => $agente->n_giorni_preventivo,
                                    'motivazione_rifiuto' => $agente->motivazione_rifiuto,
                                    'created_at' => $agente->created_at->toDateTimeString(),
                                ];
                            }
                        ),

                ]),
                'availability_response' => $req->disponibilita
                    ->sortByDesc('created_at')
                    ->values()
                    ->filter()
                    ->map(
                        function ($agente) use ($req) {
                            return [
                                'id' => $agente->id,
                                'quote_request_id' => $req->id,
                                'user_id' => $agente->user_id,
                                'nome_agente' => $agente->user->nome,
                                'cognome_agente' => $agente->user->cognome,
                                'stato' => $agente->stato,
                                'n_giorni_preventivo' => $agente->n_giorni_preventivo,
                                'motivazione_rifiuto' => $agente->motivazione_rifiuto,
                                'created_at' => $agente->created_at->toDateTimeString(),
                            ];
                        }
                    ),
            ],
            'type_requests' => $typeRequests,
            'customers' => $customers,
            'admins' => $admins,
        ]);
        ;
    }


    public function storeWithAgents(Request $request)
    {
        // Validazione dei dati in ingresso
        $validated = $request->validate([
            'created_by' => 'nullable|exists:users,id',
            'customer_id' => 'required|exists:customers,id',
            'oggetto' => 'nullable|string|max:255',
            'email_cliente' => 'nullable|email',
            'meta_viaggio' => 'nullable|string|max:255',
            'data_ricezione_richiesta' => 'nullable|date',
            'sono_gia_disponibile' => 'nullable|boolean',
            'cliente_gestito_da_me' => 'nullable|boolean',
            'scadenza' => 'nullable|date',
            'note' => 'nullable|string',
            'n_giorni_preventivo' => 'nullable|integer|min:1|max:8',

            // Multi-selezione agenti da notificare
            'agenti_inviati' => 'required|array|min:1',
            'agenti_inviati.*' => 'exists:users,id',

            // Agenti da assegnare direttamente (opzionale)
            'gestita_da' => 'nullable|array',
            'gestita_da.*' => 'exists:users,id',

            'assegnata_da' => 'nullable|exists:users,id',
            'data_assegnazione' => 'nullable|date',

            'tipo_richieste' => 'nullable|array',
            'tipo_richieste.*' => 'exists:type_requests,id',
        ]);

        // Verifica che gli agenti inviati siano presenti
        if (empty($validated['agenti_inviati'])) {
            return response()->json([
                'message' => 'Devi selezionare almeno un agente per procedere.',
            ], 400);
        }

        $createdBy = $validated['created_by'] ?? auth()->id();
        $sonoDisponibile = $validated['sono_gia_disponibile'] ?? false;
        $hasGestori = !empty($validated['gestita_da']);

        $quoteRequest = QuoteRequest::create([
            'customer_id' => $validated['customer_id'],
            'oggetto' => $validated['oggetto'] ?? null,
            'email_cliente' => $validated['email_cliente'] ?? null,
            'meta_viaggio' => $validated['meta_viaggio'] ?? null,
            'data_ricezione_richiesta' => $validated['data_ricezione_richiesta'] ?? now(),
            'data_assegnazione' => now(),
            'sono_gia_disponibile' => $sonoDisponibile,
            'cliente_gestito_da_me' => $validated['cliente_gestito_da_me'] ?? null,
            'scadenza' => $validated['scadenza'] ?? null,
            'note' => $validated['note'] ?? null,
            'assegnata_da' => ($hasGestori || $sonoDisponibile) ? ($validated['assegnata_da'] ?? $createdBy) : null,
            'created_by' => $createdBy,
            'tipo_richieste' => $validated['tipo_richieste'] ?? null,
            'stato_richiesta' => 'assegnata',
        ]);

        // Filtra gli agenti da inviare (esclude il creatore)
        $agentiInviatiFiltered = array_filter(
            $validated['agenti_inviati'],
            fn($id) => $id != $createdBy
        );

        // Associa gli agenti a cui inviare la notifica
        $quoteRequest->agenti_inviati()->sync($agentiInviatiFiltered);

        // Gestione agenti assegnati direttamente
        $agentiAssegnati = [];
        if ($hasGestori) {
            // Filtra gli agenti gestori (esclude il creatore se già disponibile e l'assegnatore)
            $assegnatoreId = $validated['assegnata_da'] ?? $createdBy;
            $agentiGestoriFiltered = array_filter(
                $validated['gestita_da'],
                fn($id) => $id != $assegnatoreId
            );

            // Sync degli agenti gestori
            $quoteRequest->agenti_gestori()->sync($agentiGestoriFiltered, false);

            $agentiAssegnati = $agentiGestoriFiltered;

            // ========================================
            // CREA LA DISPONIBILITÀ PER OGNI AGENTE ASSEGNATO
            // ========================================
            foreach ($agentiGestoriFiltered as $agenteId) {
                AgentAvailability::create([
                    'user_id' => $agenteId,
                    'quote_request_id' => $quoteRequest->id,
                    'stato' => 'disponibile',
                    'n_giorni_preventivo' => null, // L'agente lo inserirà dopo
                    'motivazione_rifiuto' => null,
                    'start_date' => null,
                    'end_date' => null,
                    'start_time' => null,
                    'end_time' => null,
                    'note' => 'Assegnazione diretta da amministratore',
                ]);
            }
        }

        // Se l'utente loggato è già disponibile, crea anche la sua disponibilità
        if ($sonoDisponibile) {
            AgentAvailability::create([
                'user_id' => $createdBy,
                'quote_request_id' => $quoteRequest->id,
                'stato' => 'disponibile',
                'n_giorni_preventivo' => $validated['n_giorni_preventivo'] ?? null,
                'motivazione_rifiuto' => null,
                'start_date' => null,
                'end_date' => null,
                'start_time' => null,
                'end_time' => null,
                'note' => 'Auto-assegnazione',
            ]);
        }

        // NOTIFICHE

        // 1. Notifica agli agenti SOLO inviati (non assegnati)
        $agentiSoloNotifica = array_diff($agentiInviatiFiltered, $agentiAssegnati);

        if (!empty($agentiSoloNotifica)) {
            $usersNotifica = User::whereIn('id', $agentiSoloNotifica)->get();

            $titleNotifica = 'Nuova Richiesta Disponibile';
            $bodyNotifica = "È disponibile una nuova richiesta: {$quoteRequest->oggetto}. Conferma la tua disponibilità.";

            NotificationFacade::send(
                $usersNotifica,
                new PersonalNotification(
                    title: $titleNotifica,
                    body: $bodyNotifica,
                    type: 'quote_request',
                )
            );

            \Filament\Notifications\Notification::make()
                ->title($titleNotifica)
                ->body($bodyNotifica)
                ->success()
                ->actions([
                    Action::make('Track Order')
                        ->label("Vedi Richiesta")
                        ->button()
                        ->url(
                            QuoteRequestResource::getUrl('view', ['record' => $quoteRequest->id]),
                            shouldOpenInNewTab: true
                        ),
                ])
                ->sendToDatabase($usersNotifica)
                ->broadcast($usersNotifica);

            // FCM per agenti solo notificati
            $this->sendFCMNotifications(
                $usersNotifica,
                $titleNotifica,
                $bodyNotifica,
                $quoteRequest->id
            );
        }

        // 2. Notifica e gestione agenti ASSEGNATI
        if (!empty($agentiAssegnati)) {
            $usersAssegnati = User::whereIn('id', $agentiAssegnati)->get();

            foreach ($usersAssegnati as $user) {
                if ($user->role->nome === 'agente') {
                    // Decrementa preventivi gestibili
                    if ($user->n_preventivi_gestibili > 0) {
                        $user->update([
                            'n_preventivi_gestibili' => max(0, $user->n_preventivi_gestibili - 1),
                        ]);
                    }

                    // Notifica per inserire i giorni del preventivo
                    $titleAssegnazione = 'Richiesta Assegnata - Inserisci Giorni';
                    $bodyAssegnazione = "Ti è stata assegnata la richiesta: {$quoteRequest->oggetto}. Inserisci i giorni necessari per completarla.";

                    NotificationFacade::send(
                        $user,
                        new PersonalNotification(
                            title: $titleAssegnazione,
                            body: $bodyAssegnazione,
                            type: 'quote_request_preventivo',
                        )
                    );

                    \Filament\Notifications\Notification::make()
                        ->title($titleAssegnazione)
                        ->body($bodyAssegnazione)
                        ->warning() // Warning per indicare azione richiesta
                        ->sendToDatabase($user)
                        ->send();

                    // FCM per singolo agente assegnato
                    if ($user->fcm_token && !$user->hasMuteNotifications()) {
                        $messaging = (new Factory)
                            ->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')))
                            ->createMessaging();

                        $message = CloudMessage::fromArray([
                            'token' => $user->fcm_token,
                            'notification' => [
                                'title' => $titleAssegnazione,
                                'body' => $bodyAssegnazione,
                            ],
                            'data' => [
                                'type' => 'quote_request_preventivo',
                                'quote_request_id' => (string) $quoteRequest->id,
                                'action' => 'insert_preventivo',
                                'title' => $titleAssegnazione,
                                'body' => $bodyAssegnazione,
                            ],
                            'android' => [
                                'priority' => 'high',
                                'notification' => [
                                    'channel_id' => 'high_importance_channel',
                                    'sound' => 'default'
                                ],
                            ],
                            'apns' => [
                                'headers' => [
                                    'apns-priority' => '10',
                                ],
                                'payload' => [
                                    'aps' => [
                                        'alert' => [
                                            'title' => $titleAssegnazione,
                                            'body' => $bodyAssegnazione,
                                        ],
                                        'sound' => 'default',
                                    ],
                                ],
                            ],
                        ]);

                        try {
                            $messaging->send($message);
                        } catch (MessagingException | Throwable $e) {
                            report($e);
                        }
                    }
                }
            }
        }

        // Messaggio di risposta
        $infoMessage = [];
        if ($sonoDisponibile) {
            $infoMessage[] = 'Richiesta assegnata a te.';
        }
        if (!empty($agentiAssegnati)) {
            $infoMessage[] = count($agentiAssegnati) . ' agente/i assegnato/i direttamente.';
        }
        if (!empty($agentiSoloNotifica)) {
            $infoMessage[] = count($agentiSoloNotifica) . ' agente/i notificato/i per conferma disponibilità.';
        }

        return response()->json([
            'message' => 'Richiesta creata con successo.',
            'data' => $quoteRequest->load(['agenti_inviati', 'agenti_gestori', 'customer', 'disponibilita']),
            'info' => implode(' ', $infoMessage),
        ], 201);
    }

    /**
     * Metodo helper per inviare notifiche FCM a multipli utenti
     */
    private function sendFCMNotifications($users, $title, $body, $quoteRequestId)
    {
        $tokens = $users
            ->filter(fn($user) => $user->fcm_token && !$user->hasMuteNotifications())
            ->pluck('fcm_token')
            ->values()
            ->all();

        if (empty($tokens)) {
            return;
        }

        $messaging = (new Factory)
            ->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')))
            ->createMessaging();

        $base = CloudMessage::fromArray([
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => [
                'type' => 'quote_request',
                'quote_request_id' => (string) $quoteRequestId,
                'title' => $title,
                'body' => $body,
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
                            'title' => $title,
                            'body' => $body,
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
   public function store(Request $request)
{
    $validated = $request->validate([
        'customer_id' => 'required|exists:customers,id',
        'oggetto' => 'nullable|string|max:255',
        'email_cliente' => 'nullable|email',
        'meta_viaggio' => 'nullable|string|max:255',
        'data_ricezione_richiesta' => 'nullable|date',
        'data_assegnazione' => 'nullable|date',
        'sono_gia_disponibile' => 'nullable|boolean',
        'cliente_gestito_da_me' => 'nullable|boolean',
        'stato_richiesta' => 'nullable|in:inviata,risposta pervenuta,in lavorazione,non completata, evasa',
        'scadenza' => 'nullable|date',
        'note' => 'nullable|string',
        'assegnata_da' => 'nullable|exists:users,id',
        'created_by' => 'nullable|exists:users,id',
        'n_giorni_preventivo' => 'nullable|integer|min:1|max:8',
        'agenti_inviati' => 'nullable|array',
        'agenti_inviati.*' => 'exists:users,id',
        'tipo_richieste' => 'nullable|array',
        'tipo_richieste.*' => 'exists:type_requests,id',
    ]);

    $sonoDisponibile = $validated['sono_gia_disponibile'] ?? false;
    $createdBy = $validated['created_by'] ?? auth()->id();
    $clienteGestitoDaMe = $validated['cliente_gestito_da_me'] ?? false;

    // Crea la richiesta
    $quoteRequest = QuoteRequest::create([
        'customer_id' => $validated['customer_id'],
        'oggetto' => $validated['oggetto'] ?? null,
        'email_cliente' => $validated['email_cliente'] ?? null,
        'meta_viaggio' => $validated['meta_viaggio'] ?? null,
        'data_ricezione_richiesta' => $validated['data_ricezione_richiesta'] ?? null,
        'data_assegnazione' => $validated['data_assegnazione'] ?? null,
        'sono_gia_disponibile' => $validated['sono_gia_disponibile'] ?? null,
        'cliente_gestito_da_me' => $validated['cliente_gestito_da_me'] ?? null,
        'scadenza' => $validated['scadenza'] ?? null,
        'note' => $validated['note'] ?? null,
        'assegnata_da' => $validated['assegnata_da'] ?? null,
        'created_by' => $validated['created_by'] ?? null,
        'tipo_richieste' => $validated['tipo_richieste'] ?? null,
    ]);

    $agentiDaNotificare = [];

    if (!empty($validated['agenti_inviati'])) {
        $agentiDaNotificare = array_filter(
            $validated['agenti_inviati'], 
            fn($id) => $id != $createdBy
        );
        $quoteRequest->agenti_inviati()->sync($agentiDaNotificare);
    } elseif (!$clienteGestitoDaMe && !$sonoDisponibile) {
        $tipoRichiesteIds = $validated['tipo_richieste'] ?? [];
        
        if (!empty($tipoRichiesteIds)) {
            $agentiDaNotificare = User::query()
                ->whereHas('role', fn($q) => $q->where('nome', 'agente'))
                ->whereHas('tipo_richieste', fn($q) => $q->whereIn('type_requests.id', $tipoRichiesteIds))
                ->where('n_preventivi_gestibili', '>', 0)
                ->where('id', '!=', $createdBy)
                ->pluck('id')
                ->all();
            
            $quoteRequest->agenti_inviati()->sync($agentiDaNotificare);
        }
    }

    if ($sonoDisponibile || $clienteGestitoDaMe) {
        $user = User::find($createdBy);

        if ($user && $user->role && $user->role->nome === 'agente') {
            AgentAvailability::create([
                'user_id' => $user->id,
                'quote_request_id' => $quoteRequest->id,
                'stato' => 'disponibile',
                'n_giorni_preventivo' => $validated['n_giorni_preventivo'] ?? null,
                'motivazione_rifiuto' => null,
                'start_date' => null,
                'end_date' => null,
                'start_time' => null,
                'end_time' => null,
                'note' => $clienteGestitoDaMe ? 'Cliente gestito da me' : 'Auto-assegnazione',
            ]);
        }
    }

    $admins = User::query()
        ->whereHas('role', fn($q) => $q->whereIn('nome', ['superadmin', 'admin']))
        ->pluck('id')
        ->all();

    $recipientIds = ($clienteGestitoDaMe || $sonoDisponibile)
        ? $admins
        : array_unique(array_merge($agentiDaNotificare, $admins));

    if (!empty($createdBy)) {
        $recipientIds = array_filter($recipientIds, fn($id) => $id != $createdBy);
    }

    if (!empty($recipientIds)) {
        $users_rec = User::whereIn('id', $recipientIds)->get();

        $title = 'Nuova Richiesta';
        $body = "Hai ricevuto una nuova richiesta: {$quoteRequest->oggetto}";

        NotificationFacade::send(
            $users_rec,
            new PersonalNotification(
                title: $title,
                body: $body,
                type: 'quote_request',
            )
        );

        \Filament\Notifications\Notification::make()
            ->title($title)
            ->body($body)
            ->success()
            ->actions([
                Action::make('Track Order')
                    ->label("Vedi Richiesta")
                    ->button()
                    ->url(
                        QuoteRequestResource::getUrl('view', ['record' => $quoteRequest->id]),
                        shouldOpenInNewTab: true
                    ),
            ])
            ->sendToDatabase($users_rec)
            ->send();

        $tokens = $users_rec
            ->filter(fn($user) => $user->fcm_token && !$user->hasMuteNotifications())
            ->pluck('fcm_token')
            ->values()
            ->all();

        if (!empty($tokens)) {
            $credentialsPath = base_path(config('services.firebase.credentials', env('FIREBASE_CREDENTIALS')));
            $messaging = (new Factory)
                ->withServiceAccount($credentialsPath)
                ->createMessaging();

            $message = CloudMessage::fromArray([
                'notification' => [
                    'title' => $title,
                    'body' => $body,
                ],
                'data' => [
                    'type' => 'quote_request',
                    'quote_request_id' => (string) $quoteRequest->id,
                    'title' => $title,
                    'body' => $body,
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
                                'title' => $title,
                                'body' => $body,
                            ],
                            'sound' => 'default',
                        ],
                    ],
                ],
            ]);

            foreach (array_chunk($tokens, 500) as $batch) {
                try {
                    $messaging->sendMulticast($message, $batch);
                } catch (MessagingException | Throwable $e) {
                    report($e);
                }
            }
        }
    }

    return response()->json([
        'message' => 'Richiesta creata con successo',
        'data' => $quoteRequest->load('agenti_inviati'),
    ], 200);
}

    public function update(Request $request, $id)
    {
        $req = QuoteRequest::findOrFail($id);

        $validated = $request->validate([
            'customer_id' => 'exists:customers,id',
            'oggetto' => 'nullable|string|max:255',
            'email_cliente' => 'nullable|email',
            'meta_viaggio' => 'nullable|string|max:255',
            'data_ricezione_richiesta' => 'nullable|date',
            'stato_richiesta' => 'nullable|string',
            'note' => 'nullable|string',
            'agenti_inviati' => 'array',
            'agenti_inviati.*' => 'exists:users,id',
            'scadenza' => 'nullable|date',
            'gestita_da' => 'nullable|array',
            'gestita_da.*' => 'exists:users,id',
            'assegnata_da' => 'nullable|exists:users,id',
            'data_assegnazione' => 'nullable|date',
            'tipo_richieste' => 'nullable|array',
            'tipo_richieste.*' => 'exists:type_requests,id',
        ]);

        $sonoDisponibile = $validated['sono_gia_disponibile'] ?? false;
        $clienteGestitoDaMe = $validated['cliente_gestito_da_me'] ?? false;
        $createdBy = $req->created_by ?? auth()->id();

        // tolgo agenti_inviati dai campi per l'update classico
        $data = collect($validated)->except('agenti_inviati', 'gestita_da')->toArray();
        if ($sonoDisponibile || $clienteGestitoDaMe) {
            $user = User::find($createdBy);

            if ($user && $user->role && $user->role->nome === 'agente') {
                // Controlla se esiste già una disponibilità
                $existingAvailability = AgentAvailability::where('user_id', $user->id)
                    ->where('quote_request_id', $req->id)
                    ->first();

                if ($existingAvailability) {
                    // Aggiorna esistente
                    $existingAvailability->update([
                        'stato' => 'disponibile',
                        'n_giorni_preventivo' => $validated['n_giorni_preventivo'] ?? $existingAvailability->n_giorni_preventivo,
                        'note' => $clienteGestitoDaMe ? 'Cliente gestito da me' : 'Auto-assegnazione',
                    ]);
                } else {
                    // Crea nuova
                    AgentAvailability::create([
                        'user_id' => $user->id,
                        'quote_request_id' => $req->id,
                        'stato' => 'disponibile',
                        'n_giorni_preventivo' => $validated['n_giorni_preventivo'] ?? null,
                        'motivazione_rifiuto' => null,
                        'start_date' => null,
                        'end_date' => null,
                        'start_time' => null,
                        'end_time' => null,
                        'note' => $clienteGestitoDaMe ? 'Cliente gestito da me' : 'Auto-assegnazione',
                    ]);
                }
            }
        }
        // aggiorno solo le colonne del DB
        $req->update($data);

        // aggiorno relazione many-to-many se è stata inviata
        if (array_key_exists('agenti_inviati', $validated)) {
            $req->agenti_inviati()->sync($validated['agenti_inviati']);
        }
        if (array_key_exists('gestita_da', $validated)) {
            $req->agenti_gestori()->sync($validated['gestita_da']);
        }
        return response()->json([
            'message' => 'Richiesta aggiornata con successo',
            'data' => $req->load(['customer', 'agenti_inviati', 'agenti_gestori'])
        ]);
    }



    public function notificaassegnazione(Request $request, $id)
    {
        $req = QuoteRequest::findOrFail($id);

        $validated = $request->validate([
            'customer_id' => 'exists:customers,id',
            'oggetto' => 'nullable|string|max:255',
            'email_cliente' => 'nullable|email',
            'meta_viaggio' => 'nullable|string|max:255',
            'data_ricezione_richiesta' => 'nullable|date',
            'stato_richiesta' => 'nullable|string',
            'note' => 'nullable|string',
            'agenti_inviati' => 'array',
            'agenti_inviati.*' => 'exists:users,id',
            'scadenza' => 'nullable|date',
            'gestita_da' => 'nullable|array',
            'gestita_da.*' => 'exists:users,id',
            'assegnata_da' => 'nullable|exists:users,id',
            'data_assegnazione' => 'nullable|date',
            'tipo_richieste' => 'nullable|array',
            'tipo_richieste.*' => 'exists:type_requests,id',
        ]);


        // tolgo agenti_inviati dai campi per l'update classico
        $data = collect($validated)->except('agenti_inviati', 'gestita_da')->toArray();

        // aggiorno solo le colonne del DB
        $req->update($data);

        // aggiorno relazione many-to-many se è stata inviata
        if (array_key_exists('agenti_inviati', $validated)) {
            $req->agenti_inviati()->sync($validated['agenti_inviati']);
        }
        if (array_key_exists('gestita_da', $validated)) {
            $req->agenti_gestori()->sync($validated['gestita_da']);
        }


        $assegnatoreId = $validated['assegnata_da'] ?? Auth::id();

        // Filtra solo gli agenti che non sono l’assegnatore
        $agentiDaNotificare = $req->agenti_gestori->filter(function ($user) use ($assegnatoreId) {
            return $user->id !== $assegnatoreId;
        });

        foreach ($agentiDaNotificare as $user) {


            if ($user->role->nome === 'agente') {
                $req->update([
                    'stato_richiesta' => 'in lavorazione',
                ]);
                if ($user->n_preventivi_gestibili > 0) {
                    $user->update([
                        'n_preventivi_gestibili' => max(0, $user->n_preventivi_gestibili - 1),
                    ]);
                }



                $title = 'Richiesta Assegnata';
                $body = "Ti è stata assegnata la richiesta: {$req->oggetto}";

                NotificationFacade::send(
                    $user,
                    new PersonalNotification(
                        title: $title,
                        body: $body,
                        type: 'quote_request',
                    )
                );

                \Filament\Notifications\Notification::make()
                    ->title($title)
                    ->body($body)
                    ->success()
                    ->actions([
                        Action::make('Track Order')
                            ->label("Vedi Richiesta")
                            ->button()
                            ->url(
                                QuoteRequestResource::getUrl('view', ['record' => $req->id]),
                                shouldOpenInNewTab: true
                            ),
                    ])
                    ->sendToDatabase($user)
                    ->send();


                // push FCM
                if ($user->fcm_token && !$user->hasMuteNotifications()) {
                    $messaging = (new Factory)
                        ->withServiceAccount(base_path(env('FIREBASE_CREDENTIALS')))
                        ->createMessaging();

                    $message = CloudMessage::fromArray([
                        'token' => $user->fcm_token,
                        'notification' => [
                            'title' => 'Richiesta Assegnata',
                            'body' => "Ti è stata assegnata la richiesta: {$req->oggetto}",
                        ],
                        'data' => [
                            'title' => 'Richiesta Assegnata',
                            'body' => "Ti è stata assegnata la richiesta: {$req->oggetto}",
                            'id' => (string) $req->id,
                        ],
                        'android' => [
                            'priority' => 'high',
                            'notification' => [
                                'channel_id' => 'high_importance_channel',
                                'sound' => 'default'
                            ],
                        ],
                        'apns' => [
                            'headers' => [
                                'apns-priority' => '10', // 10 = immediata
                            ],
                            'payload' => [
                                'aps' => [
                                    'alert' => [
                                        'title' => 'Richiesta Assegnata',
                                        'body' => "Ti è stata assegnata la richiesta: {$req->oggetto}",
                                    ],
                                    'sound' => 'default', // Usa il suono di sistema
                                ],
                            ],
                        ],
                    ]);
                    $messaging->send($message);
                }
            }
        }


        return response()->json([
            'message' => 'Richiesta aggiornata con successo',
            'data' => $req->load(['customer', 'agenti_inviati', 'agenti_gestori'])
        ]);
    }

    public function delete($id)
    {
        $req = QuoteRequest::find($id);
        if (!$req) {
            return response()->json([
                'message' => 'Richiesta non trovata'
            ], 404);
        }

        $req->preventives()->delete();
        $req->disponibilita()->delete();

        $req->agenti_inviati()->detach(); //per staccare le relazioni
        $req->agenti_gestori()->detach(); //per staccare le relazioni

        $req->delete();

        return response()->json([
            'message' => 'Richiesta rimossa con successo'
        ], 200);
    }
}
