<?php

namespace App\Models;

use App\Models\Email as ModelsEmail;
use App\PreventiveStatus;
use Egulias\EmailValidator\Warning\EmailTooLong;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Validation\Rules\Email;
use Illuminate\Support\Facades\Storage;

class Preventive extends Model
{
    protected $casts = [
        // Date e datetime
        'data_preventivo' => 'date',
        'data_inizio_viaggio' => 'date',
        'data_fine_viaggio' => 'date',
        'date_expiration' => 'date',

        // JSON
        'itinerario' => 'array',
        'immagini' => 'array',
        'files_pratica_accettata' => 'array',

        // ENUM personalizzato
        'stato' => PreventiveStatus::class,
    ];

    protected static function booted()
    {
        static::creating(function ($preventivo) {
            if (empty($preventivo->cod_alfa)) {
                $preventivo->cod_alfa = \Illuminate\Support\Str::random(8);
            }
            if (empty($preventivo->created_by)) {
                $preventivo->created_by = auth()->id();
            }
        });

        static::created(function ($preventivo) {
            if (empty($preventivo->numero)) {
                // Esempio base:
                $preventivo->numero = $preventivo->id;

                // Oppure formato personalizzato:
                // $preventivo->numero = now()->year . '-' . str_pad($preventivo->id, 5, '0', STR_PAD_LEFT);

                $preventivo->saveQuietly();
            }
        });
    }



    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function quote_request(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function hotel_preventives()
    {
        return $this->hasMany(HotelPreventive::class);
    }

    public function trasporto_andata()
    {
        return $this->hasOne(PreventiveTransport::class)->where('direzione_trasporto', 'andata');
    }
    public function trasporto_rientro()
    {
        return $this->hasOne(PreventiveTransport::class)->where('direzione_trasporto', 'rientro');
    }

    public function trasporto_intermedio()
    {
        return $this->hasMany(PreventiveTransport::class)->where('direzione_trasporto', 'intermedio');
    }

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }


    public function extra_services()
    {
        return $this->hasMany(PreventiveExtraService::class);
    }

    public function emails()
    {
        return $this->belongsToMany(ModelsEmail::class, 'email_preventive');
    }


    public function getNumeroGiorni()
    {
        if (!$this->data_inizio_viaggio || !$this->data_fine_viaggio) {
            return;
        }

        $inizio = Carbon::parse($this->data_inizio_viaggio);
        $fine = Carbon::parse($this->data_fine_viaggio);

        return $inizio->diffInDays($fine) + 1;
    }

    public function getNumeroNotti()
    {
        if (!$this->data_inizio_viaggio || !$this->data_fine_viaggio) {
            return;
        }

        $inizio = Carbon::parse($this->data_inizio_viaggio);
        $fine = Carbon::parse($this->data_fine_viaggio);

        return $inizio->diffInDays($fine);
    }

    public function getNumeroPaganti()
    {
        if (!$this->numero_persone) {
            return;
        }

        $count_paganti = $this->numero_persone - $this->numero_gratuita;


        return $count_paganti;
    }


    public function getNumeroGratuita()
    {
        if (!$this->numero_gratuita) {
            return;
        }

        $gratuita = $this->numero_gratuita;


        return $gratuita;
    }



