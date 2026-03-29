<?php

namespace App\Filament\Resources\TransportCompanyResource\Pages;

use App\Filament\Resources\TransportCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransportCompany extends CreateRecord
{
    protected static string $resource = TransportCompanyResource::class;

    protected static ?string $title = 'Nuova Azienda Trasporto';
}
