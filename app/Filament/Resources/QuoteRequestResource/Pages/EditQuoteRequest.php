<?php

namespace App\Filament\Resources\QuoteRequestResource\Pages;

use App\Filament\Resources\QuoteRequestResource;
use App\Models\AgentAvailability;
use App\Models\CustomNotification;
use App\Models\User;
use App\Notifications\PersonalNotification;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\DB;
use Kreait\Firebase\Messaging\CloudMessage;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

class EditQuoteRequest extends EditRecord
{
    protected static string $resource = QuoteRequestResource::class;

    protected static ?string $title = 'Modifica Richiesta Interna';





    protected function beforeFill(): void
    {
        // Se il record non ha ancora un valore per 'assegnata_da', impostalo
        if (blank($this->record?->assegnata_da)) {
            $this->record->assegnata_da = auth()->id();
        }


    }


    protected function afterFill(): void
    {
        // Forza il refresh del campo created_by dopo il caricamento
        if ($this->record && $this->record->created_by) {
            $user = User::find($this->record->created_by);
            if ($user) {
                $this->data['created_by'] = "{$user->nome} {$user->cognome}";
            }
        }
    }


    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('salva')
                ->label('Salva')
                ->color('primary')
                ->action(fn() => $this->save()),
            Actions\DeleteAction::make()
                ->modalHeading('Elimina Richiesta')
                ->modalDescription('Sei sicuro di voler eliminare questa richiesta? Questa azione è irreversibile.')
                ->successNotificationTitle('Richiesta eliminata con successo')
                ->visible(
                    fn($record) =>
                    auth()->user()?->hasAnyRole(['admin', 'superadmin']) /* ||
$record?->created_by === auth()->id() */
                ),
        ];
    }
    protected function getFormActions(): array
    {
        return [
            Actions\Action::make('assegna')
                ->label('Assegna e Notifica')
                ->color('success')
                ->requiresConfirmation()
                ->hidden(fn() => auth()->user()?->hasRole('agente'))
                ->visible(fn($record) => !in_array($record->stato_richiesta, ['in lavorazione', 'evasa']))
                ->action(function () {
                    $this->assegnaEInviaNotifiche($this->record);
                })
        ];
    }
private function assegnaEInviaNotifiche($quoteRequest): void
{
    \Log::info('=== INIZIO assegnaEInviaNotifiche ===');
    
    DB::transaction(function () use ($quoteRequest) {
        \Log::info('Dentro transaction');
        
        $originalCreatedBy = $quoteRequest->created_by;
        
        $this->save();
        \Log::info('Dopo save()');
        
        if ($quoteRequest->created_by !== $originalCreatedBy) {
            $quoteRequest->created_by = $originalCreatedBy;
        }
        
        $quoteRequest->update([
            'stato_richiesta' => 'in lavorazione',
            'data_assegnazione' => now(),
        ]);
        \Log::info('Dopo update()');

        \Log::info('Auth user ID: ' . auth()->id());
        \Log::info('Agenti gestori IDs: ' . $quoteRequest->agenti_gestori->pluck('id')->implode(', '));

        $agentiDaNotificare = $quoteRequest->agenti_gestori->filter(
            fn($user) => $user->id !== auth()->id()

        );


        \Log::info('Agenti da notificare IDs: ' . $agentiDaNotificare->pluck('id')->implode(', '));
        \Log::info('Count agenti da notificare: ' . $agentiDaNotificare->count());

        foreach ($agentiDaNotificare as $user) {
            \Log::info('=== LOOP per utente ID: ' . $user->id . ' ===');
            
            if ($user->hasMuteNotifications()) {
                \Log::info('Utente ' . $user->id . ' ha notifiche mutate, skip');
                continue;
            }
            
            \Log::info('Sto per inviare notifica a: ' . $user->id);
            
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
                ->sendToDatabase($user);

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

    $this->refreshFormData(['stato_richiesta', 'data_assegnazione']);
}
}
