<?php

namespace App\Filament\Resources\PreventiveResource\Pages;

use App\Filament\Resources\PreventiveResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Actions;

class ViewPreventive extends ViewRecord
{
    protected static string $resource = PreventiveResource::class;

    protected static ?string $title = 'Scheda Preventivo';

}