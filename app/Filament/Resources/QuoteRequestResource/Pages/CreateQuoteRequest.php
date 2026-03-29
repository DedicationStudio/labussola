<?php

namespace App\Filament\Resources\QuoteRequestResource\Pages;

use App\Filament\Resources\QuoteRequestResource;
use App\Models\QuoteRequest;
use App\Models\RequestWp;
use App\Models\User;
use App\Notifications\PersonalNotification;
use App\PreventiveStatus;
use App\QuoteRequestStatus;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Exception\MessagingException;
use Throwable;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Filament\Actions;
use Filament\Notifications\Actions\Action as NotificationAction;

class CreateQuoteRequest extends CreateRecord
{
    protected static string $resource = QuoteRequestResource::class;

    protected static ?string $title = 'Nuova Richiesta Interna';

    public bool $fromRequestWp = false;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();
        return $data;
    }

    

    public function mount(): void
{
    parent::mount();

    $data = [];

    if ($id = request('duplicate_from')) {
    $source = QuoteRequest::find($id);

    if ($source) {
        $data = array_merge(
            $source->duplicatedAttributes(),
            [
                'created_by' => auth()->id(),
                'stato_richiesta' => QuoteRequestStatus::INVIATA->value,
                'assegnata_da' => $source->assegnata_da, // se serve
            ]
        );

        $this->form->fill($data);
        }
    }

    // 🔹 DA REQUEST WP (già esistente)
    elseif ($id = request('from_request_wp')) {
        $requestWp = RequestWp::find($id);

        if ($requestWp) {
            $data = [
                'created_by' => Auth::id(),
                'request_wp_id' => $requestWp->id,
                'email_cliente' => $requestWp->email,
                'messaggio_wp' => $requestWp->messaggio,
                'data_ricezione_richiesta' => $requestWp->created_at,
                'oggetto' => 'Richiesta da sito web',
                'stato_richiesta' => QuoteRequestStatus::INVIATA->value,
            ];
        }
    }

    // 🔹 CUSTOMER FORZATO
    if ($cid = request('customer_id')) {
        $data['customer_id'] = $cid;
    }

    // 🔹 DEFAULT
    $data['stato_richiesta'] ??= QuoteRequestStatus::INVIATA->value;

    $this->form->fill($data);
}
    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('salva')
                ->label('Invia')
                ->color('primary')
                ->action(function () {
                    $this->create();
                    $this->invia();
                }),
        ];
    }
    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('assegna')
                ->label('Assegna')
                ->color('success')
                ->requiresConfirmation()
                ->hidden(fn() => auth()->user()?->hasRole('agente'))
                ->action(function () {

                    $inviaA = $this->data['invia_a'] ?? [];

                    if (empty($inviaA)) {
                        \Filament\Notifications\Notification::make()
                            ->title('Nessun agente selezionato')
                            ->body('Seleziona almeno un agente nel campo "Invia a" per procedere con l\'assegnazione.')
                            ->warning()
                            ->send();

                        $this->halt();
                        return;
                    }

                    $this->create();

                    $this->inviaEAssegna($this->record);
                })
        ];
    }

    private function inviaEAssegna($quoteRequest): void
    {

        DB::transaction(function () use ($quoteRequest) {

            $quoteRequest->load('agenti_inviati');
            // $originalCreatedBy = $quoteRequest->created_by;

            //

            $inviaA = $this->data['invia_a'] ?? [];

            $quoteRequest->update([
                'stato_richiesta' => 'assegnata',
                'data_assegnazione' => now(),
            ]);
            $quoteRequest->agenti_gestori()->sync($inviaA);
            foreach ($inviaA as $userId) {
                $quoteRequest->disponibilita()->updateOrCreate(
                    [
                        'user_id' => $userId,
                        'quote_request_id' => $quoteRequest->id,
                    ],
                    [
                        'stato' => 'disponibile',
                        'n_giorni_preventivo' => $this->data['n_giorni_preventivo'] ?? null,
                    ]
                );
            }

            $agentiDaNotificare = $quoteRequest->agenti_inviati->filter(
                fn($user) => $user->id !== auth()->id()

            );




            foreach ($agentiDaNotificare as $user) {

                \Log::info("Tentativo invio notifica", [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'has_mute' => $user->hasMuteNotifications(),
                ]);

                if ($user->hasMuteNotifications()) {
                    continue;
                }




                // notifica in-app
                if (!is_null($user->n_preventivi_gestibili) && $user->n_preventivi_gestibili > 0) {
                    $user->update([
                        'n_preventivi_gestibili' => max(0, $user->n_preventivi_gestibili - 1),
                    ]);
                }
                $title = 'Richiesta Assegnata';
                $body = "Ti è stata assegnata la richiesta: {$quoteRequest->oggetto}";

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
                        NotificationAction::make('Track Order')
                            ->label("Vedi Richiesta")
                            ->button()
                            ->url(
                                QuoteRequestResource::getUrl('view', ['record' => $quoteRequest->id]),
                                shouldOpenInNewTab: true
                            ),
                    ])
                    ->sendToDatabase($user)
                    ->broadcast($user);

                \Log::info("Notifica salvata per user_id: {$user->id}");


                // CAMBIATO: verifica FCM token e invia notifica push
                if (!empty($user->fcm_token)) {
                    $credentialsPath = base_path(config('services.firebase.credentials', env('FIREBASE_CREDENTIALS')));
                    $messaging = (new Factory)
                        ->withServiceAccount($credentialsPath)
                        ->createMessaging();

                    $message = CloudMessage::new()
                        ->withTarget('token', $user->fcm_token)
                        ->withNotification([
                            'title' => $title,
                            'body' => $body,
                        ])
                        ->withData([
                            'title' => $title,
                            'body' => $body,
                            'id' => (string) $quoteRequest->id,
                        ]);

                    try {
                        $messaging->send($message);
                    } catch (\Throwable $e) {
                        \Log::error("Errore invio FCM a {$user->email}: " . $e->getMessage());
                    }
                }
            }
        });

        \Filament\Notifications\Notification::make()
            ->title('Richiesta assegnata e notifiche inviate')
            ->success()
            ->send();

    }
    private function invia(): void
    {
        $user = auth()->user();

        $sonoDisponibile = $this->data['sono_gia_disponibile'] ?? false;
        $clienteGestito = $this->data['cliente_gestito_da_me'] ?? false;
        $giorniPreventivo = $this->data['n_giorni_preventivo'] ?? null;

        //  1. Gestione disponibilità
        if ($user->hasRole('agente')) {
            if ($sonoDisponibile || $clienteGestito) {
                $this->record->disponibilita()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'stato' => 'disponibile',
                        'n_giorni_preventivo' => $giorniPreventivo,
                    ]
                );

                if ($clienteGestito) {
                $dataAssegnazione = now();
                $dataScadenza = $giorniPreventivo 
                    ? $dataAssegnazione->copy()->addDays((int) $giorniPreventivo)
                    : null;

                $this->record->update([
                    'stato_richiesta' => 'in lavorazione',
                    'data_assegnazione' => $dataAssegnazione,
                    'scadenza' => $dataScadenza,
                ]);

                // Aggiorna le relazioni many-to-many
                $this->record->agenti_inviati()->syncWithoutDetaching([$user->id]);
                $this->record->agenti_gestori()->syncWithoutDetaching([$user->id]);
            }
            }
        }

        $quote = $this->record->loadMissing('agenti_inviati:id,fcm_token');

        //  2. Determina i destinatari
        if ($user->hasRole('agente') && ($sonoDisponibile || $clienteGestito)) {
            $agentIds = User::query()
                ->whereHas('role', fn($r) => $r->whereIn('nome', ['admin', 'superadmin']))
                ->pluck('id')
                ->all();

            $title = 'Nuova Richiesta';
            $body = "Un agente è disponibile per una richiesta: {$quote->oggetto}";
        } else {
           $inviaA = $this->data['invia_a'] ?? [];

            if (!empty($inviaA)) {
            // Se sono stati selezionati agenti, usa solo quelli
            $agentIds = array_filter($inviaA, fn($id) => $id != $user->id);
            
            // Sync nel database
            $this->record->agenti_inviati()->sync($agentIds);
        } else {
            // Se non selezionati, prendi tutti i competenti
            $tipiRichieste = $quote->tipo_richieste ?? [];

            $agentIds = User::query()
                ->whereHas('role', fn($q) => $q->where('nome', 'agente'))
                ->whereHas('tipo_richieste', fn($q) => $q->whereIn('type_requests.id', $tipiRichieste))
                ->where('n_preventivi_gestibili', '>', 0)
                ->where('id', '!=', $user->id)
                ->pluck('id')
                ->all();

            // Sync nel database
            $this->record->agenti_inviati()->sync($agentIds);
        }

            $title = 'Nuova Richiesta';
            $body = "Hai ricevuto una nuova richiesta: {$quote->oggetto}";
        }

        //  3. Filtra utenti che hanno silenziato le notifiche
        $users = User::query()
            ->whereIn('id', $agentIds)
            ->where('id', '!=', $user->id)
            ->get()
            ->reject->hasMuteNotifications();

        if ($users->isEmpty()) {
            return;
        }
        //  4. Crea la notifica Laravel (Database classica)
        NotificationFacade::send(
            $users,
            new PersonalNotification(
                title: $title,
                body: $body,
                type: 'quote_request',
                url: QuoteRequestResource::getUrl('view', ['record' => $quote->id])

            )
        );

        \Filament\Notifications\Notification::make()
    ->title($title)
    ->body($body)
    ->success()
    ->actions([
        NotificationAction::make('Track Order')
            ->label("Vedi Richiesta")
            ->button()
            ->url(
                QuoteRequestResource::getUrl('view', ['record' => $quote->id]),
                shouldOpenInNewTab: true
            ),
    ])
    ->sendToDatabase($users)
    ->broadcast($users);
    //->sendTo($users);

        //  6. Invia notifiche push Firebase (solo se token presenti)
        $tokens = $users
            ->whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->filter()
            ->values()
            ->all();

        if (empty($tokens)) {
            return;
        }

        $credentialsPath = base_path(config('services.firebase.credentials', env('FIREBASE_CREDENTIALS')));

        $messaging = (new Factory)
            ->withServiceAccount($credentialsPath)
            ->createMessaging();

        $base = CloudMessage::fromArray([
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => [
                'type' => 'quote_request',
                'quote_request_id' => (string) $quote->id,
                'title' => $title,
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
                            'title' => $title,
                            'body' => $body,
                        ],
                        'sound' => 'default', // Usa il suono di sistema
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