    public function duplicateWithRelations(): Preventive
    {
        $new = $this->replicate();
        $new->numero = null;
        $new->created_at = now();
        $new->data_preventivo = now();
        $new->cod_alfa = \Illuminate\Support\Str::random(8);
        $new->stato = PreventiveStatus::BOZZA;
        $new->created_by = auth()->id();
        $new->updated_at = now();


        $new->save();

        // Duplica hotels
        if (!empty($this->hotel_preventives)) {
            foreach ($this->hotel_preventives as $hotel) {
                $newHotelPrev = $hotel->replicate();
                $newHotelPrev->preventive_id = $new->id;
                $newHotelPrev->save();

                // Unisci entrambe le relazioni in un'unica collezione
                $stanze = $hotel->rooms_paganti->merge($hotel->rooms_gratuite);

                foreach ($stanze as $room) {
                    $newRoom = $room->replicate();
                    $newRoom->hotel_preventive_id = $newHotelPrev->id;
                    $newRoom->save();
                }
            }
        }


        //duplica trasporti
        if (!empty($this->trasporto_andata)) {
            $newAndata = $this->trasporto_andata->replicate();
            $newAndata->preventive_id = $new->id;
            $newAndata->save();
        }

        if (!empty($this->trasporto_rientro)) {
            $newRientro = $this->trasporto_rientro->replicate();
            $newRientro->preventive_id = $new->id;
            ;
            $newRientro->save();
        }

        if (!empty($this->trasporto_intermedio)) {
            foreach ($this->trasporto_intermedio as $intermedio) {
                $newIntermedio = $intermedio->replicate();
                $newIntermedio->preventive_id = $new->id;
                ;
                $newIntermedio->save();
            }
        }


        if (!empty($this->extra_services)) {
            foreach ($this->extra_services as $service) {
                $newService = $service->replicate();
                $newService->preventive_id = $new->id;
                ;
                $newService->save();
            }
        }

        $new->emails()->detach();

        return $new;
    }


