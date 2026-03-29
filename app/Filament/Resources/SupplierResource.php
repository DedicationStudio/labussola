<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers;
use App\Models\Supplier;
use App\ReliabilitySupplierStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Actions\Action;
use App\Models\Type;
use Filament\Notifications\Notification;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;
    // protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'Anagrafica Fornitori';
    protected static ?string $pluralModelLabel = 'Fornitori';
    protected static ?int $navigationSort = 10;


    public static function getGloballySearchableAttributes(): array
    {
        return [
            'ragione_sociale',
            'nome',
            'cognome',
            'email',
            'telefono',
            'citta',
            'regione',
            'stato'
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('cognome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('ragione_sociale')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('piva_cf')
                    ->label('P.Iva')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('codice_fiscale')
                    ->label('Codice Fiscale')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('indirizzo')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Repeater::make('telefono')
                    ->schema([
                        Forms\Components\TextInput::make('telefono')
                            ->label('Telefono')
                            ->required(),
                    ])
                    ->addActionLabel('Aggiungi Numero di Telefono')
                    ->label('Numeri di Telefono')
                    ->columns(1)
                    ->collapsible()
                    ->defaultItems(1),
                Forms\Components\Repeater::make('email')
                    ->schema([
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->unique(ignorable: fn($record) => $record)
                            ->required(),
                    ])
                    ->addActionLabel('Aggiungi Email')
                    ->label('Emails')
                    ->columns(1)
                    ->collapsible()
                    ->defaultItems(1),
                Forms\Components\Repeater::make('sito_web')
                    ->schema([
                        Forms\Components\TextInput::make('sito_web')
                            ->label('Sito Web')
                            ->required(),
                    ])
                    ->addActionLabel('Aggiungi Sito Web')
                    ->label('Siti Web')
                    ->columns(1)
                    ->collapsible()
                    ->defaultItems(1),
                Forms\Components\Repeater::make('portale_web')
                    ->addActionLabel('Aggiungi Portale Web')
                    ->schema([
                        Forms\Components\RichEditor::make('portale_web')
                            ->label('Portale Web')
                            ->default("<h3>Credenziali</h3><p>Utente: <br>Password:</p>")
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'italic',
                                'orderedList',
                                'redo',
                                'link',
                                'underline',
                                'undo',
                            ])
                            ->required(),
                    ])
                    ->label('Portali Web')
                    ->columns(1)
                    ->collapsible()
                    ->defaultItems(1),
                Forms\Components\TextInput::make('regione')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('stato')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('citta')
                    ->label('Città')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('cap')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('provincia')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\Select::make('type_supplier')
                    ->label('Tipologia Fornitore')
                    ->multiple()
                    ->relationship('type_supplier', 'tipologia_fornitore')
                    ->preload()
                    ->searchable()

                    // --- CREA NUOVA ---
                    ->createOptionForm([
                        Forms\Components\TextInput::make('tipologia_fornitore')
                            ->label('Nuova Tipologia')
                            ->required(),
                    ])
                    ->createOptionUsing(function (array $data, $livewire): int {
                        $id = Type::create(['tipologia_fornitore' => $data['tipologia_fornitore']])->id;
                        $livewire->dispatch('$refresh');
                        return $id;
                    })

                    // --- MODIFICA TUTTE LE TIPOLOGIE ---
                    ->hintActions([//oppure suffixActions([])
                        Action::make('modifica_tipologie')
                            ->label('Modifica Tipologie')
                            ->icon('heroicon-o-pencil-square')
                            ->modalHeading('Modifica Tipologie Fornitore')
                            ->form([
                                Forms\Components\Repeater::make('tipologie')
                                    ->schema([
                                        Forms\Components\Hidden::make('id'),
                                        Forms\Components\TextInput::make('tipologia_fornitore')
                                            ->label('Nome Tipologia')
                                            ->required(),
                                    ])
                                    ->columns(1)
                                    ->default(fn() => Type::all(['id', 'tipologia_fornitore'])->toArray())
                                    ->createItemButtonLabel('Aggiungi Tipologia'),
                            ])
                            ->action(function (array $data, $livewire, Forms\Components\Select $component) {
                                foreach ($data['tipologie'] as $tipo) {
                                    if (!empty($tipo['id'])) {
                                        Type::where('id', $tipo['id'])
                                            ->update(['tipologia_fornitore' => $tipo['tipologia_fornitore']]);
                                    } else {
                                        Type::create(['tipologia_fornitore' => $tipo['tipologia_fornitore']]);
                                    }
                                }

                                // Aggiorna le opzioni del campo
                                $component->options(Type::pluck('tipologia_fornitore', 'id')->toArray());

                                // Mantiene selezionati gli ID già scelti
                                $component->state(
                                    collect($component->getState())
                                        ->filter(fn($id) => Type::where('id', $id)->exists())
                                        ->values()
                                        ->toArray()
                                );

                                $livewire->dispatch('$refresh');

                                Notification::make()
                                    ->title('Tipologie fornitore aggiornate')
                                    ->success()
                                    ->send();
                            }),
                    ]),

                Forms\Components\Select::make('competence_area')
                    ->label('Area Geografica di Competenza')
                    ->multiple()
                    ->relationship('competence_area', 'area')
                    ->preload()
                    ->searchable()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('area')
                            ->label('Aggiungi Area Geografica di Competenza')
                            ->required(),
                    ])
                    ->editOptionForm([
                        Forms\Components\TextInput::make('area')
                            ->label('Modifica Area Geografica di Competenza')
                            ->required(),
                    ]),
                Forms\Components\Select::make('reliability_id')
                    ->label('Affidabilità')
                    ->relationship('reliability', 'nome')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->createOptionForm([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('nome')->required(),
                                Forms\Components\Select::make('colore')
                                    ->label('Colore badge')
                                    ->options([
                                        'gray' => 'Grigio',
                                        'primary' => 'Blu',
                                        'success' => 'Verde',
                                        'warning' => 'Giallo',
                                        'danger' => 'Rosso',
                                        'info' => 'Azzurro',
                                        'purple' => 'Viola',
                                        'pink' => 'Rosa',
                                    ])
                                    ->required(),
                            ])
                            ->columns(2),
                    ])
                    ->editOptionForm([
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('nome')->required(),
                                Forms\Components\Select::make('colore')
                                    ->label('Colore badge')
                                    ->options([
                                        'gray' => 'Grigio',
                                        'primary' => 'Blu',
                                        'success' => 'Verde',
                                        'warning' => 'Giallo',
                                        'danger' => 'Rosso',
                                        'info' => 'Azzurro',
                                        'purple' => 'Viola',
                                        'pink' => 'Rosa',
                                    ])
                                    ->required()
                            ])
                            ->columns(2),
                    ]),
                Forms\Components\Textarea::make('descrizione')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('note')
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(url: fn($record) => SupplierResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Fornitore')
                    ->formatStateUsing(fn($state, $record) => trim("{$record->nome} {$record->cognome}"))
                    ->searchable(['nome', 'cognome']),
                Tables\Columns\TextColumn::make('type_supplier')
                    ->formatStateUsing(
                        fn($record) =>
                        $record->type_supplier->pluck('tipologia_fornitore')->join(', ')
                    )
                    ->label('Tipologia'),
                Tables\Columns\TextColumn::make('reliability.nome')
                    ->label('Affidabilità')
                    ->badge()
                    ->color(fn($state, $record) => $record->reliability?->colore ?? 'gray'),
                Tables\Columns\TextColumn::make('ragione_sociale')
                    ->label('Ragione Sociale')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('citta')
                    ->label('Città')
                    ->sortable(),
                Tables\Columns\TextColumn::make('descrizione')
                    ->label('Descrizione')
                    ->limit(20)
                    ->tooltip(fn($record) => $record->descrizione) // mostra tutta la descrizione al passaggio del mouse
                    ->sortable(),
            ])
            ->persistFiltersInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->label('Tipologia')
                    ->relationship('type_supplier', 'tipologia_fornitore'),

                Tables\Filters\SelectFilter::make('reliability_id')
                    ->label('Affidabilità')
                    ->relationship('reliability', 'nome')
                    ->preload()
                    ->searchable(),
                Tables\Filters\SelectFilter::make('citta')
                    ->label('Città')
                    ->options(
                        fn() => Supplier::query()
                            ->select('citta')
                            ->distinct()
                            ->orderBy('citta')
                            ->pluck('citta', 'citta')
                            ->filter() // rimuove eventuali null/empty
                    ),
                Tables\Filters\SelectFilter::make('competence_area')
                    ->label('Area Geografica di Competenza')
                    ->relationship('competence_area', 'area'),

            ])
            ->defaultSort('cognome', 'asc')
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading(fn($record): string => 'Scheda Fornitore'),
                    Tables\Actions\EditAction::make()
                        ->visible(
                            fn($record) =>
                            auth()->user()?->hasAnyRole(['admin', 'superadmin'])
                        ),
                    Tables\Actions\DeleteAction::make()
                        ->visible(
                            fn($record) =>
                            auth()->user()?->hasAnyRole(['admin', 'superadmin'])
                        )
                        ->modalHeading(fn($record): string => 'Elimina Fornitore'),
                   
                ]),
            ])

            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]))
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
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
            'view' => Pages\ViewSupplier::route('/{record}'),
        ];
    }
}
