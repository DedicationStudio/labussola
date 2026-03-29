<?php

namespace App\Filament\Resources\RequestWpResource\Pages;

use App\Filament\Resources\RequestWpResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRequestWp extends EditRecord
{
    protected static string $resource = RequestWpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
