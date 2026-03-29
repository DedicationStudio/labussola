<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TypeSupplierResource\Pages;
use App\Filament\Resources\TypeSupplierResource\RelationManagers;
use App\Models\Type;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TypeSupplierResource extends Resource
{
    protected static ?string $model = Type::class;

    protected static ?string $navigationLabel = 'Tipologia Fornitori';
    protected static ?string $navigationGroup = 'Anagrafica Fornitori';

    protected static ?int $navigationSort = 9;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tipologia_fornitore')
                    ->label('Nome Tipologia')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tipologia_fornitore')
                    ->label('Nome Tipologia')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->modalHeading(fn($record): string => 'Modifica Tipologia Fornitore'),
                Tables\Actions\DeleteAction::make()
                ->visible(fn() => auth()->user()?->hasAnyRole(['admin', 'superadmin']))
                    ->modalHeading(fn($record): string => 'Elimina Tipologia Fornitore'),
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
            'index' => Pages\ListTypeSuppliers::route('/'),
            'create' => Pages\CreateTypeSupplier::route('/create'),
            'edit' => Pages\EditTypeSupplier::route('/{record}/edit'),
        ];
    }

    public static function getBreadcrumb(): string
    {
        return 'Tipologia Fornitori';
    }
}
