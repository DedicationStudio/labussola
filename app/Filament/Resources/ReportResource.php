<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\Customer;
use App\Models\Itinerary;
use App\Models\Preventive;
use App\Models\QuoteRequest;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Transport;
use App\Models\TransportCompany;
use App\PreventiveStatus;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Support\Arr;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Filament\Notifications\Notification;
use App\Exports\PreventivesExport;
use Maatwebsite\Excel\Facades\Excel;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Filament\Tables\Filters\SelectFilter;
use Guava\FilamentIconPicker\Forms\IconPicker;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Type;
use App\Filament\Helpers\ValidatedTab;
use App\QuoteRequestStatus;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;






class ReportResource extends Resource
{
    protected static ?string $model = Preventive::class;

    protected static ?string $navigationGroup = 'Preventivi';

    protected static ?string $navigationLabel = 'Report';
    //protected static ?string $pluralModelLabel = 'Report Preventivi';
    //protected static ?string $slug = 'report-preventives';
    protected static ?int $navigationSort = 7;


     public static function form(Form $form): Form
    {

        return $form
            ->schema([
                Forms\Components\Placeholder::make('avviso_bozza')
                    ->label('')
                    ->content('Attenzione: per visualizzare il preventivo aggiornato, è necessario prima cliccare su "Salva come bozza".')
                    ->visibleOn('edit')
                    ->columnSpanFull(),
                Tabs::make('Creazione Preventivo')
                    ->tabs([
                        ValidatedTab::make('Dati Preventivo', [
                            'tipo_preventivo',
                            'titolo',
                            'customer_id',
                            'meta_viaggio',
                            'data_preventivo',
                            'date_expiration',
                            'meta_viaggio',
                            'tag',
                            'numero_persone',
                            'numero_gratuita',
                            'data_inizio_viaggio',
                            'data_fine_viaggio',
                            'foto_introduttiva',
                            'file_preventivo',
                        ])
                            ->schema([


                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('numero')
                                            ->label('Numero Preventivo')
                                            ->disabled()
                                            ->dehydrated(true)
                                            ->visibleOn('edit'),
                                        Forms\Components\TextInput::make('created_by')
                                            ->label('Creato da')
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


                                    ])
                                    ->columns(2),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Select::make('tipo_preventivo')
                                            ->label('Tipo Preventivo')
                                            ->columnSpanFull()
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
                                            }),
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Forms\Components\Toggle::make('gita_giornaliera')
                                                    ->label('Gita Giornaliera')
                                                    ->live()
                                                    ->disabled(fn($record) => $record && $record->exists && $record->hotel_preventives()->count() > 0)
                                                    
                                                    ->afterStateUpdated(function ($state, Set $set) {
                                                        if ($state === true) {
                                                            $set('hotel_preventives', []);
                                                        }
                                                        // Quando viene disattivato (false), non fa nulla
                                                    })
                                                    ->hidden(fn(Get $get): bool => $get('allego_file') === true)
                                                    ->default(false),

                                                Forms\Components\Actions::make([
                                                    Forms\Components\Actions\Action::make('enable_gita_giornaliera')
                                                        ->label('Attiva Gita Giornaliera')
                                                        ->color('success')
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Conferma Gita Giornaliera')
                                                        ->modalDescription('Attivando la gita giornaliera, i campi Hotels/Alloggi verranno eliminati. L\'operazione non è reversibile. Sei sicuro di voler continuare?')
                                                        ->modalSubmitActionLabel('Sì, elimina gli hotel')
                                                        ->modalCancelActionLabel('Annulla')
                                                        ->action(function (Set $set, $livewire) {
                                                            if ($livewire->record && $livewire->record->exists) {
                                                                // Elimina hotel e relative rooms
                                                                foreach ($livewire->record->hotel_preventives as $hotel) {
                                                                    $hotel->rooms_paganti()->delete();
                                                                    $hotel->rooms_gratuite()->delete();
                                                                    $hotel->delete();
                                                                }

                                                                Notification::make()
                                                                    ->title('Tab Hotel Rimosso')
                                                                    ->body('Questa è una gita giornaliera. Ora puoi attivare/disattivare il toggle liberamente.')
                                                                    ->success()
                                                                    ->send();
                                                            }

                                                            $set('gita_giornaliera', true);
                                                            $set('hotel_preventives', []);
                                                        })
                                                        ->visible(fn($record) => $record && $record->exists && $record->hotel_preventives()->count() > 0),
                                                ]),

                                                Forms\Components\Toggle::make('allego_file')
                                                    ->label('Allego File Preventivo')
                                                    ->live()
                                                    ->disabled(fn($record) => $record && $record->exists && (
                                                        $record->hotel_preventives()->count() > 0 ||
                                                        $record->trasporto_andata()->exists() ||
                                                        $record->trasporto_rientro()->exists() ||
                                                        $record->trasporto_intermedio()->exists() ||
                                                        $record->extra_services()->count() > 0
                                                    ))
                                                    
                                                    ->afterStateUpdated(function ($state, Set $set) {
                                                        if ($state === true) {
                                                            $set('hotel_preventives', []);
                                                            $set('extra_services', []);
                                                            $set('itinerario', []);
                                                            $set('itinerary_id', null);
                                                        }
                                                    }),

                                                Forms\Components\Actions::make([
                                                    Forms\Components\Actions\Action::make('enable_allego_file')
                                                        ->label('Attiva Allego File')
                                                        ->color('info')
                                                        ->requiresConfirmation()
                                                        ->modalHeading('Conferma Eliminazione Dati')
                                                        ->modalDescription('Attivando "Allego File", tutti gli hotel, trasporti e servizi extra saranno eliminati. L\'operazione non è reversibile. Sei sicuro di voler continuare?')
                                                        ->modalSubmitActionLabel('Sì, elimina tutto')
                                                        ->modalCancelActionLabel('Annulla')
                                                        ->action(function (Set $set, $livewire) {
                                                            if ($livewire->record && $livewire->record->exists) {
                                                                $record = $livewire->record;

                                                                // Elimina hotel e relative rooms
                                                                foreach ($record->hotel_preventives as $hotel) {
                                                                    $hotel->rooms_paganti()->delete();
                                                                    $hotel->rooms_gratuite()->delete();
                                                                    $hotel->delete();
                                                                }

                                                                // Elimina trasporti dalle tabelle relazionate
                                                                $record->trasporto_andata()->delete();
                                                                $record->trasporto_rientro()->delete();
                                                                $record->trasporto_intermedio()->delete();

                                                                // Elimina extra services
                                                                $record->extra_services()->delete();

                                                                // Azzera i campi trasporti nella tabella preventives
                                                                $record->update([
                                                                    'luogo_di_partenza_andata' => null,
                                                                    'luogo_di_arrivo_andata' => null,
                                                                    'data_ora_partenza_andata' => null,
                                                                    'data_ora_arrivo_andata' => null,
                                                                    'luogo_di_partenza_rientro' => null,
                                                                    'luogo_di_arrivo_rientro' => null,
                                                                    'data_ora_partenza_rientro' => null,
                                                                    'data_ora_arrivo_rientro' => null,
                                                                    'prezzo_per_persona' => null,
                                                                    'prezzo_forzato' => null,
                                                                    'markup' => null,
                                                                    'totale_incasso' => null,
                                                                ]);

                                                                Notification::make()
                                                                    ->title('Dati Rimossi')
                                                                    ->body('Tutti i dati del preventivo sono stati eliminati. Ora puoi attivare/disattivare il toggle liberamente.')
                                                                    ->success()
                                                                    ->send();
                                                            }

                                                            $set('allego_file', true);
                                                            $set('hotel_preventives', []);
                                                            $set('extra_services', []);
                                                            $set('itinerario', []);
                                                            $set('itinerary_id', null);

                                                            // Azzera anche nel form i trasporti
                                                            $set('trasporto_andata', null);
                                                            $set('trasporto_rientro', null);
                                                            $set('trasporto_intermedio', []);

                                                            // Azzera i campi diretti nel form
                                                            $set('luogo_di_partenza_andata', null);
                                                            $set('luogo_di_arrivo_andata', null);
                                                            $set('data_ora_partenza_andata', null);
                                                            $set('data_ora_arrivo_andata', null);
                                                            $set('luogo_di_partenza_rientro', null);
                                                            $set('luogo_di_arrivo_rientro', null);
                                                            $set('data_ora_partenza_rientro', null);
                                                            $set('data_ora_arrivo_rientro', null);
                                                            $set('prezzo_per_persona', null);
                                                            $set('prezzo_forzato', null);
                                                            $set('markup', null);
                                                            $set('totale_incasso', null);
                                                        })
                                                        ->visible(fn($record) => $record && $record->exists && (
                                                            $record->hotel_preventives()->count() > 0 ||
                                                            $record->trasporto_andata()->exists() ||
                                                            $record->trasporto_rientro()->exists() ||
                                                            $record->trasporto_intermedio()->exists() ||
                                                            $record->extra_services()->count() > 0
                                                        )),
                                                ]),

                                            ])
                                            ->columns(2)
                                            ->columnSpanFull(),



                                        Forms\Components\Hidden::make('anno')
                                            ->label('Anno')
                                            ->default(Carbon::now()->format('Y'))
                                            ->dehydrated()
                                            ->required(),
                                        Forms\Components\DatePicker::make('data_preventivo')
                                            ->displayFormat('d/m/Y')
                                            ->default(Carbon::now())
                                            ->disabled()
                                            ->dehydrated(true)
                                            ->label('Data Preventivo')
                                            ->required(),

                                        Forms\Components\DatePicker::make('date_expiration')
                                            ->displayFormat('d/m/Y')
                                            ->label('Data Validità Preventivo')
                                            ->required(),
                                    ])
                                    ->columns(2),
                                Forms\Components\TextInput::make('titolo')
                                    ->label('Titolo')
                                    ->live()
                                    ->dehydrated(fn($state) => $state != null)
                                    ->maxLength(255)
                                    ->columnSpanFull()
                                    ->required(),
                                Forms\Components\Group::make()
                                    ->schema([

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
                                        Forms\Components\TextInput::make('meta_viaggio')
                                            ->label('Meta')
                                            ->maxLength(255)
                                            ->required(),
                                        Forms\Components\TextInput::make('tag')
                                            ->label('Tag')
                                            ->helperText('Identificativo ad uso interno (non verrà mostrato nel preventivo)')
                                            ->maxLength(255)
                                            ->required(),

                                    ])
                                    ->columns(2),
                                Forms\Components\Group::make()
                                    ->schema([


                                        Forms\Components\Select::make('customer_id')
                                            ->relationship('customer', 'nome', )
                                            ->preload()
                                            ->label('Cliente')
                                            ->searchable()
                                            ->columnSpanfull()
                                            ->required()
                                            ->getOptionLabelFromRecordUsing(
                                                fn(Customer $record) =>
                                                "{$record->nome} {$record->cognome}" . ($record->ragione_sociale ? " ({$record->ragione_sociale})" : '')
                                            )
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                $customer = Customer::find($state);
                                                if ($customer) {
                                                    $set('email_cliente', $customer->email);
                                                } else {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Attenzione')
                                                        ->body('Nessun cliente valido selezionato.')
                                                        ->warning()
                                                        ->send();
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
                                    ])
                                    ->columns(2),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\TextInput::make('numero_persone')
                                            ->label('Numero Persone')
                                            ->numeric()
                                            ->required()
                                            ->default(1),
                                        Forms\Components\TextInput::make('numero_gratuita')
                                            ->label('Numero Gratuità')
                                            ->numeric()
                                            ->default(1)
                                            ->required(),

                                    ])->columns(2),
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\DatePicker::make('data_inizio_viaggio')
                                            ->label('Data Inizio Viaggio')
                                            ->displayFormat('d/m/Y')
                                            ->live(debounce: 500)
                                            ->required()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                if ($state) {
                                                    $set('trasporto_andata.data_ora_partenza_andata', $state . ' 00:00:00');
                                                    $set('trasporto_andata.data_ora_arrivo_andata', $state . ' 00:00:00');

                                                }
                                            }),
                                        Forms\Components\DatePicker::make('data_fine_viaggio')
                                            ->label('Data Fine Viaggio')
                                            ->displayFormat('d/m/Y')
                                            ->live(debounce: 500)
                                            ->required()
                                            ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                                if ($state) {
                                                    $set('trasporto_rientro.data_ora_partenza_rientro', $state . ' 00:00:00');
                                                    $set('trasporto_rientro.data_ora_arrivo_rientro', $state . ' 00:00:00');


                                                }
                                            }),
                                    ])->columns(2),

                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\FileUpload::make('foto_introduttiva')
                                            ->image()
                                            ->hidden(condition: fn(Get $get): bool => $get('allego_file') === true)
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                            ->required(true)
                                            ->preserveFilenames()
                                            ->minSize(200)
                                            ->maxSize(1024)
                                            ->helperText('Carica un\'immagine (.png, .jpg o .jpeg) di almeno 200 KB per garantire una buona qualità nel preventivo')
                                            ->directory('preventivi')
                                            ->disk('public')
                                            ->visibility('public')
                                            ->label('Foto Introduttiva')
                                            ->columnSpanFull(),





                                    ])->columns(2),
                                    Forms\Components\Select::make('stato')
                                    ->label('Stato Preventivo')
                                    ->options(PreventiveStatus::class)
                                    ->columnSpanFull()
                                    ->visible(fn(Get $get): bool => $get('allego_file') === true)
                                    ->default(PreventiveStatus::BOZZA->value)
                                    ->required(),

                                Forms\Components\Fieldset::make('Allegati')
                                    ->visible(fn($get, $context) => $get('allego_file') === true || $context === 'edit')

                                    ->schema([
                                        Forms\Components\FileUpload::make('file_preventivo')
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->preserveFilenames()
                                            ->visible(fn($get) => $get('allego_file') === true)
                                            ->required(fn($get) => $get('allego_file') === true)
                                            ->maxSize(3072)
                                            ->directory('files_preventivo')
                                            ->label('File Preventivo')
                                            ->columnSpanFull(),
                                        Forms\Components\FileUpload::make('files_pratica_accettata')
                                            ->multiple()
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->preserveFilenames()
                                            ->maxSize(3072)
                                            ->visibleOn('edit')
                                            ->directory('preventivi')
                                            ->label('Files Pratica Accettata')
                                            ->columnSpanFull(),
                                    ])->columns(2),

                            ]),
                        ValidatedTab::make('Itinerario', [
                            'itinerary_id',
                            'nome_itinerario',
                            'itinerario',
                        ])
                            ->hidden(condition: fn(Get $get): bool => $get('allego_file') === true)

                            ->schema([
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Select::make('itinerary_id')
                                            ->label('Tipo Itinerario')
                                            ->relationship('itinerary', 'nome')
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            ->live(debounce: 500)
                                            ->createOptionForm([
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
                                                            ->dehydrated(true) // fondamentale: invia i file anche se il repeater è annidato
                                                            ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per l\'itinerario. Minimo 50KB.')
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->addActionLabel('Aggiungi itinerario')
                                                    ->columnSpanFull(),
                                            ])
                                            ->editOptionAction(
                                                fn(Action $action) => $action->after(function (Set $set, Get $get) {
                                                    $itineraryId = $get('itinerary_id');

                                                    if ($itineraryId) {
                                                        $it = Itinerary::find($itineraryId);
                                                        $set('nome_itinerario', $it->nome);
                                                        $set('itinerario', $it->itinerario ?? []);
                                                    }
                                                })
                                            )
                                            ->editOptionForm([
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
                                                            ->minSize(50)
                                                            ->maxSize(1024)
                                                            ->multiple()
                                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                            ->required()
                                                            ->minFiles(3)
                                                            ->maxFiles(3)
                                                            ->preserveFilenames()
                                                            ->disk('public')
                                                            ->visibility('public')
                                                            ->directory('preventivi')
                                                            ->dehydrated(true) //  fondamentale: invia i file anche se il repeater è annidato
                                                            ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per l\'itinerario. Minimo 50KB.')
                                                            ->columnSpanFull(),
                                                    ])
                                                    ->addActionLabel('Aggiungi itinerario')
                                                    ->columnSpanFull(),
                                            ])
                                            ->afterStateUpdated(function ($state, Set $set) {
                                                $it = $state ? Itinerary::find(id: $state) : null;

                                                $set('nome_itinerario', $it?->nome);
                                                $set('itinerario', $it?->itinerario ?? []);
                                            }),
                                    ]),

                                Forms\Components\TextInput::make('nome_itinerario')
                                    ->label('Nome'),

                                Forms\Components\Repeater::make('itinerario')
                                    ->label('Programma di Viaggio')
                                    ->addActionLabel('Aggiungi itinerario')
                                    ->schema([
                                        Forms\Components\TextInput::make('titolo')
                                            ->label('Titolo'),
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
                                            ->multiple()
                                            ->minSize(50)
                                            ->maxSize(1024)
                                            ->required()
                                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                            ->minFiles(3)
                                            ->maxFiles(3)
                                            ->preserveFilenames()
                                            ->disk('public')
                                            ->visibility('public')
                                            ->directory('preventivi')
                                            ->dehydrated(true) // fondamentale: invia i file anche se il repeater è annidato
                                            ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per l\'itinerario. Minimo 50KB.')
                                            ->columnSpanFull(),
                                    ])
                                    ->columnSpanFull(),
                            ]),
                        ValidatedTab::make('Hotels/Alloggi', [
                            'hotel_preventives',
                        ])
                            ->hidden(condition: fn(Get $get): bool => $get('gita_giornaliera') === true || $get('allego_file') === true)
                            ->schema([
                                Forms\Components\Placeholder::make('info_persone_forzate')
                                    ->content(function (Get $get) {
                                        $persone = (int) $get('numero_persone');
                                        $forzate = (int) $get('n_persone_forzato');

                                        if ($forzate > 0 && $forzate !== $persone) {
                                            return "Calcolo basato su {$forzate} partecipanti (forzato, anziché {$persone} partecipanti)";
                                        }

                                        return null; // Non mostra nulla se il campo non è impostato
                                    })
                                    ->columnSpanFull()
                                    ->disableLabel(),
                                Forms\Components\Repeater::make('hotel_preventives')
                                    ->label('Hotel collegati al preventivo')


                                    ->relationship('hotel_preventives')
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->hint(function (Get $get) {
                                        $hotelId = $get('hotel_id');
                                        if (!$hotelId)
                                            return null;

                                        $hotel = \App\Models\Hotel::find($hotelId);

                                        if (!$hotel)
                                            return null;

                                        if (empty($hotel->foto) || count($hotel->foto) === 0) {
                                            return new \Illuminate\Support\HtmlString(
                                                '<span class="text-warning-600">⚠️ Nessuna foto presente per questo hotel.</span>'
                                            );
                                        }

                                        return new \Illuminate\Support\HtmlString(
                                            '<span class="text-success-600">✅ Foto disponibili ('
                                            . count($hotel->foto)
                                            . ')</span>'
                                        );
                                    })
                                    ->itemLabel(fn(array $state): ?string => isset($state['hotel_id'])
                                        ? optional(\App\Models\Hotel::find($state['hotel_id']))?->nome
                                        : 'Hotel')
                                    ->schema([
                                        Forms\Components\Select::make('hotel_id')
                                            ->label('Hotel')
                                            ->relationship('hotel', 'nome')  // HotelPreventive::hotel()
                                            ->searchable()
                                            ->preload()
                                            ->required()
                                            // crea Hotel “al volo”
                                            ->createOptionForm([
                                                Forms\Components\Group::make()
                                                    ->schema([
                                                        Forms\Components\Select::make('supplier_id')
                                                            ->relationship('supplier', 'nome')
                                                            ->preload()
                                                            ->label('Fornitore')
                                                            ->searchable()
                                                            ->getSearchResultsUsing(function (string $search) {
                                                                return Supplier::query()
                                                                    ->where(function ($query) use ($search) {
                                                                        $query->where('nome', 'like', "%{$search}%")
                                                                            ->orWhere('cognome', 'like', "%{$search}%");
                                                                    })
                                                                    ->limit(50)
                                                                    ->get()
                                                                    ->mapWithKeys(function ($supplier) {
                                                                        return [
                                                                            $supplier->id => $supplier->nome . ' ' . $supplier->cognome,
                                                                        ];
                                                                    });
                                                            })
                                                            ->getOptionLabelFromRecordUsing(callback: fn(Supplier $record) => "{$record->nome} {$record->cognome}")->createOptionForm([
                                                                    Forms\Components\TextInput::make('nome')
                                                                        ->required()
                                                                        ->maxLength(255),
                                                                    Forms\Components\TextInput::make('cognome')
                                                                        ->required()
                                                                        ->maxLength(255),
                                                                    Forms\Components\TextInput::make('ragione_sociale')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    Forms\Components\TextInput::make('piva_cf')
                                                                        ->label('P.Iva')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    Forms\Components\TextInput::make('codice_fiscale')
                                                                        ->label('Codice Fiscale')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    Forms\Components\TextInput::make('indirizzo')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    Forms\Components\Repeater::make('telefono')
                                                                        ->schema([
                                                                            Forms\Components\TextInput::make('telefono')
                                                                                ->label('Telefono')
                                                                                ->required(),
                                                                        ])
                                                                        ->addActionLabel('Aggiungi Numero di Telefono')
                                                                        ->label('Numeri di Telefono')
                                                                        ->columns(1)
                                                                        ->collapsible()
                                                                        ->defaultItems(1),
                                                                    Forms\Components\Repeater::make('email')
                                                                        ->schema([
                                                                            Forms\Components\TextInput::make('email')
                                                                                ->label('Email')
                                                                                ->email()
                                                                                ->unique(ignorable: fn($record) => $record)
                                                                                ->required(),
                                                                        ])
                                                                        ->addActionLabel('Aggiungi Email')
                                                                        ->label('Emails')
                                                                        ->columns(1)
                                                                        ->collapsible()
                                                                        ->defaultItems(1),
                                                                    Forms\Components\Repeater::make('sito_web')
                                                                        ->schema([
                                                                            Forms\Components\TextInput::make('sito_web')
                                                                                ->label('Sito Web')
                                                                                ->required(),
                                                                        ])
                                                                        ->addActionLabel('Aggiungi Sito Web')
                                                                        ->label('Siti Web')
                                                                        ->columns(1)
                                                                        ->collapsible()
                                                                        ->defaultItems(1),
                                                                    Forms\Components\Repeater::make('portale_web')
                                                                        ->addActionLabel('Aggiungi Portale Web')
                                                                        ->schema([
                                                                            Forms\Components\RichEditor::make('portale_web')
                                                                                ->label('Portale Web')
                                                                                ->default("<h3>Credenziali</h3><p>Utente: <br>Password:</p>")
                                                                                ->toolbarButtons([
                                                                                    'bold',
                                                                                    'bulletList',
                                                                                    'italic',
                                                                                    'orderedList',
                                                                                    'redo',
                                                                                    'link',
                                                                                    'underline',
                                                                                    'undo',
                                                                                ])
                                                                                ->required(),
                                                                        ])
                                                                        ->label('Portali Web')
                                                                        ->columns(1)
                                                                        ->collapsible()
                                                                        ->defaultItems(1),
                                                                    Forms\Components\TextInput::make('regione')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    Forms\Components\TextInput::make('stato')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    Forms\Components\TextInput::make('citta')
                                                                        ->label('Città')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    Forms\Components\TextInput::make('cap')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    Forms\Components\TextInput::make('provincia')
                                                                        ->maxLength(255)
                                                                        ->default(null),
                                                                    Forms\Components\Select::make('type_supplier')
                                                                        ->label('Tipologia Fornitore')
                                                                        ->multiple()
                                                                        ->relationship('type_supplier', 'tipologia_fornitore')
                                                                        ->preload()
                                                                        ->searchable()

                                                                        // --- CREA NUOVA ---
                                                                        ->createOptionForm([
                                                                            Forms\Components\TextInput::make('tipologia_fornitore')
                                                                                ->label('Nuova Tipologia')
                                                                                ->required(),
                                                                        ])
                                                                        ->createOptionUsing(function (array $data, $livewire): int {
                                                                            $id = Type::create(['tipologia_fornitore' => $data['tipologia_fornitore']])->id;
                                                                            $livewire->dispatch('$refresh');
                                                                            return $id;
                                                                        })

                                                                        // --- MODIFICA TUTTE LE TIPOLOGIE ---
                                                                        ->hintActions([//oppure suffixActions([])
                                                                            Action::make('modifica_tipologie')
                                                                                ->label('Modifica Tipologie')
                                                                                ->icon('heroicon-o-pencil-square')
                                                                                ->modalHeading('Modifica Tipologie Fornitore')
                                                                                ->form([
                                                                                    Forms\Components\Repeater::make('tipologie')
                                                                                        ->schema([
                                                                                            Forms\Components\Hidden::make('id'),
                                                                                            Forms\Components\TextInput::make('tipologia_fornitore')
                                                                                                ->label('Nome Tipologia')
                                                                                                ->required(),
                                                                                        ])
                                                                                        ->columns(1)
                                                                                        ->default(fn() => Type::all(['id', 'tipologia_fornitore'])->toArray())
                                                                                        ->createItemButtonLabel('Aggiungi Tipologia'),
                                                                                ])
                                                                                ->action(function (array $data, $livewire, Forms\Components\Select $component) {
                                                                                    foreach ($data['tipologie'] as $tipo) {
                                                                                        if (!empty($tipo['id'])) {
                                                                                            Type::where('id', $tipo['id'])
                                                                                                ->update(['tipologia_fornitore' => $tipo['tipologia_fornitore']]);
                                                                                        } else {
                                                                                            Type::create(['tipologia_fornitore' => $tipo['tipologia_fornitore']]);
                                                                                        }
                                                                                    }

                                                                                    // Aggiorna le opzioni del campo
                                                                                    $component->options(Type::pluck('tipologia_fornitore', 'id')->toArray());

                                                                                    // Mantiene selezionati gli ID già scelti
                                                                                    $component->state(
                                                                                        collect($component->getState())
                                                                                            ->filter(fn($id) => Type::where('id', $id)->exists())
                                                                                            ->values()
                                                                                            ->toArray()
                                                                                    );

                                                                                    $livewire->dispatch('$refresh');

                                                                                    Notification::make()
                                                                                        ->title('Tipologie fornitore aggiornate')
                                                                                        ->success()
                                                                                        ->send();
                                                                                }),
                                                                        ]),

                                                                    Forms\Components\Select::make('competence_area')
                                                                        ->label('Area Geografica di Competenza')
                                                                        ->multiple()
                                                                        ->relationship('competence_area', 'area')
                                                                        ->preload()
                                                                        ->searchable()
                                                                        ->required()
                                                                        ->createOptionForm([
                                                                            Forms\Components\TextInput::make('area')
                                                                                ->label('Aggiungi Area Geografica di Competenza')
                                                                                ->required(),
                                                                        ])
                                                                        ->editOptionForm([
                                                                            Forms\Components\TextInput::make('area')
                                                                                ->label('Modifica Area Geografica di Competenza')
                                                                                ->required(),
                                                                        ]),
                                                                    Forms\Components\Select::make('reliability_id')
                                                                        ->label('Affidabilità')
                                                                        ->relationship('reliability', 'nome')
                                                                        ->searchable()
                                                                        ->preload()
                                                                        ->required()
                                                                        ->createOptionForm([
                                                                            Forms\Components\Group::make()
                                                                                ->schema([
                                                                                    Forms\Components\TextInput::make('nome')->required(),
                                                                                    Forms\Components\Select::make('colore')
                                                                                        ->label('Colore badge')
                                                                                        ->options([
                                                                                            'gray' => 'Grigio',
                                                                                            'primary' => 'Blu',
                                                                                            'success' => 'Verde',
                                                                                            'warning' => 'Giallo',
                                                                                            'danger' => 'Rosso',
                                                                                            'info' => 'Azzurro',
                                                                                            'purple' => 'Viola',
                                                                                            'pink' => 'Rosa',
                                                                                        ])
                                                                                        ->required(),
                                                                                ])
                                                                                ->columns(2),
                                                                        ])
                                                                        ->editOptionForm([
                                                                            Forms\Components\Group::make()
                                                                                ->schema([
                                                                                    Forms\Components\TextInput::make('nome')->required(),
                                                                                    Forms\Components\Select::make('colore')
                                                                                        ->label('Colore badge')
                                                                                        ->options([
                                                                                            'gray' => 'Grigio',
                                                                                            'primary' => 'Blu',
                                                                                            'success' => 'Verde',
                                                                                            'warning' => 'Giallo',
                                                                                            'danger' => 'Rosso',
                                                                                            'info' => 'Azzurro',
                                                                                            'purple' => 'Viola',
                                                                                            'pink' => 'Rosa',
                                                                                        ])
                                                                                        ->required()
                                                                                ])
                                                                                ->columns(2),
                                                                        ]),
                                                                    Forms\Components\Textarea::make('descrizione')
                                                                        ->columnSpanFull(),
                                                                    Forms\Components\Textarea::make('note')
                                                                        ->label('Note ad uso interno')
                                                                        ->columnSpanFull(),

                                                                ]),
                                                        Forms\Components\TextInput::make('stelle')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->minValue(0)
                                                            ->maxValue(5),
                                                    ])->columns(2),
                                                Forms\Components\Group::make()
                                                    ->schema([
                                                        Forms\Components\TextInput::make('nome')->required(),
                                                        Forms\Components\TextInput::make('indirizzo'),
                                                    ])->columns(2),
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
                                                Forms\Components\FileUpload::make('foto')
                                                    ->image()
                                                    ->preserveFilenames()
                                                    ->multiple()
                                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                    ->minSize(50)
                                                    ->maxSize(1024)
                                                    ->disk('public')
                                                    ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per hotel/alloggio. Minimo 50KB.')
                                                    ->minFiles(3)
                                                    ->maxFiles(3)
                                                    ->visibility('public')
                                                    ->directory('foto_hotel')
                                                    ->columnSpanFull()
                                                    ->label('Foto'),

                                            ])
                                            ->editOptionForm([
                                                Forms\Components\Group::make()
                                                    ->schema([
                                                        Forms\Components\Select::make('supplier_id')
                                                            ->relationship('supplier', 'nome')
                                                            ->preload()
                                                            ->label('Fornitore')
                                                            ->searchable()
                                                            ->getSearchResultsUsing(function (string $search) {
                                                                return Supplier::query()
                                                                    ->where(function ($query) use ($search) {
                                                                        $query->where('nome', 'like', "%{$search}%")
                                                                            ->orWhere('cognome', 'like', "%{$search}%");
                                                                    })
                                                                    ->limit(50)
                                                                    ->get()
                                                                    ->mapWithKeys(function ($supplier) {
                                                                        return [
                                                                            $supplier->id => trim("{$supplier->nome} {$supplier->cognome}"),
                                                                        ];
                                                                    });
                                                            })
                                                            ->getOptionLabelFromRecordUsing(fn(Supplier $record) => trim("{$record->nome} {$record->cognome}"))
                                                            ->createOptionForm([
                                                                Forms\Components\TextInput::make('nome')
                                                                    ->required()
                                                                    ->maxLength(255),
                                                                Forms\Components\TextInput::make('cognome')
                                                                    ->required()
                                                                    ->maxLength(255),
                                                                Forms\Components\TextInput::make('ragione_sociale')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                Forms\Components\TextInput::make('piva_cf')
                                                                    ->label('P.Iva')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                Forms\Components\TextInput::make('codice_fiscale')
                                                                    ->label('Codice Fiscale')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                Forms\Components\TextInput::make('indirizzo')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                Forms\Components\Repeater::make('telefono')
                                                                    ->schema([
                                                                        Forms\Components\TextInput::make('telefono')
                                                                            ->label('Telefono')
                                                                            ->required(),
                                                                    ])
                                                                    ->addActionLabel('Aggiungi Numero di Telefono')
                                                                    ->label('Numeri di Telefono')
                                                                    ->columns(1)
                                                                    ->collapsible()
                                                                    ->defaultItems(1),
                                                                Forms\Components\Repeater::make('email')
                                                                    ->schema([
                                                                        Forms\Components\TextInput::make('email')
                                                                            ->label('Email')
                                                                            ->email()
                                                                            ->unique(ignorable: fn($record) => $record)
                                                                            ->required(),
                                                                    ])
                                                                    ->addActionLabel('Aggiungi Email')
                                                                    ->label('Emails')
                                                                    ->columns(1)
                                                                    ->collapsible()
                                                                    ->defaultItems(1),
                                                                Forms\Components\Repeater::make('sito_web')
                                                                    ->schema([
                                                                        Forms\Components\TextInput::make('sito_web')
                                                                            ->label('Sito Web')
                                                                            ->required(),
                                                                    ])
                                                                    ->addActionLabel('Aggiungi Sito Web')
                                                                    ->label('Siti Web')
                                                                    ->columns(1)
                                                                    ->collapsible()
                                                                    ->defaultItems(1),
                                                                Forms\Components\Repeater::make('portale_web')
                                                                    ->addActionLabel('Aggiungi Portale Web')
                                                                    ->schema([
                                                                        Forms\Components\RichEditor::make('portale_web')
                                                                            ->label('Portale Web')
                                                                            ->default("<h3>Credenziali</h3><p>Utente: <br>Password:</p>")
                                                                            ->toolbarButtons([
                                                                                'bold',
                                                                                'bulletList',
                                                                                'italic',
                                                                                'orderedList',
                                                                                'redo',
                                                                                'link',
                                                                                'underline',
                                                                                'undo',
                                                                            ])
                                                                            ->required(),
                                                                    ])
                                                                    ->label('Portali Web')
                                                                    ->columns(1)
                                                                    ->collapsible()
                                                                    ->defaultItems(1),
                                                                Forms\Components\TextInput::make('regione')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                Forms\Components\TextInput::make('stato')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                Forms\Components\TextInput::make('citta')
                                                                    ->label('Città')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                Forms\Components\TextInput::make('cap')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                Forms\Components\TextInput::make('provincia')
                                                                    ->maxLength(255)
                                                                    ->default(null),
                                                                Forms\Components\Select::make('type_supplier')
                                                                    ->label('Tipologia Fornitore')
                                                                    ->multiple()
                                                                    ->relationship('type_supplier', 'tipologia_fornitore')
                                                                    ->preload()
                                                                    ->searchable()

                                                                    // --- CREA NUOVA ---
                                                                    ->createOptionForm([
                                                                        Forms\Components\TextInput::make('tipologia_fornitore')
                                                                            ->label('Nuova Tipologia')
                                                                            ->required(),
                                                                    ])
                                                                    ->createOptionUsing(function (array $data, $livewire): int {
                                                                        $id = Type::create(['tipologia_fornitore' => $data['tipologia_fornitore']])->id;
                                                                        $livewire->dispatch('$refresh');
                                                                        return $id;
                                                                    })

                                                                    // --- MODIFICA TUTTE LE TIPOLOGIE ---
                                                                    ->hintActions([//oppure suffixActions([])
                                                                        Action::make('modifica_tipologie')
                                                                            ->label('Modifica Tipologie')
                                                                            ->icon('heroicon-o-pencil-square')
                                                                            ->modalHeading('Modifica Tipologie Fornitore')
                                                                            ->form([
                                                                                Forms\Components\Repeater::make('tipologie')
                                                                                    ->schema([
                                                                                        Forms\Components\Hidden::make('id'),
                                                                                        Forms\Components\TextInput::make('tipologia_fornitore')
                                                                                            ->label('Nome Tipologia')
                                                                                            ->required(),
                                                                                    ])
                                                                                    ->columns(1)
                                                                                    ->default(fn() => Type::all(['id', 'tipologia_fornitore'])->toArray())
                                                                                    ->createItemButtonLabel('Aggiungi Tipologia'),
                                                                            ])
                                                                            ->action(function (array $data, $livewire, Forms\Components\Select $component) {
                                                                                foreach ($data['tipologie'] as $tipo) {
                                                                                    if (!empty($tipo['id'])) {
                                                                                        Type::where('id', $tipo['id'])
                                                                                            ->update(['tipologia_fornitore' => $tipo['tipologia_fornitore']]);
                                                                                    } else {
                                                                                        Type::create(['tipologia_fornitore' => $tipo['tipologia_fornitore']]);
                                                                                    }
                                                                                }

                                                                                // Aggiorna le opzioni del campo
                                                                                $component->options(Type::pluck('tipologia_fornitore', 'id')->toArray());

                                                                                // Mantiene selezionati gli ID già scelti
                                                                                $component->state(
                                                                                    collect($component->getState())
                                                                                        ->filter(fn($id) => Type::where('id', $id)->exists())
                                                                                        ->values()
                                                                                        ->toArray()
                                                                                );

                                                                                $livewire->dispatch('$refresh');

                                                                                Notification::make()
                                                                                    ->title('Tipologie fornitore aggiornate')
                                                                                    ->success()
                                                                                    ->send();
                                                                            }),
                                                                    ]),

                                                                Forms\Components\Select::make('competence_area')
                                                                    ->label('Area Geografica di Competenza')
                                                                    ->multiple()
                                                                    ->relationship('competence_area', 'area')
                                                                    ->preload()
                                                                    ->searchable()
                                                                    ->required()
                                                                    ->createOptionForm([
                                                                        Forms\Components\TextInput::make('area')
                                                                            ->label('Aggiungi Area Geografica di Competenza')
                                                                            ->required(),
                                                                    ])
                                                                    ->editOptionForm([
                                                                        Forms\Components\TextInput::make('area')
                                                                            ->label('Modifica Area Geografica di Competenza')
                                                                            ->required(),
                                                                    ]),
                                                                Forms\Components\Select::make('reliability_id')
                                                                    ->label('Affidabilità')
                                                                    ->relationship('reliability', 'nome')
                                                                    ->searchable()
                                                                    ->preload()
                                                                    ->required()
                                                                    ->createOptionForm([
                                                                        Forms\Components\Group::make()
                                                                            ->schema([
                                                                                Forms\Components\TextInput::make('nome')->required(),
                                                                                Forms\Components\Select::make('colore')
                                                                                    ->label('Colore badge')
                                                                                    ->options([
                                                                                        'gray' => 'Grigio',
                                                                                        'primary' => 'Blu',
                                                                                        'success' => 'Verde',
                                                                                        'warning' => 'Giallo',
                                                                                        'danger' => 'Rosso',
                                                                                        'info' => 'Azzurro',
                                                                                        'purple' => 'Viola',
                                                                                        'pink' => 'Rosa',
                                                                                    ])
                                                                                    ->required(),
                                                                            ])
                                                                            ->columns(2),
                                                                    ])
                                                                    ->editOptionForm([
                                                                        Forms\Components\Group::make()
                                                                            ->schema([
                                                                                Forms\Components\TextInput::make('nome')->required(),
                                                                                Forms\Components\Select::make('colore')
                                                                                    ->label('Colore badge')
                                                                                    ->options([
                                                                                        'gray' => 'Grigio',
                                                                                        'primary' => 'Blu',
                                                                                        'success' => 'Verde',
                                                                                        'warning' => 'Giallo',
                                                                                        'danger' => 'Rosso',
                                                                                        'info' => 'Azzurro',
                                                                                        'purple' => 'Viola',
                                                                                        'pink' => 'Rosa',
                                                                                    ])
                                                                                    ->required()
                                                                            ])
                                                                            ->columns(2),
                                                                    ]),
                                                                Forms\Components\Textarea::make('descrizione')
                                                                    ->columnSpanFull(),
                                                                Forms\Components\Textarea::make('note')
                                                                    ->label('Note ad uso interno')
                                                                    ->columnSpanFull(),

                                                            ]),
                                                        Forms\Components\TextInput::make('stelle')
                                                            ->numeric()
                                                            ->default(0)
                                                            ->minValue(0)
                                                            ->maxValue(5),
                                                    ])->columns(2),
                                                Forms\Components\Group::make()
                                                    ->schema([
                                                        Forms\Components\TextInput::make('nome')->required(),
                                                        Forms\Components\TextInput::make('indirizzo'),
                                                    ])->columns(2),
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
                                                Forms\Components\FileUpload::make('foto')
                                                    ->image()
                                                    ->minSize(50)
                                                    ->maxSize(1024)
                                                    ->preserveFilenames()
                                                    ->multiple()
                                                    ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                    ->disk('public')
                                                    ->helperText('Carica esattamente 3 immagini (.png, .jpg o .jpeg) per hotel/alloggio. Minimo 50KB.')
                                                    ->minFiles(3)
                                                    ->maxFiles(3)
                                                    ->visibility('public')
                                                    ->directory('foto_hotel')
                                                    ->columnSpanFull()
                                                    ->label('Foto'),

                                            ]),



                                        // Stanze (tabella: hotel_preventive_rooms)((paganti))
                                        Forms\Components\Repeater::make('rooms_paganti')
                                            ->label('Stanze Paganti')
                                            ->relationship('rooms_paganti')
                                            ->defaultItems(0)
                                            ->dehydrated(false)
                                            ->collapsible()

                                            ->schema([
                                                Forms\Components\Group::make()
                                                    ->schema([
                                                        Forms\Components\Select::make('tipologia_stanza')
                                                            ->label('Tipologia Stanza')
                                                            ->required()
                                                            ->live(debounce: 500)
                                                            ->options([
                                                                'singola' => 'Singola',
                                                                'doppia' => 'Doppia',
                                                                'tripla' => 'Tripla',
                                                                'quadrupla' => 'Quadrupla',
                                                                'stanza da 5' => 'Stanza da 5',
                                                                'stanza da 6' => 'Stanza da 6',
                                                                'stanza da 7' => 'Stanza da 7',
                                                                'stanza da 8' => 'Stanza da 8',
                                                                'stanza da 9' => 'Stanza da 9',
                                                                'stanza da 10' => 'Stanza da 10',
                                                                'multipla' => 'Multipla',
                                                            ]),
                                                        Forms\Components\Select::make('tipo_costo')
                                                            ->label('Tipo Costo')
                                                            ->label('Tipo Costo')
                                                            ->options([
                                                                'a persona' => 'Per persona',
                                                                'a camera' => 'Per camera',
                                                            ])
                                                            ->default('a persona')
                                                            ->required()
                                                            ->live(debounce: 500),
                                                        Forms\Components\TextInput::make('quantita_camere')
                                                            ->label('N° Camere')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->visible(fn(Get $get) => $get('tipo_costo') === 'a camera'),
                                                        Forms\Components\TextInput::make('n_notti')
                                                            ->label('N° Notti')
                                                            ->numeric()
                                                            ->required()
                                                            ->afterStateHydrated(function ($state, Set $set, Get $get, $record, $livewire) {
                                                                // Recupera sempre le date
                                                                $inizio = Carbon::parse($get('../../../../data_inizio_viaggio') ?? $record?->data_inizio_viaggio);
                                                                $fine = Carbon::parse($get('../../../../data_fine_viaggio') ?? $record?->data_fine_viaggio);

                                                                // Se non ci sono date valide, non fare nulla
                                                                if (!$inizio || !$fine) {
                                                                    return;
                                                                }

                                                                // Se il campo ha già un valore, non lo toccare
                                                                if (filled($state)) {
                                                                    return;
                                                                }

                                                                $count_notti = $inizio->diffInDays($fine);
                                                                $set('n_notti', max($count_notti, 1));
                                                            }),

                                                        Forms\Components\TextInput::make('numero_paganti_stanza')
                                                            ->label('N° Paganti')
                                                            ->numeric()
                                                            ->required()
                                                            ->live()
                                                            ->afterStateHydrated(function ($state, Set $set, Get $get, $record, $livewire) {
                                                                // Se è già valorizzato (modifica o reload), non toccarlo
                                                                if (filled($state)) {
                                                                    return;
                                                                }

                                                                // Prendo numero persone normale e forzato
                                                                $numeroPersone = (int) ($get('../../../../numero_persone') ?? $record?->numero_persone ?? 0);
                                                                $numeroForzate = (int) ($get('../../../../n_persone_forzato') ?? $record?->n_persone_forzato ?? 0);

                                                                // Se esiste numero persone forzato > 0, lo uso
                                                                $personeEffettive = $numeroForzate > 0 ? $numeroForzate : $numeroPersone;

                                                                // Tolgo le gratuità
                                                                $numeroGratuitaT = (int) ($get('../../../../numero_gratuita') ?? $record?->numero_gratuita ?? 0);
                                                                $totPaganti = max($personeEffettive - $numeroGratuitaT, 0);


                                                                // Stanze paganti già presenti nel repeater
                                                                $stanze = $get('../../rooms_paganti') ?? [];

                                                                // Somma già assegnata (esclude null / stringhe)
                                                                $sommaPrecedente = collect($stanze)
                                                                    ->pluck('numero_paganti_stanza')
                                                                    ->map(fn($v) => (int) $v)
                                                                    ->sum();

                                                                $rimanenti = max($totPaganti - $sommaPrecedente, 0);

                                                                // Prima stanza → totale, altrimenti rimanenti
                                                                $valore = count($stanze) <= 1 ? $totPaganti : $rimanenti;

                                                                $set('numero_paganti_stanza', $valore);
                                                            }),



                                                        Forms\Components\TextInput::make('costo_notte')
                                                            ->label('Costo / notte')
                                                            ->numeric()->minValue(0)
                                                            ->required()
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                                $data = $get('../../../../');

                                                                [$quota, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($data);

                                                                $set('../../../../prezzo_per_persona', $quota);
                                                                $set('../../../../totale_incasso', $tot);
                                                            })
                                                            ->prefix('€'),

                                                    ])->columns(3),
                                                Forms\Components\Hidden::make('gratuita')
                                                    ->default(false),



                                            ])
                                            ->addActionLabel('Aggiungi stanza'),

                                        //((gratuità))
                                        Forms\Components\Repeater::make('rooms_gratuite')
                                            ->label('Stanze Gratuità')
                                            ->relationship('rooms_gratuite')
                                            ->defaultItems(0)
                                            ->dehydrated(false)
                                            ->collapsible()

                                            ->schema([
                                                Forms\Components\Group::make()
                                                    ->schema([
                                                        Forms\Components\Select::make('tipologia_stanza')
                                                            ->label('Tipologia Stanza')
                                                            ->required()
                                                            ->live(debounce: 500)
                                                            ->options([
                                                                'singola' => 'Singola',
                                                                'doppia' => 'Doppia',
                                                                'tripla' => 'Tripla',
                                                                'quadrupla' => 'Quadrupla',
                                                                'stanza da 5' => 'Stanza da 5',
                                                                'stanza da 6' => 'Stanza da 6',
                                                                'stanza da 7' => 'Stanza da 7',
                                                                'stanza da 8' => 'Stanza da 8',
                                                                'stanza da 9' => 'Stanza da 9',
                                                                'stanza da 10' => 'Stanza da 10',
                                                                'multipla' => 'Multipla',
                                                            ]),
                                                        Forms\Components\Select::make('tipo_costo')
                                                            ->label('Tipo Costo')
                                                            ->label('Tipo Costo')
                                                            ->options([
                                                                'a persona' => 'Per persona',
                                                                'a camera' => 'Per camera',
                                                            ])
                                                            ->default('a persona')
                                                            ->required()
                                                            ->live(debounce: 500),
                                                        Forms\Components\TextInput::make('quantita_camere')
                                                            ->label('N° Camere')
                                                            ->numeric()
                                                            ->minValue(1)
                                                            ->visible(fn(Get $get) => $get('tipo_costo') === 'a camera'),
                                                        Forms\Components\TextInput::make('n_notti')
                                                            ->label('N° Notti')
                                                            ->numeric()
                                                            ->required()
                                                            ->afterStateHydrated(function ($state, Set $set, Get $get, $record, $livewire) {
                                                                // Recupera sempre le date
                                                                $inizio = Carbon::parse($get('../../../../data_inizio_viaggio') ?? $record?->data_inizio_viaggio);
                                                                $fine = Carbon::parse($get('../../../../data_fine_viaggio') ?? $record?->data_fine_viaggio);

                                                                // Se non ci sono date valide, non fare nulla
                                                                if (!$inizio || !$fine) {
                                                                    return;
                                                                }

                                                                // Se il campo ha già un valore, non lo toccare
                                                                if (filled($state)) {
                                                                    return;
                                                                }

                                                                $count_notti = $inizio->diffInDays($fine);
                                                                $set('n_notti', max($count_notti, 1));
                                                            }),
                                                        Forms\Components\TextInput::make('numero_gratuita_stanza')
                                                            ->label('Numero Gratuità')
                                                            ->numeric()
                                                            ->afterStateHydrated(function ($state, Set $set, Get $get, $record, $livewire) {
                                                                // Se è già valorizzato (modifica o reload), non toccarlo
                                                                if (filled($state)) {
                                                                    return;
                                                                }

                                                                // Totale gratuita dalla form state (fallback al record in edit)
                                                                $totGratuita = (int) ($get('../../../../numero_gratuita') ?? $record?->numero_gratuita ?? 0);

                                                                // Stanze gratuite già presenti nel repeater
                                                                $stanze = $get('../../rooms_gratuite') ?? [];

                                                                $sommaPrecedente = collect($stanze)
                                                                    ->pluck('numero_gratuita_stanza')
                                                                    ->map(fn($v) => (int) $v)
                                                                    ->sum();

                                                                $rimanenti = max($totGratuita - $sommaPrecedente, 0);

                                                                $valore = count($stanze) <= 1 ? $totGratuita : $rimanenti;

                                                                $set('numero_gratuita_stanza', $valore);
                                                            })

                                                            ->required(),
                                                        Forms\Components\TextInput::make('costo_notte')
                                                            ->label('Costo / notte')
                                                            ->numeric()
                                                            ->minValue(0)
                                                            ->live(onBlur: true)
                                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                                $data = $get('../../../../');

                                                                [$quota, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($data);

                                                                $set('../../../../prezzo_per_persona', $quota);
                                                                $set('../../../../totale_incasso', $tot);
                                                            })
                                                            ->required(),



                                                        Forms\Components\Hidden::make('gratuita')
                                                            ->default(true),

                                                    ])->columns(3),


                                            ])
                                            ->collapsible()
                                            ->addActionLabel('Aggiungi stanza'),
                                        Forms\Components\RichEditor::make('quota_comprende_hotel')
                                            ->label('La quota comprende')
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
                                        Forms\Components\RichEditor::make('quota_non_comprende_hotel')
                                            ->label('La quota non comprende')
                                            ->toolbarButtons([
                                                'bold',
                                                'bulletList',
                                                'italic',
                                                'orderedList',
                                                'redo',
                                                'underline',
                                                'undo',
                                            ])->columnSpanFull(),

                                        Forms\Components\FileUpload::make('file_fornitore_hotel')
                                            ->disk('public')
                                            ->directory('fornitori_hotel')
                                            ->multiple()
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->maxSize(3072)
                                            ->preserveFilenames()
                                            ->columnSpanFull()
                                            ->label('File Fornitore Hotel'),
                                        Forms\Components\Textarea::make('note')
                                            ->label('Note ad uso interno')
                                            ->columnSpanFull(),




                                    ])
                                    ->addActionLabel('Aggiungi hotel'),
                            ]),
                        ValidatedTab::make('Trasporti', [
                            'trasporto_andata',
                            'trasporto_rientro',
                        ])
                            ->hidden(condition: fn(Get $get): bool => $get('allego_file') === true)

                            ->schema([
                                Tabs::make('Tabs')
                                    ->columnSpanFull()
                                    ->contained(false)->tabs([
                                            Tabs\Tab::make('Andata')
                                                ->schema([

                                                    Forms\Components\Group::make()
                                                        ->relationship('trasporto_andata')
                                                        ->schema([
                                                            Forms\Components\Group::make()
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('luogo_di_partenza_andata')
                                                                        ->label('Luogo di Partenza (Andata)')
                                                                        ->required(),
                                                                    Forms\Components\TextInput::make('luogo_di_arrivo_andata')
                                                                        ->label('Luogo di Arrivo (Andata)')
                                                                        ->required(),
                                                                ])->columns(2),

                                                            Forms\Components\Group::make()
                                                                ->schema([

                                                                    Forms\Components\DateTimePicker::make('data_ora_partenza_andata')
                                                                        ->label('Data/Ora Partenza (Andata)')
                                                                        ->displayFormat('d/m/Y H:i')
                                                                        ->seconds(false)
                                                                        ->required()
                                                                    ,
                                                                    Forms\Components\DateTimePicker::make('data_ora_arrivo_andata')
                                                                        ->label('Data/Ora Arrivo (Andata)')
                                                                        ->displayFormat('d/m/Y H:i')
                                                                        ->seconds(false)
                                                                        ->required()
                                                                    ,

                                                                ])->columns(2),
                                                            Forms\Components\Group::make()
                                                                ->schema([
                                                                    Forms\Components\Select::make('tipo_trasporto')
                                                                        ->label('Tipologia Trasporto')
                                                                        ->native(false)
                                                                        ->options([
                                                                            'bus' => 'Bus',
                                                                            'aereo' => 'Aereo',
                                                                            'treno' => 'Treno',
                                                                            'nave' => 'Nave',
                                                                            'traghetto' => 'Traghetto',
                                                                        ])
                                                                        ->default(null)
                                                                        ->required()
                                                                        ->live()
                                                                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                                            if ($state !== 'aereo') {
                                                                                $set('kg_bg_a_mano', null);
                                                                                $set('kg_bg_in_stiva', null);
                                                                                $set('misura_bg_a_mano', null);
                                                                            }
                                                                            $aziendaId = $get('transport_company_id');
                                                                            $azienda = $aziendaId ? \App\Models\TransportCompany::find($aziendaId) : null;

                                                                            if ($state === 'aereo' && $azienda) {
                                                                                $set('misura_bg_a_mano', $azienda->misura_bg_a_mano);
                                                                            } else {
                                                                                $set('misura_bg_a_mano', null);
                                                                            }
                                                                        }),
                                                                    Forms\Components\Select::make('transport_company_id')
                                                                        ->label('Azienda di Trasporto')
                                                                        ->relationShip('transport_company', 'nome')
                                                                        ->searchable()
                                                                        ->preload()

                                                                        ->live(debounce: 500)
                                                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                                            $azienda = $state ? \App\Models\TransportCompany::find($state) : null;

                                                                            // Controllo il tipo di trasporto
                                                                            if ($get('tipo_trasporto') === 'aereo' && $azienda) {
                                                                                // Imposta il campo del preventivo
                                                                                $set('misura_bg_a_mano', $azienda->misura_bg_a_mano);
                                                                            } else {
                                                                                // Reset nel caso non serva
                                                                                $set('misura_bg_a_mano', null);
                                                                            }
                                                                        })
                                                                        ->getOptionLabelFromRecordUsing(fn(TransportCompany $record) => "{$record->nome}" ?: 'Senza nome')
                                                                        ->createOptionForm([
                                                                            Forms\Components\TextInput::make('nome')
                                                                                ->label('Nome')
                                                                                ->maxLength(255),
                                                                            Forms\Components\FileUpload::make('immagine')
                                                                                ->image()
                                                                                ->maxSize(1024)
                                                                                ->helperText('Carica un\'immagine (.png, .jpg o .jpeg)')
                                                                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                                                ->preserveFilenames()
                                                                                ->label('Immagine'),
                                                                        ]),
                                                                ])
                                                                ->columns(2),
                                                            Forms\Components\Hidden::make('direzione_trasporto')
                                                                ->default('andata'),
                                                            Forms\Components\Group::make()
                                                                ->schema([


                                                                    Forms\Components\Select::make('tipo_costo')->label('Tipologia Costo')->native(false)->options([
                                                                        'una tantum' => 'Una Tantum',
                                                                        'a persona' => 'A Persona',
                                                                    ])
                                                                        ->live(debounce: 500)
                                                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                                                            $data = $get('../');

                                                                            [$quota, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($data);

                                                                            $set('../prezzo_per_persona', $quota);
                                                                            $set('../totale_incasso', $tot);
                                                                        })
                                                                        ->default('a persona'),
                                                                    Forms\Components\TextInput::make('prezzo')
                                                                        ->label('Prezzo')
                                                                        ->numeric()
                                                                        ->required()
                                                                        ->live(onBlur: true)
                                                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                                                            $data = $get('../');

                                                                            [$quota, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($data);

                                                                            $set('../prezzo_per_persona', $quota);
                                                                            $set('../totale_incasso', $tot);
                                                                        })
                                                                        ->prefix('€'),
                                                                ])
                                                                ->columns(2),


                                                            Forms\Components\Toggle::make('scorpora_trasporto')
                                                                ->label('Scorpora')
                                                                ->live(debounce: 500)
                                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                                    $data = $get('../');

                                                                    [$quota, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($data);

                                                                    $set('../prezzo_per_persona', $quota);
                                                                    $set('../totale_incasso', $tot);
                                                                })
                                                                ->inline(false),



                                                            Forms\Components\Group::make()
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('kg_bg_a_mano')
                                                                        ->label('Kg Bagagli a Mano')
                                                                        ->required(fn(Get $get): bool => $get('tipo_trasporto') === 'aereo')
                                                                        ->visible(fn(Get $get) => $get('tipo_trasporto') === 'aereo')
                                                                        ->numeric(),
                                                                    Forms\Components\TextInput::make('kg_bg_in_stiva')
                                                                        ->label('Kg Bagagli in Stiva')
                                                                        ->required(fn(Get $get): bool => $get('tipo_trasporto') === 'aereo')
                                                                        ->visible(fn(Get $get) => $get('tipo_trasporto') === 'aereo')
                                                                        ->numeric(),
                                                                    Forms\Components\TextInput::make('misura_bg_a_mano')
                                                                        ->label('Misura Bagagli a Mano')
                                                                        ->required(fn(Get $get): bool => $get('tipo_trasporto') === 'aereo')
                                                                        ->visible(fn(Get $get) => $get('tipo_trasporto') === 'aereo'),
                                                                ])
                                                                ->columns(2),
                                                            Forms\Components\FileUpload::make('file_fornitore_trasporto')
                                                                ->disk('public')
                                                                ->directory('fornitori_trasporti')
                                                                ->multiple()
                                                                ->acceptedFileTypes(['application/pdf'])
                                                                ->maxSize(3072)
                                                                ->preserveFilenames()
                                                                ->columnSpanFull()

                                                                ->label('File Fornitore Trasporto'),
                                                            Forms\Components\RichEditor::make('quota_comprende_trasporti')
                                                                ->label('La quota comprende')
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
                                                            Forms\Components\RichEditor::make('quota_non_comprende_trasporti')
                                                                ->label('La quota non comprende')
                                                                ->toolbarButtons([
                                                                    'bold',
                                                                    'bulletList',
                                                                    'italic',
                                                                    'orderedList',
                                                                    'redo',
                                                                    'underline',
                                                                    'undo',
                                                                ])->columnSpanFull(),


                                                        ]),
                                                ]),
                                            Tabs\Tab::make('Rientro')
                                                ->schema([
                                                    Forms\Components\Group::make()
                                                        ->relationship('trasporto_rientro')
                                                        ->schema([
                                                            Forms\Components\Group::make()
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('luogo_di_partenza_rientro')
                                                                        ->label('Luogo di Partenza (Rientro)')
                                                                        ->required(),
                                                                    Forms\Components\TextInput::make('luogo_di_arrivo_rientro')
                                                                        ->label('Luogo di Arrivo (Rientro)')
                                                                        ->required(),
                                                                ])->columns(2),

                                                            Forms\Components\Group::make()
                                                                ->schema([
                                                                    Forms\Components\DateTimePicker::make('data_ora_partenza_rientro')
                                                                        ->label('Data/Ora Partenza (Rientro)')
                                                                        ->seconds(false)

                                                                        ->required(),
                                                                    Forms\Components\DateTimePicker::make('data_ora_arrivo_rientro')
                                                                        ->label('Data/Ora Arrivo (Rientro)')
                                                                        ->seconds(false)
                                                                        ->default(function (Get $get, $state, $set, $livewire) {
                                                                            // Se non è già valorizzato, prova a prendere la data dal primo step
                                                                            $fine = $livewire->data['data_fine_viaggio'] ?? null;
                                                                            return $fine ? \Carbon\Carbon::parse($fine) : null;
                                                                        })
                                                                        ->required(),
                                                                ])->columns(2),
                                                            Forms\Components\Group::make()
                                                                ->schema([
                                                                    Forms\Components\Select::make('tipo_trasporto')
                                                                        ->label('Tipologia Trasporto')
                                                                        ->native(false)
                                                                        ->options([
                                                                            'bus' => 'Bus',
                                                                            'aereo' => 'Aereo',
                                                                            'treno' => 'Treno',
                                                                            'nave' => 'Nave',
                                                                            'traghetto' => 'Traghetto',
                                                                        ])
                                                                        ->default(null)
                                                                        ->required()
                                                                        ->live()
                                                                        ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                                            if ($state !== 'aereo') {
                                                                                $set('kg_bg_a_mano', null);
                                                                                $set('kg_bg_in_stiva', null);
                                                                                $set('misura_bg_a_mano', null);
                                                                            }
                                                                            $aziendaId = $get('transport_company_id');
                                                                            $azienda = $aziendaId ? \App\Models\TransportCompany::find($aziendaId) : null;

                                                                            if ($state === 'aereo' && $azienda) {
                                                                                $set('misura_bg_a_mano', $azienda->misura_bg_a_mano);
                                                                            } else {
                                                                                $set('misura_bg_a_mano', null);
                                                                            }
                                                                        }),
                                                                    Forms\Components\Select::make('transport_company_id')
                                                                        ->label('Azienda di Trasporto')
                                                                        ->relationShip('transport_company', 'nome')
                                                                        ->searchable()
                                                                        ->preload()
                                                                        ->live(debounce: 500)
                                                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                                                            $azienda = $state ? \App\Models\TransportCompany::find($state) : null;

                                                                            // Controllo il tipo di trasporto
                                                                            if ($get('tipo_trasporto') === 'aereo' && $azienda) {
                                                                                // Imposta il campo del preventivo
                                                                                $set('misura_bg_a_mano', $azienda->misura_bg_a_mano);
                                                                            } else {
                                                                                // Reset nel caso non serva
                                                                                $set('misura_bg_a_mano', null);
                                                                            }
                                                                        })
                                                                        ->getOptionLabelFromRecordUsing(fn(TransportCompany $record) => "{$record->nome}" ?: 'Senza nome')
                                                                        ->createOptionForm([
                                                                            Forms\Components\TextInput::make('nome')
                                                                                ->label('Nome')
                                                                                ->maxLength(255),
                                                                            Forms\Components\FileUpload::make('immagine')
                                                                                ->image()
                                                                                ->maxSize(1024)
                                                                                ->helperText('Carica un\'immagine (.png, .jpg o .jpeg)')
                                                                                ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg'])
                                                                                ->preserveFilenames()
                                                                                ->label('Immagine'),
                                                                        ]),

                                                                ])
                                                                ->columns(2),
                                                            Forms\Components\Hidden::make('direzione_trasporto')
                                                                ->default('rientro'),
                                                            Forms\Components\Group::make()
                                                                ->schema([



                                                                    Forms\Components\Select::make('tipo_costo')
                                                                        ->label('Tipologia Costo')
                                                                        ->native(false)
                                                                        ->options([
                                                                            'una tantum' => 'Una Tantum',
                                                                            'a persona' => 'A Persona'
                                                                        ])
                                                                        ->live(debounce: 500)
                                                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                                                            $data = $get('../');

                                                                            [$quota, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($data);

                                                                            $set('../prezzo_per_persona', $quota);
                                                                            $set('../totale_incasso', $tot);
                                                                        })
                                                                        ->default('a persona'),
                                                                    Forms\Components\TextInput::make('prezzo')
                                                                        ->label('Prezzo')
                                                                        ->numeric()
                                                                        ->required()
                                                                        ->live(onBlur: true)
                                                                        ->afterStateUpdated(function (Set $set, Get $get) {
                                                                            $data = $get('../');

                                                                            [$quota, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($data);

                                                                            $set('../prezzo_per_persona', $quota);
                                                                            $set('../totale_incasso', $tot);
                                                                        })
                                                                        ->prefix('€'),
                                                                ])
                                                                ->columns(2),


                                                            Forms\Components\Toggle::make('scorpora_trasporto')
                                                                ->label('Scorpora')
                                                                ->live(debounce: 500)
                                                                ->afterStateUpdated(function (Set $set, Get $get) {
                                                                    $data = $get('../');

                                                                    [$quota, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($data);

                                                                    $set('../prezzo_per_persona', $quota);
                                                                    $set('../totale_incasso', $tot);
                                                                })
                                                                ->default(false)
                                                                ->inline(false),



                                                            Forms\Components\Group::make()
                                                                ->schema([
                                                                    Forms\Components\TextInput::make('kg_bg_a_mano')
                                                                        ->label('Kg Bagagli a Mano')
                                                                        ->visible(fn(Get $get) => $get('tipo_trasporto') === 'aereo')
                                                                        ->required(fn(Get $get): bool => $get('tipo_trasporto') === 'aereo')
                                                                        ->numeric(),
                                                                    Forms\Components\TextInput::make('kg_bg_in_stiva')
                                                                        ->label('Kg Bagagli in Stiva')
                                                                        ->visible(fn(Get $get) => $get('tipo_trasporto') === 'aereo')
                                                                        ->required(fn(Get $get): bool => $get('tipo_trasporto') === 'aereo')
                                                                        ->numeric(),
                                                                    Forms\Components\TextInput::make('misura_bg_a_mano')
                                                                        ->label('Misura Bagagli a Mano')
                                                                        ->required(fn(Get $get): bool => $get('tipo_trasporto') === 'aereo')
                                                                        ->visible(fn(Get $get) => $get('tipo_trasporto') === 'aereo'),
                                                                ])
                                                                ->columns(2),
                                                            Forms\Components\FileUpload::make('file_fornitore_trasporto')
                                                                ->disk('public')
                                                                ->directory('fornitori_trasporti')
                                                                ->multiple()
                                                                ->acceptedFileTypes(['application/pdf'])
                                                                ->maxSize(3072)
                                                                ->preserveFilenames()
                                                                ->columnSpanFull()

                                                                ->label('File Fornitore Trasporto'),
                                                            Forms\Components\RichEditor::make('quota_comprende_trasporti')
                                                                ->label('La quota comprende')
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
                                                            Forms\Components\RichEditor::make('quota_non_comprende_trasporti')
                                                                ->label('La quota non comprende')
                                                                ->toolbarButtons([
                                                                    'bold',
                                                                    'bulletList',
                                                                    'italic',
                                                                    'orderedList',
                                                                    'redo',
                                                                    'underline',
                                                                    'undo',
                                                                ])->columnSpanFull(),

                                                        ]),

                                                ]),

                                        ]),
                                Forms\Components\Textarea::make('note')
                                    ->label('Note ad uso interno')
                                    ->columnSpanFull(),
                            ]), //fine trasporti
                        ValidatedTab::make('Servizi Extra', [
                            'extra_services',
                        ])
                            ->hidden(condition: fn(Get $get): bool => $get('allego_file') === true)

                            ->schema([
                                Forms\Components\Repeater::make('extra_services')
                                    ->relationship('extra_services')
                                    ->columns(2)
                                    ->collapsible()
                                    ->collapsed()
                                    ->defaultItems(0)
                                    ->itemLabel(function (array $state): ?string {
                                        // Ottieni il nome della tipologia dall'array delle icone
                                        $tipoServizio = null;

                                        // Se c'è un extra_service_id, mostra anche il nome
                                        if (!empty($state['extra_service_id'])) {
                                            $extraService = \App\Models\ExtraService::find($state['extra_service_id']);

                                            if ($extraService) {

                                                return $tipoServizio = $extraService?->tipo;
                                            }
                                        }
                                        if (!empty($state['tipo'])) {
                                            return $state['tipo'];
                                        }

                                        // Fallback: nessuna label
                                        return 'Servizio Extra';



                                    })
                                    ->extraAttributes([
                                        'x-data' => '{}',
                                        'x-init' => '$el.querySelectorAll(".fi-fo-repeater-item-header").forEach(el => el.style.backgroundColor = "#f0fdf4")'
                                    ])
                                    ->addActionLabel('Aggiungi servizio')
                                    ->label('')
                                    ->mutateRelationshipDataBeforeSaveUsing(function (array $data, ?\Illuminate\Database\Eloquent\Model $record): array|null {
                                        // Se è completamente vuoto, non salvarlo
                                        if (
                                            blank($data['tipo']) &&
                                            blank($data['tipo_costo']) &&
                                            blank($data['prezzo'])
                                        ) {
                                            return null;
                                        }

                                        // Se manca tipo_costo, imposta default
                                        if (blank($data['tipo_costo'])) {
                                            $data['tipo_costo'] = 'a_persona';
                                        }

                                        // Se c'è un servizio associato (selezionato dall'utente), usa quello
                                        if (!empty($data['extra_service_id'])) {
                                            $extra = \App\Models\ExtraService::find($data['extra_service_id']);
                                            if ($extra) {
                                                $data['tipo'] = $extra->tipo;
                                            }
                                        } else if (!blank($data['tipo'])) {
                                            // Cerca un servizio con questo tipo ma SENZA nome (servizio generico)
                                            $service = \App\Models\ExtraService::where('tipo', $data['tipo'])
                                                ->where(function ($q) {
                                                $q->whereNull('nome')
                                                    ->orWhere('nome', '')
                                                    ->orWhere('nome', '');
                                            })
                                                ->first();

                                            // Se non esiste, crealo al volo
                                            if (!$service) {
                                                $service = \App\Models\ExtraService::create([
                                                    'tipo' => $data['tipo'],
                                                    'nome' => null, // o ''
                                                    'supplier_id' => $data['supplier_id'] ?? null,
                                                ]);
                                            }

                                            $data['extra_service_id'] = $service->id;
                                        }

                                        // Non salvare mai il campo 'nome' separatamente
                                        if (isset($data['nome'])) {
                                            unset($data['nome']);
                                        }

                                        return $data;
                                    })

                                    ->schema([
                                        Forms\Components\Select::make('tipo')
                                            ->required()
                                            ->label('Tipologia')
                                            ->searchable()
                                            ->options(getIconsService())
                                            ->dehydrated(true)
                                            ->live()
                                            ->options(function (Get $get) {
                                                $supplierId = $get('supplier_id');

                                                // Se non c'è un fornitore selezionato, mostra tutte le tipologie
                                                if (blank($supplierId)) {
                                                    return getIconsService();
                                                }

                                                // Altrimenti mostra solo le tipologie associate a quel fornitore
                                                $tipologie = \App\Models\ExtraService::where('supplier_id', $supplierId)
                                                    ->distinct()
                                                    ->pluck('tipo')
                                                    ->filter()
                                                    ->toArray();

                                                // Filtra le opzioni di getIconsService() per mostrare solo quelle associate
                                                $allOptions = getIconsService();

                                                return collect($tipologie)
                                                    ->mapWithKeys(function ($tipo) use ($allOptions) {
                                                        return [$tipo => $allOptions[$tipo] ?? ucwords(str_replace('_', ' ', $tipo))];
                                                    })
                                                    ->toArray();
                                            })
                                            ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                if (!empty($state)) {
                                                    $set('extra_service_id', null);
                                                    $supplierId = $get('supplier_id');

                                                    // Cerca servizi generici (senza nome) per questa tipologia
                                                    $query = \App\Models\ExtraService::where('tipo', $state)
                                                        ->where(function ($q) {
                                                        $q->whereNull('nome')
                                                            ->orWhere('nome', '')
                                                            ->orWhere('nome', '');
                                                    })
                                                        ->whereNotNull('descrizione_servizio')
                                                        ->where('descrizione_servizio', '!=', '');

                                                    if (!blank($supplierId)) {
                                                        $query->where('supplier_id', $supplierId);
                                                    }

                                                    $service = $query->first();

                                                    // Setta descrizione
                                                    if ($service && $service->descrizione_servizio) {
                                                        if (blank($get('descrizione_servizio'))) {
                                                            $set('descrizione_servizio', $service->descrizione_servizio);
                                                        }
                                                    }

                                                    // Setta fornitore se non è già selezionato
                                                    if ($service && $service->supplier_id) {
                                                        if (blank($get('supplier_id'))) {
                                                            $set('supplier_id', $service->supplier_id);
                                                        }
                                                    }
                                                } else {
                                                    $set('extra_service_id', null);
                                                    $set('supplier_id', null);
                                                    $set('descrizione_servizio', null);
                                                }
                                            })
                                            ->afterStateHydrated(function ($set, $state, $record) {
                                                if ($record && $record->extra_service) {
                                                    $set('tipo', $record->extra_service->tipo);
                                                }
                                            })
                                            ->createOptionForm([
                                                Forms\Components\Select::make('supplier_id')
                                                    ->label('Fornitore')
                                                    ->searchable()
                                                    ->preload()
                                                    ->options(function () {
                                                        return Supplier::query()
                                                            ->orderBy('nome')
                                                            ->orderBy('cognome')
                                                            ->get()
                                                            ->mapWithKeys(function ($supplier) {
                                                                return [
                                                                    $supplier->id => trim("{$supplier->nome} {$supplier->cognome}"),
                                                                ];
                                                            })
                                                            ->toArray();
                                                    })
                                                    ->getSearchResultsUsing(function (string $search) {
                                                        return Supplier::query()
                                                            ->where(function ($query) use ($search) {
                                                                $query->where('nome', 'like', "%{$search}%")
                                                                    ->orWhere('cognome', 'like', "%{$search}%");
                                                            })
                                                            ->limit(50)
                                                            ->get()
                                                            ->mapWithKeys(function ($supplier) {
                                                                return [
                                                                    $supplier->id => trim("{$supplier->nome} {$supplier->cognome}"),
                                                                ];
                                                            });
                                                    })
                                                    ->getOptionLabelFromRecordUsing(fn(Supplier $record) => trim("{$record->nome} {$record->cognome}"))
                                                    ->createOptionForm([
                                                        Forms\Components\TextInput::make('nome')
                                                            ->required()
                                                            ->maxLength(255),
                                                        Forms\Components\TextInput::make('cognome')
                                                            ->required()
                                                            ->maxLength(255),
                                                        Forms\Components\TextInput::make('ragione_sociale')
                                                            ->maxLength(255),
                                                        Forms\Components\TextInput::make('piva_cf')
                                                            ->label('P.Iva')
                                                            ->maxLength(255),
                                                        Forms\Components\TextInput::make('codice_fiscale')
                                                            ->label('Codice Fiscale')
                                                            ->maxLength(255),
                                                        Forms\Components\TextInput::make('indirizzo')
                                                            ->maxLength(255),
                                                        // ... altri campi del fornitore
                                                    ]),


                                                Forms\Components\Select::make('tipo')
                                                    ->required()
                                                    ->searchable()
                                                    ->label('Tipologia')
                                                    ->options(getIconsOptions()),

                                                Forms\Components\TextInput::make('nome')
                                                    ->label('Nome Servizio')
                                                    ->maxLength(255),
                                                Forms\Components\RichEditor::make('descrizione_servizio')
                                                    ->toolbarButtons([
                                                        'bold',
                                                        'bulletList',
                                                        'italic',
                                                        'orderedList',
                                                        'redo',
                                                        'underline',
                                                        'undo',
                                                    ])
                                                    ->label('Descrizione')
                                                    ->columnSpanFull(),

                                                Forms\Components\FileUpload::make('allegati')
                                                    ->multiple()
                                                    ->maxSize(3072)
                                                    ->acceptedFileTypes(['application/pdf'])
                                                    ->preserveFilenames()
                                                    ->directory('allegati_servizi')
                                                    ->label('Allegati'),
                                            ])
                                            ->createOptionUsing(function (array $data, Get $get): string {

                                                \App\Models\ExtraService::create([
                                                    'supplier_id' => $data['supplier_id'] ?? null,
                                                    'tipo' => $data['tipo'],
                                                    'nome' => $data['nome'] ?? '',
                                                    'descrizione_servizio' => $data['descrizione_servizio'] ?? null,  // ← AGGIUNGI QUESTO
                                                    'allegati' => $data['allegati'] ?? null,
                                                ]);

                                                // Restituisci il TIPO (non l'ID) perché è questo il valore del campo Select
                                                return $data['tipo'];
                                            })
                                            ->createOptionModalHeading('Crea Nuovo Servizio Extra'),


                                        Forms\Components\Select::make('supplier_id')
                                            ->label('Fornitore')
                                            ->searchable()
                                            ->live()
                                            ->options(function (Get $get) {
                                                $tipo = $get('tipo');

                                                // Se non c'è una tipologia selezionata, mostra tutti i fornitori
                                                if (blank($tipo)) {
                                                    return Supplier::query()
                                                        ->orderBy('nome')
                                                        ->orderBy('cognome')
                                                        ->get()
                                                        ->mapWithKeys(function ($supplier) {
                                                            return [
                                                                $supplier->id => trim("{$supplier->nome} {$supplier->cognome}"),
                                                            ];
                                                        })
                                                        ->toArray();
                                                }

                                                // Altrimenti mostra solo i fornitori che hanno servizi di questa tipologia
                                                $supplierIds = \App\Models\ExtraService::where('tipo', $tipo)
                                                    ->whereNotNull('supplier_id')  // ← IMPORTANTE
                                                    ->distinct()
                                                    ->pluck('supplier_id')
                                                    ->filter()
                                                    ->toArray();

                                                if (empty($supplierIds)) {
                                                    // Nessun fornitore ha questa tipologia, mostra LISTA VUOTA
                                                    return []; // ← CAMBIA QUI: ritorna array vuoto invece di tutti i fornitori
                                                }

                                                return Supplier::whereIn('id', $supplierIds)
                                                    ->orderBy('nome')
                                                    ->orderBy('cognome')
                                                    ->get()
                                                    ->mapWithKeys(function ($supplier) {
                                                        return [
                                                            $supplier->id => trim("{$supplier->nome} {$supplier->cognome}"),
                                                        ];
                                                    })
                                                    ->toArray();
                                            })

                                            ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                // Resetta solo l'extra_service_id
                                                $set('extra_service_id', null);

                                                // Se c'è un fornitore, carica dati dal servizio generico
                                                if (!blank($state)) {
                                                    $tipo = $get('tipo');

                                                    if (!blank($tipo)) {
                                                        $service = \App\Models\ExtraService::where('tipo', $tipo)
                                                            ->where('supplier_id', $state)
                                                            ->where(function ($q) {
                                                                $q->whereNull('nome')
                                                                    ->orWhere('nome', '')
                                                                    ->orWhere('nome', '');
                                                            })
                                                            ->first(); // ← RIMOSSI i filtri su descrizione_servizio
                                    
                                                        if ($service) {
                                                            // Setta descrizione se presente
                                                            if ($service->descrizione_servizio) {
                                                                $set('descrizione_servizio', $service->descrizione_servizio);
                                                            }

                                                            // Setta file se presenti
                                                            if ($service->allegati) {
                                                                $set('file_fornitore_servizi_extra', $service->allegati);
                                                            }
                                                        }
                                                    }
                                                }
                                            })
                                            ->afterStateHydrated(function ($set, $state, $record, Get $get) {
                                                // Carica il supplier_id dall'extra_service quando modifichi
                                                if ($record && $record->extra_service && $record->extra_service->supplier_id) {
                                                    $set('supplier_id', $record->extra_service->supplier_id);
                                                }
                                            })
                                            ->dehydrated(false), // NON salvare in preventive_extra_services


                                        Forms\Components\Select::make('extra_service_id')
                                            ->label('Nome')
                                            ->live()
                                            ->options(function (Get $get) {
                                                $tipo = $get('tipo');
                                                $supplierId = $get('supplier_id');

                                                if (blank($tipo)) {
                                                    return [];
                                                }

                                                $query = \App\Models\ExtraService::where('tipo', $tipo)
                                                    ->whereNotNull('nome')
                                                    ->where('nome', '<>', '')
                                                    ->where('nome', '!=', '');

                                                // Filtra anche per fornitore se selezionato
                                                if (!blank($supplierId)) {
                                                    $query->where('supplier_id', $supplierId);
                                                }

                                                return $query->pluck('nome', 'id')->toArray();
                                            })

                                            ->afterStateUpdated(function ($state, callable $set, Get $get) {
                                                // Quando selezioni un nome, carica descrizione E fornitore
                                                if (!empty($state)) {
                                                    $service = \App\Models\ExtraService::find($state);

                                                    if ($service) {
                                                        // Setta descrizione
                                                        if ($service->descrizione_servizio) {
                                                            $set('descrizione_servizio', $service->descrizione_servizio);
                                                        }

                                                        // Setta fornitore se non è già selezionato
                                                        if ($service->supplier_id && blank($get('supplier_id'))) {
                                                            $set('supplier_id', $service->supplier_id);
                                                        }
                                                    }
                                                }
                                            })
                                            ->visible(function (Get $get, $state) {
                                                $tipo = $get('tipo');
                                                if (blank($tipo)) {
                                                    return false;
                                                }

                                                $count = \App\Models\ExtraService::where('tipo', $tipo)
                                                    ->whereNotNull('nome')
                                                    ->where('nome', '<>', '')
                                                    ->where('nome', '!=', '')
                                                    ->count();

                                                if ($count === 0) {
                                                    return false;
                                                }

                                                if ($state) {
                                                    $service = \App\Models\ExtraService::find($state);
                                                    return $service && !blank($service->nome);
                                                }

                                                return true;
                                            })
                                            ->afterStateHydrated(function ($set, $state, $record, Get $get) {
                                                // Se il campo non dovrebbe essere visibile, azzera il valore
                                                $tipo = $get('tipo');

                                                if (blank($tipo)) {
                                                    $set('extra_service_id', null);
                                                    return;
                                                }

                                                $count = \App\Models\ExtraService::where('tipo', $tipo)
                                                    ->whereNotNull('nome')
                                                    ->where('nome', '<>', '')
                                                    ->where('nome', '!=', '')
                                                    ->count();

                                                if ($count === 0) {
                                                    $set('extra_service_id', null);
                                                    return;
                                                }

                                                if ($state) {
                                                    $service = \App\Models\ExtraService::find($state);
                                                    if ($service && blank($service->nome)) {
                                                        $set('extra_service_id', null);
                                                    }
                                                }

                                                if ($record && $record->extra_service) {
                                                    $set('tipo', $record->extra_service->tipo);
                                                }
                                            })
                                            ->dehydrated(true)
                                            ->placeholder('Seleziona un nome')
                                            ->searchable(),
                                        Forms\Components\Select::make('tipo_costo')
                                            ->label('Tipo Costo')
                                            ->options([
                                                'a_persona' => 'A persona',
                                                'una_tantum' => 'Una tantum',
                                            ])
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                $data = $get('../../');

                                                [$quota, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($data);

                                                $set('../../prezzo_per_persona', $quota);
                                                $set('../../totale_incasso', $tot);
                                            })
                                            ->default('a_persona')
                                            ->live() // serve per mostrare/nascondere "quantita"
                                            ->required(),

                                        // Prezzo unitario
                                        Forms\Components\TextInput::make('prezzo')
                                            ->label('Prezzo')
                                            ->prefix('€')
                                            ->numeric()
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(function (Set $set, Get $get) {
                                                $data = $get('../../');

                                                [$quota, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($data);

                                                $set('../../prezzo_per_persona', $quota);
                                                $set('../../totale_incasso', $tot);
                                            })

                                            ->required(),

                                        // Quantità: solo se "a_persona"
                                        Forms\Components\TextInput::make('quantita_a_persona')
                                            ->label('Quantità (per persona)')
                                            ->numeric()
                                            ->minValue(1)
                                            ->visible(fn(Get $get) => $get('tipo_costo') === 'a_persona'),
                                        Forms\Components\TextInput::make('quantita')
                                            ->label('Quantità')
                                            ->numeric()
                                            ->minValue(1)
                                            ->visible(fn(Get $get) => $get('tipo_costo') === 'una_tantum'),
                                        Forms\Components\Group::make()
                                            ->schema([
                                                Forms\Components\Toggle::make('scorpora_servizio')
                                                    ->label('Scorpora')
                                                    ->inline(false)
                                                    ->live(debounce: 500)
                                                    ->afterStateUpdated(function (Set $set, Get $get) {
                                                        $data = $get('../../');

                                                        [$quota, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($data);

                                                        $set('../../prezzo_per_persona', $quota);
                                                        $set('../../totale_incasso', $tot);
                                                    })

                                            ])->columns(2),
                                        // Informazioni aggiuntive
                                        Forms\Components\RichEditor::make('descrizione_servizio')
                                            ->toolbarButtons([
                                                'bold',
                                                'bulletList',
                                                'italic',
                                                'orderedList',
                                                'redo',
                                                'underline',
                                                'undo',
                                            ])
                                            ->label('Descrizione')
                                            ->columnSpanFull(),

                                        // La quota comprende
                                        Forms\Components\RichEditor::make('quota_comprende_servizi')
                                            ->label('La quota comprende')
                                            ->toolbarButtons([
                                                'bold',
                                                'bulletList',
                                                'italic',
                                                'orderedList',
                                                'redo',
                                                'underline',
                                                'undo',
                                            ])
                                            ->columnSpan(2),
                                        Forms\Components\RichEditor::make('quota_non_comprende_servizi')
                                            ->label('La quota non comprende')
                                            ->toolbarButtons([
                                                'bold',
                                                'bulletList',
                                                'italic',
                                                'orderedList',
                                                'redo',
                                                'underline',
                                                'undo',
                                            ])
                                            ->columnSpan(2),
                                        Forms\Components\FileUpload::make('file_fornitore_servizi_extra')
                                            ->disk('public')
                                            ->maxSize(3072)
                                            ->acceptedFileTypes(['application/pdf'])
                                            ->directory('fornitori_servizi_extra')
                                            ->multiple()
                                            ->preserveFilenames()
                                            ->columnSpanFull()

                                            ->label('File Fornitore Servizi Extra'),
                                        Forms\Components\Textarea::make('note')
                                            ->label('Note ad uso interno')
                                            ->columnSpanFull(),


                                    ]),
                            ]),
                        ValidatedTab::make('Riepilogo e Costi', [
                            'prezzo_per_persona',
                            'markup',
                            'prezzo_forzato',
                            'totale_incasso',
                            'stato',
                        ])
                            ->hidden(
                                fn(Get $get, string $operation): bool =>
                                $get('allego_file') === true ||
                                $operation === 'create'
                            )
                            ->schema([
                                Forms\Components\Group::make()
                                    ->schema([
                                        Forms\Components\Group::make()
                                            ->schema([
                                                // --- Campo calcolato ---
                                                Forms\Components\TextInput::make('prezzo_per_persona')
                                                    ->label('Prezzo per Persona')
                                                    ->prefix('€')
                                                    ->numeric()
                                                    ->disabled()
                                                    ->dehydrated(true)
                                                    ->default(function (Get $get) {
                                                        [$quota] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($get());
                                                        return $quota;
                                                    })
                                                    ->afterStateHydrated(function (Set $set, Get $get) {
                                                        [$quota, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($get());
                                                        $set('prezzo_per_persona', $quota);
                                                        $set('totale_incasso', $tot);
                                                    }),

                                                // --- Markup ---
                                                Forms\Components\TextInput::make('markup')
                                                    ->label('Markup (€ per persona)')
                                                    ->numeric()
                                                    ->default(0)
                                                    ->required()
                                                    ->prefix('€')
                                                    ->debounce(500)
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                        $data = $get();
                                                        $data['markup'] = $state;
                                                        [$quota, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($data);
                                                        $set('prezzo_per_persona', $quota);
                                                        $set('totale_incasso', $tot);
                                                    }),

                                                // --- Prezzo Forzato ---
                                                Forms\Components\TextInput::make('prezzo_forzato')
                                                    ->label('Prezzo Forzato')
                                                    ->prefix('€')
                                                    ->numeric()
                                                    ->required(),

                                                Forms\Components\TextInput::make('n_persone_forzato')
                                                    ->label('N° Persone Forzato')
                                                    ->numeric()
                                                    ->nullable()
                                                    ->default(null)
                                                    ->helperText('Dato ad uso interno. Quota individuale calcolata sulla base del numero inserito.')
                                                    ->debounce(500)
                                                    ->live()
                                                    ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                        $data = $get();
                                                        $data['n_persone_forzato'] = $state;
                                                        [$quota, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($data);
                                                        $set('prezzo_per_persona', $quota);
                                                        $set('totale_incasso', $tot);
                                                    }),
                                            ])->columns(4),
                                    ])->columnSpanFull(),

                                Forms\Components\Select::make('stato')
                                    ->label('Stato Preventivo')
                                    ->options(PreventiveStatus::class)
                                    ->default(PreventiveStatus::BOZZA->value)
                                    ->required(),
                                Forms\Components\RichEditor::make('campo_attenzione')
                                    ->label('Campo Attenzione')
                                    ->toolbarButtons(['bold', 'bulletList', 'italic', 'orderedList', 'redo', 'underline', 'undo'])
                                    ->dehydrated(true)
                                    ->columnSpanFull(),


                                // -------- RIEPILOGO COMPLETO (anche in CREATE) --------
                                Forms\Components\View::make('forms.riepilogo-table')
                                    ->viewData(function (Get $get, $livewire) {
                                        $record = $livewire->record ?? null;
                                        $form = $get();

                                        // Helper: prendi dai record se esiste, altrimenti dal form
                                        $pick = function ($path, $default = null) use ($record, $form) {
                                            $v = $record ? data_get($record, $path) : null;
                                            if (!is_null($v) && $v !== '')
                                                return $v;
                                            $v = data_get($form, $path);
                                            return $v ?? $default;
                                        };

                                        $isGitaGiornaliera = (bool) $pick('gita_giornaliera', false);

                                        // Collezioni grezze per create/edit
                                        $hotelPreventives = $isGitaGiornaliera ? [] : $pick('hotel_preventives', []);
                                        $traspAndata = $pick('trasporto_andata', null);
                                        $traspRientro = $pick('trasporto_rientro', null);
                                        $traspIntermedi = $pick('trasporto_intermedio', []);
                                        $extraServices = $pick('extra_services', []);

                                        $forzate = (int) $pick('n_persone_forzato', 0);
                                        $persone = $forzate > 0
                                            ? $forzate
                                            : max((int) $pick('numero_persone', 0), 0);
                                        $markup = (int) $pick('markup', 0);
                                        $gratuite = max((int) $pick('numero_gratuita', 0), 0);
                                        $pagantiBase = max($persone - $gratuite, 1);
                                        $paganti = $pagantiBase;

                                        // -------- HOTEL: dettagli e totale --------
                                        $hotelDettaglio = collect($hotelPreventives)->map(function ($hotel) {
                                            $hotelNome = optional(\App\Models\Hotel::find($hotel['hotel_id'] ?? null))?->nome ?? '-';
                                            $rooms = collect($hotel['rooms_paganti'] ?? collect())
                                                ->merge($hotel['rooms_gratuite'] ?? collect());
                                            if ($rooms->isEmpty())
                                                return "{$hotelNome}: nessuna stanza inserita.";

                                            return $rooms->map(function ($room) use ($hotelNome) {
                                                $tipo = $room['tipo_costo'] ?? 'a persona';
                                                $notti = (int) ($room['n_notti'] ?? 0);
                                                $costo = (float) ($room['costo_notte'] ?? 0);
                                                $quantita = (int) ($room['quantita_camere'] ?? 1);
                                                $paganti = (int) ($room['numero_paganti_stanza'] ?? 0);
                                                $gratuita = (bool) ($room['gratuita'] ?? false);
                                                $numGrat = (int) ($room['numero_gratuita_stanza'] ?? 0);

                                                if ($tipo === 'a persona') {
                                                    $persone = $gratuita ? $numGrat : $paganti;
                                                    $totale = $costo * $notti * max(1, $persone);
                                                    $tipoLabel = $gratuita ? 'gratuità' : 'paganti';
                                                    return "<b>{$hotelNome}</b>: " . number_format($totale, 2, ',', '.') .
                                                        " € ({$room['tipologia_stanza']} – {$notti} notti × €{$costo}/notte × {$persone} {$tipoLabel})";
                                                }

                                                if ($tipo === 'a camera') {
                                                    $totale = $costo * $notti * max(1, $quantita);
                                                    return "<b>{$hotelNome}</b>: " . number_format($totale, 2, ',', '.') .
                                                        " € ({$room['tipologia_stanza']} – {$notti} notti × €{$costo}/notte × {$quantita} camere)";
                                                }

                                                return '';
                                            })->filter()->implode('<br>');
                                        })->implode('<hr>');

                                        $hotelTot = collect($hotelPreventives)->reduce(function ($carry, $hotel) {
                                            $rooms = collect($hotel['rooms_paganti'] ?? collect())
                                                ->merge($hotel['rooms_gratuite'] ?? collect());

                                            foreach ($rooms as $room) {
                                                $tipo = $room['tipo_costo'] ?? 'a persona';
                                                $costo = (float) ($room['costo_notte'] ?? 0);
                                                $notti = (int) ($room['n_notti'] ?? 0);
                                                $quantita = (int) ($room['quantita_camere'] ?? 1);

                                                if ($tipo === 'a persona') {
                                                    $persone = ((int) ($room['numero_paganti_stanza'] ?? 0))
                                                        + ((int) ($room['numero_gratuita_stanza'] ?? 0));
                                                    $carry += $costo * $notti * max(1, $persone);
                                                }

                                                if ($tipo === 'a camera') {
                                                    $carry += $costo * $notti * max(1, $quantita);
                                                }
                                            }

                                            return $carry;
                                        }, 0);


                                        // -------- TRASPORTI: dettagli e totale --------
                            

                                        // -------- TRASPORTI: dettagli e totale --------
                            
                                        // Partecipanti e paganti
                                        $nPartecipanti = max($persone, 1);
                                        $nPaganti = max($paganti, 1);

                                        // --- DETTAGLIO ANDATA/RIENTRO ---
                                        $traspBlocks = collect([
                                            'Andata' => $traspAndata,
                                            'Rientro' => $traspRientro,
                                        ])
                                            ->filter(function ($t) {

                                            if (!$t)
                                                return false;
                                            return !($t['scorpora_trasporto'] ?? false);
                                        })
                                            ->map(function ($t, $tipoViaggio) use ($nPartecipanti, $nPaganti) {

                                            $tipo = ucfirst($t['tipo_trasporto'] ?? 'N/D');
                                            $prezzo = $t['prezzo'] ?? 0;
                                            $tc = $t['tipo_costo'] ?? 'a persona';

                                            if ($tc === 'a persona') {
                                                return "{$tipoViaggio}: {$tipo} × {$nPartecipanti} ({$tc}) → € {$prezzo}";
                                            }

                                            return "{$tipoViaggio}: {$tipo} ({$tc} ÷ {$nPaganti} paganti) → € {$prezzo}";
                                        })
                                            ->implode('<br>');


                                        // --- DETTAGLIO INTERMEDI ---
                                        $traspBlocks .= collect($traspIntermedi ?? [])
                                            ->values()
                                            ->map(function ($t, $i) use ($nPartecipanti, $nPaganti) {

                                            $tipo = ucfirst($t['tipo_trasporto'] ?? 'N/D');
                                            $prezzo = $t['prezzo'] ?? 0;
                                            $tc = $t['tipo_costo'] ?? 'a persona';

                                            if ($tc === 'a persona') {
                                                return "<br>Intermedio #" . ($i + 1) . ": {$tipo} × {$nPartecipanti} ({$tc}) → € {$prezzo}";
                                            }

                                            return "<br>Intermedio #" . ($i + 1) . ": {$tipo} ({$tc} ÷ {$nPaganti} paganti) → € {$prezzo}";
                                        })
                                            ->implode('');


                                        // --- TOTALE TRASPORTI ---
                                        $traspTot = 0;

                                        // Andata + Rientro
                                        foreach (array_filter([$traspAndata, $traspRientro]) as $t) {
                                            $scorpora = (bool) ($t['scorpora_trasporto'] ?? false);
                                            if ($scorpora) {
                                                continue;
                                            }
                                            $prezzo = $t['prezzo'] ?? 0;
                                            $tc = $t['tipo_costo'] ?? '';

                                            $traspTot += ($tc === 'a persona')
                                                ? $prezzo * $nPartecipanti
                                                : $prezzo;
                                        }



                                        // -------- SERVIZI EXTRA: dettagli e totale --------
                                        // -------- SERVIZI EXTRA: dettagli e totale --------
                                        $serviziDett = collect($extraServices)
                                            ->filter(function ($s) {
                                            // Gestisci sia array che oggetti
                                            $scorpora = is_array($s)
                                                ? ($s['scorpora_servizio'] ?? false)
                                                : ($s->scorpora_servizio ?? false);
                                            return !$scorpora;
                                        })
                                            ->map(function ($s) use ($nPartecipanti, $nPaganti) {
                                            // Gestisci sia array che oggetti
                                            $serviceId = is_array($s) ? ($s['extra_service_id'] ?? null) : ($s->extra_service_id ?? null);
                                            $tipo = optional(\App\Models\ExtraService::find($serviceId))->tipo ?? '-';
                                            $nome = optional(\App\Models\ExtraService::find($serviceId))->nome ?? '';

                                            $tc = is_array($s) ? ($s['tipo_costo'] ?? 'a_persona') : ($s->tipo_costo ?? 'a_persona');
                                            $prezzo = is_array($s) ? ($s['prezzo'] ?? 0) : ($s->prezzo ?? 0);

                                            if ($tc === 'a_persona') {
                                                $q = is_array($s) ? (int) ($s['quantita_a_persona'] ?? 1) : (int) ($s->quantita_a_persona ?? 1);
                                                return "{$tipo} {$nome} = € {$prezzo} (a persona × {$nPartecipanti} × {$q})";
                                            }

                                            // UNA TANTUM
                                            $q = is_array($s) ? (int) ($s['quantita'] ?? 1) : (int) ($s->quantita ?? 1);
                                            return ($nome != "" ? "$nome" : $tipo) . " = € {$prezzo} (una tantum × {$q})";
                                        })
                                            ->implode('<br>');
                                        //test
                            
                                        // -------- TOTALE SERVIZI --------
                                        // -------- TOTALE SERVIZI --------
                                        $serviziTot = collect($extraServices)->reduce(function ($carry, $s) use ($nPartecipanti, $nPaganti) {
                                            // Gestisci sia array che oggetti
                                            $scorpora = is_array($s)
                                                ? ($s['scorpora_servizio'] ?? false)
                                                : ($s->scorpora_servizio ?? false);

                                            if ($scorpora) {
                                                return $carry;
                                            }

                                            $prezzo = is_array($s) ? ($s['prezzo'] ?? 0) : ($s->prezzo ?? 0);
                                            $tc = is_array($s) ? ($s['tipo_costo'] ?? 'a_persona') : ($s->tipo_costo ?? 'a_persona');

                                            if ($tc === 'a_persona') {
                                                $q = is_array($s) ? (int) ($s['quantita_a_persona'] ?? 1) : (int) ($s->quantita_a_persona ?? 1);
                                                $carry += $prezzo * $q * $nPartecipanti;
                                            } else {
                                                // UNA TANTUM
                                                $q = is_array($s) ? (int) ($s['quantita'] ?? 1) : (int) ($s->quantita ?? 1);
                                                $carry += ($prezzo * $q);
                                            }

                                            return $carry;
                                        }, 0);
                                        $cleanHtml = fn($html) =>
                                            trim(
                                                preg_replace([
                                                    '#<p><br></p>#i',     // elimina paragrafi vuoti
                                                    '#<p>&nbsp;</p>#i',   // elimina paragrafi con spazio
                                                    '#^\s*<br\s*/?>#i',   // elimina <br> all’inizio
                                                    '#<\/p>\s*<p>#i',     // unisci paragrafi consecutivi
                                                ], '', $html)
                                            );

                                        // -------- Quote comprende / non comprende --------
                                        $quotaComprende = collect([
                                            ...collect($hotelPreventives)->pluck('quota_comprende_hotel')->filter()->toArray(),
                                            data_get($traspAndata, 'quota_comprende_trasporti'),
                                            data_get($traspRientro, 'quota_comprende_trasporti'),
                                            ...collect($traspIntermedi)->pluck('quota_comprende_trasporti')->filter()->toArray(),
                                            ...collect($extraServices)->pluck('quota_comprende_servizi')->filter()->toArray(),
                                            $pick('quota_comprende_generico'),
                                        ])->filter()->map(fn($i) => "<li>" . $cleanHtml($i) . "</li>")->implode('');

                                        $quotaNonComprende = collect([
                                            ...collect($hotelPreventives)->pluck('quota_non_comprende_hotel')->filter()->toArray(),
                                            data_get($traspAndata, 'quota_non_comprende_trasporti'),
                                            data_get($traspRientro, 'quota_non_comprende_trasporti'),
                                            ...collect($traspIntermedi)->pluck('quota_non_comprende_trasporti')->filter()->toArray(),
                                            ...collect($extraServices)->pluck('quota_non_comprende_servizi')->filter()->toArray(),
                                            $pick('quota_non_comprende_generico'),
                                        ])->filter()->map(fn($i) => "<li>" . $cleanHtml($i) . "</li>")->implode('');

                                        // -------- Totale costi (hotel+trasporti+servizi) --------
                                        $totaleCosti = $hotelTot + $traspTot + $serviziTot;

                                        // -------- Prezzo per persona e incasso (dal form calcolato) --------
                                        [$quotaInd, $totIncasso] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($form);

                                        // -------- Metadati testata --------
                                        $fieldsHead = [
                                            'Creato da' => $record?->creator
                                                ? trim(($record->creator->nome ?? '') . ' ' . ($record->creator->cognome ?? ''))
                                                : '-',
                                            'Stato' => $pick('stato', '-'),
                                            'Tipo Preventivo' => $pick('tipo_preventivo') === 'libero' ? 'Preventivo Libero' : 'Preventivo da Richiesta',
                                            'Numero Preventivo' => $pick('numero', '-'),
                                            'Anno' => $pick('anno', '-'),
                                            'Data Preventivo' => $pick('data_preventivo')
                                                ? Carbon::parse($pick('data_preventivo'))->format('d-m-Y') : '-',
                                            'Data Validità' => $pick('date_expiration'),
                                            'Cliente' => (function () use ($record, $pick) {
                                            // se è già associato al record, prendo da relazione Eloquent
                                            if ($record?->customer) {
                                                return trim(($record->customer->nome ?? '') . ' ' . ($record->customer->cognome ?? ''));
                                            }

                                            // altrimenti provo a leggerlo dal form (create mode)
                                            $customer = $pick('customer') ?? $pick('customer_id');
                                            if (is_array($customer)) {
                                                return trim(($customer['nome'] ?? '') . ' ' . ($customer['cognome'] ?? ''));
                                            }

                                            // se è solo l'id, puoi opzionalmente caricarlo da DB
                                            if (is_numeric($customer)) {
                                                $c = \App\Models\Customer::find($customer);
                                                return $c ? trim(($c->nome ?? '') . ' ' . ($c->cognome ?? '')) : '-';
                                            }

                                            return '-';
                                        })(),

                                            'Numero Persone' => $persone ?: '-',
                                            'Numero Gratuità' => $gratuite ?: '-',
                                            'Numero Paganti' => $paganti ?: '-',
                                            'Data Inizio Viaggio' => $pick('data_inizio_viaggio')
                                                ? \Carbon\Carbon::parse($pick('data_inizio_viaggio'))->format('d-m-Y') : '-',
                                            'Data Fine Viaggio' => $pick('data_fine_viaggio')
                                                ? \Carbon\Carbon::parse($pick('data_fine_viaggio'))->format('d-m-Y') : '-',
                                        ];

                                        return [
                                            'fields' => [
                                                ...$fieldsHead,

                                                //---File-----
                                                'File fornitore Hotel' => new \Illuminate\Support\HtmlString(
                                                    collect($record->hotel_preventives ?? [])->map(function ($hotel) {
                                                        $files = $hotel['file_fornitore_hotel'] ?? [];
                                                        if (empty($files)) {
                                                            return 'Nessun file caricato';
                                                        }

                                                        return collect($files)->map(function ($file) {
                                                            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                                return "File (in caricamento...)";
                                                            } elseif (is_string($file)) {
                                                                $url = asset('storage/' . $file);
                                                                $name = basename($file);
                                                                return "<a href='{$url}' target='_blank' class='text-blue-600 underline'>Visualizza file</a>";
                                                            }
                                                            return null;
                                                        })->filter()->implode('<br>');
                                                    })->implode('<hr>') ?: 'Nessun file caricato'
                                                ),
                                                'File fornitore Andata' => new \Illuminate\Support\HtmlString(
                                                    collect($record?->trasporto_andata['file_fornitore_trasporto'] ?? [])->map(function ($file) {
                                                        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                            return "File (in caricamento...)";
                                                        } elseif (is_string($file)) {
                                                            $url = asset('storage/' . $file);
                                                            $name = basename($file);
                                                            return "<a href='{$url}' target='_blank' class='text-blue-600 underline'>Visualizza file</a>";
                                                        }
                                                        return null;
                                                    })->filter()->implode('<br>') ?: 'Nessun file caricato'
                                                ),
                                                'File fornitore Rientro' => new \Illuminate\Support\HtmlString(
                                                    collect($record?->trasporto_rientro['file_fornitore_trasporto'] ?? [])->map(function ($file) {
                                                        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                            return "File (in caricamento...)";
                                                        } elseif (is_string($file)) {
                                                            $url = asset('storage/' . $file);
                                                            $name = basename($file);
                                                            return "<a href='{$url}' target='_blank' class='text-blue-600 underline'>Visualizza file</a>";
                                                        }
                                                        return null;
                                                    })->filter()->implode('<br>') ?: 'Nessun file caricato'
                                                ),

                                                'File fornitore Servizi Extra' => new \Illuminate\Support\HtmlString(
                                                    collect($record?->extra_services ?? [])->map(function ($servizio) {
                                                        $files = $servizio['file_fornitore_servizi_extra'] ?? [];
                                                        if (empty($files))
                                                            return 'Nessun file caricato';

                                                        return collect($files)->map(function ($file) {
                                                            if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                                return "File (in caricamento...)";
                                                            } elseif (is_string($file)) {
                                                                $url = asset('storage/' . $file);
                                                                $name = basename($file);
                                                                return "<a href='{$url}' target='_blank' class='text-blue-600 underline'>Visualizza file</a>";
                                                            }
                                                            return null;
                                                        })->filter()->implode('<br>');
                                                    })->filter()->implode('<hr>') ?: 'Nessun file caricato'
                                                ),

                                                // --- Dettagli economici ---
                                                ...($isGitaGiornaliera ? [] : [
                                                    'Prezzi dettaglio hotel' => new \Illuminate\Support\HtmlString($hotelDettaglio ?: '—'),
                                                    'Prezzo totale hotel' => '€ ' . number_format((int) $hotelTot, 0, ',', '.'),
                                                ]),

                                                'Prezzi dettaglio trasporti' => new \Illuminate\Support\HtmlString($traspBlocks ?: '—'),
                                                'Prezzo totale trasporti' => '€ ' . number_format((int) $traspTot, 0, ',', '.'),

                                                'Prezzi dettaglio servizi' => new \Illuminate\Support\HtmlString($serviziDett ?: '—'),
                                                'Prezzo totale servizi' => '€ ' . number_format((int) $serviziTot, 0, ',', '.'),

                                                'Totale costi' => '€ ' . number_format((int) $totaleCosti, 0, ',', '.'),
                                                // --- Quota/Incasso (live) ---
                                                'Prezzo per persona' => '€ ' . number_format($quotaInd, 2, ',', '.'),
                                                'Totale incasso ' => '€ ' . number_format((int) $totIncasso, 0, ',', '.'),

                                                // --- Comprende / Non comprende ---
                                                'La quota comprende' => new \Illuminate\Support\HtmlString($quotaComprende ?: '-'),
                                                'La quota non comprende' => new \Illuminate\Support\HtmlString($quotaNonComprende ?: '-'),
                                            ],
                                        ];
                                    }),

                                Forms\Components\TextInput::make('totale_incasso')
                                    ->label('Totale Incasso')
                                    ->prefix('€')
                                    ->numeric()
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->default(function (Get $get) {
                                        [, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($get());
                                        return $tot;
                                    })
                                    ->afterStateHydrated(function (Set $set, Get $get) {
                                        [, $tot] = \App\Filament\Resources\PreventiveResource::calcolaCostoPerPersona($get());
                                        $set('totale_incasso', $tot);
                                    }),

                            ]),

                        ValidatedTab::make('Invio Email', [
                            'email_template_id',
                            'email_cliente',
                        ])
                            ->hiddenOn('create')
                            ->schema([
                                Forms\Components\Select::make('email_template_id')
                                    ->label('Template Email')
                                    ->options(\App\Models\EmailTemplate::pluck('nome', 'id'))
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->live()
                                    ->afterStateUpdated(function ($state, Set $set) {
                                        if ($template = \App\Models\EmailTemplate::find($state)) {
                                            $set('corpo_email', $template->corpo_email);
                                        }
                                    })
                                    ->afterStateHydrated(function ($state, $set, $get, $record) {
                                        // Se non c'è record O il record non esiste nel DB O non ci sono email, esci
                                        if (!$record || !$record->exists || !$record->emails()->exists()) {
                                            return;
                                        }

                                        // Carica l'ultima email solo se esistente E corrisponde al cliente corrente
                                        if (!$state) {
                                            $lastEmail = $record->emails()
                                                ->where('customer_id', $record->customer_id)
                                                ->orderBy('created_at', 'desc')
                                                ->first();

                                            if ($lastEmail) {
                                                if (empty($get('email_template_id'))) {
                                                    $set('email_template_id', $lastEmail->email_template_id);
                                                }
                                                if (empty($get('corpo_email'))) {
                                                    $set('corpo_email', $lastEmail->corpo_email);
                                                }

                                                if (empty($get('email_cc'))) {
                                                    $set('email_cc', collect($lastEmail->email_cc ?? [])
                                                        ->map(fn($cc) => ['email_cc' => $cc])
                                                        ->toArray());
                                                }
                                                if (empty($get('allegati'))) {
                                                    $set('allegati', $lastEmail->allegati ?? []);
                                                }
                                            }
                                        }
                                    })
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('nome')->required()->columnSpanFull(),
                                        Forms\Components\RichEditor::make('corpo_email')
                                            ->toolbarButtons(['bold', 'bulletList', 'italic', 'orderedList', 'redo', 'underline', 'undo'])
                                            ->required()
                                            ->columnSpanFull(),
                                    ])
                                    ->createOptionUsing(fn(array $data) => \App\Models\EmailTemplate::create($data)->getKey()),

                                Forms\Components\TextInput::make('email_cliente')
                                    ->label('Email Cliente')
                                    ->dehydrated(true)
                                    ->live()
                                    ->readOnly()
                                    ->required()
                                    ->afterStateHydrated(function ($state, Set $set, $record) {
                                        // SEMPRE forza l'email del cliente corrente
                                        if ($record && $record->customer && $record->customer->email) {
                                            $set('email_cliente', $record->customer->email);
                                        }
                                    }),

                                Forms\Components\Repeater::make('email_cc')
                                    ->label('')
                                    ->schema([
                                        Forms\Components\TextInput::make('email_cc')->label('Email CC'),
                                    ])
                                    ->collapsible()
                                    ->defaultItems(0)
                                    ->addActionLabel('Aggiungi Email CC')
                                    ->columnSpanFull(),

                                Forms\Components\RichEditor::make('corpo_email')
                                    ->label('Corpo Email')
                                    ->live(onBlur: true)
                                    ->dehydrated(true)
                                    ->toolbarButtons(['bold', 'bulletList', 'italic', 'orderedList', 'redo', 'underline', 'undo'])
                                    ->columnSpanFull(),



                                Forms\Components\FileUpload::make('allegati')
                                    ->directory('allegati_email')
                                    ->multiple()
                                    ->acceptedFileTypes(['application/pdf'])
                                    ->maxSize(3072)
                                    ->preserveFilenames()
                                    ->label('Allegati'),

                                Forms\Components\Group::make([
                                    Forms\Components\Actions::make([
                                        Action::make('inviaEmail')
                                            ->label('Salva & Invia Email')
                                            ->color('success')

                                            ->requiresConfirmation()
                                            ->action(function ($livewire, array $data) {
                                                try {
                                                    $livewire->validate();
                                                } catch (\Filament\Support\Exceptions\Halt $e) {
                                                    Notification::make()
                                                        ->title('Compila tutti i campi obbligatori')
                                                        ->danger()
                                                        ->persistent()
                                                        ->send();
                                                    return;
                                                }
                                                /** @var \App\Models\Preventive|null $preventivo */
                                                $preventivo = $livewire->record;
                                                $formData = $livewire->form->getState();

                                                $allegoFile = $formData['allego_file'] ?? false;

                                                if (!$allegoFile) {
                                                    //  Validazione hotel ed extra services
                                                    $hasHotels = false;
                                                    $hasServices = false;

                                                    // Controlla se il record esiste già
                                                    if ($preventivo && $preventivo->exists) {
                                                        // Record esistente: controlla le relazioni caricate
                                                        $hasHotels = $preventivo->hotel_preventives()->exists();
                                                        $hasServices = $preventivo->extra_services()->exists();
                                                    } else {
                                                        // Nuovo record: controlla i dati del form
                                                        $hasHotels = !empty($formData['hotel_preventives']);
                                                        $hasServices = !empty($formData['extra_services']);
                                                    }



                                                    // Blocca se mancano hotel
                                                    if (!$hasHotels && $formData['gita_giornaliera'] === false) {
                                                        Notification::make()
                                                            ->title('Hotel mancanti')
                                                            ->body('Devi inserire almeno un hotel.')
                                                            ->danger()
                                                            ->persistent()
                                                            ->send();
                                                        return;
                                                    }

                                                    // Blocca se mancano servizi extra
                                                    if (!$hasServices) {
                                                        Notification::make()
                                                            ->title('Servizi Extra mancanti')
                                                            ->body('Devi inserire almeno un servizio.')
                                                            ->danger()
                                                            ->persistent()
                                                            ->send();
                                                        return;
                                                    }
                                                    $itinerari = $formData['itinerario'] ?? [];

                                                    $itinerariSenzaFoto = collect($itinerari)->filter(function ($item) {
                                                        $foto = $item['immagini'] ?? [];
                                                        return empty($foto) || count($foto) < 3;
                                                    });

                                                    if ($itinerariSenzaFoto->isNotEmpty()) {
                                                        Notification::make()
                                                            ->title('Salvataggio interrotto')
                                                            ->body('Le tappe dell\'itinerario non hanno le 3 immagini richieste.')
                                                            ->danger()
                                                            ->persistent()
                                                            ->send();
                                                        return;
                                                    }

                                                    //  VALIDAZIONE 3: Controlla foto negli hotel
                                                    $hotelIds = collect();

                                                    if ($preventivo && $preventivo->exists) {
                                                        // Record esistente: prendi gli hotel già salvati
                                                        $hotelIds = $preventivo->hotel_preventives()->pluck('hotel_id')->filter()->unique();
                                                    } else {
                                                        // Nuovo record: prendi gli hotel dal form
                                                        $hotelIds = collect($formData['hotel_preventives'] ?? [])
                                                            ->pluck('hotel_id')
                                                            ->filter()
                                                            ->unique();
                                                    }

                                                    if ($hotelIds->isNotEmpty()) {
                                                        $hotels = \App\Models\Hotel::whereIn('id', $hotelIds)->get();

                                                        $hotelSenzaFoto = $hotels->filter(function ($hotel) {
                                                            $foto = $hotel->foto;

                                                            // Se è una stringa JSON, decodificala
                                                            if (is_string($foto)) {
                                                                $decoded = json_decode($foto, true);
                                                                $foto = is_array($decoded) ? $decoded : [];
                                                            }

                                                            // Se è null o array vuoto
                                                            if ($foto === null || (is_array($foto) && count($foto) === 0)) {
                                                                return true; // Hotel senza foto
                                                            }

                                                            return false;
                                                        });

                                                        if ($hotelSenzaFoto->isNotEmpty()) {
                                                            $nomi = $hotelSenzaFoto->pluck('nome')->join(', ');

                                                            Notification::make()
                                                                ->title('Salvataggio interrotto')
                                                                ->body("I seguenti hotel non hanno foto: {$nomi}.")
                                                                ->danger()
                                                                ->persistent()
                                                                ->send();
                                                            return;
                                                        }
                                                    }
                                                    $hotelSenzaStanze = [];

                                                    if ($preventivo && $preventivo->exists) {
                                                        // Record esistente: controlla le relazioni
                                                        foreach ($preventivo->hotel_preventives as $hotelPrev) {
                                                            $totalRooms = $hotelPrev->rooms_paganti()->count() + $hotelPrev->rooms_gratuite()->count();

                                                            if ($totalRooms === 0) {
                                                                $hotelSenzaStanze[] = $hotelPrev->hotel->nome ?? 'Hotel senza nome';
                                                            }
                                                        }
                                                    } else {
                                                        // Nuovo record: controlla i dati del form
                                                        foreach ($formData['hotel_preventives'] ?? [] as $hotelData) {
                                                            $hasRooms = !empty($hotelData['rooms_paganti']) || !empty($hotelData['rooms_gratuite']);

                                                            if (!$hasRooms) {
                                                                $hotelId = $hotelData['hotel_id'] ?? null;
                                                                if ($hotelId) {
                                                                    $hotel = \App\Models\Hotel::find($hotelId);
                                                                    $hotelSenzaStanze[] = $hotel?->nome ?? 'Hotel senza nome';
                                                                }
                                                            }
                                                        }
                                                    }

                                                    if (!empty($hotelSenzaStanze)) {
                                                        $nomi = implode(', ', $hotelSenzaStanze);

                                                        Notification::make()
                                                            ->title('Salvataggio interrotto')
                                                            ->body("I seguenti hotel non hanno stanze (paganti o gratuite): {$nomi}.")
                                                            ->danger()
                                                            ->persistent()
                                                            ->send();
                                                        return;
                                                    }
                                                }
                                                $emailDraft = [
                                                    'email_template_id' => $formData['email_template_id'] ?? null,
                                                    'email_cliente' => $formData['email_cliente'] ?? null,
                                                    'email_cc' => collect($formData['email_cc'] ?? [])->pluck('email_cc')->filter()->values()->toArray(),
                                                    'corpo_email' => $formData['corpo_email'] ?? null,
                                                    'allegati' => $formData['allegati'] ?? [],
                                                ];
                                                if (empty($emailDraft['email_cliente']) && empty($emailDraft['email_cc'])) {
                                                    Notification::make()
                                                        ->title('Destinatario mancante')
                                                        ->body('Inserisci almeno un indirizzo email del cliente.')
                                                        ->danger()
                                                        ->persistent()
                                                        ->send();
                                                    return;
                                                }

                                                //  Salva usando il metodo standard di Filament
                                                if (!$preventivo || !$preventivo->exists) {
                                                    try {
                                                        // Rimuovi i campi email temporanei dal form data
                                                        $dataToSave = $formData;
                                                        unset(
                                                            $dataToSave['email_template_id'],
                                                            //$dataToSave['email_cliente'],
                                                            $dataToSave['email_cc'],
                                                            $dataToSave['corpo_email'],
                                                            $dataToSave['allegati']
                                                        );

                                                        // Usa il metodo save() di Filament
                                                        $livewire->form->model($preventivo ?? \App\Models\Preventive::class)->saveRelationships();
                                                        $preventivo = $livewire->form->model(\App\Models\Preventive::class)->create($dataToSave);
                                                        $livewire->form->model($preventivo)->saveRelationships();
                                                        $livewire->record = $preventivo;

                                                    } catch (\Throwable $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Errore durante il salvataggio del preventivo.')
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }
                                                } else {
                                                    // Se il record esiste già, aggiornalo
                                                    try {
                                                        $dataToSave = $formData;
                                                        unset(
                                                            $dataToSave['email_template_id'],
                                                            // $dataToSave['email_cliente'],
                                                            $dataToSave['email_cc'],
                                                            $dataToSave['corpo_email'],
                                                            $dataToSave['allegati']
                                                        );

                                                        $preventivo->update($dataToSave);
                                                        $livewire->form->model($preventivo)->saveRelationships();

                                                    } catch (\Throwable $e) {
                                                        \Filament\Notifications\Notification::make()
                                                            ->title('Errore durante l\'aggiornamento del preventivo.')
                                                            ->body($e->getMessage())
                                                            ->danger()
                                                            ->send();
                                                        return;
                                                    }
                                                }

                                                //  Normalizza email CC
                                                $emailCc = collect($formData['email_cc'] ?? [])
                                                    ->pluck('email_cc')
                                                    ->filter()
                                                    ->values()
                                                    ->toArray();

                                                //  Gestione allegati
                                                $storedFiles = [];
                                                if (!empty($formData['allegati']) && is_array($formData['allegati'])) {
                                                    foreach ($formData['allegati'] as $file) {
                                                        if ($file instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                                            $storedFiles[] = $file->store('allegati_email', 'public');
                                                        } elseif (is_string($file)) {
                                                            $storedFiles[] = $file;
                                                        } elseif (is_array($file)) {
                                                            $path = reset($file);
                                                            if (is_string($path)) {
                                                                $storedFiles[] = $path;
                                                            }
                                                        }
                                                    }
                                                }




                                                $existingEmail = $preventivo->emails()->latest('updated_at')->first();

                                                if ($existingEmail) {
                                                    $existingEmail->update([
                                                        'customer_id' => $preventivo->customer_id,
                                                        'sent_by' => $preventivo->created_by,
                                                        'email_template_id' => $emailDraft['email_template_id'],
                                                        'email_cliente' => $emailDraft['email_cliente'],
                                                        'email_cc' => $emailDraft['email_cc'],
                                                        'corpo_email' => $emailDraft['corpo_email'],
                                                        'allegati' => $storedFiles,
                                                        'quote_request_id' => $preventivo->quote_request_id ?: null,
                                                        'is_draft' => false,
                                                    ]);

                                                    $email = $existingEmail->fresh();

                                                    \Log::info('Email AGGIORNATA', [
                                                        'email_id' => $email->id,
                                                        'updated_at' => $email->updated_at,
                                                    ]);
                                                } else {
                                                    $email = \App\Models\Email::create([
                                                        'customer_id' => $preventivo->customer_id,
                                                        'sent_by' => $preventivo->created_by,
                                                        'email_template_id' => $emailDraft['email_template_id'],
                                                        'email_cliente' => $emailDraft['email_cliente'],
                                                        'email_cc' => $emailDraft['email_cc'],
                                                        'corpo_email' => $emailDraft['corpo_email'],
                                                        'allegati' => $storedFiles,
                                                        'quote_request_id' => $preventivo->quote_request_id ?: null,
                                                        'is_draft' => false,
                                                    ]);

                                                    $preventivo->emails()->attach($email->id);

                                                    \Log::info('Email CREATA', [
                                                        'email_id' => $email->id,
                                                    ]);
                                                }
                                                try {
                                                    if (empty($email->email_cliente)) {
                                                        throw new \Exception('Email cliente non presente.');
                                                    }

                                                    $mail = \Illuminate\Support\Facades\Mail::to($email->email_cliente);

                                                    if (!empty($email->email_cc)) {
                                                        $mail->cc($email->email_cc);
                                                    }

                                                    $mail->send(new \App\Mail\PreventiveCreatedMail($preventivo, $email));

                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Email inviata con successo')
                                                        ->success()
                                                        ->send();

                                                } catch (\Throwable $e) {
                                                    \Filament\Notifications\Notification::make()
                                                        ->title('Errore durante l\'invio della email.')
                                                        ->body($e->getMessage())
                                                        ->danger()
                                                        ->send();

                                                    \Log::error('Errore invio email', [
                                                        'error' => $e->getMessage(),
                                                        'email_cliente' => $email->email_cliente ?? 'NULL',
                                                    ]);

                                                    return;
                                                }

                                                //  Aggiorna stato preventivo
                                                $preventivo->update([
                                                    'data_invio' => $preventivo->data_invio ?? now(),
                                                    'stato' => PreventiveStatus::IN_ATTESA,
                                                ]);

                                                if (!empty($preventivo->quote_request)) {
                                                    $preventivo->quote_request->update([
                                                        'stato_richiesta' => QuoteRequestStatus::EVASA,
                                                    ]);
                                                }
                                            }),
                                    ]),
                                ])->columnSpanFull(),
                            ])
                            ->columnSpanFull()





                    ])
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->recordUrl(url: fn($record) => PreventiveResource::getUrl('edit', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->searchable()
                    ->label('Numero'),
                Tables\Columns\TextColumn::make('customer.nome')
                    ->label('Cliente')
                    ->formatStateUsing(fn($state, $record) => "{$record->customer?->nome} {$record->customer?->cognome}")
                    ->tooltip(fn($state, $record) => "{$record->customer?->nome} {$record->customer?->cognome}")
                    ->searchable(),
                /*  Tables\Columns\TextColumn::make('quote_request.agenti_gestori')
                     ->getStateUsing(fn($record) => $record->quote_request?->agenti_gestori?->map(fn($u) => "{$u->nome} {$u->cognome}"))
                     ->label('Gestore'),*/

                Tables\Columns\TextColumn::make('titolo')
                    ->label('Titolo')
                    ->limit(20)
                    ->tooltip(fn($record) => $record->titolo)
                    ->searchable(),
                Tables\Columns\TextColumn::make('data_preventivo')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('data_inizio_viaggio')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('data_fine_viaggio')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('meta_viaggio')
                    ->searchable()
                    ->label('Meta'),
                Tables\Columns\TextColumn::make('stato')
                    ->label('Stato')
                    ->badge(),
                Tables\Columns\TextColumn::make('stato')
                    ->label('Stato')
                    /*  ->formatStateUsing(function (string $state, $record): string {
                        return match ($state) {
                            'accettato' => 'Accetto il preventivo',
                            'interesse più tempo' => "L'offerta è di interesse, ma ho bisogno di più tempo",
                            'superiore budget' => "L'offerta risulta superiore al budget previsto",
                            'oltre tempi' => "L'offerta è pervenuta oltre i tempi necessari alla valutazione",
                            'programma non interessa' => "Il programma proposto non incontra i miei interessi",
                            'rivedere proposta' => "Vorrei rivedere la proposta insieme a voi",
                            'altro' => $record->stato_altro_testo ?? 'Altro',
                            default => ucfirst($state),
                        };
                    })
                    ->limit(20)
                    ->tooltip(function ($record) {
                        return match ($record->stato) {
                            'accettato' => 'Accetto il preventivo',
                            'interesse più tempo' => "L'offerta è di interesse, ma ho bisogno di più tempo",
                            'superiore budget' => "L'offerta risulta superiore al budget previsto",
                            'oltre tempi' => "L'offerta è pervenuta oltre i tempi necessari alla valutazione",
                            'programma non interessa' => "Il programma proposto non incontra i miei interessi",
                            'rivedere proposta' => "Vorrei rivedere la proposta insieme a voi",
                            'altro' => $record->stato_altro_testo ?? 'Altro',
                            default => $record->stato,
                        };
                    }) */
                    ->formatStateUsing(function ($state, $record): string {
                        return match ($state) {
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
                            default => $record->stato?->value ?? '',
                        };
                    })
                    ->limit(20)
                    ->tooltip(function ($record) {
                        return match ($record->stato) {
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
                            default => $record->stato?->value ?? '',
                        };
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('totale_incasso')
                    ->label('Totale Incasso')->money('EUR', true),
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
                                fn(Builder $q, $date) => $q->whereDate('data_preventivo', '>=', $date),
                            )
                            ->when(
                                $data['until'],
                                fn(Builder $q, $date) => $q->whereDate('data_preventivo', '<=', $date),
                            );
                    }),
                SelectFilter::make('gestore')
                    ->label('Agente Gestore')
                    ->options(function () {
                        return User::whereHas(
                            'role',
                            fn($q) => $q->whereNotIn('nome', ['admin', 'superadmin'])
                        )
                            ->pluck('nome', 'id')
                            ->toArray();
                    })
                    ->query(function (Builder $query, array $data) {
                        if (filled($data['value'])) {
                            return $query->whereHas('quote_request.agenti_gestori', function (Builder $q) use ($data) {
                                $q->where('users.id', $data['value']);
                            });
                        }
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('ultimo_mese')
                    ->label('Ultimo mese')
                    ->query(
                        fn(Builder $query): Builder => $query->where('data_preventivo', '>=', now()->subMonth()) // ultimi 30 gg
                    ),
                Tables\Filters\Filter::make('mese_precedente')
                    ->label('Mese precedente')
                    ->query(
                        fn(Builder $query): Builder => $query->whereBetween('data_preventivo', [
                            now()->subMonth()->startOfMonth(),
                            now()->subMonth()->endOfMonth(),
                        ])
                    ),

                Tables\Filters\Filter::make('customer_nome')
                    ->form([
                        Forms\Components\TextInput::make('q')
                            ->label('Cliente (nome/cognome/azienda/scuola)'),
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
                Tables\Filters\SelectFilter::make('stato')
                    ->label('Stato Preventivo')
                    ->options(PreventiveStatus::class)
                    ->multiple(),



            ])
            /*          ->headerActions([
             \Filament\Tables\Actions\Action::make('export_all')
                 ->label('Esporta Tutti in Excel')
                 ->icon('heroicon-o-document-arrow-down')
                 ->color('success')
                 ->form([
                     Forms\Components\CheckboxList::make('columns')
                         ->label('Colonne da esportare')
                         ->options([
                             'id' => 'ID',
                             'titolo' => 'Titolo',
                             'data_preventivo' => 'Data Preventivo',
                             'stato' => 'Stato',
                             'totale_incasso' => 'Totale Incasso',
                         ])
                         ->default(['id', 'titolo', 'data_preventivo', 'stato', 'totale_incasso'])
                         ->columns(2)
                         ->required(),
                     Forms\Components\DatePicker::make('from')->label('Da'),
                     Forms\Components\DatePicker::make('until')->label('A'),
                 ])
                 ->action(function (array $data) {
                     $query = \App\Models\Preventive::query();

                     $query
                         ->when($data['from'], fn($q) => $q->whereDate('data_preventivo', '>=', $data['from']))
                         ->when($data['until'], fn($q) => $q->whereDate('data_preventivo', '<=', $data['until']));

                     $ids = $query->pluck('id')->toArray();
                     $columns = $data['columns'];
                     $filename = 'preventivi_export_' . now()->format('Ymd_His') . '.xlsx';

                     return Excel::download(new PreventivesExport($ids, $columns), $filename);
                 }),
         ]) */
            ->actionsPosition(\Filament\Tables\Enums\ActionsPosition::BeforeColumns)
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('export_single_excel')
                        ->label('Esporta in Excel')
                        ->icon('heroicon-o-document-text')
                        ->color('success')
                        ->action(function ($record) {
                            $columns = [
                                'id',
                                'numero',
                                'anno',
                                'tag',
                                'titolo',
                                'created_by',
                                'customer_id',
                                'email_cliente',
                                'data_preventivo',
                                'meta_viaggio',
                                'nome_itinerario',
                                'data_inizio_viaggio',
                                'data_fine_viaggio',
                                'numero_persone',
                                'numero_gratuita',
                                'prezzo_per_persona',
                                'markup',
                                'totale_incasso',
                                'stato',
                            ];

                            $filename = 'preventivo_' . $record->id . '_' . now()->format('Ymd_His') . '.xlsx';

                            return \Maatwebsite\Excel\Facades\Excel::download(
                                new \App\Exports\PreventivesExport([$record->id], $columns),
                                $filename
                            );
                        }),
                    Tables\Actions\Action::make('duplicate')
                        ->label('Duplica')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('gray')
                        ->label('Duplica Preventivo')
                        ->requiresConfirmation()
                        ->action(function ($record) {
                            $new = $record->duplicateWithRelations();
                            $url = PreventiveResource::getUrl('edit', parameters: ['record' => $new]);


                            Notification::make()
                                ->title('Preventivo duplicato con successo!')
                                ->body("È stata creata una copia: {$new->tag}")
                                ->success()
                                ->send();

                            return redirect($url);
                        }),
                    Tables\Actions\Action::make('scaricaPdf')
                        ->label('Scarica PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->visible(
                            fn($record) =>
                            auth()->user()->hasAnyRole(['admin', 'superadmin']) ||
                            (auth()->user()->hasRole('agente') && $record->created_by === auth()->id())
                        )
                        ->url(function ($record) {
                            // Se allego_file è true, apri la rotta che mostra il file inline
                            if ($record->allego_file) {
                                return route('preventivo.download.allegato', ['cod_alfa' => $record->cod_alfa]);
                            }

                            // Altrimenti apri la pagina normale con il link
                            return route('preventivi.pdf', $record);
                        })
                        ->openUrlInNewTab(),
                    Tables\Actions\ViewAction::make()
                        ->icon('heroicon-o-globe-alt')
                        ->disabled(false)
                        ->label('Visualizza Preventivo')
                        ->visible(fn($record) => filled($record->cod_alfa))
                        ->openUrlInNewTab()
                        ->extraAttributes(['target' => '_blank'])
                        ->url(function ($record) {
                            // Se allego_file è true, apri la rotta che mostra il file inline
                            if ($record->allego_file) {
                                return route('preventivo.show.allegato', ['cod_alfa' => $record->cod_alfa]);
                            }

                            // Altrimenti apri la pagina normale con il link
                            return route('preventivo.show', ['cod_alfa' => $record->cod_alfa]);
                        }),

                    Tables\Actions\ViewAction::make()
                        ->label('Scheda Preventivo')
                        ->modalHeading(fn($record): string => 'Scheda Preventivo'),
                    Tables\Actions\EditAction::make()->visible(function ($record) {
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
                            auth()->user()?->hasAnyRole(['admin', 'superadmin']) ||
                            $record->created_by === auth()->id()
                        )
                        ->modalHeading(fn($record): string => 'Elimina Preventivo'),

                ])
            ])



            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    BulkAction::make('export_excel')
                        ->label('Esporta in Excel (.xlsx)')
                        ->icon('heroicon-o-document-arrow-down')
                        ->form([
                            Forms\Components\CheckboxList::make('columns')
                                ->label('Colonne da esportare')
                                ->options([
                                    'id' => 'ID',
                                    'numero' => 'Numero',
                                    'anno' => 'Anno',
                                    'tag' => 'Tag',
                                    'titolo' => 'Titolo',
                                    'created_by' => 'Creato da',
                                    'customer_id' => 'Cliente',
                                    'email_cliente' => 'Email Cliente',
                                    'data_preventivo' => 'Data Preventivo',
                                    'meta_viaggio' => 'Meta Viaggio',
                                    'nome_itinerario' => 'Nome Itinerario',
                                    'data_inizio_viaggio' => 'Data Inizio Viaggio',
                                    'data_fine_viaggio' => 'Data Fine Viaggio',
                                    'numero_persone' => 'Numero Persone',
                                    'numero_gratuita' => 'Numero Gratuita',
                                    'prezzo_per_persona' => 'Prezzo per Persona',
                                    'markup' => 'Markup',
                                    'totale_incasso' => 'Totale Incasso',
                                    'stato' => 'Stato',
                                ])
                                ->default([
                                    'id',
                                    'numero',
                                    'anno',
                                    'tag',
                                    'titolo',
                                    'created_by',
                                    'customer_id',
                                    'email_cliente',
                                    'data_preventivo',
                                    'meta_viaggio',
                                    'nome_itinerario',
                                    'data_inizio_viaggio',
                                    'data_fine_viaggio',
                                    'numero_persone',
                                    'numero_gratuita',
                                    'prezzo_per_persona',
                                    'markup',
                                    'totale_incasso',
                                    'stato',
                                ])
                                ->columns(2)
                                ->required(),

                            //  Aggiungiamo i campi del range di date
                            Forms\Components\DatePicker::make('from')
                                ->label('Da')
                                ->placeholder('Data inizio')
                                ->native(false), // opzionale, per UI più carina

                            Forms\Components\DatePicker::make('until')
                                ->label('A')
                                ->placeholder('Data fine')
                                ->native(false),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $ids = $records->pluck('id')->toArray();
                            $columns = $data['columns'];
                            $filename = 'report_preventivi_' . now()->format('Ymd_His') . '.xlsx';

                            //  Se l'utente seleziona date, filtra i record
                            $query = \App\Models\Preventive::query()
                                ->whereIn('id', $ids)
                                ->when($data['from'], fn($q) => $q->whereDate('data_preventivo', '>=', $data['from']))
                                ->when($data['until'], fn($q) => $q->whereDate('data_preventivo', '<=', $data['until']));

                            // Recupera solo gli ID effettivamente nel range
                            $filteredIds = $query->pluck('id')->toArray();

                            return Excel::download(new \App\Exports\PreventivesExport($filteredIds, $columns), $filename);
                        }),

                    Tables\Actions\DeleteBulkAction::make()
                    ->visible(condition: fn() => auth()->user()?->hasRole('admin') || auth()->user()?->hasRole('superadmin')),
                ]),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getBreadcrumb(): string
    {
        return 'Report';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReports::route('/'),
            'create' => Pages\CreateReport::route('/create'),
            'edit' => Pages\EditReport::route('/{record}/edit'),
        ];
    }
    public static function calcolaCostoPerPersona(array $test): array
    {

        $personeTot = 0;
        $partecipanti = (int) ($test['numero_persone'] ?? 0);
        $persone_forzate = (int) ($test['n_persone_forzato'] ?? 0);

        if (!empty($persone_forzate) && $persone_forzate > 0) {
            $personeTot = (int) $persone_forzate;
        } else {
            $personeTot = $partecipanti;
        }

        $gratuite = (int) ($test['numero_gratuita'] ?? 0);
        $paganti = max(1, $personeTot - $gratuite);



        $totale = 0;

        $isGitaGiornaliera = (bool) ($test['gita_giornaliera'] ?? false);

        // ---------------- HOTEL ----------------

        //  Evita doppioni dovuti a ->relationship() + ->live()
        if (!$isGitaGiornaliera) {
            $hotelPreventives = collect($test['hotel_preventives'] ?? [])
                ->map(fn($item) => (array) $item)
                ->unique(fn($item) => $item['hotel_id'] ?? spl_object_id((object) $item))
                ->values()
                ->toArray();

            foreach ($hotelPreventives as $hotel) {
                // Somma sia stanze paganti che gratuite
                $allRooms = array_merge($hotel['rooms_paganti'] ?? [], $hotel['rooms_gratuite'] ?? []);

                foreach ($allRooms as $room) {
                    $tipo_costo = $room['tipo_costo'] ?? 'a persona';
                    $costo_notte = (float) ($room['costo_notte'] ?? 0);
                    $n_notti = (int) ($room['n_notti'] ?? 0);
                    $quantita = (int) ($room['quantita_camere'] ?? 1);
                    $isGratuita = (bool) ($room['gratuita'] ?? false);

                    // Persone nella stanza
                    $personeInStanza = $isGratuita
                        ? (int) ($room['numero_gratuita_stanza'] ?? 0)
                        : (int) ($room['numero_paganti_stanza'] ?? 0);

                    $costo_unitario = $costo_notte * $n_notti;

                    if ($tipo_costo === 'a persona') {
                        //  Usa il numero corretto di persone, includendo anche le gratuite
                        $totale += $costo_unitario * max(1, $personeInStanza);
                    } elseif ($tipo_costo === 'a camera') {
                        $totale += $costo_unitario * max(1, $quantita);
                    }
                }
            }
        }

        // ---------------- TRASPORTI ----------------
        $tutti_trasporti = array_values(array_filter([
            $test['trasporto_andata'] ?? null,
            $test['trasporto_rientro'] ?? null,
            ...($test['trasporto_intermedio'] ?? []),
        ]));

        foreach ($tutti_trasporti as $trasporto) {
            $tipo_costo = $trasporto['tipo_costo'] ?? null;
            $prezzo = (float) ($trasporto['prezzo'] ?? 0);
            $scorpora = (bool) ($trasporto['scorpora_trasporto'] ?? false);
            if ($scorpora) {
                continue;
            }

            if ($tipo_costo === 'a persona') {
                $totale += $prezzo * $personeTot;
            } elseif ($tipo_costo === 'una tantum') {
                $totale += $prezzo;
            }
        }

        // ---------------- SERVIZI EXTRA ----------------
        foreach ($test['extra_services'] ?? [] as $service) {
            $tipo_costo = $service['tipo_costo'] ?? null;
            $prezzo = (float) ($service['prezzo'] ?? 0);
            $scorpora = (bool) ($service['scorpora_servizio'] ?? false);
            $quantita = (int) ($service['quantita'] ?? 1);
            $quantita_persona = (int) ($service['quantita_a_persona'] ?? 1);

            if ($scorpora) {
                continue;
            }

            if ($tipo_costo === 'a_persona') {
                $totale += $prezzo * $quantita_persona * $personeTot;
            } elseif ($tipo_costo === 'una_tantum') {
                $totale += $prezzo * $quantita;
            }
        }

        $markup = (float) ($test['markup'] ?? 0);


        $quota_individuale = ($paganti > 0 ? ($totale / $paganti) : 0) + $markup;


        $quota_individuale = round($quota_individuale);
        $totale_incasso = $quota_individuale * $paganti;

        return [$quota_individuale, $totale_incasso];
    }

    public static function aggiornaQuoteLive(callable $set, callable $get): void
    {
        try {
            $data = $get(); // prendo tutto lo stato del form (non triggera validazione)
            [$quota, $tot] = self::calcolaCostoPerPersona($data);

            $set('prezzo_per_persona', $quota);
            $set('totale_incasso', $tot);
        } catch (\Throwable $e) {
            \Log::error('Errore in aggiornaQuoteLive: ' . $e->getMessage());
        }
    }

}


function getIconsServiceReport(): array
{
    $basePath = public_path('icone');
    $options = [];

    foreach (File::directories($basePath) as $directory) {
        $category = basename($directory);
        $categoryLabel = ucfirst($category);

        foreach (File::files($directory) as $file) {
            if ($file->getExtension() === 'png') {
                $filename = ucfirst($file->getFilenameWithoutExtension());

                // chiave = valore = quello che salvi in DB
                $options[$categoryLabel][$filename] = $filename;
            }
        }
    }

    return $options;
}