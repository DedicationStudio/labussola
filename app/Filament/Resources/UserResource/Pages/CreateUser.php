<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Mail\CredenzialiAgente;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Hash;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Nuovo Utente';

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['password'] ?? null)) {
            $data['password'] = config('auth.default_password'); // verrà hashata dal cast
        }

        if (auth()->user()?->role?->nome === 'agente') {
            unset($data['tipo_richieste']);
        }

        return $data;
    }

    protected function beforeCreate()
    {
        $domain = URL::to('/');
        $url = $domain . '/credentials-app-user';

        $state = $this->form->getRawState();

        $plainPassword = $state['password'] ?? config('auth.default_password');

        $data['url'] = $url;
        $data['ruolo'] = $this->form->getState()['role_id'];
        $data['nome'] = $this->form->getState()['nome'];
        $data['cognome'] = $this->form->getState()['cognome'];
        $data['email'] = $this->form->getState()['email'];
        $data['telefono'] = $this->form->getState()['telefono'];
        $data['password'] = $plainPassword;

        Mail::to($data['email'])->send(new CredenzialiAgente($data));

    }
}
