<?php

namespace App\Filament\Resources\ArchivedRequestsResource\Pages;

use App\Filament\Resources\ArchivedRequestsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArchivedRequests extends EditRecord
{
    protected static string $resource = ArchivedRequestsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
