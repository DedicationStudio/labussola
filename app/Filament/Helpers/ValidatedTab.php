<?php

namespace App\Filament\Helpers;

use Filament\Forms\Components\Tabs\Tab;
use Filament\Forms\Components\Component;
use Filament\Forms\Get;

class ValidatedTab extends Tab
{
    protected function setUp(): void
    {
        parent::setUp();
    }
    protected array $validationFields = [];

    public static function make(string $name, array $validationFields = []): static
    {
        $instance = parent::make($name);
        $instance->validationFields = $validationFields;

        return $instance
            ->label($name)
            ->badge(function (Get $get, Component $component) {
                return self::hasErrors($get, $component) ? '●' : '●';
            })
            ->badgeColor(function (Get $get, Component $component) {
                return self::hasErrors($get, $component) ? 'danger' : 'success';
            })
            ->live(); // aggiornamento live
    }


    protected static function hasErrors(Get $get, Component $component): bool
    {
        $fields = $component->validationFields ?? [];
        $livewire = $component->getLivewire();
        $record = method_exists($livewire, 'getRecord') ? $livewire->getRecord() : null;
        $allegoFile = $get('allego_file') ?? false;
        foreach ($fields as $field) {
            if ($allegoFile && $field === 'foto_introduttiva') {
                continue;
            }
            if ($field === 'file_preventivo') {
            if ($allegoFile && blank($get('file_preventivo'))) {
                return true;
            }
            // Se allego_file è false, salta la validazione di file_preventivo
            continue;
        }
            $value = $get($field);

            // Repeater / array complesso
            if (is_array($value)) {

                // Caso specifico per repeater "itinerario"
                if ($field === 'itinerario') {
                    $validItems = collect($value)->filter(function ($item) {
                        if (!is_array($item))
                            return false;

                        $imgs = $item['immagini'] ?? [];
                        return is_array($imgs) && count($imgs) >= 3;
                    });

                    if ($validItems->isEmpty()) {
                        return true; // errore
                    }

                    continue;
                }

                // Caso specifico per repeater "hotel_preventives"
                //  Caso specifico per repeater "hotel_preventives"
                if ($field === 'hotel_preventives') {
                    $items = collect($value)->filter(fn($i) => is_array($i));

                    //  Scarta gli item completamente vuoti o placeholder
                    $items = $items->filter(function ($i) {
                        // rimuovo campi tecnici
                        $filtered = collect($i)->reject(function ($v, $k) {
                            return in_array($k, ['id', 'created_at', 'updated_at', 'pivot']);
                        });

                        // ignoro item dove TUTTO è vuoto o solo relazioni vuote
                        $nonEmpty = $filtered->reject(fn($v) => blank($v) || $v === [] || $v === 0 || $v === '0');
                        return $nonEmpty->isNotEmpty();
                    });

                    //  Un tab è valido solo se esiste almeno un hotel selezionato
                    //     E se ha almeno una stanza pagante o gratuita
                    $validHotel = $items->first(function ($i) {
                        $hotelId = data_get($i, 'hotel_id') ?? data_get($i, 'hotel.id');
                        $roomsPaganti = data_get($i, 'rooms_paganti', []);
                        $roomsGratuite = data_get($i, 'rooms_gratuite', []);

                        $hasHotel = !blank($hotelId);

                        // Controlla se ci sono stanze con dati validi
                        $hasValidRoomsPaganti = is_array($roomsPaganti) && collect($roomsPaganti)->filter(function ($r) {
                            return !blank($r) &&
                                !blank(data_get($r, 'tipologia_stanza'));
                        })->isNotEmpty();

                        $hasValidRoomsGratuite = is_array($roomsGratuite) && collect($roomsGratuite)->filter(function ($r) {
                            return !blank($r) &&
                                !blank(data_get($r, 'tipologia_stanza'));
                        })->isNotEmpty();

                        return $hasHotel && $hasValidRoomsPaganti;
                    });

                    if (!$validHotel) {
                        return true;
                    }

                    continue;
                }

                //  Caso specifico per "Servizi Extra"
                if ($field === 'extra_services' || str_starts_with($field, 'extra_services.')) {
                    if (blank($value)) {
                        return true; // nessun servizio aggiunto → errore
                    }

                    $items = collect(is_array($value) ? $value : $value?->toArray());

                    // Un servizio è valido solo se tutti i campi obbligatori sono compilati
                    $validItems = $items->filter(function ($item) {
                        if (!is_array($item)) {
                            return false;
                        }

                        $tipo = $item['tipo'] ?? null;
                        $tipoCosto = $item['tipo_costo'] ?? null;
                        $prezzo = $item['prezzo'] ?? null;

                        // Deve avere tutti e tre valorizzati
                        return !blank($tipo) && !blank($tipoCosto) && !blank($prezzo);
                    });

                    // Se almeno un servizio valido esiste → tab verde
                    if ($validItems->isNotEmpty()) {
                        continue; // ok
                    }

                    return true; // altrimenti tab rosso
                }

            }



            //  Caso specifico per trasporti (andata/rientro) — richiede TUTTI i campi minimi
            if (in_array($field, ['trasporto_andata', 'trasporto_rientro'])) {
                // normalizza anche se $value è un Model
                $arr = is_array($value) ? $value : ($value?->toArray() ?? []);

                // campi richiesti per sezione
                $required = $field === 'trasporto_andata'
                    ? [
                        'luogo_di_partenza_andata',
                        'luogo_di_arrivo_andata',
                        'data_ora_partenza_andata',
                        'data_ora_arrivo_andata',
                        'tipo_trasporto',
                        'prezzo',
                    ]
                    : [
                        'luogo_di_partenza_rientro',
                        'luogo_di_arrivo_rientro',
                        'data_ora_partenza_rientro',
                        'data_ora_arrivo_rientro',
                        'tipo_trasporto',
                        'prezzo',
                    ];

                // se manca anche solo UNO dei campi richiesti → errore
                $missing = collect($required)->first(function ($key) use ($arr) {
                    $v = $arr[$key] ?? null;
                    return blank($v); // include null, '', [], 0, '0'
                });

                if ($missing !== null) {
                    return true; // rosso finché la sezione non è completa
                }

                continue; // sezione completa → ok
            }



            //  Campo singolo: se non vuoto → ok
            if (!blank($value))
                continue;

            //  Controllo relazioni record (in edit)
            if ($record && method_exists($record, $field)) {
                try {
                    $relation = $record->{$field}();
                    if (method_exists($relation, 'count') && $relation->count() > 0)
                        continue;
                } catch (\Throwable $e) {
                    // ignora
                }
            }
            //  Fallback: se il valore non è nello stato, prova a leggerlo dal record salvato
            if ($record) {
                try {
                    // Se il campo è una relazione hasMany o simile
                    if (method_exists($record, $field)) {
                        $relation = $record->{$field}();
                        if (method_exists($relation, 'count') && $relation->count() > 0) {
                            continue; //  relazione con dati → ok
                        }
                    }

                    // Se è un campo semplice sul record
                    $propValue = data_get($record, $field);
                    if (!blank($propValue)) {
                        continue; //  campo nel DB compilato → ok
                    }
                } catch (\Throwable $e) {
                    // ignora eccezioni di relazioni non definite
                }
            }

            //  Campo vuoto → errore
            return true;
        }

        return false;
    }





}
