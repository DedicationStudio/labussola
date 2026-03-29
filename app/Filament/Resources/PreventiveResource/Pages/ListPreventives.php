<?php

namespace App\Filament\Resources\PreventiveResource\Pages;

use App\Filament\Resources\PreventiveResource;
use App\PreventiveStatus;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
class ListPreventives extends ListRecords
{
    protected static string $resource = PreventiveResource::class;
    protected static ?string $title = 'Preventivi';



    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();

        // Se l'utente è admin o superadmin → mostra solo "Tutti" e "In attesa"
        if ($user->hasAnyRole(['admin', 'superadmin'])) {
            return [
                'Tutti' => Tab::make(),

               'Preventivi Accettati' => Tab::make()
                    ->modifyQueryUsing(
                        fn(Builder $query) =>
                        $query->where('stato', PreventiveStatus::ACCETTATO->value)
                    ),
                    
                'Preventivi Evasi' => Tab::make()
                    ->modifyQueryUsing(
                        fn(Builder $query) =>
                        $query->whereIn('stato', [
                            PreventiveStatus::RIFIUTATO->value,
                            PreventiveStatus::INTERESSE_PIU_TEMPO->value,
                            PreventiveStatus::SUPERIORE_BUDGET->value,
                            PreventiveStatus::OLTRE_TEMPI->value,
                            PreventiveStatus::NON_INTERESSA->value,
                            PreventiveStatus::DA_RIVEDERE->value,
                            PreventiveStatus::ALTRO->value,
                        ])
                    ),


             'In Attesa' => Tab::make()
                    ->modifyQueryUsing(
                        fn(Builder $query) =>
                        $query->where('stato', PreventiveStatus::IN_ATTESA->value)
                    ),
                    
                'Bozze' => Tab::make()
                    ->modifyQueryUsing(
                        fn(Builder $query) =>
                        $query->where('stato', PreventiveStatus::BOZZA->value)
                    ),

            ];
        }

        // Se è agente → mostra tutte le schede
        return [
            'Preventivi Personali' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('created_by', $user->id)
                ),

              'In Attesa' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('stato', PreventiveStatus::IN_ATTESA->value)
                        ->where('created_by', $user->id)
                ),

                 'Preventivi Accettati' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('stato', PreventiveStatus::ACCETTATO->value)
                        ->where('created_by', $user->id)
                ),

            'Evasi' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->whereIn('stato', [
                        PreventiveStatus::RIFIUTATO->value,
                        PreventiveStatus::INTERESSE_PIU_TEMPO->value,
                        PreventiveStatus::SUPERIORE_BUDGET->value,
                        PreventiveStatus::OLTRE_TEMPI->value,
                        PreventiveStatus::NON_INTERESSA->value,
                        PreventiveStatus::DA_RIVEDERE->value,
                        PreventiveStatus::ALTRO->value,
                    ])
                    ->where('created_by', $user->id)
                ),

            'Bozze' => Tab::make()
                ->modifyQueryUsing(
                    fn(Builder $query) =>
                    $query->where('stato', PreventiveStatus::BOZZA->value)
                        ->where('created_by', $user->id)
                ),

            'Tutti' => Tab::make('Tutti')
                ->modifyQueryUsing(fn(Builder $query) => $query),
        ];
    }

    

    public function getDefaultActiveTab(): string|int|null
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['admin', 'superadmin'])) {
            return 'Tutti';
        }

        if ($user->hasRole('agente')) {
            return 'Preventivi Personali';
        }

        return 'Tutti';
    }
}
