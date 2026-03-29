<?php

namespace App\Filament\Resources\TransportCompanyResource\Pages;

use App\Filament\Resources\TransportCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransportCompanies extends ListRecords
{
    protected static string $resource = TransportCompanyResource::class;

    protected static ?string $title = 'Aziende Trasporti';


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
