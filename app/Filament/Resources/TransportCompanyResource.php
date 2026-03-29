<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransportCompanyResource\Pages;
use App\Filament\Resources\TransportCompanyResource\RelationManagers;
use App\Models\TransportCompany;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransportCompanyResource extends Resource
{
    protected static ?string $model = TransportCompany::class;

    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';


    protected static ?string $navigationGroup = 'Servizi';

    protected static ?string $navigationLabel = 'Aziende di Trasporti';

    protected static ?int $navigationSort = 16;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')
                    ->label('Nome')
                    ->maxLength(255),
                Forms\Components\TextInput::make('misura_bg_a_mano')
                    ->label('Misura Bagagli a Mano'),
                Forms\Components\FileUpload::make('immagine')
                    ->image()
                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                    ->maxSize(1024)
                    ->helperText('Carica un\'immagine (.png, .jpg o .jpeg)')
                    ->label('Logo')
                    ->preserveFilenames(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')->searchable()->label('Nome'),
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
                    ->modalHeading(fn($record): string => 'Elimina Azienda Trasporti'),
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
            'index' => Pages\ListTransportCompanies::route('/'),
            'create' => Pages\CreateTransportCompany::route('/create'),
            'edit' => Pages\EditTransportCompany::route('/{record}/edit'),
        ];
    }

    public static function getBreadcrumb(): string
    {
        return 'Aziende Trasporti';
    }
}
