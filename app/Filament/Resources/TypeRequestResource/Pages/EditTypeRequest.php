<?php

namespace App\Filament\Resources\TypeRequestResource\Pages;

use App\Filament\Resources\TypeRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTypeRequest extends EditRecord
{
    protected static string $resource = TypeRequestResource::class;

    protected static ?string $title = 'Modifica Competenza';


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
             ->modalHeading(fn($record): string => 'Elimina Competenza'),
        ];
    }
}
