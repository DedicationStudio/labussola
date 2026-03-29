<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Anagrafica Clienti';

    protected static ?string $navigationLabel = 'Clienti';
    protected static ?int $navigationSort = 8;
    //test


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tipo_cliente')
                    ->label('Tipologia')
                    ->options([
                        'azienda' => 'Azienda',
                        'privato' => 'Privato',
                        'scuola' => 'Scuola',
                    ])
                    ->default(null)
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('nome')
                    ->label('Nome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('cognome')
                    ->label('Cognome')
                    ->visible(fn($get) => $get('tipo_cliente') === 'privato'),
                Forms\Components\Select::make('genere')
                    ->label('Genere')
                    ->options([
                        'donna' => 'Donna',
                        'uomo' => 'Uomo',
                    ])
                    ->default(null)
                    ->required()
                    ->visible(fn($get) => $get('tipo_cliente') === 'privato'),
                Forms\Components\TextInput::make('ragione_sociale')
                    ->label('Ragione Sociale')
                    ->visible(fn($get) => in_array($get('tipo_cliente'), ['azienda', 'scuola']))
                    ->default(null),
                Forms\Components\TextInput::make('piva_cf')
                    ->label('P. Iva')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('indirizzo')
                    ->label('Indirizzo')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('citta')
                    ->label('Città')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('cap')
                    ->label('CAP')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('provincia')
                    ->label('Provincia')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('stato')
                    ->label('Stato')
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->unique(ignorable: fn($record) => $record)
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('telefono')
                    ->label('Telefono')
                    ->tel()
                    ->helperText('Formato: +39 seguito dal numero (es. +393331234567)')
                    ->regex('/^\+39[0-9]{9,10}$/')
                    ->validationMessages([
                        'regex' => 'Il numero deve iniziare con +39 seguito da 9-10 cifre',
                    ])
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Cliente')
                    ->formatStateUsing(fn($state, $record) => trim("{$record->nome} {$record->cognome}"))
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo_cliente')
                    ->label('Tipo Cliente')
                    ->searchable(),
                /*  Tables\Columns\TextColumn::make('ragione_sociale')
                    ->searchable(),
                Tables\Columns\TextColumn::make('piva_cf')
                    ->searchable(),
                Tables\Columns\TextColumn::make('indirizzo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('citta')
                    ->searchable(),
                Tables\Columns\TextColumn::make('cap')
                    ->searchable(),
                Tables\Columns\TextColumn::make('provincia')
                    ->searchable(),
                Tables\Columns\TextColumn::make('stato')
                    ->searchable(), */
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->label('Telefono')
                    ->searchable(),
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
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns) // azioni a sinistra
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading(fn($record): string => 'Visualizza Cliente'),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\DeleteAction::make()
                        ->modalHeading(fn($record): string => 'Elimina Cliente')
                         ->visible(
                            fn($record) =>
                            auth()->user()?->hasAnyRole(['admin', 'superadmin'])
                        )
                        ->before(function (Customer $record, Tables\Actions\DeleteAction $action) {
                            $preventiviCount = $record->preventives()->count();
                            $richiesteCount = $record->quote_requests()->count();
                            $emailCount = $record->emails()->count();
                            $totale = $preventiviCount + $richiesteCount + $emailCount;

                            if ($totale > 0) {
                                $messaggi = [];
                                if ($preventiviCount > 0)
                                    $messaggi[] = "{$preventiviCount} preventivi";
                                if ($richiesteCount > 0)
                                    $messaggi[] = "{$richiesteCount} richieste";
                                if ($emailCount > 0)
                                    $messaggi[] = "{$emailCount} email";

                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title('Impossibile eliminare il cliente')
                                    ->body("Questo cliente ha " . implode(', ', $messaggi) . " collegati. I preventivi devono essere conservati.")
                                    ->persistent()
                                    ->send();

                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                    ->visible(condition: fn() => auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('superadmin'))
                    ->before(function ($records, Tables\Actions\DeleteBulkAction $action) {
                $clientiBloccati = [];
                
                foreach ($records as $record) {
                    $preventiviCount = $record->preventives()->count();
                    $richiesteCount = $record->quote_requests()->count();
                    $emailCount = $record->emails()->count();
                    $totale = $preventiviCount + $richiesteCount + $emailCount;
                    
                    if ($totale > 0) {
                        $messaggi = [];
                        if ($preventiviCount > 0) $messaggi[] = "{$preventiviCount} preventivi";
                        if ($richiesteCount > 0) $messaggi[] = "{$richiesteCount} richieste";
                        if ($emailCount > 0) $messaggi[] = "{$emailCount} email";
                        
                        $clientiBloccati[] = $record->nome . ' '. $record->cognome . ' (' . implode(', ', $messaggi) . ')';
                    }
                }
                
                if (count($clientiBloccati) > 0) {
                    \Filament\Notifications\Notification::make()
                        ->danger()
                        ->title('Impossibile eliminare alcuni clienti')
                        ->body('I seguenti clienti hanno dati collegati che devono essere conservati: ' . implode(' • ', $clientiBloccati))
                        ->persistent()
                        ->send();
                    
                    $action->cancel();
                }
            }),
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
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }

    public static function getBreadcrumb(): string
    {
        return 'Clienti';
    }
}
