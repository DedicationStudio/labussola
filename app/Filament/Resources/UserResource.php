<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\Role;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Filament\Notifications\Notification;
use Filament\Forms\Components\Actions\Action;



class UserResource extends Resource
{
    protected static ?string $model = User::class;

    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Team';

    protected static ?string $navigationLabel = 'Utenti';

    protected static ?int $navigationSort = 14;


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('role_id')
                    ->label('Ruolo')
                    ->relationship('role', 'nome')
                    ->disabled(fn() => !in_array(auth()->user()?->role?->nome, ['admin', 'superadmin']))
                    ->live()
                    ->required(),
                Forms\Components\TextInput::make('nome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('cognome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignorable: fn($record) => $record)
                    ->maxLength(255),
                Forms\Components\TextInput::make('telefono')
                    ->tel()
                    ->maxLength(255)
                    ->default(null),
                Forms\Components\TextInput::make('password')
                    ->label('Nuova Password')
                    ->password()
                    ->revealable()
                    ->nullable()
                    ->hidden(fn($record): bool => $record?->role?->nome !== 'agente')
                    ->dehydrateStateUsing(fn($state) => filled($state) ? Hash::make($state) : null)
                    ->dehydrated(fn($state) => filled($state))
                    ->visible(fn($record) => $record && $record->id === Auth::id()),

                Forms\Components\Select::make('tipo_richieste')
                    ->label('Competenze Richieste')
                    ->multiple()
                    ->relationship('tipo_richieste', 'nome')
                    ->preload()
                    ->searchable()
                    ->required()
                    //->disabled(fn() => auth()->user()?->role?->nome === 'agente')
                    ->dehydrated(false)
                    ->visible(fn($get): bool => optional(Role::find($get('role_id')))->nome === 'agente')
                    ->createOptionForm([
                        Forms\Components\TextInput::make('nome')
                            ->label('Tipo Richiesta')
                            ->required()
                            ->unique('type_requests', 'nome'),
                    ])
                    ->editOptionForm([
                        Forms\Components\TextInput::make('nome')
                            ->label('Tipo Richiesta')
                            ->required()
                            ->unique('type_requests', 'nome'),
                    ]),
                Forms\Components\TextInput::make('n_preventivi_gestibili')
                    ->label('N. Preventivi Gestibili')
                    ->numeric()
                    ->default(1)                 // sicuro in create
                    ->readOnly()                 // leggibile ma deidratato => si salva col form
                    ->dehydrated(true)
                    ->live()
                    ->required(fn($get): bool => optional(Role::find($get('role_id')))->nome === 'agente')
                    // mostra il campo quando il record in editing è un agente
                    ->visible(fn($get) => optional(Role::find($get('role_id')))->nome === 'agente')

                    // ── Tasto "−" (solo admin/superadmin su record agente)
                    ->prefixAction(
                        \Filament\Forms\Components\Actions\Action::make('decrementa')
                            ->icon('heroicon-o-minus')
                            ->tooltip('Diminuisci')
                            ->color('primary')
                            ->visible(function ($get) {
                                $loggedRole = auth()->user()?->role?->nome;
                                $targetRole = optional(\App\Models\Role::find($get('role_id')))->nome;
                                return in_array($loggedRole, ['admin', 'superadmin']) && $targetRole === 'agente';
                            })
                            ->disabled(fn($get) => (int) ($get('n_preventivi_gestibili') ?? 0) <= 0)
                            ->action(function ($get, $component) {
                                $current = (int) ($get('n_preventivi_gestibili') ?? 0);
                                if ($current > 0) {
                                    // aggiorna SOLO questo campo nello stato del form
                                    $component->state($current - 1);
                                }
                            })
                    )

                    // ── Tasto "+"
                    ->suffixAction(
                        \Filament\Forms\Components\Actions\Action::make('incrementa')
                            ->icon('heroicon-o-plus')
                            ->tooltip('Aumenta')
                            ->color('primary')
                            ->visible(fn() => in_array(auth()->user()?->role?->nome, ['admin', 'superadmin', 'agente']))
                            ->action(function ($get, $component) {
                                $current = (int) ($get('n_preventivi_gestibili') ?? 1);
                                //  aggiorna SOLO questo campo nello stato del form
                                $component->state($current + 1);
                            })
                    )


            ])
        ;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                $userId = Auth::id();

                return $query->orderByRaw("CASE WHEN id = ? THEN 0 ELSE 1 END", [$userId])
                    ->orderBy('nome');
            })
            ->recordUrl(url: fn($record) => UserResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome')
                    ->searchable()
                    ->formatStateUsing(function ($record) {
                        return $record->cognome
                            ? "{$record->nome} {$record->cognome}"
                            : $record->nome;
                    }),
                Tables\Columns\TextColumn::make('disponibilita')
                    ->label('Disponibilità')
                    ->getStateUsing(function ($record) {
                        return $record->n_preventivi_gestibili > 0 ? 'Disponibile' : 'Non disponibile';
                    })
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Disponibile' => 'success',
                        'Non disponibile' => 'danger',
                    }),
                Tables\Columns\TextColumn::make('n_preventivi_gestibili')
                    ->label('N° Preventivi Gestibili')
                    ->badge(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                /*                 Tables\Columns\TextColumn::make('email_verified_at')
                    ->dateTime()
                    ->sortable(), */
                Tables\Columns\TextColumn::make('telefono')
                    ->label('Telefono')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role.nome')
                    ->label('Ruolo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('tipo_richieste.nome')
                    ->label('Competenze')
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
            ->persistFiltersInSession()
            ->filters([
                Tables\Filters\SelectFilter::make('disponibilita')
                    ->label('Disponibilità Agenti')
                    ->options([
                        'disponibile' => 'Disponibile',
                        'non_disponibile' => 'Non disponibile',
                    ])
                    ->query(function ($query, $data) {
                        if ($data['value'] === 'disponibile') {
                            return $query->where('n_preventivi_gestibili', '>', 0);
                        }
                        if ($data['value'] === 'non_disponibile') {
                            return $query->where('n_preventivi_gestibili', '<=', 0);
                        }
                    }),
            ])
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make()
                        ->modalHeading(fn($record): string => 'Visualizza Utente'),
                    Tables\Actions\EditAction::make()
                        ->hidden(
                            fn($record): bool =>
                            // Se è agente e non è il suo record → nascondi
                            Auth::user()->role?->nome === 'agente' && $record->id !== Auth::id()
                        ),

                    Tables\Actions\DeleteAction::make()
                        ->modalHeading(fn($record): string => 'Elimina Utente')
                        ->hidden(
                            fn($record): bool =>
                            // Mostra solo se l'utente loggato è admin o superadmin
                            !in_array(Auth::user()->role?->nome, ['admin', 'superadmin'])
                        ),

                ]),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'view' => Pages\ViewUser::route('/{record}'),
        ];
    }

    public static function getBreadcrumb(): string
    {
        return 'Utenti';
    }
}
