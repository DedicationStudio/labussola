<?php

namespace App\Filament\Resources\TypeRequestResource\Pages;

use App\Filament\Resources\TypeRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTypeRequests extends ListRecords
{
    protected static string $resource = TypeRequestResource::class;

    protected static ?string $title = 'Competenze';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
