<?php

namespace App\Filament\Resources\EmailResource\Pages;

use App\Filament\Resources\EmailResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\MultiplePreventives;

class EditEmail extends EditRecord
{
    protected static string $resource = EmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sendMail')
                ->label('Invia Email')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Conferma Invio Email')
                ->modalDescription('Sei sicuro di voler inviare questa email?')
                ->modalSubmitActionLabel('Invia')
                ->action(function () {
                    $record = $this->record;
                    $record->load('customer', 'preventives');

                    // Se manca email_cliente, prova a usare la relazione customer
                    $to = $record->email_cliente ?? $record->customer?->email;

                    if (!$to) {
                        Notification::make()
                            ->title('Errore: nessuna email cliente trovata')
                            ->danger()
                            ->send();
                        return;
                    }

                    // Pulizia CC (evita nulli o stringhe vuote)
                    $cc = collect($record->email_cc ?? [])
                        ->pluck('email_cc')
                        ->filter()
                        ->all();

                    Mail::to($to)
                        ->cc($cc)
                        ->send(new MultiplePreventives($record));

                    Notification::make()
                        ->title('Email inviata con successo!')
                        ->success()
                        ->send();
                }),
            Actions\DeleteAction::make(),
        ];
    }

     protected function mutateFormDataBeforeSave(array $data): array
    {
        // Rimuovi 'preventives' dai dati che verranno salvati nella tabella emails
        unset($data['preventives']);
        
        return $data;
    }

    protected function afterSave(): void
    {
        // Recupera i preventives dal form data
        $preventives = $this->data['preventives'] ?? [];
        
        // Sincronizza la relazione many-to-many
        $this->record->preventives()->sync($preventives);
    }
           
}
