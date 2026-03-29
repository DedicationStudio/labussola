<?php

namespace App\Filament\Resources\RequestWpResource\Pages;

use App\Filament\Resources\RequestWpResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequestWps extends ListRecords
{
    protected static string $resource = RequestWpResource::class;
    protected static ?string $title = 'Richieste da Web';

   /*  protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    } */
}
