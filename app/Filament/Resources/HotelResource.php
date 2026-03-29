<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HotelResource\Pages;
use App\Filament\Resources\HotelResource\RelationManagers;
use App\Models\Hotel;
use App\Models\Supplier;
use App\ReliabilitySupplierStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Type;
use Filament\Actions\Action;
use Filament\Notifications\Notification;


class HotelResource extends Resource
{
    protected static ?string $model = Hotel::class;

    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Servizi';

    protected static ?string $navigationLabel = 'Alloggi/Hotels';

    protected static ?int $navigationSort = 15;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('supplier_id')
                    ->relationship('supplier', 'nome')
                    ->preload()
                    ->label('Fornitore')
                    ->searchable()
                    ->getSearchResultsUsing(function (string $search) {
                        return Supplier::query()
                            ->where(function ($query) use ($search) {
                                $query->where('nome', 'like', "%{$search}%")
                                    ->orWhere('cognome', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($supplier) {
                                return [
                                    $supplier->id => trim("{$supplier->nome} {$supplier->cognome}"),
                                ];
                            });
                    })
                    ->getOptionLabelFromRecordUsing(fn(Supplier $record) => trim("{$record->nome} {$record->cognome}"))
                    ->createOptionForm([
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



                    ]),
                Forms\Components\TextInput::make('nome')
                    ->required()
                    ->label('Nome')
                    ->maxLength(255),
                Forms\Components\TextInput::make('indirizzo')
                    ->label('Indirizzo')
                    ->maxLength(255),
                Forms\Components\TextInput::make('stelle')
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->maxValue(5),
                Forms\Components\RichEditor::make('descrizione')
                    ->toolbarButtons([
                        'bold',
                        'bulletList',
                        'italic',
                        'orderedList',
                        'redo',
                        'underline',
                        'undo',
                    ])
                    ->label('Descrizione')
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('foto')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                    ->minSize(50)
                    ->maxSize(1024)
                    ->preserveFilenames()
                    ->multiple()
                    ->disk('public')
                    ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per hotel/alloggio. Minimo 50KB.')
                    ->minFiles(3)
                    ->maxFiles(3)
                    ->visibility('public')
                    ->directory('foto_hotel')
                    ->columnSpanFull()
                    ->label('Foto'),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('supplier.nome')
                    ->label('Fornitore')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('indirizzo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stelle')
                    ->sortable()
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn($record) =>
                        auth()->user()?->hasAnyRole(['admin', 'superadmin'])
                    )
                    ->modalHeading(fn($record): string => 'Elimina Hotel'),

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
            'index' => Pages\ListHotels::route('/'),
            'create' => Pages\CreateHotel::route('/create'),
            'edit' => Pages\EditHotel::route('/{record}/edit'),
        ];
    }

    public static function getBreadcrumb(): string
    {
        return 'Hotels/Alloggi';
    }
}
