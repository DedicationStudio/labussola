<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;



class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;
    protected static ?string $title = 'Modifica Utente';







    protected function getHeaderActions(): array
    {
        return [

            Actions\DeleteAction::make()
             ->modalHeading(fn($record): string => 'Elimina Utente'),
        ];
    }


}
