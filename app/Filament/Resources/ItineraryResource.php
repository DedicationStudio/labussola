<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ItineraryResource\Pages;
use App\Filament\Resources\ItineraryResource\RelationManagers;
use App\Models\Itinerary;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ItineraryResource extends Resource
{
    protected static ?string $model = Itinerary::class;

    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Itinerari';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')->label('Nome')
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\Repeater::make('itinerario')
                    ->label('')
                    ->schema([
                        Forms\Components\TextInput::make('titolo')->label('Titolo')
                            ->columnSpanFull()
                            ->required(),
                        Forms\Components\RichEditor::make('descrizione')
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'italic',
                                'orderedList',
                                'redo',
                                'underline',
                                'undo',
                            ]),
                        Forms\Components\FileUpload::make('immagini')
                            ->label('Immagini Itinerario')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                            ->minSize(50)
                            ->maxSize(1024)
                            ->multiple()
                            ->required()
                            ->minFiles(3)
                            ->maxFiles(3)
                            ->preserveFilenames()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('preventivi')
                            ->dehydrated(true) //  fondamentale: invia i file anche se il repeater è annidato
                            ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per l’itinerario. Minimo 50KB.')
                            ->columnSpanFull(),
                    ])
                    ->addActionLabel('Aggiungi itinerario')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
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
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(
                        fn($record) =>
                        auth()->user()?->hasAnyRole(['admin', 'superadmin']) /* ||
$record->created_by === auth()->id() */
                    )
                    ->modalHeading(fn($record): string => 'Elimina Itinerario'),
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
            'index' => Pages\ListItineraries::route('/'),
            'create' => Pages\CreateItinerary::route('/create'),
            'edit' => Pages\EditItinerary::route('/{record}/edit'),
        ];
    }

    public static function getBreadcrumb(): string
    {
        return 'Itinerari';
    }
}
