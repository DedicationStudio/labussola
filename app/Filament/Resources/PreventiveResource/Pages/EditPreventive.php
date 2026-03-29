<?php

namespace App\Filament\Resources\PreventiveResource\Pages;

use App\Filament\Resources\PreventiveResource;
use App\Mail\PreventiveExpirationAlert;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Models\Preventive;
use App\PreventiveStatus;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use App\Filament\Resources\PreventiveResource\Pages\Concerns\HandlesDraftSaving;
use Illuminate\Support\Facades\Mail;


class EditPreventive extends EditRecord
{
    use HandlesDraftSaving;
    protected static string $resource = PreventiveResource::class;

    protected static ?string $title = 'Modifica Preventivo';




    public bool $isDraft = false;

    protected array $emailDataToSave = [];


    protected function disableAllValidationRules(): void
    {
        foreach ($this->form->getComponents() as $component) {
            $this->disableRulesRecursively($component);
        }
    }

    protected function disableRulesRecursively(Component $component): void
    {
        if ($component instanceof Field) {
            $component->required(false);
            $component->rules([]);
            $component->rule(null);
        }

        if ($component instanceof Repeater) {
            if (method_exists($component, 'minItems')) {
                $component->minItems(null);
            }
            if (method_exists($component, 'maxItems')) {
                $component->maxItems(null);
            }
        }

        if ($component instanceof FileUpload) {
            $component->required(false);
            $component->rules([]);
            $component->rule(null);
            $component->minFiles(null);
            $component->maxFiles(null);
        }

        if (method_exists($component, 'getChildComponents')) {
            foreach ($component->getChildComponents() as $child) {
                $this->disableRulesRecursively($child);
            }
        }
    }




    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Se il campo è vuoto e il record esiste, lo lascio vuoto.
        // Se il record è nuovo (id non esiste) → metto default.
        if (blank($data['campo_attenzione'] ?? null)) {
            $data['campo_attenzione'] = "Attenzione: tutte le tariffe proposte, essendo contingentate, sono soggette a disponibilità e riconferma all'atto della conferma del preventivo.";
        }

