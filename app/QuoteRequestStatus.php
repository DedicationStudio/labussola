<?php

namespace App;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum QuoteRequestStatus: string implements HasLabel, HasColor
{
    case INVIATA = 'inviata';
    case RISPOSTA = 'risposta pervenuta';

    case ASSEGNATA = 'assegnata';
    case IN_LAVORAZIONE = 'in lavorazione';
    case EVASA = 'evasa';
    case ARCHIVIATA = 'archiviata';


    //test

    public function getLabel(): ?string
    {
        return match ($this) {
            self::INVIATA => 'inviata',
            self::RISPOSTA => 'risposta pervenuta',
            self::ASSEGNATA => 'assegnata',
            self::IN_LAVORAZIONE => 'in lavorazione',
            self::EVASA => 'evasa',
            self::ARCHIVIATA => 'archiviata',
        };
    }



    public function getColor(): ?string
    {
        return match ($this) {
            self::INVIATA => 'gray',
            self::RISPOSTA => 'info',
            self::ASSEGNATA => 'primary',
            self::IN_LAVORAZIONE => 'warning',
            self::EVASA => 'success',
            self::ARCHIVIATA => 'dark',
        };
    }
}

