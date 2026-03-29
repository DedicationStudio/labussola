<?php

namespace App\Filament\Resources\QuoteRequestResource\RelationManagers;

use App\Filament\Resources\QuoteRequestResource;
use App\Models\User;
use App\Notifications\PersonalNotification;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

use App\Http\Controllers\Controller;
use App\Models\AgentAvailability;
use App\Models\Customer;
use App\Models\CustomNotification;
use App\Models\QuoteRequest;
use App\Models\Preventive;
use App\Models\TypeRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Exception\MessagingException;
use Throwable;
use Illuminate\Support\Facades\Notification as NotificationFacade;
class AvailabilitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'disponibilita';

    protected static ?string $title = 'Disponibilità';

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


    protected function invia($record): void
{
    // Evita doppio invio
    if ($record->notifica_scadenza_inviata) {
        return;
    }

    // Prendi utenti interessati (modifica la query in base alla tua logica)
    $users = User::where('role_id', 3)->get();

    if ($users->isEmpty()) {
        return;
    }

    $title = 'Richiesta Scaduta';
    $body = "Una Richiesta è scaduta ed è stato impostata la disponibilità come NON disponibile.";

    // 🔔 Laravel Notification
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
                    QuoteRequestResource::getUrl('view', ['record' => $record->quote_request_id]),
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
        $record->quote_request_id
    );


}

////
    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Select::make('user_id')
                    ->relationship(
                        'user',
                        'nome',
                        fn(Builder $query) =>
                        $query->whereHas('role', fn($q) => $q->where('nome', 'agente'))
                    )
                    ->searchable()
                    ->label('Agente')
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn(User $record) => "{$record->nome} {$record->cognome}")
                    ->required(),
                Forms\Components\Toggle::make('stato')
                    ->label('Disponibile')
                    ->default(true) // default ON
                    ->required()
                    ->live()
                    ->onIcon('heroicon-o-check')
                    ->offIcon('heroicon-o-x-mark')
                    ->formatStateUsing(fn($state) => $state === 'disponibile') // DB → UI
                    ->dehydrateStateUsing(fn($state) => $state ? 'disponibile' : 'non_disponibile'), // UI → DB
                Forms\Components\DatePicker::make('n_giorni_preventivo')
                    ->label('Numero giorni preventivo')
                    ->displayFormat('d/m/Y')
                    ->minDate(now())
                    
                    ->visible(fn (Forms\Get $get) => $get('stato') == true)
                    
                    ->required(fn (Forms\Get $get) => $get('stato') == true)
                    
                    ->dehydrated(fn (Forms\Get $get) => $get('stato') == true)

                    ->live()
                    ->reactive()

                    ->default($disponibilita->n_giorni_preventivo ?? null),
                Forms\Components\Textarea::make('motivazione_rifiuto')
                    ->label('Motivazione rifiuto')
                    ->visible(fn(Get $get) => $get('stato') == false)
                    ->dehydrated(fn(Get $get) => $get('stato') == false)
                    ->maxLength(255)
                    ->rows(3)
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
    ->recordTitleAttribute('disponibile')
    ->columns([
        Tables\Columns\TextColumn::make('user.name')
            ->label('Agente'),

        Tables\Columns\IconColumn::make('stato')
    ->label('Disponibile')
    ->boolean()
    ->getStateUsing(function ($record) {

        // Se non c'è scadenza → forza NON disponibile
        if (!$record->n_giorni_preventivo) {

            if ($record->stato !== 'non_disponibile') {
                $record->update(['stato' => 'non_disponibile']);
            }

            return false;
        }

            $scadenza = Carbon::parse($record->n_giorni_preventivo);
            $oggi = today();

            // 🔴 SCADUTO → NON DISPONIBILE
            if ($scadenza->lt($oggi)) {

                if ($record->stato !== 'non_disponibile') {

                    $record->update(['stato' => 'non_disponibile']);

                    // 🔔 NOTIFICHE
                        $this->invia($record);

                }

                return false;
            }

            // 🟢 NON SCADUTO → DISPONIBILE
            if ($record->stato !== 'disponibile') {
                $record->update(['stato' => 'disponibile']);
            }

            return true;
    }),

        Tables\Columns\TextColumn::make('n_giorni_preventivo')
            ->label('Scadenza preventivo')
            ->date('d/m/Y'),

        Tables\Columns\TextColumn::make('motivazione_rifiuto')
            ->label('Motivazione Rifiuto/Note')
            ->limit(30)
            ->formatStateUsing(fn($state,$record) => $record->motivazione_rifiuto ?? $record->note ?? "-")
            ->tooltip(fn($state,$record) => $record->motivazione_rifiuto ?? $record->note ?? "-")
            ->getStateUsing(fn ($record) =>
                $record->motivazione_rifiuto ?? $record->note ?? "-"
            )
    ])
            ->filters([

                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalHeading(fn($record): string => 'Salva Disponibilità')
                    ->after(function ($record, $data, $action) {
                        // aggiorno lo stato della richiesta principale
                        $record->quote_request()->update(['stato_richiesta' => 'risposta pervenuta']);
                    }),
            ])
           ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(function () {
                        $user = auth()->user();
                        
                        // Admin e Superadmin possono sempre creare
                        if ($user->hasAnyRole(['admin', 'superadmin'])) {
                            return true;
                        }
                        
                        // Gli agenti possono creare solo se la richiesta è stata inviata a loro
                        if ($user->hasRole('agente')) {
                            $quoteRequest = $this->getOwnerRecord();
                            return $quoteRequest->agenti_inviati->contains($user->id);
                        }
                        
                        return false;
                    })
                    ->mutateFormDataUsing(function (array $data) {
                        // Se è un agente, forza user_id al proprio ID
                        if (auth()->user()->hasRole('agente')) {
                            $data['user_id'] = auth()->id();
                        }
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(function ($record) {
                        $user = auth()->user();
                        
                        // Admin e Superadmin possono modificare tutto
                        if ($user->hasAnyRole(['admin', 'superadmin'])) {
                            return true;
                        }
                        
                        // Gli agenti possono modificare solo la propria disponibilità
                        if ($user->hasRole('agente') && $record->user_id === $user->id) {
                            return true;
                        }
                        
                        return false;
                    }),
                    
                Tables\Actions\DeleteAction::make()
                    ->visible(function ($record) {
                        $user = auth()->user();
                        
                        // Admin e Superadmin possono eliminare tutto
                        if ($user->hasAnyRole(['admin', 'superadmin'])) {
                            return true;
                        }
                        
                        // Gli agenti possono eliminare solo la propria disponibilità
                        if ($user->hasRole('agente') && $record->user_id === $user->id) {
                            return true;
                        }
                        
                        return false;
                    }),
            ])
           
            ->emptyStateHeading('Nessuna disponibilità presente')
            ->emptyStateDescription('')
             ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()->hasAnyRole(['admin', 'superadmin'])),
                ]),
            ]);
    }
}
