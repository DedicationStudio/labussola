<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmailResource\Pages;
use App\Filament\Resources\EmailResource\RelationManagers;
use App\Models\Email;
use App\Models\EmailTemplate;
use App\Models\Customer;
use App\Models\Preventive;
use App\Models\QuoteRequest;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\MultiplePreventives;
use Filament\Forms\Get;
use Illuminate\Support\Facades\Auth;
use App\PreventiveStatus;
use App\QuoteRequestStatus;




class EmailResource extends Resource
{
    protected static ?string $model = Email::class;

    protected static ?string $navigationGroup = 'Email';

    protected static ?string $navigationLabel = 'Invio Preventivo';

    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('sent_by')
                    ->label('Inviata da')
                    ->disabled()
                    ->visibleOn('edit')
                    ->default(Auth::id())
                    ->formatStateUsing(function ($state) {
                        if (!$state) {
                            return null;
                        }
                        $user = User::find($state);
                        return $user ? "{$user->nome} {$user->cognome}" : 'Utente Rimosso';
                    })
                    ->columnSpanFull()
                    ->dehydrated(false),
                Forms\Components\Select::make('tipo_preventivo')
                    ->label('Tipo Preventivo')
                    ->options([
                        'libero' => 'Preventivo Libero',
                        'con_richiesta' => 'Preventivo da Richiesta',
                    ])
                    ->default('libero')
                    ->required()
                    ->live(debounce: 500)
                    ->afterStateUpdated(function ($state, Set $set) {
                        // se è libero, svuoto l’eventuale richiesta collegata
                        if ($state === 'libero') {
                            $set('quote_request_id', null);
                        }
                    })
                    ->columnSpan([
                        'default' => 1,
                        'md' => fn(Get $get): int|string => $get('tipo_preventivo') === 'con_richiesta' ? 'full' : 1,
                    ]),
                Forms\Components\Select::make('quote_request_id')
                    ->relationship('quote_request', 'oggetto', modifyQueryUsing: function ($query) {
                        if (auth()->user()->hasAnyRole(['admin', 'superadmin'])) {
                            return $query->where('stato_richiesta', '!=', QuoteRequestStatus::EVASA);
                        }
                        return $query->where('stato_richiesta', '!=', QuoteRequestStatus::EVASA)
                            ->where(function ($q) {
                                $q->where('created_by', auth()->id())
                                    ->orWhereHas('agenti_gestori', function ($agentiQuery) {
                                        $agentiQuery->where('user_id', auth()->id());
                                    });
                            });
                    })
                    ->preload()
                    ->hidden(fn(Get $get): bool => $get('tipo_preventivo') !== 'con_richiesta')
                    ->live(debounce: 500)
                    ->getSearchResultsUsing(function (string $search) {
                        return QuoteRequest::query()
                            ->where('stato_richiesta', '!=', QuoteRequestStatus::EVASA)
                            ->where(function ($query) {
                                $query->where('created_by', auth()->id())
                                    ->orWhereHas('agenti_gestori', function ($agentiQuery) {
                                        $agentiQuery->where('user_id', auth()->id());
                                    });
                            })->where(function ($query) use ($search) {
                                $query->where('oggetto', 'like', "%{$search}%")
                                    ->orWhere('id', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($supplier) {
                                return [
                                    $supplier->id => trim("{$supplier->nome} {$supplier->cognome}"),
                                ];
                            });
                    })
                    // quando cambia la richiesta, imposto il customer_id associato
                    ->afterStateUpdated(function ($state, Set $set) {
                        $req = QuoteRequest::find($state);
                        if ($req) {
                            $set('customer_id', $req->customer_id);
                            $set('meta_viaggio', $req->meta_viaggio);
                        }
                        if ($req && $req->customer?->email) {
                            $set('email_cliente', $req->customer->email);
                        }
                    })
                    ->columnSpanfull()
                    ->label('Richiesta')
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn(QuoteRequest $record) => "{$record->id} - {$record->oggetto}")
                    ->required(),
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
                    ->getSearchResultsUsing(function (string $search) {
                        return Customer::query()
                            ->where(function ($query) use ($search) {
                                $query->where('nome', 'like', "%{$search}%")
                                    ->orWhere('cognome', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            })
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($customer) {
                                return [
                                    $customer->id => $customer->nome . ' ' . $customer->cognome,
                                ];
                            });
                    })
                    ->getOptionLabelFromRecordUsing(fn(Customer $record) => "{$record->nome} {$record->cognome}")
                    ->createOptionForm([

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
                            ->maxLength(255)
                            ->default(null),
                    ])

                    ->required(),
                Forms\Components\Select::make('preventives')
                    ->relationship('preventives', 'tag', modifyQueryUsing: function (Builder $query) {
                        $user = auth()->user();

                        // Filtra per stato
                        $query->where('stato', '!=', PreventiveStatus::BOZZA);

                        // Se l'utente è agente, mostra solo i suoi preventivi
                        if ($user->hasRole('agente')) {
                            $query->where('created_by', $user->id);
                        }
                        // Admin e Superadmin vedono tutti i preventivi (nessun filtro aggiuntivo)
            
                        return $query;
                    })
                    ->searchable()
                    ->label('Preventivi')
                    ->preload()
                    ->dehydrated(true)
                    ->getSearchResultsUsing(function (string $search) {
                        $currentUserId = auth()->id();

                        return Preventive::query()
                            ->with('creator')
                            ->where('stato', '!=', PreventiveStatus::BOZZA)
                            ->where(function ($query) use ($search) {
                                $query->where('numero', 'like', "%{$search}%")
                                    ->orWhere('tag', 'like', "%{$search}%")
                                    ->orWhereHas('creator', function ($q) use ($search) {
                                        $q->where('nome', 'like', "%{$search}%")
                                            ->orWhere('cognome', 'like', "%{$search}%")
                                            ->orWhereRaw("CONCAT(nome, ' ', cognome) LIKE ?", ["%{$search}%"]);
                                    });
                            })
                            ->orderByRaw('CASE WHEN created_by = ? THEN 0 ELSE 1 END', [$currentUserId])
                            ->orderBy('created_at', 'desc')
                            ->limit(50)
                            ->get()
                            ->mapWithKeys(function ($preventive) {
                                // Gestisci il nome dell'agente con cognome opzionale
                                $agentName = $preventive->creator
                                    ? ' | Creato da: ' . trim("{$preventive->creator->nome} " . ($preventive->creator->cognome ?? ''))
                                    : '';

                                return [
                                    $preventive->id =>
                                        'N° ' . $preventive->numero .
                                        ' - ' . $preventive->tag .
                                        $agentName .
                                        ' (' . $preventive->created_at->format('d/m/Y') . ')',
                                ];
                            });
                    })
                    ->getOptionLabelFromRecordUsing(
                        fn(Preventive $record) =>
                        "{$record->numero} - {$record->tag}" .
                        ($record->creator
                            ? " | Creato da: " . trim("{$record->creator->nome} " . ($record->creator->cognome ?? ''))
                            : " | Creato da: Agente Rimosso"
                        )
                    )
                    ->multiple()
                    ->label('Preventivi'),
                //test

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
                    ->email()
                    ->disabled()/* anche se il campo è disabilitato il suo valore viene comunque “deidratato” (cioè incluso nei dati inviati a Filament quando salvi il record). */
                    ->dehydrated(),
                Forms\Components\Repeater::make('email_cc')
                    ->label('')
                    ->schema([
                        Forms\Components\TextInput::make('email_cc')
                            ->label('Email CC'),
                    ])
                    ->addActionLabel('Aggiungi Email CC')
                    ->columnSpanFull(),


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
                    ->multiple()
                    ->maxSize(3072)
                    ->acceptedFileTypes(['application/pdf'])
                    ->preserveFilenames()
                    ->directory('allegati_email')
                    ->label('Allegati')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                $query->where(function ($q) {
                    // Mostra solo email inviate (is_draft = false)
                    $q->where('is_draft', false)
                        ->orWhereHas('preventives', function ($preventiveQuery) {
                        $preventiveQuery->where('stato', '!=', PreventiveStatus::BOZZA);
                    });
                });
            })
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('sent_by')
                    ->searchable()
                    ->limit(20)
                    ->searchable()
                    ->tooltip(fn($state, $record) => ($record->sentBy ? "{$record->sentBy->nome} {$record->sentBy->cognome}" : 'Agente Rimosso'))
                    ->formatStateUsing(fn($state, $record) => $record->sentBy
                        ? "{$record->sentBy->nome} {$record->sentBy->cognome}"
                        : 'Agente Rimosso')
                    ->badge(fn($record) => !$record->sentBy)
                    ->color(fn($record) => !$record->sentBy ? 'danger' : 'grey')
                    ->label('Inviata da'),
                Tables\Columns\TextColumn::make('customer.nome')
                    ->searchable()
                    ->formatStateUsing(fn($state, $record) => "{$record->customer?->nome} {$record->customer?->cognome}")
                    ->searchable()
                    ->label('Inviata a'),
                Tables\Columns\TextColumn::make('preventives_count')
                    ->label('Preventivi Inviati')
                    ->counts('preventives')
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
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('sendMail')
                        ->label('Invia Mail')
                        ->icon('heroicon-o-paper-airplane') // oppure 'heroicon-o-envelope'
                        ->color('info')
                        ->visible(function ($record) {
                            $user = auth()->user();

                            // Admin e Superadmin possono modificare tutto
                            if ($user->hasAnyRole(['admin', 'superadmin'])) {
                                return true;
                            }

                            // Gli agenti possono modificare solo le proprie richieste
                            if ($user->hasRole('agente') && $record->sent_by === $user->id) {
                                return true;
                            }

                            // Tutti gli altri no
                            return false;
                        })
                        ->requiresConfirmation()
                        ->action(function (Email $record) {
                            $record->load('customer', 'preventives');

                            // Se manca email_cliente, prova a usare la relazione customer
                            $to = $record->email_cliente ?? $record->customer?->email;

                            if (!$to) {
                                Notification::make()
                                    ->title('Errore: nessuna email cliente trovata')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Pulizia CC (evita nulli o stringhe vuote)
                            $cc = collect($record->email_cc ?? [])
                                ->pluck('email_cc')
                                ->filter()
                                ->all();


                            Mail::to($to)
                                ->cc($cc)
                                ->send(new MultiplePreventives($record));

                            Notification::make()
                                ->title('Email inviata con successo!')
                                ->success()
                                ->send();
                        }),
                    Tables\Actions\ViewAction::make()
                        ->modalHeading(fn($record): string => 'Visualizza Richiesta'),
                    Tables\Actions\EditAction::make()
                        ->visible(function ($record) {
                            $user = auth()->user();

                            // Admin e Superadmin possono modificare tutto
                            if ($user->hasAnyRole(['admin', 'superadmin'])) {
                                return true;
                            }

                            // Gli agenti possono modificare solo le proprie richieste
                            if ($user->hasRole('agente') && $record->sent_by === $user->id) {
                                return true;
                            }

                            // Tutti gli altri no
                            return false;
                        }),
                    Tables\Actions\DeleteAction::make()
                        ->visible(
                            fn($record) =>
                            auth()->user()?->hasAnyRole(['admin', 'superadmin']) /* ||
$record->sent_by === auth()->id() */
                        )
                        ->modalHeading(fn($record): string => 'Elimina Email')
                        ->before(function ($record, Tables\Actions\DeleteAction $action) {
                            // Controlla se ci sono preventivi collegati
                            if ($record->preventives()->count() > 0) {
                                Notification::make()
                                    ->warning()
                                    ->title('Impossibile eliminare')
                                    ->body('Questa email ha ' . $record->preventives()->count() . ' preventivo/i collegato/i e non può essere eliminata.')
                                    ->persistent()
                                    ->send();

                                // Blocca l'azione di eliminazione
                                $action->cancel();
                            }
                        }),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(condition: fn() => auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('superadmin'))
                        ->before(function ($records, Tables\Actions\DeleteBulkAction $action) {
                            // Controlla se ci sono record con preventivi collegati
                            $recordsWithPreventives = $records->filter(function ($record) {
                                return $record->preventives()->count() > 0;
                            });

                            if ($recordsWithPreventives->isNotEmpty()) {
                                Notification::make()
                                    ->warning()
                                    ->title('Impossibile eliminare alcune email')
                                    ->body($recordsWithPreventives->count() . ' email non possono essere eliminate perché hanno preventivi collegati.')
                                    ->persistent()
                                    ->send();

                                // Blocca l'azione
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
            'index' => Pages\ListEmails::route('/'),
            'create' => Pages\CreateEmail::route('/create'),
            'edit' => Pages\EditEmail::route('/{record}/edit'),
        ];
    }
}
