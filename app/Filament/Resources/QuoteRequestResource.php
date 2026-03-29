<?php

namespace App\Filament\Resources;

use App\Filament\Resources\QuoteRequestResource\Pages;
use App\Filament\Resources\QuoteRequestResource\RelationManagers;
use App\Filament\Resources\QuoteRequestResource\RelationManagers\AvailabilitiesRelationManager;
use App\Models\RequestWp;
use App\Models\Customer;
use App\Models\QuoteRequest;
use App\Models\TypeRequest;
use App\Models\User;
use App\QuoteRequestStatus;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;
use App\PreventiveStatus;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;

class QuoteRequestResource extends Resource
{
    protected static ?string $model = QuoteRequest::class;

    //protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Richieste';

    protected static ?string $navigationLabel = 'Richieste Interne';

    protected static ?int $navigationSort = 3;



    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Hidden::make('request_wp_id'),

                Forms\Components\TextInput::make('created_by')
                    ->label('Creata da')
                    ->disabled()
                    ->visibleOn('edit')
                    ->afterStateHydrated(function ($component, $state, $record) {
                        if (!$record || !$record->created_by) {
                            $component->state('Agente Rimosso');
                            return;
                        }

                        $user = User::find($record->created_by);
                        $component->state($user ? "{$user->nome} {$user->cognome}" : 'Agente Rimosso');
                    })
                    ->dehydrated(false),

                Forms\Components\TextInput::make('oggetto')
                    ->label('Oggetto')
                    ->maxLength(255)
                    ->required(),
                Forms\Components\DatePicker::make('data_ricezione_richiesta')
                    ->displayFormat('d/m/Y')
                    ->label('Data Ricezione Richiesta')
                    ->required(),