        return $data;
    }



    // In EditPreventive (header actions)-
    protected function getHeaderActions(): array
    {

        return [

            Actions\Action::make('salva_bozza')
                ->label('Salva come Bozza')
                ->color('gray')
                ->submit(false)

                ->action(fn() => $this->saveAsDraft()),

            Actions\Action::make('alert_scaduto')
                ->label('Alert Preventivo Scaduto')
                ->color('danger')
                ->requiresConfirmation()
                ->tooltip('Premere solo dopo che il preventivo risulta scaduto')
                ->modalHeading('Invia Alert Preventivo Scaduto')
                ->modalDescription('Verrà inviata una email al cliente per informarlo che il preventivo è scaduto e richiedere se è ancora interessato.')
                ->modalSubmitActionLabel('Invia Email')
                ->visible(fn() => $this->record->stato === PreventiveStatus::IN_ATTESA)
                ->action(function () {
                    $this->sendExpiredAlert();
                    Notification::make()
                        ->title('Email di alert scadenza inviata con successo')
                        ->success()
                        ->send();
                }),
            Actions\ViewAction::make()
                ->color('info')
                ->label('Visualizza Preventivo')
                ->openUrlInNewTab()
                ->extraAttributes(['target' => '_blank'])
                ->url(function ($record) {
                    // Se allego_file è true, apri la rotta che mostra il file inline
                    if ($record->allego_file) {
                        return route('preventivo.show.allegato', ['cod_alfa' => $record->cod_alfa]);
                    }

                    // Altrimenti apri la pagina normale con il link
                    return route('preventivo.show', ['cod_alfa' => $record->cod_alfa]);
                }),


        ];
    }

    protected function sendExpiredAlert()
{
    // Recupera l'ultima email associata al preventivo
    $lastEmail = $this->record->emails()->latest('updated_at')->first();
    
    \Log::info("=== sendExpiredAlert ===");
    \Log::info("Preventivo ID: {$this->record->id}");
    \Log::info("Last Email: " . ($lastEmail ? "ID {$lastEmail->id}" : "NULL"));
    
    if ($lastEmail) {
        \Log::info("Email->allegati: " . json_encode($lastEmail->allegati));
    }
    
    // Passa ANCHE l'email alla classe Mailable
    Mail::to($this->record->customer->email)
        ->send(new PreventiveExpirationAlert($this->record, $lastEmail)); 
    
    \Log::info("Email inviata con successo");
}
    protected function handleRecordUpdate($record, array $data): Preventive
    {
        $emailDraft = [
            'email_template_id' => $data['email_template_id'] ?? null,
            'email_cliente' => $data['email_cliente'] ?? null,
            'email_cc' => collect($data['email_cc'] ?? [])->pluck('email_cc')->filter()->values()->toArray(),
            'corpo_email' => $data['corpo_email'] ?? null,
            'allegati' => $data['allegati'] ?? [],
        ];

        unset(
            $data['email_template_id'],
            $data['email_cliente'],
            $data['email_cc'],
            $data['corpo_email'],
            $data['allegati']
        );

        foreach (['data_preventivo', 'date_expiration', 'data_inizio_viaggio', 'data_fine_viaggio'] as $f) {
            if (!empty($data[$f])) {
                $data[$f] = \Illuminate\Support\Carbon::parse($data[$f])->format('Y-m-d');
            }
        }

        // Gestisci tutto manualmente (come saveAsDraft)
        \Illuminate\Support\Facades\DB::transaction(function () use ($record, $data, $emailDraft) {
            $record->fill($data)->save();

            if (!empty(array_filter($emailDraft))) {
                $record->emails()->updateOrCreate(
                    ['is_draft' => true],
                    array_merge($emailDraft, [
                        'customer_id' => $record->customer_id,
                        'quote_request_id' => $record->quote_request_id,
                        'sent_by' => $record->created_by,
                        'is_draft' => true,
                    ])
                );
            }

            $this->form->model($record)->fill($this->form->getState());
            $this->record = $record;
        });

        \Filament\Notifications\Notification::make()
            ->title('Preventivo aggiornato con successo!')
            ->success()
            ->send();

        return $record;
    }

       protected function getFormActions(): array
    {
        return [
           
            Actions\Action::make('salva_bozza')
                ->label('Salva')
                ->color('primary')
                ->submit(false)
                ->action(fn() => $this->saveMomentum()),

        ];
    }
    protected function mutateFormDataBeforeSave(array $data): array
    {
        \Log::info('mutateFormDataBeforeSave - INIZIO', [
            'preventivo_id' => $this->record->id ?? 'nuovo',
            'has_email_template' => !empty($data['email_template_id']),
            'has_email_cliente' => !empty($data['email_cliente']),
        ]);

        if (!empty($data['customer_id'])) {
            $customer = \App\Models\Customer::find($data['customer_id']);
            if ($customer && $customer->email) {
                $data['email_cliente'] = $customer->email;
            }
        }

        // Salva i dati email in una variabile temporanea
        $this->emailDataToSave = [
            'email_template_id' => $data['email_template_id'] ?? null,
            'email_cliente' => $data['email_cliente'] ?? null,
            'email_cc' => collect($data['email_cc'] ?? [])->pluck('email_cc')->filter()->values()->toArray(),
            'corpo_email' => $data['corpo_email'] ?? null,
            'allegati' => $data['allegati'] ?? [],
        ];

        \Log::info('mutateFormDataBeforeSave - email salvate', [
            'emailDataToSave' => $this->emailDataToSave,
        ]);

        // Rimuovi i campi email dai dati del preventivo
        unset(
            $data['email_template_id'],
            $data['email_cliente'],
            $data['email_cc'],
            $data['corpo_email'],
            $data['allegati']
        );

        return $data;
    }

    protected function afterSave(): void
    {
        $preventivo = $this->record;

        \Log::info('afterSave - INIZIO', [
            'preventivo_id' => $preventivo->id,
            'emailDataToSave_empty' => empty(array_filter($this->emailDataToSave)),
            'emailDataToSave' => $this->emailDataToSave,
        ]);

        if (!empty(array_filter($this->emailDataToSave))) {
            \Log::info('afterSave - Chiamando saveEmailDraft');

            $this->saveEmailDraft($preventivo, $this->emailDataToSave);

            // Pulisci la variabile temporanea
            $this->emailDataToSave = [];

            \Log::info('afterSave - saveEmailDraft completato');
        } else {
            \Log::warning('afterSave - Nessun dato email da salvare');
        }

        $this->record->refresh();
        $this->fillForm();
    }

}




