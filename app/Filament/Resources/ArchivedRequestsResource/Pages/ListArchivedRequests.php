<?php

namespace App\Filament\Resources\ArchivedRequestsResource\Pages;

use App\Filament\Resources\ArchivedRequestsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArchivedRequests extends ListRecords
{
    protected static string $resource = ArchivedRequestsResource::class;

    protected static ?string $title = 'Richieste Archiviate';


    /* protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    } */
}
