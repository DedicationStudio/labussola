<?php

namespace App\Filament\Resources\PreventiveResource\Pages;

use App\Filament\Resources\PreventiveResource;
use App\Models\Preventive;
use App\PreventiveStatus;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Illuminate\Validation\ValidationException;
use App\Filament\Resources\PreventiveResource\Pages\Concerns\HandlesDraftSaving;
use Filament\Support\Exceptions\Halt;



class CreatePreventive extends CreateRecord
{
    use HandlesDraftSaving;
    protected static string $resource = PreventiveResource::class;

    protected static ?string $title = 'Nuovo Preventivo';


    public bool $isDraft = false;

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

    protected function getFormActions(): array
    {
        return [
           
            Actions\Action::make('salva_bozza')
                ->label('Salva come Bozza')
                ->color('gray')
                ->submit(false)
                ->action(fn() => $this->saveAsDraft()),

        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
       

        $data['created_by'] = auth()->id();

        if (!empty($data['customer_id'])) {
        $customer = \App\Models\Customer::find($data['customer_id']);
        if ($customer && $customer->email) {
            $data['email_cliente'] = $customer->email;
        }
    }

        $raw = $this->form->getRawState();

        $hotelIds = collect($raw['hotel_preventives'] ?? [])
            ->pluck('hotel_id')
            ->filter()
            ->unique()
            ->values();

        if ($hotelIds->isNotEmpty()) {
            dd($hotelIds);
            $hotels = \App\Models\Hotel::whereIn('id', $hotelIds)->get();

            $senzaFoto = $hotels->filter(function ($h) {
                $f = $h->foto;
                // fallback se non castato (ma dovresti aver fatto il cast)
                if (is_string($f)) {
                    $json = json_decode($f, true);
                    $f = is_array($json) ? $json : [];
                }
                return empty($f);
            });

            if ($senzaFoto->isNotEmpty()) {
                $nomi = $senzaFoto->pluck('nome')->join(', ');

                // Banner/Toast
                Notification::make()
                    ->title('Salvataggio interrotto')
                    ->body("I seguenti hotel non hanno foto: {$nomi}.")
                    ->danger()
                    ->persistent()
                    ->send();

                // Errore sul repeater (ferma il create)
                throw ValidationException::withMessages([
                    'hotel_preventives' => 'Alcuni hotel non hanno foto caricate.',
                ]);
            }
        }




        return $data;
    }
}