    public function toPdfArray(): array
    {
        $this->loadMissing([
            'trasporto_andata.transport',
            'trasporto_andata.transport_company',
            'trasporto_rientro.transport',
            'trasporto_rientro.transport_company',
            'trasporto_intermedio.transport',
            'trasporto_intermedio.transport_company',
            'hotel_preventives.hotel',
            'hotel_preventives.rooms_paganti',
            'hotel_preventives.rooms_gratuite',
            'extra_services.extra_service',
            'creator',
            'customer',
        ]);
        $genere_cliente = null;

        $customer = $this->customer;

        if ($customer->tipo_cliente === 'privato') {
            $cliente = trim($customer->nome . ' ' . $customer->cognome);
            $tipo_cliente = $customer->tipo_cliente;
            $genere_cliente = $customer->genere;
        } else {
            $cliente = $customer->nome;
            $tipo_cliente = $customer->tipo_cliente;
        }






        // ---- ITINERARIO ----
        $rawItinerario = $this->itinerario ?? $this->itinerary->itinerario ?? [];

        // Se è una stringa JSON, decodificala
        if (is_string($rawItinerario)) {
            $decoded = json_decode($rawItinerario, true);
            $rawItinerario = is_array($decoded) ? $decoded : [];
        }

        // Se è un array associativo con chiavi UUID, prendi solo i valori
        if (array_is_list($rawItinerario) === false) {
            $rawItinerario = array_values($rawItinerario);
        }

        // Se è un singolo oggetto (non array di oggetti), wrappalo
        if (!isset($rawItinerario[0]) && !empty($rawItinerario)) {
            $rawItinerario = [$rawItinerario];
        }

        $itinerarioData = collect($rawItinerario)
            ->map(function ($step) {
                return [
                    'titolo' => $step['titolo'] ?? '',
                    'descrizione' => $step['descrizione'] ?? '',
                    'immagini' => collect($step['immagini'] ?? [])
                        ->map(function ($img) {
                            $path = storage_path('app/public/' . ltrim($img, '/'));
                            if (!file_exists($path)) {
                                return null;
                            }
                            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
                            $data = file_get_contents($path);
                            return 'data:image/' . $ext . ';base64,' . base64_encode($data);
                        })
                        ->filter()
                        ->values()
                        ->toArray(),
                ];
            })
            ->values()
            ->toArray();



        // ---- Hotels ----
        $hotelsData = [];
        $quoteHotelComprende = [];
        $quoteHotelNonComprende = [];

        $quoteServiziComprende = [];
        $quoteServiziNonComprende = [];

        foreach ($this->hotel_preventives as $hp) {
            $summary = [];

            // unisci paganti + gratuite in un'unica collezione
            $allRooms = $hp->rooms_paganti->merge($hp->rooms_gratuite);

            // raggruppa per tipologia stanza
            foreach ($allRooms->groupBy('tipologia_stanza') as $tipo => $stanze) {

                $summary[] = [
                    'tipologia_stanza' => ucfirst($tipo),
                    'num_camere' => $stanze->sum('quantita_camere'), // somma reale camere
                    'num_persone_paganti' => $stanze->sum('numero_paganti_stanza'), // somma reale persone
                    'num_persone_gratuita' => $stanze->sum('numero_gratuita_stanza'), // somma reale persone
                    'paganti' => $stanze->where('gratuita', false)->count(),
                    'gratuite' => $stanze->where('gratuita', true)->count(),
                ];
            }


            $stelle = null;
            if (!empty($hp->hotel->stelle) && (int) $hp->hotel->stelle > 0) {
                $num = (int) $hp->hotel->stelle;
                $file = $num . ($num === 1 ? '-stella.png' : '-stelle.png');
                $stelle = $this->imageToBase64(public_path("icone/stars/{$file}"));
            }

            $hotelsData[] = [
                'id' => $hp->id,
                'stelle' => $stelle,
                'nome' => $hp->hotel->nome,
                'descrizione' => $hp->hotel->descrizione,
                'indirizzo' => $hp->hotel->indirizzo,
                'quota_comprende_hotel' => $hp->quota_comprende_hotel,
                'quota_non_comprende_hotel' => $hp->quota_non_comprende_hotel,
                'note' => $hp->note,
                'foto' => collect($hp->hotel->foto ?? [])
                    ->map(fn($img) => $this->imageToBase64(storage_path('app/public/' . $img)))
                    ->filter()
                    ->toArray(),
                'rooms' => $summary,
            ];

            if (!empty($hp->quota_comprende_hotel)) {
                $quoteHotelComprende[] = $hp->quota_comprende_hotel;
            }

            if (!empty($hp->quota_non_comprende_hotel)) {
                $quoteHotelNonComprende[] = $hp->quota_non_comprende_hotel;
            }
        }

        // ---- Trasporti intermedi ----
        //test


        $quoteAndataComprende = [];
        $quoteAndataNonComprende = [];
        $quoteRientroComprende = [];
        $quoteRientroNonComprende = [];

        if ($this->trasporto_andata) {
            if (!empty($this->trasporto_andata->quota_comprende_trasporti)) {
                $quoteAndataComprende[] = $this->trasporto_andata->quota_comprende_trasporti;
            }
            if (!empty($this->trasporto_andata->quota_non_comprende_trasporti)) {
                $quoteAndataNonComprende[] = $this->trasporto_andata->quota_non_comprende_trasporti;
            }
        }

        if ($this->trasporto_rientro) {
            if (!empty($this->trasporto_rientro->quota_comprende_trasporti)) {
                $quoteRientroComprende[] = $this->trasporto_rientro->quota_comprende_trasporti;
            }
            if (!empty($this->trasporto_rientro->quota_non_comprende_trasporti)) {
                $quoteRientroNonComprende[] = $this->trasporto_rientro->quota_non_comprende_trasporti;
            }
        }

        $trasportiIntermedi = [];
        $quoteTrasportoIntermedioComprende = [];
        $quoteTrasportoIntermedioNonComprende = [];

        foreach ($this->trasporto_intermedio as $ti) {
            $trasportiIntermedi[] = [
                'id' => $ti->id,
                'tipo' => $ti->tipo_trasporto,
                'azienda' => $ti->transport_company?->nome,
                'foto' => $ti->transport?->foto
                    ? $this->imageToBase64(storage_path('app/public/' . $ti->transport->foto))
                    : $this->imageToBase64(storage_path('app/public/' . $ti->transport_company?->immagine)),
                'kg_bg_a_mano' => $ti->kg_bg_a_mano,
                'kg_bg_in_stiva' => $ti->kg_bg_in_stiva,
                'misura_bg_a_mano' => $ti->misura_bg_a_mano,
                'prezzo' => $ti->prezzo,
                'quota_comprende' => $ti->quota_comprende_trasporti,
                'quota_non_comprende' => $ti->quota_non_comprende_trasporti,
                'note' => $ti->note,

            ];

            if (!empty($ti->quota_comprende_trasporti)) {
                $quoteTrasportoIntermedioComprende[] = $ti->quota_comprende_trasporti;
            }
            if (!empty($ti->quota_non_comprende_trasporti)) {
                $quoteTrasportoIntermedioNonComprende[] = $ti->quota_non_comprende_trasporti;
            }
        }

        // ---- Servizi extra ----
        $servicesData = [];
        foreach ($this->extra_services as $service) {
            $tipoServizio = $service->extra_service?->tipo;
            $nomeServizio = $service->extra_service?->nome;
            $iconName = $service->extra_service?->tipo;

            $iconPath = null;

            if ($iconName) {
                $files = File::allFiles(public_path('icone'));
                $file = collect($files)->first(
                    fn($f) => strtolower($f->getFilenameWithoutExtension()) === strtolower($iconName)
                );

                if ($file) {
                    $iconPath = $this->imageToBase64($file->getPathname()); //  NON il path, ma il base64
                }
            }
            $allegati = [];
            if (!empty($service->extra_service?->allegati)) {
                foreach ($service->extra_service->allegati as $file) {
                    $cleanPath = str_replace('storage/', '', ltrim($file, '/'));
                    $url = Storage::url($cleanPath);
                    // Escape HTML e caratteri speciali
                    $allegati[] = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
                }
            }


            $servicesData[] = [
                'tipo' => ucwords(str_replace('_', ' ', $tipoServizio)),
                'nome' => ucwords(str_replace('_', ' ', $nomeServizio)),
                'descrizione' => $service->descrizione_servizio,
                'icona' => $iconPath,
                'allegati' => $allegati,
            ];

            if (!empty($service->quota_comprende_servizi)) {
                $quoteServiziComprende[] = $service->quota_comprende_servizi;
            }
            if (!empty($service->quota_non_comprende_servizi)) {
                $quoteServiziNonComprende[] = $service->quota_non_comprende_servizi;
            }
        }







        // --- QUOTE SCORPORATE ---
        $quote_scorporate = [];

        // Se almeno un trasporto o servizio extra è scorporato
        if (
            $this->trasporto_andata?->scorpora_trasporto ||
            $this->trasporto_rientro?->scorpora_trasporto ||
            $this->trasporto_intermedio?->contains(fn($t) => $t->scorpora_trasporto) ||
            $this->extra_services?->contains(fn($s) => $s->scorpora_servizio ?? false)
        ) {
            // Trasporto Andata
            if ($this->trasporto_andata && $this->trasporto_andata->scorpora_trasporto) {
                $quote_scorporate[] = [
                    'label' => 'Trasporto Andata',
                    'prezzo' => $this->trasporto_andata->prezzo ?? 0,
                ];
            }

            //  Trasporto Rientro
            if ($this->trasporto_rientro && $this->trasporto_rientro->scorpora_trasporto) {
                $quote_scorporate[] = [
                    'label' => 'Trasporto Rientro',
                    'prezzo' => $this->trasporto_rientro->prezzo ?? 0,
                ];
            }

            //  Trasporti intermedi
            foreach ($this->trasporto_intermedio as $ti) {
                if ($ti->scorpora_trasporto) {
                    $quote_scorporate[] = [
                        'label' => 'Trasporto Intermedio',
                        'prezzo' => $ti->prezzo ?? 0,
                    ];
                }
            }

            //  Servizi extra
            foreach ($this->extra_services as $service) {
                if ($service->scorpora_servizio ?? false) {
                    $quote_scorporate[] = [
                        'label' => 'Servizio extra: ' . ($service->extra_service?->nome ?? ''),
                        'prezzo' => $service->prezzo ?? 0,
                    ];
                }
            }
        }


        // --- NOTE COSTI SCORPORATI SEPARATE ---
        $note_scorporo_trasporti = null;
        $note_scorporo_servizi = null;

        //  Totale TRASPORTI scorporati
        $trasporti_scorporati = collect([
            $this->trasporto_andata,
            $this->trasporto_rientro,
        ])->filter(fn($t) => $t && $t->scorpora_trasporto);

        $trasporti_intermedi_scorporati = $this->trasporto_intermedio?->filter(fn($t) => $t->scorpora_trasporto) ?? collect();
        $trasporti_scorporati = $trasporti_scorporati->merge($trasporti_intermedi_scorporati);

        if ($trasporti_scorporati->isNotEmpty()) {
            $totale_trasporti = $trasporti_scorporati->sum('prezzo');
            $tipo_costo_trasporti = $trasporti_scorporati->contains(fn($t) => $t->tipo_costo === 'a persona')
                ? 'a persona'
                : 'una tantum';

            $importo_trasporti = number_format($totale_trasporti, 2, ',', '.');
            $note_scorporo_trasporti = "€ {$importo_trasporti} da pagare " .
                ($tipo_costo_trasporti === 'a persona' ? 'a persona' : 'complessivamente') .
                " per trasporti non inclusi nella quota di partecipazione.";
        }

        // Totale SERVIZI EXTRA scorporati
        $servizi_scorporati = $this->extra_services?->filter(fn($s) => $s->scorpora_servizio ?? false) ?? collect();

        if ($servizi_scorporati->isNotEmpty()) {
            $totale_servizi = $servizi_scorporati->sum('prezzo');
            $tipo_costo_servizi = $servizi_scorporati->contains(fn($s) => $s->tipo_costo === 'a persona')
                ? 'a persona'
                : 'una tantum';

            $importo_servizi = number_format($totale_servizi, 2, ',', '.');
            $note_scorporo_servizi = "€ {$importo_servizi} da pagare " .
                ($tipo_costo_servizi === 'a persona' ? 'a persona' : 'complessivamente') .
                " per servizi extra non inclusi nella quota di partecipazione.";
        }

        return [
            'logo' => $this->imageToBase64(public_path('pdf_images/logo.png')),
            'sole' => $this->imageToBase64(public_path('pdf_images/sole.png')),
            'test' => $this->imageToBase64(public_path('pdf_images/attivita educazione ambientale_tem.png')),
            'luna' => $this->imageToBase64(public_path('pdf_images/luna.png')),
            'gente' => $this->imageToBase64(public_path('pdf_images/paganti.png')),
            'pig' => $this->imageToBase64(public_path('pdf_images/pig.png')),
            'bus' => $this->imageToBase64(public_path('pdf_images/bus.png')),
            'treno' => $this->imageToBase64(public_path('pdf_images/treno.png')),
            'aereo' => $this->imageToBase64(public_path('pdf_images/aereo.png')),
            'nave' => $this->imageToBase64(public_path('pdf_images/nave.png')),
            'traghetto' => $this->imageToBase64(public_path('pdf_images/traghetto.png')),
            'assistenza_icon' => $this->imageToBase64(public_path('pdf_images/assistenza.png')),
            'store' => $this->imageToBase64(public_path('pdf_images/store.png')),
            'google' => $this->imageToBase64(public_path('pdf_images/google.png')),
            'icona' => $this->imageToBase64(public_path('pdf_images/icona.jpeg')),
            'link' => $this->imageToBase64(public_path('pdf_images/click.png')),
            'cod_alfa' => $this->cod_alfa,
            'numero' => $this->numero,
            'anno' => $this->anno,
            'gita_giornaliera' => $this->gita_giornaliera,
            'data_preventivo' => $this->data_preventivo,
            'cliente' => $cliente,
            'init' => $tipo_cliente,
            'genere' => $genere_cliente,
            /*  'foto_introduttiva' => $this->foto_introduttiva
                 ? Storage::url($this->foto_introduttiva)
                 : null, */
            'foto_introduttiva' => $this->foto_introduttiva
                ? $this->imageToBase64(storage_path('app/public/' . $this->foto_introduttiva))
                : null,
            /*  'immagini' => $this->immagini ?? [], */
            /*  'immagini' => collect($this->immagini ?? [])
                 ->map(fn($img) => $this->imageToBase64(storage_path('app/public/' . $img)))
                 ->filter()
                 ->toArray(), */

            'titolo' => $this->titolo,
            'notti' => $this->getNumeroNotti(),
            'giorni' => $this->getNumeroGiorni(),
            'gratuita' => $this->numero_gratuita,
            'paganti' => $this->getNumeroPaganti(),

            'andata' => [
                'data' => optional($this->trasporto_andata?->data_ora_partenza_andata)->format('d/m/Y'),
                'da' => $this->trasporto_andata?->luogo_di_partenza_andata,
                'a' => $this->trasporto_andata?->luogo_di_arrivo_andata,
                'ora_partenza' => optional($this->trasporto_andata?->data_ora_partenza_andata)->format('H:i'),
                'ora_arrivo' => optional($this->trasporto_andata?->data_ora_arrivo_andata)->format('H:i'),
            ],


            'rientro' => [
                'data' => optional($this->trasporto_rientro?->data_ora_partenza_rientro)->format('d/m/Y'),
                'da' => $this->trasporto_rientro?->luogo_di_partenza_rientro,
                'a' => $this->trasporto_rientro?->luogo_di_arrivo_rientro,
                'ora_partenza' => optional($this->trasporto_rientro?->data_ora_partenza_rientro)->format('H:i'),
                'ora_arrivo' => optional($this->trasporto_rientro?->data_ora_arrivo_rientro)->format('H:i'),
            ],


            'data_inizio_viaggio' => $this->data_inizio_viaggio,
            'data_fine_viaggio' => $this->data_fine_viaggio,

            'bagaglio_mano_andata' => $this->trasporto_andata?->kg_bg_a_mano,
            'bagaglio_stiva_andata' => $this->trasporto_andata?->kg_bg_in_stiva,
            'bagaglio_mano_rientro' => $this->trasporto_rientro?->kg_bg_a_mano,
            'bagaglio_stiva_rientro' => $this->trasporto_rientro?->kg_bg_in_stiva,
            'misura_bg_a_mano_andata' => $this->trasporto_andata?->misura_bg_a_mano,
            'misura_bg_a_mano_rientro' => $this->trasporto_rientro?->misura_bg_a_mano,
            'trasporto_andata_tipo' => $this->trasporto_andata?->tipo_trasporto,
            'trasporto_rientro_tipo' => $this->trasporto_rientro?->tipo_trasporto,

            'trasporto_andata_foto' => $this->trasporto_andata?->transport?->foto ? $this->imageToBase64(storage_path('app/public/' . $this->trasporto_andata?->transport?->foto)) : $this->imageToBase64(storage_path('app/public/' . $this->trasporto_andata?->transport_company?->immagine)),
            'trasporto_rientro_foto' => $this->trasporto_rientro?->transport?->foto ? $this->imageToBase64(storage_path('app/public/' . $this->trasporto_rientro?->transport?->foto)) : $this->imageToBase64(storage_path('app/public/' . $this->trasporto_rientro?->transport_company?->immagine)),
            'trasporto_company_andata' => $this->trasporto_andata?->transport_company?->nome,
            'trasporto_company_rientro' => $this->trasporto_rientro?->transport_company?->nome,

            'trasporti_intermedi' => $trasportiIntermedi,


            'itinerario' => $itinerarioData,




            'quote_hotel_comprende' => $quoteHotelComprende,
            'quote_hotel_non_comprende' => $quoteHotelNonComprende,

            'quota_comprende_generico' => $this->quota_comprende_generico,
            'quota_non_comprende_generico' => $this->quota_non_comprende_generico,


            'quote_andata_comprende' => $quoteAndataComprende,
            'quote_andata_non_comprende' => $quoteAndataNonComprende,
            'quote_rientro_comprende' => $quoteRientroComprende,
            'quote_rientro_non_comprende' => $quoteRientroNonComprende,
            'quote_intermedio_comprende' => $quoteTrasportoIntermedioComprende,
            'quote_intermedio_non_comprende' => $quoteTrasportoIntermedioNonComprende,

            'hotels' => $hotelsData,
            'servizi' => $servicesData,
            'quote_servizi_comprende' => $quoteServiziComprende,
            'quote_servizi_non_comprende' => $quoteServiziNonComprende,



            'prezzo_per_persona' => $this->prezzo_per_persona,
            'prezzo_forzato' => $this->prezzo_forzato,
            'scadenza' => $this->date_expiration,
            'creato_da' => $this->creator?->nome . ' ' . $this->creator?->cognome,
            'telefono' => $this->creator?->telefono,

            'quote_scorporate' => $quote_scorporate,
            'note_scorporo_trasporti' => $note_scorporo_trasporti,
            'note_scorporo_servizi' => $note_scorporo_servizi,
            'campo_attenzione' => $this->campo_attenzione,




            // es. note e riepiloghi statici: puoi adattarli se arrivano dal DB
            'voli_note' => [
                'Partenza: Milano Malpensa (MXP) – ore 10:15 – Compagnia: Aegeans Airlines',
                'Arrivo: Atene (ATH) – ore 13:40',
                "Incontro con l’autista e partenza in bus privato verso il Peloponneso.",
            ],

            'riepilogo' => [
                // qui potresti usare dati reali oppure lasciarli statici
            ],

            'non_compreso' => ['Bevande'],

            'note_finali' => [
                '*Attenzione: tutte le tariffe proposte, essendo contingentate, sono soggette a disponibilità...',
                'L’offerta è subordinata al raggiungimento di un numero minimo...',
            ],

            'operatore' => ['nome' => 'Sergio Colavecchia', 'contatto' => '3477559985'],

            'assistenza' => [
                'Assistenza in fase di realizzazione del viaggio...',
                'Assistenza Centrale operativa 24h su 24...',
                'Help-Line attiva 24h su 24 tutti i giorni...',
            ],

            'app' => ['nome' => 'La Bussola on the road', 'descrizione' => ''],

            'contatti' => [
                'ragione_sociale' => 'LA BUSSOLA srl – Agenzia viaggi e tour operator',
                'indirizzo' => 'Via Altaguardia, 1 – 20135 – Milano IT',
                'cf_pi' => 'Cod. Fisc. / P. IVA 08114120960',
                'rea_cap' => 'REA: MI – 2003676 – Capitale Sociale: € 10.000',
                'tel' => '+39 02 8219 6055',
                'wa' => '+39 02 8088 6574',
                'email' => 'preventivi@labussola.it',
                'pec' => 'labussolamilano@pec.it',
            ],
        ];
    }
    private function imageToBase64($path)
    {
        if (empty($path) || !file_exists($path) || !is_file($path)) {
            return null;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $data = file_get_contents($path);

        if ($ext === 'svg') {
            // Forza un colore se mancante
            if (!str_contains($data, 'fill=')) {
                $data = str_replace('<svg', '<svg fill="#000000"', $data); // nero di default
            }

            return 'data:image/svg+xml;base64,' . base64_encode($data);
        }

        return 'data:image/' . $ext . ';base64,' . base64_encode($data);
    }
}
