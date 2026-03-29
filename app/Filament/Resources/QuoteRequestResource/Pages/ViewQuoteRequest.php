<?php

namespace App\Filament\Resources\QuoteRequestResource\Pages;

use App\Filament\Resources\QuoteRequestResource;
use App\Models\AgentAvailability;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Livewire\Attributes\Reactive;
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
use Filament\Notifications\Actions\Action as NotificationAction;

class ViewQuoteRequest extends ViewRecord
{
    protected static string $resource = QuoteRequestResource::class;
    protected static ?string $title = 'Scheda Richiesta';

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

    private function invia($data, $userName, $url): void
    {
        $user = auth()->user();

        $quote = $this->record->loadMissing('agenti_inviati:id,fcm_token');

        //  2. Determina i destinatari

        $agentIds = User::query()
            ->whereHas('role', fn($r) => $r->whereIn('nome', ['admin', 'superadmin']))
            ->pluck('id')
            ->all();

        $title = "$userName ha motivato la mancata consegna";
        $body = "Motivazione: $data";
        $users = User::query()
            ->whereIn('id', $agentIds)
            ->where('id', '!=', $user->id)
            ->get()
            ->reject->hasMuteNotifications();
        //  4. Crea la notifica Laravel (Database classica)
        NotificationFacade::send(
            $users,
            new PersonalNotification(
                title: $title,
                body: $body,
                type: 'preventivo_scaduto',
            )
        );

        // 🔔 Filament Notification
        \Filament\Notifications\Notification::make()
            ->title($title)
            ->body($body)
            ->danger()
            ->actions([
                \Filament\Notifications\Actions\Action::make('view')
                    ->label("Vedi Preventivo")
                    ->button()
                    ->url(
                        QuoteRequestResource::getUrl('view', ['record' => $url]),
                        shouldOpenInNewTab: true
                    ),
            ])
            ->sendToDatabase($users)
            ->broadcast($users);

        // 🔔 FCM
        $this->sendFCMNotifications(
            $users,
            $title,
            $body,
            $quote->quote_request_id
        );
    }
    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $record = $this->record;

        return [
            // Azione per gestire la disponibilità (solo per agenti)
            Actions\Action::make('gestisci_disponibilita')
                ->label('Imposta Giorni Preventivo')
                ->color('success')
                ->visible(function () use ($user, $record) {

                    if (!$user->hasRole('agente')) {
                        return false;
                    }

                    return
                        // caso 1: è tra gli agenti inviati
                        $record->agenti_inviati->contains($user->id)

                        // caso 2: è tra gli agenti gestori (assegnata / in lavorazione)
                        || $record->agenti_gestori->contains($user->id);
                })
                ->form(function () use ($user, $record) {

                    $disponibilita = $record->disponibilita()
                        ->where('user_id', $user->id)
                        ->first();

                    return [
                        Forms\Components\Select::make('stato')
                            ->label('Stato')
                            ->options([
                                'disponibile' => 'Disponibile',
                                'non_disponibile' => 'Non Disponibile',
                            ])
                            ->required()
                            ->reactive()
                            ->live()
                            ->default($disponibilita->stato ?? 'disponibile'),

                        Forms\Components\DatePicker::make('n_giorni_preventivo')

                            ->label('Numero giorni per il preventivo')
                            ->displayFormat('d/m/Y')
                            ->minDate(now())
                            ->visible(fn(Forms\Get $get) => $get('stato') === 'disponibile')
                            ->required(fn(Forms\Get $get) => $get('stato') === 'disponibile')
                            ->live()
                            ->reactive()
                            ->default($disponibilita->n_giorni_preventivo ?? null),

                        Forms\Components\TextInput::make('motivazione_rifiuto')
                            ->label('Motivazione Rifiuto')
                            ->visible(fn(Forms\Get $get) => $get('stato') === 'non_disponibile')
                            ->maxLength(255)
                            ->required(fn(Forms\Get $get) => $get('stato') === 'non_disponibile')
                            ->live()
                            ->reactive()
                            ->default($disponibilita->motivazione_rifiuto ?? null),

                        Forms\Components\TextInput::make('motivazione_richiesta_scaduta')
                            ->label('Motivazione Della Scadenza')
                            ->maxLength(255)

                            ->visible(function (Forms\Get $get) use ($disponibilita) {
                                return $get('stato') === 'disponibile'
                                    && $disponibilita
                                    && $disponibilita->n_giorni_preventivo
                                    && \Carbon\Carbon::parse($disponibilita->n_giorni_preventivo)->isPast();
                            })

                            ->required(function (Forms\Get $get) use ($disponibilita) {
                                return $get('stato') === 'disponibile'
                                    && $disponibilita
                                    && $disponibilita->n_giorni_preventivo
                                    && \Carbon\Carbon::parse($disponibilita->n_giorni_preventivo)->isPast();
                            })

                            ->live()
                            ->reactive()
                            ->default($disponibilita->motivazione_richiesta_scaduta ?? null),

                        Forms\Components\Textarea::make('note')
                            ->label('Note')
                            ->rows(3)
                            ->required(fn(Forms\Get $get) => $get('stato') === 'disponibile')
                            ->default($disponibilita->note ?? null),
                    ];
                })
                ->action(function (array $data) use ($user, $record) {
                    $record->disponibilita()->updateOrCreate(
                        ['user_id' => $user->id],
                        [
                            'stato' => $data['stato'],
                            'n_giorni_preventivo' => $data['n_giorni_preventivo'] ?? null,
                            'motivazione_rifiuto' => $data['motivazione_rifiuto'] ?? null,
                            'motivazione_richiesta_scaduta' => $data['motivazione_richiesta_scaduta'] ?? null,
                            'note' => $data['note'] ?? null,
                        ]
                    );

                    $this->invia($data['motivazione_richiesta_scaduta'] ?? null, $user->nome, $record->id);
                    // JS console log
        

                    Notification::make()
                        ->title('Disponibilità aggiornata')
                        ->success()
                        ->send();
                }),

            // Azione Edit originale
            Actions\EditAction::make()
                ->visible(function () use ($user, $record) {
                    if ($user->hasAnyRole(['admin', 'superadmin'])) {
                        return true;
                    }

                    if ($user->hasRole('agente') && $record->created_by === $user->id) {
                        return true;
                    }

                    return false;
                }),
        ];
    }
}