                Forms\Components\Select::make('tipo_richieste')
                    ->label('Tipi di Richiesta')
                    ->options(fn() => TypeRequest::pluck('nome', 'id')->toArray())
                    ->multiple()
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
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
                    ->getOptionLabelFromRecordUsing(fn(Customer $record) => "{$record->nome} {$record->cognome}")
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
                    //->getOptionLabelFromRecordUsing(fn(Customer $record) => "{$record->nome} {$record->cognome}")
                    ->createOptionForm([

                        Forms\Components\Select::make('tipo_cliente')
                            ->label('Tipo Cliente')
                            ->options([
                                'azienda' => 'Azienda',
                                'privato' => 'Privato',
                                'scuola' => 'Scuola',
                            ])
                            ->default(null)
                            ->live()
                            ->required(),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('nome')
                                    ->label('Nome')
                                    ->required(),
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
                                    ->visible(fn($get) => in_array($get('tipo_cliente'), ['azienda', 'scuola']))
                                    ->default(null),
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
                    ->editOptionForm([

                        Forms\Components\Select::make('tipo_cliente')
                            ->label('Tipo Cliente')
                            ->options([
                                'azienda' => 'Azienda',
                                'privato' => 'Privato',
                                'scuola' => 'Scuola',
                            ])
                            ->default(null)
                            ->live()
                            ->required(),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('nome')
                                    ->label('Nome')
                                    ->required(),
                                Forms\Components\TextInput::make('cognome')
                                    ->label('Cognome')
                                    ->nullable()
                                    ->visible(fn($get) => $get('tipo_cliente') === 'privato'),
                            ])
                            ->columns(2),
                        Forms\Components\Group::make()
                            ->schema([
                                Forms\Components\TextInput::make('ragione_sociale')
                                    ->label('Ragione Sociale')
                                    ->visible(fn($get) => in_array($get('tipo_cliente'), ['azienda', 'scuola']))
                                    ->default(null),
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
                Forms\Components\TextInput::make('email_cliente')
                    ->label('Email Cliente')
                    ->email()
                    ->disabled()
                    ->dehydrated(),
                Forms\Components\TextInput::make('meta_viaggio')
                    ->maxLength(255)
                    ->label('Meta Viaggio')
                    ->default(null),
                Forms\Components\Select::make('invia_a')
                    ->relationship('agenti_inviati', 'nome', function (Builder $query, Get $get) {
                        $tipi = $get('tipo_richieste'); // array di ID dal campo JSON
            
                        return $query
                            ->whereHas('role', fn($r) => $r->where('roles.nome', 'agente'))
                            ->where('n_preventivi_gestibili', '>', 0)
                            ->when(
                                filled($tipi),
                                fn($q) =>
                                $q->whereHas(
                                    'tipo_richieste',
                                    fn($tr) =>
                                    $tr->whereIn('type_requests.id', $tipi)
                                )
                            );
                    })
                    ->searchable()
                    ->label('Invia a')
                    ->preload()

                    ->getOptionLabelFromRecordUsing(function (User $record) {
                        $nome = "{$record->nome} {$record->cognome}";
                        $preventivi = $record->n_preventivi_gestibili ?? 'N/D';
                        return "$nome - N° Preventivi: $preventivi";
                    })
                    ->multiple(),
                Forms\Components\Select::make('assegnata_da')
                    ->label('Assegnata da')
                    ->relationship(
                        name: 'user',
                        titleAttribute: 'nome',
                        modifyQueryUsing: fn(Builder $query) => $query->whereHas(
                            'role',
                            fn($q) => $q->whereIn('nome', ['admin', 'superadmin'])
                        )
                    )
                    ->disabled(fn() => !auth()->user()?->hasAnyRole(['admin', 'superadmin']))
                    ->default(Auth::id())
                    ->dehydrated(true)
                    ->visibleOn('edit')
                    ->getOptionLabelFromRecordUsing(function (User $record, Get $get) {
                        $admin = "{$record->nome} {$record->cognome}";

                        return $admin;
                    })
                    ->required(fn() => auth()->user()?->hasAnyRole(['admin', 'superadmin'])),
                Forms\Components\Select::make('gestita_da')
                    ->disabled(fn() => !in_array(auth()->user()?->role?->nome, ['admin', 'superadmin']))
                    ->relationship('agenti_gestori', 'nome', function (Builder $query, Get $get) {
                        $tipo = $get('type_request_id');
                        return $query
                            ->whereHas('role', fn($r) => $r->where('roles.nome', 'agente'))
                            ->when(
                                $tipo,
                                fn($q) =>
                                $q->whereHas(
                                    'tipo_richieste',
                                    fn($tr) =>
                                    $tr->where('type_requests.id', $tipo)   // <-- QUALIFICATO
                                )
                            );
                    })
                    ->searchable()
                    ->label('Gestita da')
                    ->visibleOn('edit')
                    ->preload()
                    ->getOptionLabelFromRecordUsing(function (User $record, Get $get) {
                        $nome = "{$record->nome} {$record->cognome}";
                        $preventivi = $record->n_preventivi_gestibili ?? 'N/D';
                        return "$nome - N° Preventivi: $preventivi";
                    })
                    ->multiple(),

                Forms\Components\DatePicker::make('data_assegnazione')
                    ->disabled(fn() => !in_array(auth()->user()?->role?->nome, ['admin', 'superadmin']))
                    ->displayFormat('d/m/Y')
                    ->visibleOn('edit')
                    ->label('Data Assegnazione Richiesta'),
                Forms\Components\Select::make('stato_richiesta')
                    ->label('Stato')
                    ->options(QuoteRequestStatus::class)
                    ->default(QuoteRequestStatus::INVIATA->value)
                    ->live()
                    ->required(),
                Forms\Components\DateTimePicker::make('scadenza')
                    //->disabled(fn() => !in_array(auth()->user()?->role?->nome, ['admin', 'superadmin']))
                    ->minDate(now())
                    ->displayFormat('d/m/Y')
                    ->required()
                    ->label('Data Scadenza'),
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Placeholder::make('avviso_preventivi')
            ->label('')
            ->content(new \Illuminate\Support\HtmlString(
                '<div style="padding: 12px; background-color: #fef3c7; border-left: 4px solid #f59e0b; border-radius: 6px;">
                    <p style="margin: 0; color: #92400e; font-weight: 500;">
                         Attenzione: Non hai preventivi disponibili da gestire.
                    </p>
                    <p style="margin: 4px 0 0 0; color: #78350f; font-size: 14px;">
                        Devi aumentare il numero di preventivi gestibili nel tuo profilo.
                    </p>
                </div>'
            ))
            ->visible(function () {
                $user = User::find(auth()->id());
                return $user?->role?->nome === 'agente' && ($user->n_preventivi_gestibili ?? 0) <= 0;
            })
            ->columnSpanFull(),
                        Forms\Components\Checkbox::make('sono_gia_disponibile')
                            ->label('Sono disponibile per questa richiesta')
                            ->live()
                            ->visible(function () {
                                $user = User::find(auth()->id()); // Ricarica l'utente dal database
                                return $user?->role?->nome === 'agente' && ($user->n_preventivi_gestibili ?? 0) > 0;
                            }),

                        Forms\Components\Checkbox::make('cliente_gestito_da_me')
                            ->label('Cliente gestito da me')
                            ->live()
                            ->visible(function ($livewire, $record) {
                                $user = User::find(auth()->id()); // Ricarica l'utente dal database
                    
                                // Nascondi se l'agente ha 0 preventivi gestibili
                                if ($user?->role?->nome === 'agente' && ($user->n_preventivi_gestibili ?? 0) <= 0) {
                                    return false;
                                }

                                // Mostra per agenti con preventivi > 0
                                if ($user?->role?->nome === 'agente') {
                                    return true;
                                }

                                // Admin/Superadmin solo in edit e se il campo è true
                                if ($user && in_array($user->role?->nome, ['admin', 'superadmin'])) {
                                    return $record !== null && $record->cliente_gestito_da_me === true;
                                }

                                return false;
                            })
                            ->disabled(function () {
                                $user = User::find(auth()->id());
                                return $user && in_array($user->role?->nome, ['admin', 'superadmin']);
                            }),

                        Forms\Components\DatePicker::make('n_giorni_preventivo')
                            ->label('Numero giorni per il preventivo')
                            ->displayFormat('d/m/Y')
                            ->minDate(now())
                            ->hidden(
                                fn(Get $get) =>
                                !($get('sono_gia_disponibile') || $get('cliente_gestito_da_me'))
                            )
                            ->afterStateHydrated(function (DatePicker $component, $state, $record) {
                                if (!$record)
                                    return;

                                $availability = $record->disponibilita()
                                    ->where('user_id', $record->created_by) // ✅ Cerca per created_by invece di auth()->id()
                                    ->first();

                                if ($availability) {
                                    $component->state($availability->n_giorni_preventivo);
                                }
                            })
                            ->dehydrateStateUsing(fn() => null)
                            ->default(null)
                            ->disabled(fn() => auth()->user()?->hasAnyRole(['admin', 'superadmin'])) // ✅ Admin può solo visualizzare
                            ->dehydrated(false),
                    ])
                    ->columns(1),
                Forms\Components\Textarea::make('messaggio_wp')
                    ->label('Messaggio da sito web')
                    ->visible(
                        fn($livewire) =>
                        filled(optional($livewire->record)->request_wp_id) // edit
                        || filled($livewire->data['request_wp_id'] ?? null) // create
                    )
                    ->disabled()
                    ->dehydrated(true)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('motivazione_archivio')
                    ->label('Motivazione Archiviazione')
                    ->visible(fn(Get $get) => $get('stato_richiesta') == 'archiviata')
                    ->required(fn(Get $get): bool => $get('stato_richiesta') === 'archiviata')
                    ->dehydrated(fn(Get $get) => $get('stato') == false)
                    ->maxLength(255)
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('note')
                    ->autosize()
                    ->columnSpanFull(),
                Forms\Components\FileUpload::make('foto_introduttiva')
                    ->label("Carica il File")
                    ->multiple()
                    ->preserveFilenames()
                    ->directory('preventivi')
                    ->disk('public')
                    ->visibility('public')
                    ->helperText(function ($get) {

                        $files = $get('foto_introduttiva') ?? [];

                        if (empty($files)) {
                            return 'Carica file';
                        }

                        $links = collect($files)->map(fn ($file) =>
                            '<a href="'.route('view.file', $file).'"
                                target="_blank"
                                class="underline text-primary-600">'
                            .basename($file).'</a>'
                        )->implode('<br>');

                        return new HtmlString($links);
                    }),
                    /*Forms\Components\Actions::make([

Action::make('download_file')
    ->label('Scarica file caricato')
    ->icon('heroicon-o-arrow-down-tray')
    ->color('primary')
    ->visible(fn (Get $get) => filled($get('foto_introduttiva')))
    ->action(function (Get $get) {

        $file = $get('foto_introduttiva');

        // Estrai il path reale
        $path = is_array($file)
            ? array_values($file)[0]
            : $file;

        // Redirect HTTP → download reale
        return redirect()->route('download.file', $path);
    }),
]),*/]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordUrl(
                fn($record) =>
                auth()->user()->hasAnyRole(['admin', 'superadmin'])
                ? QuoteRequestResource::getUrl('edit', ['record' => $record->id]) // admin e superadmin -> edit
                : (auth()->user()->hasRole('agente') && $record->created_by === auth()->id()
                    ? QuoteRequestResource::getUrl('edit', ['record' => $record->id]) // agente -> edit solo se proprio
                    : QuoteRequestResource::getUrl('view', ['record' => $record]) // tutti gli altri -> view
                )
            )
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->label('Numero'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label("Data di Creazione")
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_by')
                    ->searchable()
                    ->limit(20)
                    ->getStateUsing(function ($record) {
                        if ($record->creator) {
                            return "{$record->creator->nome} {$record->creator->cognome}";
                        }
                        return 'Agente Rimosso';
                    })
                    ->tooltip(fn($state, $record) => ($record->creator ? "{$record->creator->nome} {$record->creator->cognome}" : 'Agente Rimosso'))
                    ->formatStateUsing(fn($state, $record) => $record->creator
                        ? "{$record->creator->nome} {$record->creator->cognome}"
                        : "Agente Rimosso")
                    ->badge(fn($record) => !$record->creator)
                    ->color(fn($record) => !$record->creator ? 'danger' : 'grey')
                    ->label('Creata da'),
Tables\Columns\TextColumn::make('visualizza')
    ->label('Preventivo')
    ->state(function ($record) {
        $preventivo = $record?->preventives?->last();

        return match (true) {
    $preventivo && $preventivo->stato === PreventiveStatus::BOZZA
        => 'Vis. Bozza',

    $preventivo && $record->stato_richiesta === QuoteRequestStatus::EVASA
        => 'Vis. Preventivo',

    default => '-',
};

        
    })

    ->color(function ($record) {
        $preventivo = $record?->preventives?->last();

        return ($preventivo && ($preventivo->stato === PreventiveStatus::BOZZA || $record->stato_richiesta === QuoteRequestStatus::EVASA))
            ? 'info'
            : null;
    })

    // ⭐ Icona SOLO se è cliccabile
    ->icon(function ($record) {
        $preventivo = $record?->preventives?->last();

        return ($preventivo && ($preventivo->stato === PreventiveStatus::BOZZA || $record->stato_richiesta === QuoteRequestStatus::EVASA))
            ? 'heroicon-o-globe-alt'
            : null;
    })

    // 🔗 Link SOLO se BOZZA
->url(function ($record) {
    $preventivo = $record->preventives()
        ->latest()
        ->first();

    if (! $preventivo) {
        return null;
    }

    $user = auth()->user();

    if ($user && ($user->hasRole('admin') || $user->hasRole('superadmin'))) {
        return route('preventivi.edit', $preventivo->id);
    }

    return route('preventivi.show', $preventivo->id);
})
    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('oggetto')
                    ->searchable()
                    ->label('Oggetto'),
                Tables\Columns\TextColumn::make('tipo_richieste')
                    ->label('Tipi di Richiesta')
                    ->formatStateUsing(function ($state) {
                        if (empty($state))
                            return '';

                        //  Se è stringa JSON ("[4,5]") → decodifica
                        if (is_string($state) && str_starts_with(trim($state), '[')) {
                            $decoded = json_decode($state, true);
                            $state = is_array($decoded) ? $decoded : [$state];
                        }

                        //  Se è stringa CSV ("4,5") → separa
                        elseif (is_string($state) && str_contains($state, ',')) {
                            $state = array_map('intval', explode(',', $state));
                        }

                        //  Se è singolo valore
                        elseif (is_string($state)) {
                            $state = [$state];
                        }

                        //  Ora $state è sempre un array di ID
                        $records = \App\Models\TypeRequest::whereIn('id', $state)->pluck('nome');

                        return $records->join(', ');
                    }),

                Tables\Columns\TextColumn::make('meta_viaggio')
                    ->searchable()
                    ->label('Meta'),
                Tables\Columns\TextColumn::make('customer.nome')
                    ->label('Cliente')
                    ->formatStateUsing(fn($state, $record) => "{$record->customer?->nome} {$record->customer?->cognome}")
                    ->tooltip(fn($state, $record) => "{$record->customer?->nome} {$record->customer?->cognome}")
                    ->searchable(),
                Tables\Columns\TextColumn::make('agenti_inviati')
                    ->label('Inviata a')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->agenti_inviati
                            ->map(fn($user) => "{$user->nome} {$user->cognome}")
                            ->join(', ');
                    }),
                Tables\Columns\TextColumn::make('agenti_gestori')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->agenti_gestori
                            ->map(fn($user) => "{$user->nome} {$user->cognome}")
                            ->join(', ');
                    })
                    ->label('Gestita da'),
                Tables\Columns\TextColumn::make('stato_richiesta')
                    ->label('Stato Richiesta')
                    ->badge(),
                Tables\Columns\TextColumn::make('stato_preventivo')
                    ->label('Stato Preventivo')
                    ->getStateUsing(function ($record) {
                        $preventivo = $record->preventives->last();
                        if (!$preventivo?->stato) {
                            return '';
                        }

                        return match ($preventivo->stato) {
                            PreventiveStatus::RIFIUTATO => 'Rifiutato',
                            PreventiveStatus::IN_ATTESA => 'In attesa',
                            PreventiveStatus::BOZZA => 'bozza',
                            PreventiveStatus::ACCETTATO => 'Accettato',
                            PreventiveStatus::INTERESSE_PIU_TEMPO => "L'offerta è di interesse, ma ho bisogno di più tempo",
                            PreventiveStatus::SUPERIORE_BUDGET => "L'offerta risulta superiore al budget previsto",
                            PreventiveStatus::OLTRE_TEMPI => "L'offerta è pervenuta oltre i tempi necessari alla valutazione",
                            PreventiveStatus::NON_INTERESSA => "Il programma proposto non incontra i miei interessi",
                            PreventiveStatus::DA_RIVEDERE => "Vorrei rivedere la proposta insieme a voi",
                            PreventiveStatus::ALTRO => $preventivo->stato_altro_testo ?? 'Altro',
                        };
                    })
                    ->badge()
                    ->color(fn($record) => $record->preventives->last()?->stato?->getColor() ?? 'secondary')
                    ->tooltip(function ($record) {
                        $preventivo = $record->preventives->last();
                        if (!$preventivo?->stato) {
                            return '';
                        }

                        return match ($preventivo->stato) {
                            PreventiveStatus::RIFIUTATO => 'Rifiutato',
                            PreventiveStatus::IN_ATTESA => 'In attesa',
                            PreventiveStatus::BOZZA => 'bozza',
                            PreventiveStatus::ACCETTATO => 'Accettato',
                            PreventiveStatus::INTERESSE_PIU_TEMPO => "L'offerta è di interesse, ma ho bisogno di più tempo",
                            PreventiveStatus::SUPERIORE_BUDGET => "L'offerta risulta superiore al budget previsto",
                            PreventiveStatus::OLTRE_TEMPI => "L'offerta è pervenuta oltre i tempi necessari alla valutazione",
                            PreventiveStatus::NON_INTERESSA => "Il programma proposto non incontra i miei interessi",
                            PreventiveStatus::DA_RIVEDERE => "Vorrei rivedere la proposta insieme a voi",
                            PreventiveStatus::ALTRO => $preventivo->stato_altro_testo ?? 'Altro',
                        };
                    }),

                
            ])
            ->persistFiltersInSession()
            ->filters([

                Tables\Filters\Filter::make('data_ricezione_richiesta')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Da'),
                        Forms\Components\DatePicker::make('until')
                            ->label('A'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn(Builder $q, $date) => $q->whereDate('data_ricezione_richiesta', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $q, $date) => $q->whereDate('data_ricezione_richiesta', '<=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('ultimo_mese')
                    ->label('Ultimo mese')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('data_ricezione_richiesta', '>=', now()->subMonth())
                    ),
                Tables\Filters\SelectFilter::make('tipo_richieste')
                    ->label('Tipo Richiesta')
                    ->options(TypeRequest::pluck('nome', 'id'))
                    ->query(function (Builder $query, array $data) {
                        if (!filled($data['value'])) {
                            return $query;
                        }
                        return $query->whereJsonContains('tipo_richieste', (int) $data['value']);
                    }),

                Tables\Filters\SelectFilter::make('gestita_da')
                    ->label('Agente Gestore')
                    ->relationship('agenti_gestori', 'nome', fn(Builder $query) => $query->whereHas(
                        'role',
                        fn($q) => $q->whereNotIn('nome', ['admin', 'superadmin'])
                    ))
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('customer_nome')
                    ->form([
                        Forms\Components\TextInput::make('q')
                            ->label('Cliente (nome/cognome)'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $term = trim($data['q'] ?? '');
                        return $query->when($term !== '', function (Builder $q) use ($term) {
                            $q->whereHas('customer', function (Builder $c) use ($term) {
                                $c->where(function (Builder $w) use ($term) {
                                    $w->where('nome', 'like', "%{$term}%")
                                        ->orWhere('cognome', 'like', "%{$term}%");
                                });
                            });
                        });
                    }),
                Tables\Filters\Filter::make('email_cliente')
                    ->form([
                        Forms\Components\TextInput::make('q')
                            ->label('Email Cliente'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $term = trim($data['q'] ?? '');
                        return $query->when($term !== '', function (Builder $q) use ($term) {
                            $q->whereHas('customer', function (Builder $c) use ($term) {
                                $c->where(function (Builder $w) use ($term) {
                                    $w->where('email', 'like', "%{$term}%");
                                });
                            });
                        });
                    }),
                Tables\Filters\Filter::make('meta_viaggio')
                    ->form([
                        Forms\Components\TextInput::make('q')
                            ->label('Meta Viaggio'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $term = trim($data['q'] ?? '');
                        return $query->when(
                            $term !== '',
                            fn(Builder $q) =>
                            $q->where('meta_viaggio', 'like', "%{$term}%")
                        );
                    }),

                //  filtro per stato richiesta
                Tables\Filters\SelectFilter::make('stato_richiesta')
                    ->label('Stato Richiesta')
                    ->options(QuoteRequestStatus::class)
                    ->multiple(),


            ])
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    /*Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->label('Vis. Bozza Preventivo')
                        ->icon('heroicon-o-globe-alt')
                        ->openUrlInNewTab()
                        ->extraAttributes(['target' => '_blank'])
                        ->visible(function ($record) {
                            $preventivo = $record->preventives->last();
                            return $preventivo?->stato === PreventiveStatus::BOZZA;
                        })
                        ->url(function ($record) {
                            $preventivo = $record->preventives->last();
                            if (! $preventivo) {
                                return null;
                            }

                            // Costruizione del link usando l'ID del preventivo
                            return route('preventivi.show', ['id' => $preventivo->id]);
                        }),*/
                        Tables\Actions\Action::make('duplicate')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->label('Dup. Richieste Interne')
                        ->requiresConfirmation()
                        ->action(function ($record) {

                            return redirect(
                                QuoteRequestResource::getUrl('create', [
                                    'duplicate_from' => $record->id,
                                ])
                            );
                        }),
                    Tables\Actions\ViewAction::make()
                        ->color('info')
                        ->label('Visualizza Preventivo')
                        ->visible(fn($record) => $record->stato_richiesta === QuoteRequestStatus::EVASA)
                        ->openUrlInNewTab()
                        ->icon('heroicon-o-globe-alt')
                        ->extraAttributes(['target' => '_blank'])
                        ->url(function ($record) {
    if (! $record) {
        return null;
    }

    // 1️⃣ PRIORITÀ: preventivo in BOZZA
    $bozza = $record->preventives()
        ->where('stato', PreventiveStatus::BOZZA)
        ->latest()
        ->first();

    if ($bozza) {
        return route('preventivi.show', ['id' => $bozza->id]);
    }

    // 2️⃣ Altrimenti: ultimo preventivo NON bozza
    $preventivo = $record->preventives()
        ->where('stato', '!=', PreventiveStatus::BOZZA)
        ->latest()
        ->first();

    if (! $preventivo) {
        return null;
    }

    // 3️⃣ Se ha allegato → mostra inline
    if ($preventivo->allego_file) {
        return route('preventivo.show.allegato', [
            'cod_alfa' => $preventivo->cod_alfa,
        ]);
    }

    // 4️⃣ Default
    return route('preventivo.show', [
        'cod_alfa' => $preventivo->cod_alfa,
    ]);
})
->openUrlInNewTab(),

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
                            if ($user->hasRole('agente') && $record->created_by === $user->id) {
                                return true;
                            }

                            // Tutti gli altri no
                            return false;
                        }),

                    Tables\Actions\DeleteAction::make()
                        ->visible(
                            fn($record) =>
                            auth()->user()?->hasAnyRole(['admin', 'superadmin']) /* ||
$record->created_by === auth()->id() */
                        )
                        ->modalHeading(fn($record): string => 'Elimina Richiesta'),
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
            AvailabilitiesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuoteRequests::route('/'),
            'create' => Pages\CreateQuoteRequest::route('/create'),
            'edit' => Pages\EditQuoteRequest::route('/{record}/edit'),
            'view' => Pages\ViewQuoteRequest::route('/{record}'),
        ];
    }

    public static function getBreadcrumb(): string
    {
        return 'Richieste Interne';
    }


}
