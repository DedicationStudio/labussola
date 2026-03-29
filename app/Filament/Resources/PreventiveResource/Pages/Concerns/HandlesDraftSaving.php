<?php

namespace App\Filament\Resources\PreventiveResource\Pages\Concerns;

use App\Models\Preventive;
use App\Models\Hotel;
use App\PreventiveStatus;
use Filament\Forms\Components\Livewire;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Filament\Support\Exceptions\Halt;

trait HandlesDraftSaving
{
    public bool $isDraft = false;



    protected function saveEmailDraft(Preventive $preventivo, array $emailDraft): void
    {
        \Log::info('saveEmailDraft - INIZIO', [
            'preventivo_id' => $preventivo->id,
            'emailDraft' => $emailDraft,
        ]);

        if (empty(array_filter($emailDraft))) {
            \Log::info('saveEmailDraft - Nessun dato email, esco');
            return;
        }

        // Gestione allegati
        $storedFiles = [];
        if (!empty($emailDraft['allegati']) && is_array($emailDraft['allegati'])) {
            foreach ($emailDraft['allegati'] as $file) {
                if ($file instanceof TemporaryUploadedFile) {
                    $storedFiles[] = $file->store('allegati_email', 'public');
                } elseif (is_string($file)) {
                    $storedFiles[] = $file;
                }
            }
        }
        $emailDraft['allegati'] = $storedFiles;

        \Log::info('saveEmailDraft - Allegati processati', [
            'storedFiles' => $storedFiles,
        ]);

        //  Cerca una bozza ESISTENTE specifica per QUESTO preventivo
        $existingDraft = \App\Models\Email::whereHas('preventives', function ($query) use ($preventivo) {
            $query->where('preventive_id', $preventivo->id);
        })
            ->where('is_draft', true)
            ->latest('updated_at')
            ->first();

        \Log::info('saveEmailDraft - Ricerca bozza esistente', [
            'found' => $existingDraft ? 'sì' : 'no',
            'existing_draft_id' => $existingDraft->id ?? null,
        ]);

        if ($existingDraft) {
            // Aggiorna la bozza esistente
            $existingDraft->update(array_merge($emailDraft, [
                'customer_id' => $preventivo->customer_id,
                'quote_request_id' => $preventivo->quote_request_id,
                'sent_by' => $preventivo->created_by,
                'is_draft' => true,
            ]));

            \Log::info('Email bozza AGGIORNATA', [
                'email_id' => $existingDraft->id,
                'preventivo_id' => $preventivo->id,
            ]);
        } else {
            // Crea nuova bozza
            $newEmail = \App\Models\Email::create(array_merge($emailDraft, [
                'customer_id' => $preventivo->customer_id,
                'quote_request_id' => $preventivo->quote_request_id,
                'sent_by' => $preventivo->created_by,
                'is_draft' => true,
            ]));

            $preventivo->emails()->attach($newEmail->id);

            \Log::info('Email bozza CREATA', [
                'email_id' => $newEmail->id,
                'preventivo_id' => $preventivo->id,
            ]);
        }
    }

    protected function saveMomentum()
{
    $this->isDraft = true;
    $this->resetValidation();
    $this->disableAllValidationRules();

    $data = $this->form->getState();

    if (!empty($data['customer_id'])) {
        $customer = \App\Models\Customer::find($data['customer_id']);
        if ($customer && $customer->email) {
            $data['email_cliente'] = $customer->email;
        }
    }

    $emailDraft = [
        'email_template_id' => $data['email_template_id'] ?? null,
        'email_cliente' => $data['email_cliente'] ?? null,
        'email_cc' => collect($data['email_cc'] ?? [])
            ->pluck('email_cc')->filter()->values()->toArray(),
        'corpo_email' => $data['corpo_email'] ?? null,
        'allegati' => $data['allegati'] ?? [],
    ];

    unset(
        $data['email_template_id'],
        $data['email_cc'],
        $data['corpo_email'],
        $data['allegati']
    );

    if (empty($data['customer_id'])) {
        Notification::make()
            ->title('Cliente mancante')
            ->body('Seleziona un cliente prima di salvare il preventivo.')
            ->danger()
            ->send();
        return;
    }

    $data = $this->processAllFiles($data);

    foreach (['data_preventivo', 'date_expiration', 'data_inizio_viaggio', 'data_fine_viaggio'] as $f) {
        if (!empty($data[$f])) {
            $data[$f] = Carbon::parse($data[$f])->format('Y-m-d');
        }
    }

    DB::transaction(function () use ($data, $emailDraft) {

        $preventivo = $this->record ?? new Preventive();

        // ❌ NON tocchiamo lo stato se esiste già
        if (!$preventivo->exists && empty($data['stato'])) {
            $data['stato'] = PreventiveStatus::BOZZA; 
            // solo per nuovi record senza stato
        }

        $preventivo->fill($data)->save();

        $preventivo->hotel_preventives()
            ->whereNull('hotel_id')
            ->delete();

        $preventivo->extra_services()
            ->where(function ($query) {
                $query->whereNull('extra_service_id')
                    ->whereNull('tipo');
            })
            ->orWhere(function ($query) {
                $query->whereNull('prezzo')
                    ->whereNull('tipo');
            })
            ->delete();

        $this->saveEmailDraft($preventivo, $emailDraft);

        $this->form->model($preventivo)->fill($this->form->getState());

        $this->record = $preventivo;
    });

    Notification::make()
        ->title('Salvataggio completato!')
        ->success()
        ->send();

    $this->isDraft = false;

    return redirect(
        \App\Filament\Resources\PreventiveResource::getUrl('edit', [
            'record' => $this->record,
        ]) . '?draft=1'
    );
}



