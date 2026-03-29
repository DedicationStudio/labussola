<?php

namespace App\Filament\Resources\TransportCompanyResource\Pages;

use App\Filament\Resources\TransportCompanyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransportCompany extends EditRecord
{
    protected static string $resource = TransportCompanyResource::class;

    protected static ?string $title = 'Modifica Azienda Trasporto';


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
             ->modalHeading(fn($record): string => 'Elimina Azienda Trasporto'),
        ];
    }
}
