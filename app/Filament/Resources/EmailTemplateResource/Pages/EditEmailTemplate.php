<?php

namespace App\Filament\Resources\EmailTemplateResource\Pages;

use App\Filament\Resources\EmailTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmailTemplate extends EditRecord
{
    protected static string $resource = EmailTemplateResource::class;

    protected static ?string $title = 'Modifica Template Email';


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
             ->modalHeading(fn($record): string => 'Elimina Template Email'),
        ];
    }
}