    protected function saveAsDraft()
    {
        $this->isDraft = true;
        $this->resetValidation();
        $this->disableAllValidationRules();

        $data = $this->form->getState();
        if (!empty($data['customer_id'])) {
        $customer = \App\Models\Customer::find($data['customer_id']);
        if ($customer && $customer->email) {
            $data['email_cliente'] = $customer->email;
        }
    }

        $emailDraft = [
            'email_template_id' => $data['email_template_id'] ?? null,
            'email_cliente' => $data['email_cliente'] ?? null,
            'email_cc' => collect($data['email_cc'] ?? [])->pluck('email_cc')->filter()->values()->toArray(),
            'corpo_email' => $data['corpo_email'] ?? null,
            'allegati' => $data['allegati'] ?? [],
        ];
        unset(
            $data['email_template_id'],
            $data['email_cc'],
            $data['corpo_email'],
            $data['allegati']
        );

        if (empty($data['customer_id'])) {
            Notification::make()
                ->title('Cliente mancante')
                ->body('Seleziona un cliente prima di salvare il preventivo.')
                ->danger()
                ->send();
            return;

        }

        $data = $this->processAllFiles($data);

        foreach (['data_preventivo', 'date_expiration', 'data_inizio_viaggio', 'data_fine_viaggio'] as $f) {
            if (!empty($data[$f])) {
                $data[$f] = Carbon::parse($data[$f])->format('Y-m-d');
            }
        }

        $data['stato'] = PreventiveStatus::BOZZA;



        //  Salvataggio transazionale
        DB::transaction(function () use ($data, $emailDraft) {
            $preventivo = $this->record ?? new Preventive();

            $preventivo->fill($data)->save();

            // Pulisci righe vuote
            $preventivo->hotel_preventives()->whereNull('hotel_id')->delete();
            // Elimina solo servizi completamente vuoti
            $preventivo->extra_services()
                ->where(function ($query) {
                    $query->whereNull('extra_service_id')
                        ->whereNull('tipo');
                })
                ->orWhere(function ($query) {
                    $query->whereNull('prezzo')
                        ->whereNull('tipo');
                })
                ->delete();
            $this->saveEmailDraft($preventivo, $emailDraft);
            // Rebind form
            $this->form->model($preventivo)->fill($this->form->getState());

            // Aggiorna record in memoria
            $this->record = $preventivo;
        });

        Notification::make()
            ->title('Bozza salvata con successo!')
            ->body('Puoi riprenderla in qualsiasi momento dalla lista preventivi.')
            ->success()
            ->send();
        $this->isDraft = false;

        return redirect(
            \App\Filament\Resources\PreventiveResource::getUrl('edit', [
                'record' => $this->record,
            ]) . '?draft=1'
        );
    }

  
    protected function processAllFiles(array $data): array
    {
        // File allegati email
        if (!empty($data['allegati'])) {
            $data['allegati'] = $this->processFiles($data['allegati'], 'email_allegati');
        }

        // File hotel
        if (!empty($data['hotel_preventives'])) {
            foreach ($data['hotel_preventives'] as $index => $hotel) {
                if (!empty($hotel['file_fornitore_hotel'])) {
                    $data['hotel_preventives'][$index]['file_fornitore_hotel'] =
                        $this->processFiles($hotel['file_fornitore_hotel'], 'fornitori_hotel');
                }
            }
        }

        // File trasporto andata
        if (!empty($data['trasporto_andata']['file_fornitore_trasporto'])) {
            $data['trasporto_andata']['file_fornitore_trasporto'] =
                $this->processFiles($data['trasporto_andata']['file_fornitore_trasporto'], 'fornitori_trasporti');
        }

        // File trasporto rientro
        if (!empty($data['trasporto_rientro']['file_fornitore_trasporto'])) {
            $data['trasporto_rientro']['file_fornitore_trasporto'] =
                $this->processFiles($data['trasporto_rientro']['file_fornitore_trasporto'], 'fornitori_trasporti');
        }

        // File servizi extra
        if (!empty($data['extra_services'])) {
            foreach ($data['extra_services'] as $index => $servizio) {
                if (!empty($servizio['file_fornitore_servizi_extra'])) {
                    $data['extra_services'][$index]['file_fornitore_servizi_extra'] =
                        $this->processFiles($servizio['file_fornitore_servizi_extra'], 'fornitori_servizi_extra');
                }
            }
        }

        return $data;
    }

    protected function processFiles(array $files, string $directory = 'preventivi'): array
    {
        return collect($files)->map(function ($file) use ($directory) {
            // Se è un file temporaneo, salvalo nello storage
            if ($file instanceof TemporaryUploadedFile) {
                // Usa storeAs per mantenere il nome originale (come preserveFilenames)
                $originalName = $file->getClientOriginalName();
                $filename = $this->sanitizeFilename($originalName);
                return $file->storeAs($directory, $filename, 'public');
            }

            // Se è già una stringa (path esistente), mantienila
            if (is_string($file)) {
                return $file;
            }

            return null;
        })->filter()->values()->toArray();
    }

    protected function sanitizeFilename(string $filename): string
    {
        // Separa nome ed estensione
        $pathInfo = pathinfo($filename);
        $name = $pathInfo['filename'];
        $extension = $pathInfo['extension'] ?? '';

        // Rimuovi/sostituisci caratteri problematici
        $name = preg_replace('/[^a-zA-Z0-9._-]/', '_', $name);

        // Rimuovi underscore multipli consecutivi
        $name = preg_replace('/_+/', '_', $name);

        // Rimuovi underscore all'inizio e alla fine
        $name = trim($name, '_');

        // Ricostruisci il nome
        return $extension ? "{$name}.{$extension}" : $name;
    }

}
