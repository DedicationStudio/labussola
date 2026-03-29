<?php

namespace App\Filament\Resources\EmailResource\Pages;

use App\Filament\Resources\EmailResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\MultiplePreventives;

class CreateEmail extends CreateRecord
{
    protected static string $resource = EmailResource::class;

    protected static ?string $title = 'Emails';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['sent_by'] = auth()->id();
        return $data;
    }

   protected function getFormActions(): array
{
    return [
        Actions\Action::make('saveAndSend')
            ->label('Salva & Invia Email')
            ->color('info')
            ->requiresConfirmation()
            ->modalHeading('Conferma invio email')
            ->modalDescription('Sei sicuro di voler salvare ed inviare questa email?')
            ->modalSubmitActionLabel('Sì, invia')
            ->modalIcon('heroicon-o-paper-airplane')
            ->action(function () {
                // Valida il form
                $this->form->validate();
                
                // Ottieni i dati
                $data = $this->form->getState();
                
                // Estrai i preventivi PRIMA di applicare le mutazioni
                $preventives = $data['preventives'] ?? [];
                
               
                
                // Rimuovi preventives dai dati da salvare
                unset($data['preventives']);
                
                // Applica le mutazioni (aggiunge sent_by)
                $data = $this->mutateFormDataBeforeCreate($data);
                
                // Crea il record Email
                $this->record = $this->getModel()::create($data);
                
                // Associa i preventivi SE ce ne sono
                if (!empty($preventives)) {
                    // Usa attach invece di sync per essere sicuri
                    $this->record->preventives()->attach($preventives);
                    
                   
                }
                
                // Ricarica il record con le relazioni
                $this->record->refresh();
                $this->record->load('preventives', 'customer');
                
                try {
                    // Invia la mail
                    $ccList = collect($this->record->email_cc ?? [])
                        ->pluck('email_cc')
                        ->filter()
                        ->toArray();

                    Mail::to($this->record->email_cliente)
                        ->cc($ccList)
                        ->send(new MultiplePreventives($this->record));

                    Notification::make()
                        ->title('Email inviata con successo!')
                        ->body('Email salvata e inviata con ' . count($preventives) . ' preventivi allegati.')
                        ->success()
                        ->send();
                        
                } catch (\Exception $e) {
                    dd([
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
                
                // Reindirizza alla lista
                $this->redirect($this->getResource()::getUrl('index'));
            }),
        
        Actions\Action::make('cancel')
            ->label('Annulla')
            ->color('gray')
            ->url($this->getResource()::getUrl('index')),
    ];
}
}