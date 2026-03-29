<?php

namespace App\Filament\Resources\TypeSupplierResource\Pages;

use App\Filament\Resources\TypeSupplierResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTypeSupplier extends CreateRecord
{
    protected static string $resource = TypeSupplierResource::class;

    protected static ?string $title = 'Crea Tipologia Fornitore';

}
