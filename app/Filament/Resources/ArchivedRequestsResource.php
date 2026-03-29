<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArchivedRequestsResource\Pages;
use App\Filament\Resources\ArchivedRequestsResource\RelationManagers;
use App\Models\QuoteRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Customer;
use App\Models\TypeRequest;
use App\Models\User;
use App\QuoteRequestStatus;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;
use App\PreventiveStatus;

class ArchivedRequestsResource extends Resource
{
    protected static ?string $model = QuoteRequest::class;

    protected static ?string $navigationGroup = 'Richieste';

    protected static ?string $navigationLabel = 'Richieste Archiviate';

    protected static ?int $navigationSort = 5;


    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('stato_richiesta', 'archiviata');
    }
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('creator.nome')
                    ->label('Creata da')
                    ->formatStateUsing(
                        fn($state, $record) =>
                        "{$record->creator?->nome} {$record->creator?->cognome}"
                    )
                    ->searchable(),
                Tables\Columns\TextColumn::make('oggetto')
                    ->searchable()
                    ->label('Oggetto'),
                Tables\Columns\TextColumn::make('meta_viaggio')
                    ->searchable()
                    ->label('Meta'),
                Tables\Columns\TextColumn::make('customer.nome')
                    ->searchable()
                    ->formatStateUsing(fn($state, $record) => "{$record->customer?->nome} {$record->customer?->cognome}")
                    ->searchable()
                    ->label('Cliente'),
                
                Tables\Columns\TextColumn::make('stato_richiesta')
                    ->label('Stato Richiesta')
                    ->badge(),
                Tables\Columns\TextColumn::make('motivazione_archivio')
                    ->label('Motivazione')
                    ->limit(20)
                    ->tooltip(fn($record) => $record->motivazione_archivio) // mostra tutta la descrizione al passaggio del mouse
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->persistFiltersInSession()
            ->filters([
                Tables\Filters\Filter::make('data_ricezione_richiesta')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Da'),
                        Forms\Components\DatePicker::make('until')
                            ->label('A'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $q, $date) => $q->whereDate('data_ricezione_richiesta', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $q, $date) => $q->whereDate('data_ricezione_richiesta', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('ultimo_mese')
                    ->label('Ultimo mese')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('data_ricezione_richiesta', '>=', now()->subMonth())
                    ),
                Tables\Filters\SelectFilter::make('gestita_da')
                    ->label('Agente Gestore')
                    ->relationship('agenti_gestori', 'nome', fn(Builder $query) => $query->whereHas(
                        'role',
                        fn($q) => $q->whereNotIn('nome', ['admin', 'superadmin'])
                    ))
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('customer_nome')
                    ->form([
                        Forms\Components\TextInput::make('q')
                            ->label('Cliente (nome/cognome)'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $term = trim($data['q'] ?? '');
                        return $query->when($term !== '', function (Builder $q) use ($term) {
                            $q->whereHas('customer', function (Builder $c) use ($term) {
                                $c->where(function (Builder $w) use ($term) {
                                    $w->where('nome', 'like', "%{$term}%")
                                        ->orWhere('cognome', 'like', "%{$term}%")
                                        ->orWhere('email', 'like', "%{$term}%");
                                });
                            });
                        });
                    }),
                Tables\Filters\Filter::make('email_cliente')
                    ->form([
                        Forms\Components\TextInput::make('q')
                            ->label('Email Cliente'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $term = trim($data['q'] ?? '');
                        return $query->when($term !== '', function (Builder $q) use ($term) {
                            $q->whereHas('customer', function (Builder $c) use ($term) {
                                $c->where(function (Builder $w) use ($term) {
                                    $w->where('email', 'like', "%{$term}%");
                                });
                            });
                        });
                    }),
                Tables\Filters\Filter::make('meta_viaggio')
                    ->form([
                        Forms\Components\TextInput::make('q')
                            ->label('Meta Viaggio'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $term = trim($data['q'] ?? '');
                        return $query->when(
                            $term !== '',
                            fn(Builder $q) =>
                            $q->where('meta_viaggio', 'like', "%{$term}%")
                        );
                    }),

                //  filtro per stato richiesta
                Tables\Filters\SelectFilter::make('stato_richiesta')
                    ->label('Stato Richiesta')
                    ->options(QuoteRequestStatus::class)
                    ->multiple(),


            ])
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading(fn($record): string => 'Visualizza Richiesta'),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->visible(
                            fn($record) =>
                            auth()->user()?->hasAnyRole(['admin', 'superadmin'])
                        )
                        ->modalHeading(fn($record): string => 'Elimina Richiesta'),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])
                ->visible(condition: fn() => auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('superadmin')),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArchivedRequests::route('/'),
            'create' => Pages\CreateArchivedRequests::route('/create'),
            'edit' => Pages\EditArchivedRequests::route('/{record}/edit'),
        ];
    }

    public static function getBreadcrumb(): string
    {
        return 'Richieste Archiviate';
    }
}
