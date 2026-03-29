<?php

namespace App\Filament\Resources\TypeSupplierResource\Pages;

use App\Filament\Resources\TypeSupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTypeSupplier extends EditRecord
{
    protected static string $resource = TypeSupplierResource::class;

    protected static ?string $title = 'Modifica Tipologie Fornitori';


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
