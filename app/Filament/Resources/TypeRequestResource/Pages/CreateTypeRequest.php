<?php

namespace App\Filament\Resources\TypeRequestResource\Pages;

use App\Filament\Resources\TypeRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTypeRequest extends CreateRecord
{
    protected static string $resource = TypeRequestResource::class;

     protected static ?string $title = 'Nuova Competenza';
}
