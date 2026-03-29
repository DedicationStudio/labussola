<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExtraServiceResource\Pages;
use App\Filament\Resources\ExtraServiceResource\RelationManagers;
use App\Models\ExtraService;
use App\Models\Supplier;
use App\Models\Type;
use App\ReliabilitySupplierStatus;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Guava\FilamentIconPicker\Forms\IconPicker;
use Illuminate\Support\Facades\File;

class ExtraServiceResource extends Resource
{
    protected static ?string $model = ExtraService::class;

    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    protected static ?string $navigationGroup = 'Servizi';

    protected static ?string $navigationLabel = 'Servizi Extra';

    protected static ?int $navigationSort = 17;



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
                            ->hintActions([ //oppure suffixActions([])
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

                Forms\Components\Select::make('tipo')
                    ->required()
                    ->dehydrated(true)
                    ->searchable()
                    ->label('Tipologia')
                    ->options(getIconsOptions())
                    ->multiple()
                    ->preload()
                    ->dehydrateStateUsing(fn($state) => json_encode($state))
                    ->afterStateHydrated(function ($component, $state) {
                        /*if (is_string($state)) {
                            $decoded = json_decode($state, true);
                            $component->state(is_array($decoded) ? $decoded : []);
                        } elseif (is_array($state)) {
                            $component->state($state);
                        } else {
                            $component->state([]);
                        }*/
                    }),
                Forms\Components\TextInput::make('nome')
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('descrizione_servizio')
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
                Forms\Components\FileUpload::make('allegati')
                    ->multiple()
                    ->acceptedFileTypes(['application/pdf'])
                    ->maxSize(3072)
                    ->preserveFilenames()
                    ->directory('allegati_servizi')
                    ->label('Allegati')
                    ->columnSpanFull(),


            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                /*   Tables\Columns\TextColumn::make('supplier.nome')
                      ->label('Fornitore')
                      ->searchable(), */
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipologia')
                    ->searchable(),
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->getStateUsing(fn($record) => $record->nome ?? '-'),
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
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn($record) => auth()->user()?->hasAnyRole(['admin', 'superadmin']))
                        ->modalHeading(fn($record): string => 'Elimina Servizio Extra'),

                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplica')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->requiresConfirmation()
                        ->action(fn($record) => redirect(
                            ExtraServiceResource::getUrl('create', [
                                'duplicate_from_service' => $record->id,
                            ])
                        )),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn() => auth()->user()?->hasAnyRole(['admin', 'superadmin'])),
                ]),
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
            'index' => Pages\ListExtraServices::route('/'),
            'create' => Pages\CreateExtraService::route('/create'),
            'edit' => Pages\EditExtraService::route('/{record}/edit'),
        ];
    }

    public static function getBreadcrumb(): string
    {
        return 'Servizi Extra';
    }
}

function getIconsOptions(): array
{
    $basePath = public_path('icone');
    $options = [];

    foreach (File::directories($basePath) as $directory) {
        $category = basename($directory);
        $categoryLabel = ucfirst($category);

        foreach (File::files($directory) as $file) {
            if ($file->getExtension() === 'png') {
                $filename = ucfirst($file->getFilenameWithoutExtension());

                // chiave = valore = quello che salvi in DB
                $options[$categoryLabel][$filename] = $filename;
            }
        }
    }

    return $options;
}

function getIcon(): array
{
    $basePath = public_path('icone'); // percorso fisico
    $baseUrl = asset('icone');        // percorso URL per il browser
    $options = [];

    foreach (File::directories($basePath) as $directory) {
        $category = basename($directory);
        $categoryLabel = ucfirst($category);

        foreach (File::files($directory) as $file) {
            if ($file->getExtension() === 'png') {
                $filename = $file->getFilename(); // nome file con estensione
                $options[$categoryLabel][$filename] = $filename; // salva solo il nome
            }
        }
    }

    return $options;
}
