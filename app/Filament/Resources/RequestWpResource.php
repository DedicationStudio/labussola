<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RequestWpResource\Pages;
use App\Filament\Resources\RequestWpResource\RelationManagers;
use App\Models\Customer;
use App\Models\QuoteRequest;
use App\Models\RequestWp;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class RequestWpResource extends Resource
{
    protected static ?string $model = RequestWp::class;

    protected static ?string $navigationGroup = 'Richieste';

    protected static ?string $navigationLabel = 'Richieste da Web';

    protected static ?int $navigationSort = 4;



public static function form(Form $form): Form
{
    return $form
        ->schema([

            // =====================
            // DATI PERSONALI
            // =====================
            Forms\Components\Section::make('Dati personali')
                ->schema([
                    Forms\Components\TextInput::make('nome')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('cognome')
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('email')
                        ->email()
                        ->required()
                        ->maxLength(255),

                    Forms\Components\TextInput::make('telefono')
                        ->tel()
                        ->required()
                        ->maxLength(255),
                ])
                ->columns(2),

            // =====================
            // DATI SCUOLA
            // =====================
            Forms\Components\Section::make('Dati scuola')
                ->schema([
                    Forms\Components\TextInput::make('scuola')
                        ->required(),

                    Forms\Components\TextInput::make('citta_scuola')
                        ->label('Città scuola'),

                    Forms\Components\TextInput::make('telefono_scuola')
                        ->tel(),

                    Forms\Components\TextInput::make('ruolo')
                        ->placeholder('Es. Docente, Referente viaggi'),

                    Forms\Components\TextInput::make('classe'),

                    Forms\Components\TextInput::make('grado')
                        ->placeholder('Es. Secondaria di primo grado'),
                ])
                ->columns(2),

            // =====================
            // DETTAGLI VIAGGIO
            // =====================
            Forms\Components\Section::make('Dettagli viaggio')
                ->schema([
                    Forms\Components\TextInput::make('meta_viaggio')
                        ->label('Meta del viaggio')
                        ->required(),

                    Forms\Components\TextInput::make('citta_partenza')
                        ->label('Città di partenza'),

                    Forms\Components\TextInput::make('durata')
                        ->placeholder('Es. 3 giorni / 2 notti'),

                    Forms\Components\TextInput::make('periodo')
                        ->placeholder('Es. Marzo 2026'),

                    Forms\Components\Select::make('trasporto')
                        ->options([
                            'bus' => 'Bus',
                            'treno' => 'Treno',
                            'aereo' => 'Aereo',
                        ]),
                ])
                ->columns(2),

            // =====================
            // NUMERI
            // =====================
            Forms\Components\Section::make('Partecipanti')
                ->schema([
                    Forms\Components\TextInput::make('num_studenti')
                        ->numeric()
                        ->required(),

                    Forms\Components\TextInput::make('num_docenti')
                        ->numeric(),

                    Forms\Components\TextInput::make('disabili')
                        ->numeric()
                        ->label('Studenti con disabilità'),
                ])
                ->columns(3),

            // =====================
            // MESSAGGI
            // =====================
            Forms\Components\Section::make('Note e richieste')
                ->schema([
                    Forms\Components\Textarea::make('messaggio')
                        ->rows(4)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('altre_info')
                        ->rows(4)
                        ->columnSpanFull(),
                ]),

        ]);
}

    public static function table(Table $table): Table
    {
        return $table
        ->query(
            RequestWp::query()
                ->where('in_richiesta_interna', null)
        )
    ->defaultSort('created_at', 'desc')
    ->columns([

        // IDENTITÀ
        Tables\Columns\TextColumn::make('nome_completo')
            ->label('Nome e Cognome')
            ->getStateUsing(fn ($record) => trim($record->nome . ' ' . $record->cognome)),

        Tables\Columns\TextColumn::make('email')
            ->searchable(),

        Tables\Columns\TextColumn::make('telefono')
            ->label('Telefono')
            ->searchable(),

        // SCUOLA
        Tables\Columns\TextColumn::make('scuola')
            ->label('Scuola')
            ->searchable(),

        Tables\Columns\TextColumn::make('ruolo')
            ->label('Ruolo'),

        Tables\Columns\TextColumn::make('citta_scuola')
            ->label('Città scuola')
            ->toggleable(isToggledHiddenByDefault: true),

        Tables\Columns\TextColumn::make('telefono_scuola')
            ->label('Tel. scuola')
            ->toggleable(isToggledHiddenByDefault: true),

        // VIAGGIO
        Tables\Columns\TextColumn::make('meta_viaggio')
            ->label('Meta'),

        Tables\Columns\TextColumn::make('citta_partenza')
            ->label('Partenza')
            ->toggleable(isToggledHiddenByDefault: true),

        Tables\Columns\TextColumn::make('durata')
            ->label('Durata'),

        Tables\Columns\TextColumn::make('periodo')
            ->label('Periodo'),

        Tables\Columns\TextColumn::make('trasporto')
            ->label('Trasporto')
            ->toggleable(isToggledHiddenByDefault: true),

        // NUMERI
        Tables\Columns\TextColumn::make('num_studenti')
            ->label('Studenti'),

        Tables\Columns\TextColumn::make('num_docenti')
            ->label('Docenti')
            ->toggleable(isToggledHiddenByDefault: true),

        Tables\Columns\TextColumn::make('disabili')
            ->label('Disabili')
            ->toggleable(isToggledHiddenByDefault: true),

        // TESTI LUNGHI
        Tables\Columns\TextColumn::make('messaggio')
            ->label('Messaggio')
            ->limit(25)
            ->tooltip(fn ($record) => $record->messaggio)
            ->toggleable(isToggledHiddenByDefault: true),

        Tables\Columns\TextColumn::make('altre_info')
            ->label('Altre info')
            ->limit(25)
            ->tooltip(fn ($record) => $record->altre_info)
            ->toggleable(isToggledHiddenByDefault: true),

        Tables\Columns\TextColumn::make('viaggio')
            ->label('Tipo viaggio')
            ->toggleable(isToggledHiddenByDefault: true),

        // DATE
        Tables\Columns\TextColumn::make('created_at')
            ->label('Creato il')
            ->dateTime()
            ->sortable(),

        Tables\Columns\TextColumn::make('updated_at')
            ->label('Aggiornato il')
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true),
        ])
            ->filters([
                //
            ])
            ->headerActions([
                ])
                ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns)
                ->actions([
                    
                    
                    Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('sync_submissions')
                        ->label('Sync submissions')
                        ->icon('heroicon-o-arrow-path')
                        ->color('secondary')
                        
                        ->action(function () {
                            \Illuminate\Support\Facades\Artisan::call('submissions:sync');
    
                            \Filament\Notifications\Notification::make()
                                ->title('Sincronizzazione completata')
                                ->success()
                                ->send();
                        }),

                        Tables\Actions\Action::make('sposta')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->label('Sposta Richieste Interne')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                               DB::transaction(function () use ($record) {
                                $record->update([
                                    'in_richiesta_interna' => true,
                                ]);
                            });

                            $new = QuoteRequest::createInternalFromWeb($record);

                            Notification::make()
                                ->title('Richiesta duplicata con successo!')
                                ->body("È stata creata una copia: {$new->tag}")
                                ->success()
                                ->send();

                            return redirect(
                                QuoteRequestResource::getUrl('edit', [
                                    'record' => $new->id,
                                ])
                            );
                        }),

                    
                        
                
                    Tables\Actions\ViewAction::make()
                        ->modalHeading(fn($record): string => 'Scheda Richiesta da Web'),
                    Tables\Actions\DeleteAction::make()
                        ->visible(
                            fn($record) =>
                            auth()->user()?->hasAnyRole(['admin', 'superadmin'])
                        )
                        ->modalHeading(fn($record): string => 'Elimina Richiesta da Web'),
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
            'index' => Pages\ListRequestWps::route('/'),
            'create' => Pages\CreateRequestWp::route('/create'),
            'edit' => Pages\EditRequestWp::route('/{record}/edit'),
        ];
    }

    public static function getBreadcrumb(): string
    {
        return 'Richieste da Web';
    }
}
