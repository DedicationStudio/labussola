<?php

namespace App\Filament\Resources\PreventiveResource\RelationManagers;

use App\Models\Customer;
use App\Models\EmailTemplate;
use App\Models\Preventive;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmailsRelationManager extends RelationManager
{
    protected static string $relationship = 'emails';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'nome')
                    ->preload()
                    ->label('Cliente')
                    ->searchable()
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, Set $set) { //$state è il numero del record
                        $customer = Customer::find($state);

                        if ($customer) {
                            $set('email_cliente', $customer->email);
                        }
                    })
                    ->getOptionLabelFromRecordUsing(fn(Customer $record) => "{$record->nome}")
                    ->createOptionForm([

                        Forms\Components\Select::make('tipo_cliente')
                            ->label('Tipo Cliente')
                            ->options([
                                'azienda' => 'Azienda',
                                'privato' => 'Privato',
                                'scuola' => 'Scuola',
                            ])
                            ->default(null)
                            ->required(),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('nome')
                                    ->label('Nome')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('cognome')
                                    ->label('Cognome')
                                    ->nullable()
                                    ->visible(fn($get) => $get('tipo_cliente') === 'privato'),
                            ])
                            ->columns(2),
                        Forms\Components\Select::make('genere')
                            ->label('Genere')
                            ->options([
                                'donna' => 'Donna',
                                'uomo' => 'Uomo',
                            ])
                            ->default(null)
                            ->required()
                            ->visible(fn($get) => $get('tipo_cliente') === 'privato'),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('ragione_sociale')
                                    ->label('Ragione Sociale')
                                    ->visible(fn($get) => in_array($get('tipo_cliente'), ['azienda', 'scuola']))->default(null),
                                Forms\Components\TextInput::make('piva_cf')
                                    ->label('P. Iva')
                                    ->maxLength(255)
                                    ->default(null),
                            ])
                            ->columns(2),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('indirizzo')
                                    ->label('Indirizzo')
                                    ->maxLength(255)
                                    ->default(null),
                                Forms\Components\TextInput::make('citta')
                                    ->label('Città')
                                    ->maxLength(255)
                                    ->default(null),
                            ])
                            ->columns(2),
                        Forms\Components\Group::make()
                            ->schema([
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
                            ])
                            ->columns(3),


                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->unique(ignorable: fn($record) => $record)
                                    ->maxLength(255)
                                    ->default(null),
                                Forms\Components\TextInput::make('telefono')
                                    ->label('Telefono')
                                    ->tel()
                                    ->maxLength(255)
                                    ->default(null),
                            ])
                            ->columns(2),
                    ])

                    ->required(),
                Forms\Components\Select::make('preventives')
                    ->relationship('preventives', 'tag')
                    ->searchable()
                    ->label('Preventivi')
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn(Preventive $record) => "{$record->tag}")
                    ->multiple()
                    ->label('Preventivi'),


                Forms\Components\Select::make('email_template_id')
                    ->relationship('template_email', 'nome')
                    ->required()
                    ->preload()
                    ->label('Template Email')
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, Set $set) { //$state è il numero del record
                        $template_email = EmailTemplate::find($state);

                        if ($template_email) {
                            $set('corpo_email', $template_email->corpo_email);
                        }
                    })
                    ->searchable(),
                Forms\Components\TextInput::make('email_cliente')
                    ->label('Email Cliente')
                    ->disabled()/* anche se il campo è disabilitato il suo valore viene comunque “deidratato” (cioè incluso nei dati inviati a Filament quando salvi il record). */
                    ->dehydrated(),
                Forms\Components\TextInput::make('email_cc')
                    ->maxLength(255)
                    ->label('Email cc'),


                Forms\Components\RichEditor::make('corpo_email')
                    ->label('Corpo Email')
                    ->toolbarButtons([
                        'bold',
                        'bulletList',
                        'italic',
                        'orderedList',
                        'redo',
                        'underline',
                        'undo',
                    ])
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('allegati')
                    ->directory('allegati_email')
                    ->acceptedFileTypes(['application/pdf'])
                    ->multiple()
                    ->maxSize(3072)
                    ->preserveFilenames()
                    ->label('Allegati'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns(components: [
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn($record) =>
                        auth()->user()?->hasAnyRole(['admin', 'superadmin']) /* ||
$record->created_by === auth()->id() */
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ])
                    ->visible(condition: fn() => auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('superadmin')),
            ]);
    }
}
