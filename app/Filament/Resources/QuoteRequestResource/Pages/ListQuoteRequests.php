<?php

namespace App\Filament\Resources\QuoteRequestResource\Pages;

use App\Filament\Resources\QuoteRequestResource;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Pages\ListRecords;

class ListQuoteRequests extends ListRecords
{
    protected static string $resource = QuoteRequestResource::class;

    protected static ?string $title = 'Richieste Interne';


    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $user = auth()->user();

        // Se l'utente è un admin o superadmin: può vedere tutte le richieste
        if ($user->hasAnyRole(['admin', 'superadmin'])) {
            return [
                'Richieste Inviate' => Tab::make()
                    ->modifyQueryUsing(
                        fn(Builder $query) =>
                        $query->where('stato_richiesta', 'inviata')
                    ),
                'Richieste da Gestire' => Tab::make()
                    ->modifyQueryUsing(
                        fn(Builder $query) =>
                        $query->where('stato_richiesta', 'in lavorazione')
                    ),
                'Richieste Evase' => Tab::make()
                    ->modifyQueryUsing(
                        fn(Builder $query) =>
                        $query->where('stato_richiesta', 'evasa')
                    ),
                'Assegnate in Attesa' => Tab::make()
                    ->modifyQueryUsing(function (Builder $query) use ($user) {

                        $query->where(function (Builder $main) use ($user) {

                            $main->where('created_by', $user->id)
                                ->whereNotNull('scadenza')
                                ->whereDate('scadenza', '<', today())
                                ->where(function (Builder $q) {
                                    // nessuna disponibilità inserita
                                    $q->orWhereDoesntHave('agentAvailabilities')
                                        // oppure tutte segnate come non disponibili
                                        ->orWhereHas('agentAvailabilities', function (Builder $qa) {
                                            $qa->where('stato', "non_disponibile");
                                        });
                                });
                        });
                    }),



                'Tutte' => Tab::make(),
            ];
        }
        $user = auth()->user();

        if (!$user->hasRole('agente')) {
            return [];
        }

        return [
            'Richieste Personali Ricevute' => Tab::make()
                ->modifyQueryUsing(function (Builder $query) use ($user) {

                    $query
                        ->whereHas(
                            'agenti_inviati',
                            fn($q) =>
                            $q->whereKey($user->id)
                        )

                        ->whereIn('stato_richiesta', ['inviata', 'risposta pervenuta'])

                        ->whereDoesntHave(
                            'agenti_gestori',
                            fn($q) =>
                            $q->whereKeyNot($user->id)
                        );
                }),
            'Senza Disponibilità' => Tab::make()
                ->modifyQueryUsing(function (Builder $query) use ($user) {

                    $query
                        ->whereHas('managedByUsers', function ($q) use ($user) {
                            $q->where('users.id', $user->id);
                        })

                        ->where(function (Builder $q) use ($user) {

                            // NON ha proprio availability
                            $q->whereDoesntHave('agentAvailabilities', function (Builder $q2) use ($user) {
                                $q2->where('user_id', $user->id);
                            })

                                // oppure ha availability ma SENZA data
                                ->orWhereHas('agentAvailabilities', function (Builder $q3) use ($user) {
                                    $q3->where('user_id', $user->id)
                                        ->whereNull('n_giorni_preventivo');
                                });
                        })

                        ->whereIn('stato_richiesta', ['in lavorazione', 'assegnata']);
                }),


            'Richieste da Gestire' => Tab::make()
                ->modifyQueryUsing(function (Builder $query) use ($user) {

                    $query

                        // richiesta collegata a me nella pivot
                        ->whereHas('managedByUsers', function ($q) use ($user) {
                            $q->where('users.id', $user->id);
                        })

                        // availability della stessa richiesta e dello stesso utente
                        ->whereHas('agentAvailabilities', function (Builder $q) use ($user) {
                            $q->where('user_id', $user->id)
                                ->whereDate('n_giorni_preventivo', '>', now());
                        })

                        ->whereIn('stato_richiesta', ['in lavorazione', 'assegnata']);
                }),

            'Richieste Evase' => Tab::make()
                ->modifyQueryUsing(function (Builder $query) use ($user) {
                    $query->where(function (Builder $q) use ($user) {
                        // Richieste dove il cliente è gestito da me
                        $q->where('cliente_gestito_da_me', true)
                            // OPPURE richieste dove sono negli agenti inviati
                            ->orWhereHas('agenti_inviati', function (Builder $q2) use ($user) {
                                $q2->where('users.id', $user->id);
                            });
                    })
                        // E deve essere anche negli agenti gestori
                        ->whereHas('agenti_gestori', function (Builder $q) use ($user) {
                            $q->where('users.id', $user->id);
                        })
                        // E deve avere stato evasa
                        ->where('stato_richiesta', 'evasa');
                }),
            'Richieste Scadute' => Tab::make()
                ->modifyQueryUsing(function (Builder $query) use ($user) {

                    $query
                        ->whereHas('managedByUsers', function ($q) use ($user) {
                            $q->where('users.id', $user->id);
                        })

                        // HA disponibilità ma è scaduta
                        ->whereHas('agentAvailabilities', function (Builder $q) use ($user) {
                            $q->where('user_id', $user->id)
                                ->whereNotNull('n_giorni_preventivo')
                                ->whereDate('n_giorni_preventivo', '<', now());
                        })

                        ->whereIn('stato_richiesta', ['in lavorazione', 'assegnata']);
                }),
            'Assegnate in Attesa' => Tab::make()
                ->modifyQueryUsing(function (Builder $query) use ($user) {

                    $query->where(function (Builder $main) use ($user) {

                        $main->where('created_by', $user->id)
                            ->whereNotNull('scadenza')
                            ->whereDate('scadenza', '<', today())
                            ->where(function (Builder $q) {
                                // nessuna disponibilità inserita
                                $q->orWhereDoesntHave('agentAvailabilities')
                                    // oppure tutte segnate come non disponibili
                                    ->orWhereHas('agentAvailabilities', function (Builder $qa) {
                                        $qa->where('stato', "non_disponibile");
                                    });
                            });
                    });
                }),





            'Tutte' => Tab::make(),

        ];
    }


    public function getDefaultActiveTab(): string|int|null
    {
        $user = auth()->user();

        if ($user->hasAnyRole(['admin', 'superadmin'])) {
            return 'Richieste da Gestire';
        }

        if ($user->hasRole('agente')) {
            return 'Richieste Personali Ricevute';
        }

        return 'Tutte';
    }
}
