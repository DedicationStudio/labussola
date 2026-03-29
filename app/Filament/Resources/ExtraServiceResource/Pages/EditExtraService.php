<?php

namespace App\Filament\Resources\ExtraServiceResource\Pages;

use App\Filament\Resources\ExtraServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExtraService extends EditRecord
{
    protected static string $resource = ExtraServiceResource::class;

    protected static ?string $title = 'Modifica Servizio Extra';


    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
             ->modalHeading(fn($record): string => 'Elimina Servizio Extra'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
{
    $data['tipo'] = is_array($data['tipo'])
        ? $data['tipo']
        : json_decode($data['tipo'] ?? '[]', true);

    $data['allegati'] = is_array($data['allegati'])
        ? $data['allegati']
        : json_decode($data['allegati'] ?? '[]', true);

    return $data;
}
    
}
