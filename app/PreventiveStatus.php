<?php

namespace App;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum PreventiveStatus: string implements HasLabel, HasColor
{

    case RIFIUTATO = 'rifiutato';
    case IN_ATTESA = 'in attesa';
    case ACCETTATO = 'accettato';
    case BOZZA = 'bozza';
    case INTERESSE_PIU_TEMPO = 'interesse più tempo';
    case SUPERIORE_BUDGET = 'superiore budget';
    case OLTRE_TEMPI = 'oltre tempi';
    case NON_INTERESSA = 'programma non interessa';
    case DA_RIVEDERE = 'rivedere proposta';
    case ALTRO = 'altro';





    public function getLabel(): ?string
    {
        return match ($this) {
            self::RIFIUTATO => 'rifiutato',
            self::IN_ATTESA => 'in attesa',
            self::BOZZA => 'bozza',
            self::ACCETTATO => 'accettato',
            self::INTERESSE_PIU_TEMPO => 'interesse più tempo',
            self::SUPERIORE_BUDGET => 'superiore budget',
            self::OLTRE_TEMPI => 'oltre tempi',
            self::NON_INTERESSA => 'programma non interessa',
            self::DA_RIVEDERE => 'rivedere proposta',
            self::ALTRO => 'altro',
        };
    }



    public function getColor(): ?string
    {
        return match ($this) {
            self::RIFIUTATO => 'danger',
            self::IN_ATTESA => 'warning',
            self::ACCETTATO => 'success',
            self::INTERESSE_PIU_TEMPO => 'gray',
            self::BOZZA => 'gray',
            self::SUPERIORE_BUDGET => 'info',
            self::OLTRE_TEMPI => 'danger',
            self::NON_INTERESSA => 'danger',
            self::DA_RIVEDERE => 'warning',
            self::ALTRO => 'dark',
        };
    }
}
