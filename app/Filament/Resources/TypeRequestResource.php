<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TypeRequestResource\Pages;
use App\Filament\Resources\TypeRequestResource\RelationManagers;
use App\Models\TypeRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TypeRequestResource extends Resource
{
    protected static ?string $model = TypeRequest::class;

    protected static ?string $navigationGroup = 'Team';

    protected static ?string $navigationLabel = 'Competenze';

    protected static ?int $navigationSort = 13;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')
                    ->required()
                    ->maxLength(255),
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
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(condition: fn() => auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('superadmin')),
                Tables\Actions\DeleteAction::make()
                    ->visible(condition: fn() => auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('superadmin'))
                    ->modalHeading(fn($record): string => 'Elimina Competenza'),
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
            'index' => Pages\ListTypeRequests::route('/'),
            'create' => Pages\CreateTypeRequest::route('/create'),
            'edit' => Pages\EditTypeRequest::route('/{record}/edit'),
        ];
    }

    public static function getBreadcrumb(): string
    {
        return 'Competenze';
    }
}
