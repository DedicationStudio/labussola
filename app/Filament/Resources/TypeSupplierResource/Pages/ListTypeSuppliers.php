<?php

namespace App\Filament\Resources\TypeSupplierResource\Pages;

use App\Filament\Resources\TypeSupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTypeSuppliers extends ListRecords
{
    protected static string $resource = TypeSupplierResource::class;

    protected static ?string $title = 'Tipologie Fornitori';


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
