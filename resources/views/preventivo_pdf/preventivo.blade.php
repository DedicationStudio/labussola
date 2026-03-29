<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Preventivo La Bussola</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap" rel="stylesheet">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @vite(['resources/css/app.css', 'resources/js/app.js'])


    <style>
        @media print {
            .page-break {
                page-break-before: always;
                break-before: page;
            }

            .avoid-break {
                page-break-inside: avoid;
                break-inside: avoid;
            }

            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            @page {
                size: A4;

                margin: 10mm 0mm 0mm 0mm;
                /* margini in millimetri */
            }

            .pdf-header {
                /*position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 30mm;
                /* altezza effettiva del tuo header */

            }

            .pdf-content {
                /*margin-top: 35mm;
                /* leggermente > altezza header per non sovrapporre */
            }


            .page-break {
                page-break-before: always;
                break-before: page;

            }


            ul.list-disc ul {
                list-style-type: disc !important;
                margin-left: 1.5rem !important;
                padding-left: 1.5rem !important;
                display: block !important;
            }

            ul.list-disc li {
                margin-bottom: 0.25rem;
            }
        }

        @media screen {
            html {
                background-color: #e2e8f0;
            }

            body {

                background-color: white;
                margin: auto;
            }

        }
        
        /* Stili per il campo attenzione */
.campo-attenzione-content p,
.campo-attenzione-content li,
.campo-attenzione-content div {
    margin: 0 !important;
    padding: 0 !important;
    line-height: 1.5 !important;
}

.campo-attenzione-content ul,
.campo-attenzione-content ol {
    list-style-type: disc !important;
    margin: 0.5rem 0 !important;
    padding-left: 1.5rem !important;
    display: block !important;
}

.campo-attenzione-content li {
    margin-bottom: 0.25rem !important;
}

.campo-attenzione-content * {
    font-family: inherit !important;
}
        /* Stili per la descrizione dei servizi - sovrascrive qualsiasi formattazione copiata */
.servizio-content p,
.servizio-content li,
.servizio-content div {
    margin: 0 !important;
    padding: 0 !important;
    line-height: 1.5 !important;
}

.servizio-content ul,
.servizio-content ol {
    margin: 0.5rem 0 !important;
    padding-left: 1.5rem !important;
}

.servizio-content * {
    font-family: inherit !important;
}

.text{
text-align: left;
    width: 56%;
    margin: 0 5% 2% 40%;
}

@media (max-width: 768px) {
      .nascondi{
        display: none;
      }
    .text-mobile{
        text-align: left;
    width: 72%;
    margin: 0 5% 6% 26%;
    }
    }


    </style>

</head>

<body class="font-sans text-gray-900  leading-relaxed  w-full lg:max-w-6xl">
    <!-- HEADER -->
    <header class="flex justify-between items-center p-4 pdf-header">

        <div class="">
            <img src="{{ $preventivo['logo'] }}" alt="Logo La Bussola" class="h-12 lg:h-16">
        </div>
        <div class="text-right">
            <div class="font-semibold">Preventivo n.
                {{ $preventivo['numero'] }}/{{ $preventivo['anno'] }}{{-- 2138/2025 --}}</div>
            <div class="">del
                {{ \Carbon\Carbon::parse($preventivo['data_preventivo'])->format('d/m/Y') }}{{-- 08/05/2025 --}}</div>
            <p class="mt-2 hidden md:block">
                @if ($preventivo['init'] === 'privato')
                    @if ($preventivo['genere'] === 'donna')
                        Gent.ma <span class="font-semibold">{{ $preventivo['cliente'] }}</span>
                    @else
                        Gent.mo <span class="font-semibold">{{ $preventivo['cliente'] }}</span>
                    @endif
                @else
                    Spett.le <span class="font-semibold">{{ $preventivo['cliente'] }}</span>
                @endif
            </p>
            {{-- <p class="">Istituto Zaccaria</p> --}}
        </div>
    </header>
     <p class="mt-2 block md:hidden" style="    margin: 4%;">
                @if ($preventivo['init'] === 'privato')
                    @if ($preventivo['genere'] === 'donna')
                        Gent.ma <span class="font-semibold">{{ $preventivo['cliente'] }}</span>
                    @else
                        Gent.mo <span class="font-semibold">{{ $preventivo['cliente'] }}</span>
                    @endif
                @else
                    Spett.le <span class="font-semibold">{{ $preventivo['cliente'] }}</span>
                @endif
            </p>


    <main class="pdf-content">


        <section class="bg-cover bg-center w-full flex items-center min-h-120"
            style="background-image: url('{{ $preventivo['foto_introduttiva'] }}');">

            <div class=" p-8">

                <div class="bg-green-600/30 p-6 rounded-2xl text-white min-h-20 flex items-center justify-center">
                    <h2 class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-wide text-center"
                        style="font-family: 'Montserrat', sans-serif;">
                        {{ $preventivo['titolo'] }}</h2>
                </div>

            </div>
        </section>

        <section class="px-6 pt-6 pb-0">
            <div class="grid grid-cols-4 gap-4">
                <div class="text-center flex flex-col items-center">
                    <div class="text-3xl font-bold flex justify-center items-center text-[#8ebf22]">
                        <img src="{{ $preventivo['sole'] }}" class="h-7 mr-1 ">
                        {{ $preventivo['giorni'] }}
                    </div>
                    <div class="font-semibold">
                        @if ($preventivo['giorni'] > 1 || $preventivo['giorni'] == 0)
                            giorni
                        @else
                            giorno
                        @endif
                    </div>
                </div>
                <div class="text-center flex flex-col items-center">
                    <div class="text-3xl font-bold flex justify-center items-center text-[#8ebf22]">
                        <img src="{{ $preventivo['luna'] }}" class="h-7 mr-1 ">
                        {{ $preventivo['notti'] }}
                    </div>
                    <div class="font-semibold">
                        @if ($preventivo['notti'] > 1 || $preventivo['notti'] == 0)
                            notti
                        @else
                            notte
                        @endif
                    </div>
                </div>
                <div class="text-center flex flex-col items-center">
                    <div class="text-3xl font-bold flex justify-center items-center text-[#8ebf22]">
                        <img src="{{ $preventivo['gente'] }}" class="h-7 mr-1 ">
                        {{ $preventivo['paganti'] }}
                        <script>console.log({{ $preventivo['paganti'] }})</script>
                    </div>
                    <div class="font-semibold">
                        @if ($preventivo['paganti'] > 1 || $preventivo['paganti'] == 0)
                            paganti
                        @else
                            pagante
                        @endif
                    </div>
                </div>
                <div class="text-center flex flex-col items-center">
                    <div class="text-3xl font-bold flex justify-center items-center text-[#8ebf22]">
                        <img src="{{ $preventivo['pig'] }}" class="h-7 mr-1 ">
                        {{ $preventivo['gratuita'] }}
                    </div>
                    <div class="font-semibold">gratuità</div>
                </div>
            </div>
        </section>


        <section>
            <div class="md:flex  gap-4 p-6 mb-8">


                <div class="w-full md:w-2/3">
                    <div class="border-2 border-[#8ebf22] rounded-lg p-4 mb-4">

                        {{-- ANDATA --}}
                        <div class="flex items-center justify-between">

                            @if (!empty($preventivo['trasporto_andata_tipo']))

                                @if ($preventivo['trasporto_andata_tipo'] === 'bus')
                                    <h3 class="font-semibold">Andata in Bus -
                                        {{ $preventivo['andata']['data'] ?? '' }}
                                    </h3>
                                    <span>
                                        <img src="{{ $preventivo['bus'] }}" class="h-4 mr-1 ">
                                    </span>
                                @elseif ($preventivo['trasporto_andata_tipo'] === 'aereo')
                                    <h3 class="font-semibold">Andata in Aereo -
                                        {{ $preventivo['andata']['data'] ?? '' }}
                                    </h3>
                                    <span>
                                        <img src="{{ $preventivo['aereo'] }}" class="h-4 mr-1 ">
                                    </span>
                                @elseif ($preventivo['trasporto_andata_tipo'] === 'treno')
                                    <h3 class="font-semibold">Andata in Treno -
                                        {{ $preventivo['andata']['data'] ?? '' }}
                                    </h3>
                                    <span>
                                        <img src="{{ $preventivo['treno'] }}" class="h-4 mr-1 ">
                                    </span>
                                     @elseif ($preventivo['trasporto_andata_tipo'] === 'nave')
                                    <h3 class="font-semibold">Andata in Nave -
                                        {{ $preventivo['andata']['data'] ?? '' }}
                                    </h3>
                                    <span>
                                        <img src="{{ $preventivo['nave'] }}" class="h-4 mr-1 ">
                                    </span>
                                     @elseif ($preventivo['trasporto_andata_tipo'] === 'traghetto')
                                    <h3 class="font-semibold">Andata in Traghetto -
                                        {{ $preventivo['andata']['data'] ?? '' }}
                                    </h3>
                                    <span>
                                        <img src="{{ $preventivo['traghetto'] }}" class="h-4 mr-1 ">
                                    </span>
                                @endif
                            @endif


                        </div>
                        <div class="flex justify-between border-b border-[#8ebf22] py-1">
                            <span>{{ $preventivo['andata']['da'] ?? '' }}</span>
                            <span>{{ $preventivo['andata']['ora_partenza'] ?? '' }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span>{{ $preventivo['andata']['a'] ?? '' }}</span>
                            <span>{{ $preventivo['andata']['ora_arrivo'] ?? '' }}</span>
                        </div>

                     <div class="flex items-center justify-between">
    <span>{{ $preventivo['trasporto_company_andata'] ? $preventivo['trasporto_company_andata'] : '' }}</span>

    @if (!empty($preventivo['trasporto_andata_foto']))
        <span class="w-28 h-10 flex items-center justify-center">
            <img src="{{ $preventivo['trasporto_andata_foto'] }}" class="max-h-10 max-w-full object-contain">
        </span>
    @endif
</div>

                        {{-- TRASPORTI INTERMEDI (non servono) --}}
                        @php
                            // Filtra gli eventuali elementi vuoti
                            $trasporti_intermedi_validi = array_filter(
                                $preventivo['trasporti_intermedi'] ?? [],
                                function ($ti) {
                                    return !empty($ti['tipo']) ||
                                        !empty($ti['azienda']) ||
                                        !empty($ti['prezzo']);
                                },
                            );
                        @endphp
                        @if (!empty($trasporti_intermedi_validi))
                            @foreach ($trasporti_intermedi_validi as $ti)
                                @if (!empty($ti))
                                    <div class="mt-6 border-t border-[#8ebf22] pt-2">
                                        <div class="flex items-center justify-between">

                                            @if (!empty($ti['tipo']))
                                                @if ($ti['tipo'] === 'bus')
                                                    <h3 class="font-semibold">Trasporto Intermedio in Bus</h3>
                                                    <span>
                                                        <img src="{{ $preventivo['bus'] }}" class="h-4 mr-1 ">
                                                    </span>
                                                @elseif ($ti['tipo'] === 'aereo')
                                                    <h3 class="font-semibold">Trasporto Intermedio in Aereo</h3>
                                                    <span>
                                                        <img src="{{ $preventivo['aereo'] }}" class="h-4 mr-1 ">
                                                    </span>
                                                @elseif ($ti['tipo'] === 'treno')
                                                    <h3 class="font-semibold">Trasporto Intermedio in Treno</h3>
                                                    <span>
                                                        <img src="{{ $preventivo['treno'] }}" class="h-4 mr-1 ">
                                                    </span>
                                                     @elseif ($ti['tipo'] === 'nave')
                                                    <h3 class="font-semibold">Trasporto Intermedio in Nave</h3>
                                                    <span>
                                                        <img src="{{ $preventivo['nave'] }}" class="h-4 mr-1 ">
                                                    </span>
                                                      @elseif ($ti['tipo'] === 'traghetto')
                                                    <h3 class="font-semibold">Trasporto Intermedio in Traghetto</h3>
                                                    <span>
                                                        <img src="{{ $preventivo['traghetto'] }}" class="h-4 mr-1 ">
                                                    </span>
                                                @endif
                                            @endif

                                        </div>
                                        <div class="flex justify-between border-b border-[#8ebf22] py-1 mt-2">
    <span>{{ $ti['azienda'] ? $ti['azienda'] : '' }}</span>
    @if (!empty($ti['foto']))
        <span class="w-16 h-8 flex items-center justify-center">
            <img src="{{ $ti['foto'] }}" class="max-h-6 max-w-full object-contain">
        </span>
    @endif
</div>
                                      
                                    </div>
                                @endif
                            @endforeach
                        @endif

                        {{-- RITORNO --}}
                        <div class="flex items-center justify-between mt-4">
                            {{-- specificare in bus o arereo o --}}
                            @if (!empty($preventivo['trasporto_rientro_tipo']))
                                @if ($preventivo['trasporto_rientro_tipo'] === 'bus')
                                    <h3 class="font-semibold">Ritorno in Bus -
                                        {{ $preventivo['rientro']['data'] ?? '' }}</h3>
                                    <span>
                                        <img src="{{ $preventivo['bus'] }}" class="h-4 mr-1 ">
                                    </span>
                                @elseif ($preventivo['trasporto_rientro_tipo'] === 'aereo')
                                    <h3 class="font-semibold">Ritorno in Aereo -
                                        {{ $preventivo['rientro']['data'] ?? '' }}</h3>
                                    <span>
                                        <img src="{{ $preventivo['aereo'] }}" class="h-4 mr-1 ">
                                    </span>
                                @elseif ($preventivo['trasporto_rientro_tipo'] === 'treno')
                                    <h3 class="font-semibold">Ritorno in Treno -
                                        {{ $preventivo['rientro']['data'] ?? '' }}</h3>
                                    <span>
                                        <img src="{{ $preventivo['treno'] }}" class="h-4 mr-1 ">
                                    </span>
                                    @elseif ($preventivo['trasporto_rientro_tipo'] === 'nave')
                                    <h3 class="font-semibold">Ritorno in Nave -
                                        {{ $preventivo['rientro']['data'] ?? '' }}</h3>
                                    <span>
                                        <img src="{{ $preventivo['nave'] }}" class="h-4 mr-1 ">
                                    </span>
                                    @elseif ($preventivo['trasporto_rientro_tipo'] === 'traghetto')
                                    <h3 class="font-semibold">Ritorno in Traghetto -
                                        {{ $preventivo['rientro']['data'] ?? '' }}</h3>
                                    <span>
                                        <img src="{{ $preventivo['traghetto'] }}" class="h-4 mr-1 ">
                                    </span>
                                @endif
                            @endif

                        </div>
                        <div class="flex justify-between border-b border-[#8ebf22] py-1">
                            <span>{{ $preventivo['rientro']['da'] ?? '' }}</span>
                            <span>{{ $preventivo['rientro']['ora_partenza'] ?? '' }}</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span>{{ $preventivo['rientro']['a'] ?? '' }}</span>
                            <span>{{ $preventivo['rientro']['ora_arrivo'] ?? '' }}</span>
                        </div>

                             <div class="flex items-center justify-between">
    <span>{{ $preventivo['trasporto_company_rientro'] ? $preventivo['trasporto_company_rientro'] : '' }}</span>

    @if (!empty($preventivo['trasporto_rientro_foto']))
        <span class="w-28 h-10 flex items-center justify-center">
            <img src="{{ $preventivo['trasporto_rientro_foto'] }}" class="max-h-10 max-w-full object-contain">
        </span>
    @endif
</div>
                           

                    </div>
                </div>
                <div class="w-full md:w-1/3">
    @if (
        $preventivo['bagaglio_mano_andata'] !== null ||
        $preventivo['bagaglio_mano_rientro'] !== null ||
        $preventivo['misura_bg_a_mano_andata'] !== null ||
        $preventivo['misura_bg_a_mano_rientro'] !== null ||
        $preventivo['bagaglio_stiva_andata'] !== null ||
        $preventivo['bagaglio_stiva_rientro'] !== null
    )

        {{-- BAGAGLIO A MANO + MISURE --}}
        <div class="p-4 bg-[#8ebf22]/30 mb-4 rounded-lg">
            <span class="font-semibold block mb-2">Bagaglio a mano</span>

            <table class="w-full text-sm">
                <thead>
                    <tr class="font-semibold border-b border-[#8ebf22]/50">
                        <th class="text-left py-1">Tratta</th>
                        <th class="text-left py-1">Peso</th>
                        <th class="text-left py-1">Misura</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Andata</td>
                        <td>{{ $preventivo['bagaglio_mano_andata'] ?? '-' }} Kg</td>
                        <td>{{ $preventivo['misura_bg_a_mano_andata'] ?? '-' }}</td>
                    </tr>
                    @php
                        $trasporti_intermedi_validi = array_filter(
                            $preventivo['trasporti_intermedi'] ?? [],
                            fn($ti) => !empty($ti['tipo']) || !empty($ti['azienda']) || !empty($ti['prezzo'])
                        );
                    @endphp
                    @if (!empty($trasporti_intermedi_validi))
                        @foreach ($trasporti_intermedi_validi as $ti)
                            @if (!empty($ti) && $ti['tipo'] === 'aereo')
                                <tr>
                                    <td>Intermedio</td>
                                    <td>{{ $ti['kg_bg_a_mano'] ?? '-' }} Kg</td>
                                    <td>{{ $ti['misura_bg_a_mano'] ?? '-' }}</td>
                                </tr>
                            @endif
                        @endforeach
                    @endif
                    <tr>
                        <td>Ritorno</td>
                        <td>{{ $preventivo['bagaglio_mano_rientro'] ?? '-' }} Kg</td>
                        <td>{{ $preventivo['misura_bg_a_mano_rientro'] ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
 @endif
        {{-- BAGAGLIO IN STIVA (stesso schema) --}}
        
        @if($preventivo['bagaglio_stiva_andata'] !== null && $preventivo['bagaglio_stiva_andata'] > 0 || $preventivo['bagaglio_stiva_rientro'] !== null && $preventivo['bagaglio_stiva_rientro'] > 0)
        <div class="p-4 bg-[#8ebf22]/30 rounded-lg">
            <span class="font-semibold block mb-2">Bagaglio in stiva</span>

            <table class="w-full text-sm">
                <thead>
                    <tr class="font-semibold border-b border-[#8ebf22]/50">
                        <th class="text-left py-1">Tratta</th>
                        <th class="text-left py-1">Peso</th>
                    </tr>
                </thead>
                <tbody>
                    @if($preventivo['bagaglio_stiva_andata'] > 0)
                    <tr>
                        <td>Andata</td>
                        <td>{{ $preventivo['bagaglio_stiva_andata'] ?? '-' }} Kg</td>
                    </tr>
                    @endif
                    @php
                        $trasporti_intermedi_validi = array_filter(
                            $preventivo['trasporti_intermedi'] ?? [],
                            fn($ti) => !empty($ti['tipo']) || !empty($ti['azienda']) || !empty($ti['prezzo'])
                        );
                    @endphp
                    @if (!empty($trasporti_intermedi_validi))
                        @foreach ($trasporti_intermedi_validi as $ti)
                            @if (!empty($ti) && $ti['tipo'] === 'aereo')
                                <tr>
                                    <td>Intermedio</td>
                                    <td>{{ $ti['kg_bg_in_stiva'] ?? '-' }} Kg</td>
                                    <td>{{ $ti['misura_bg_in_stiva'] ?? '-' }}</td>
                                </tr>
                            @endif
                        @endforeach
                    @endif
                                        @if($preventivo['bagaglio_stiva_rientro'] > 0)

                    <tr>
                        <td>Ritorno</td>
                        <td>{{ $preventivo['bagaglio_stiva_rientro'] ?? '-' }} Kg</td>
                    </tr>
                    @endif
                </tbody>
            </table>
        </div>
        @endif
   
</div>

            </div>
        </section>


        <div class="page-break"></div>
        <!-- PROGRAMMA VIAGGIO -->

        <section class="flex mb-6">
            <div class="w-1/5 md:w-1/3"></div>
            <div class="w-4/5 md:w-2/3 text-right pr-6 bg-[#385B9B] text-white font-semibold p-2">
                <h2 class="text-xl">Programma di viaggio</h2>
            </div>
        </section>
        
        @if (!empty($preventivo['itinerario']))
            @foreach ($preventivo['itinerario'] as $itinerario)
                <section class="mb-5">
                    <div class="w-2/3 bg-[#8ebf22]/30 font-semibold p-1 mb-4">
                        <h3 class="text-lg pl-6">{{ $itinerario['titolo'] }}{{-- {{ $itinerario['titolo'] }} --}}</h3>
                        <div class="w-1/3"></div>
                    </div>
                    <div class="pl-6 pr-6">
                        {!! $itinerario['descrizione'] !!}
                    </div>
                    @if (!empty($itinerario['immagini']))
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 px-6 mt-6">
                           @foreach (array_reverse($itinerario['immagini']) as $foto)
                                <div class="aspect-[4/3] overflow-hidden rounded-lg">
                                    <img loading="lazy"
                                        class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                                        src="{{ $foto }}" alt="">
                                </div>
                            @endforeach
                        </div>
                    @endif

                </section>
            @endforeach
        @endif


        {{-- 
        <section>
            <div class="grid grid-cols-2 gap-2 p-6 mb-8">
                @if (!empty($preventivo['immagini']))
                    @foreach ($preventivo['immagini'] as $immagine)
                        <div class="h-64">
                            <img class="w-full h-full rounded-lg object-cover" src="{{ $immagine }}"
                                alt="">
                        </div>
                    @endforeach
                @endif
            </div>
        </section> --}}




    @if ($preventivo['gita_giornaliera'] === 0)
        <div class="page-break"></div>
        <!-- SISTEMAZIONI -->

        <section class="flex mb-6 mt-10">    
            <div class="w-1/5 md:w-1/3"></div>
            <div class="w-4/5 md:w-2/3 text-right pr-6 bg-[#385B9B] text-white font-semibold p-2">
                <h2 class="text-xl">Sistemazioni</h2>
            </div>
        </section>
        @if (!empty($preventivo['hotels']))
            @foreach ($preventivo['hotels'] as $hotel)
                <section class="mb-5">
                    <div class="w-full bg-[#8ebf22]/30 font-semibold p-1 mb-4 flex items-center justify-between">
                        <h3 class="text-lg pl-6">{{ $hotel['nome'] }}</h3>
                        @if (!empty($hotel['stelle']))
                            <div class="pr-6"><img src="{{ $hotel['stelle'] }}" alt="stelle"></div>
                        @endif

                    </div>
                    <div class="px-6">
                        <div class="w-full mb-4 mt-4 ">
                            <div class="w-1/2">
                                <p class="font-semibold">{{ $hotel['indirizzo'] }}</p>
                            </div>

                        </div>
                    </div>
                    @if (!empty($hotel['foto']))
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 px-6 mt-6">
                            @foreach ($hotel['foto'] as $foto)
                                <div class="aspect-[4/3] overflow-hidden rounded-lg">
                                    <img loading="lazy"
                                        class="w-full h-full object-cover transition-transform duration-300 hover:scale-105"
                                        src="{{ $foto }}" alt="">
                                </div>
                            @endforeach
                        </div>
                    @endif


                    <div class="px-6 mb-4 mt-6">
                        <p>{!! $hotel['descrizione'] !!}</p>
                    </div>
                    <div class="px-6">
                        <div class="w-full border-t-[#8ebf22] border-t-2 p-1 py-4 flex items-center justify-between">
                            <div class="w-1/2">

                            </div>
                            <div class="pr-6">



                                @php
                                    // Filtra gli eventuali elementi vuoti
                                    $hotel_rooms = array_filter($hotel['rooms'] ?? [], function ($ti) {
                                        return !empty($ti['tipologia_stanza']) ||
                                            !empty($ti['num_camere']) ||
                                            !empty($ti['num_persone']) ||
                                            !empty($ti['gratuite']);
                                    });
                                @endphp
                                @if (!empty($hotel_rooms))
                                    @foreach ($hotel_rooms as $room)
                                        <div class="flex gap-5">
                                            @if ($room['paganti'] > 0 && !empty($room['num_persone_paganti']))
                                                <div
                                                    class="border border-[#8ebf22]/40 rounded-lg p-3 text-center bg-white shadow-sm">
                                                    <div class="text-lg font-semibold text-[#8ebf22] mb-1">
                                                        {{ $room['tipologia_stanza'] }}
                                                    </div>

                                                    @if (!empty($room['num_camere']))
                                                        <div class=""><strong>Camere:</strong>
                                                            {{ $room['num_camere'] }}</div>
                                                    @endif

                                                    @if (!empty($room['num_persone_paganti']))
                                                        <div class=""><strong>Persone:</strong>
                                                            {{ $room['num_persone_paganti'] }}</div>
                                                    @endif


                                                </div>
                                            @endif
                                    @endforeach
                                    @foreach ($hotel_rooms as $room)
                                        @if ($room['gratuite'] > 0 && !empty($room['num_persone_gratuita']))
                                            <div
                                                class="border border-[#8ebf22]/40 rounded-lg p-3 text-center bg-white shadow-sm">
                                                <div class="text-lg font-semibold text-[#8ebf22] mb-1">
                                                    {{ $room['tipologia_stanza'] }}
                                                </div>
                                                <div class=""><strong>Persone:</strong>
                                                    {{ $room['num_persone_gratuita'] }}</div>
                                                <div class="text-xs text-gray-600 font-semibold">(Gratuite
                                                    {{--  {{ $room['gratuite'] }} --}})
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                @endif
                            </div>
                </section>
            @endforeach
        @endif
@endif



        <div class="page-break"></div>
        <!-- SERVIZI & EXTRA -->

        <section class="flex mb-6">
            <div class="w-1/5 md:w-1/3"></div>
            <div class="w-4/5 md:w-2/3 text-right pr-6 bg-[#385B9B] text-white font-semibold p-2">
                <h2 class="text-xl">Servizi Extra</h2>
            </div>
        </section>

        <section class="mb-5">
    @if (!empty($preventivo['servizi']))
        @foreach ($preventivo['servizi'] as $servizio)
            <div class="w-2/3 bg-[#8ebf22]/30 font-semibold p-1 mb-4 mt-4">
                <h3 class="text-lg pl-6">
                    {{ $servizio['tipo'] }}
                </h3>
            </div>
            <div class="pl-5 mb-4">
                {{-- Icona + Nome (usando flexbox con items-start invece di items-center) --}}
                <div class="flex items-start gap-2 mb-1">
                    @if (!empty($servizio['icona']))
                        <img src="{{ $servizio['icona'] }}" alt="{{ $servizio['tipo'] }}"
                            class="h-5 w-auto flex-shrink-0 mt-0.5">
                    @endif
                    @if (!empty($servizio['nome']))
                        <p class="font-bold text-md m-0 leading-tight ml-4">
                            {!! $servizio['nome'] !!}
                        </p>
                    @else
                    <div class="servizio-content ml-3">
        {!! $servizio['descrizione'] !!}
    </div>
                       
                    @endif
                </div>

               {{-- Descrizione sotto --}}
@if (!empty($servizio['nome']))
    <div class="servizio-content pl-7 ml-3">
        {!! $servizio['descrizione'] !!}
    </div>
@endif

                {{-- Allegati sotto la descrizione --}}
                @if (!empty($servizio['allegati']))
                    @if($servizio['tipo'] === 'Polizza')
                        @foreach ($servizio['allegati'] as $url)
                            @php
                                $safeUrl = e($url);
                            @endphp
                            <div class="flex items-center gap-3 pl-10 my-1 mt-6">
                                <p class="m-0 text-gray-700">Per visionare le condizioni di polizza</p>
                                <a href="{{ $safeUrl }}" target="_blank" rel="noopener noreferrer"
                                    class="bg-[#8ebf22] hover:bg-[#76a51d] text-white font-semibold px-2 py-1 rounded-md transition-colors duration-200 shadow-sm">
                                    Clicchi qui
                                </a>
                            </div>
                        @endforeach
                    @else
                        @foreach ($servizio['allegati'] as $url)
                            @php
                                $safeUrl = e($url);
                            @endphp
                            <div class="flex items-center gap-3 pl-10 my-1">
                                <p class="m-0 text-gray-700">Per visionare</p>
                                <a href="{{ $safeUrl }}" target="_blank" rel="noopener noreferrer"
                                    class="bg-[#8ebf22] hover:bg-[#76a51d] text-white font-semibold px-2 py-1 rounded-md transition-colors duration-200 shadow-sm">
                                    Clicchi qui
                                </a>
                            </div>
                        @endforeach
                    @endif
                @endif
            </div>
        @endforeach
    @endif
</section>




        <div class="page-break"></div>
        <!-- RIEPILOGO E QUOTE -->

        <section class="flex mb-6">
            <div class="w-1/5 md:w-1/3"></div>
            <div class="w-4/5 md:w-2/3 text-right pr-6 bg-[#385B9B] text-white font-semibold p-2">
                <h2 class="text-xl">Riepilogo e quote</h2>
            </div>
        </section>

        <section class="mb-5">
            <div class="w-2/3 bg-[#8ebf22]/30 font-semibold p-1 mb-4">
                <h3 class="text-lg pl-6">La quota comprende</h3>
                <div class="w-1/3"></div>
            </div>
        </section>

        <section class="mb-5">
            <div class="flex items-center">
                <div class="w-6 h-10 bg-[#8ebf22]/30"></div>
                <div class="ml-6">
                    <ul class="list-disc pl-10">
                          @php
    $cleanHtml = function ($html) {
        return trim(preg_replace([
            '#<ul[^>]*>#i',         // rimuovi ul aperto
            '#</ul>#i',             // rimuovi ul chiuso
            '#<p><br></p>#i',       // elimina paragrafi vuoti
            '#<p>&nbsp;</p>#i',     // elimina paragrafi con spazio
            '#^\s*<br\s*/?>#i',     // elimina <br> all’inizio
            '#\s+$#',               // togli spazi finali
        ], '', $html));
    };
@endphp
                        {{-- HOTEL COMPRENDE --}}
                        @if (!empty($preventivo['quote_hotel_comprende']))
                            @foreach ($preventivo['quote_hotel_comprende'] as $qc)
                                {!! $cleanHtml($qc) !!}
                            @endforeach
                        @endif

                        {{-- ANDATA --}}
                        @if (!empty($preventivo['quote_andata_comprende']))
                            @foreach ($preventivo['quote_andata_comprende'] as $qc)
                                {!! $cleanHtml($qc) !!}
                            @endforeach
                        @endif

                        {{-- RIENTRO --}}
                        @if (!empty($preventivo['quote_rientro_comprende']))
                            @foreach ($preventivo['quote_rientro_comprende'] as $qc)
                                {!! $cleanHtml($qc) !!}
                            @endforeach
                        @endif

                        {{-- TRASPORTI INTERMEDI --}}
                        @if (!empty($preventivo['quote_intermedio_comprende']))
                            @foreach ($preventivo['quote_intermedio_comprende'] as $qc)
                                {!! $cleanHtml($qc) !!}
                            @endforeach
                        @endif

                        {{-- SERVIZI --}}
                        @if (!empty($preventivo['quote_servizi_comprende']))
                            @foreach ($preventivo['quote_servizi_comprende'] as $qc)
                                {!! $cleanHtml($qc) !!}
                            @endforeach
                        @endif

                        {{-- GENERICO --}}
                     @if (!empty($preventivo['quota_comprende_generico']))
    {!! $cleanHtml($preventivo['quota_comprende_generico']) !!}
@endif
                    </ul>
                </div>
            </div>
        </section>




        <section class="mb-5">
            <div class="w-2/3 bg-red-200 font-semibold p-1 mb-4">
                <h3 class="text-lg pl-6">La quota non comprende</h3>
                <div class="w-1/3"></div>
            </div>
        </section>

        <section class="mb-5">
            <div class="flex items-center">
                <div class="w-6 h-10 bg-red-200"></div>
                <div class="ml-6">
                    <ul class="list-disc pl-10">
                      @php
    $cleanHtml = function ($html) {
        return trim(preg_replace([
            '#<ul[^>]*>#i',         // rimuovi ul aperto
            '#</ul>#i',             // rimuovi ul chiuso
            '#<p><br></p>#i',       // elimina paragrafi vuoti
            '#<p>&nbsp;</p>#i',     // elimina paragrafi con spazio
            '#^\s*<br\s*/?>#i',     // elimina <br> all’inizio
            '#\s+$#',               // togli spazi finali
        ], '', $html));
    };
@endphp


                        @if (!empty($preventivo['quote_hotel_non_comprende']))
                            @foreach ($preventivo['quote_hotel_non_comprende'] as $qnc)
                                {!! $cleanHtml($qnc) !!}
                            @endforeach
                        @endif

                        @if (!empty($preventivo['quote_andata_non_comprende']))
                            @foreach ($preventivo['quote_andata_non_comprende'] as $qnc)
                                {!! $cleanHtml($qnc) !!}
                            @endforeach
                        @endif

                        {{-- TRASPORTO RIENTRO --}}
                        @if (!empty($preventivo['quote_rientro_non_comprende']))
                            @foreach ($preventivo['quote_rientro_non_comprende'] as $qnc)
                                {!! $cleanHtml($qnc) !!}
                            @endforeach
                        @endif

                        {{-- TRASPORTI INTERMEDI --}}
                        @if (!empty($preventivo['quote_intermedio_non_comprende']))
                            @foreach ($preventivo['quote_intermedio_non_comprende'] as $qnc)
                                {!! $cleanHtml($qnc) !!}
                            @endforeach
                        @endif
                     
                        @if (!empty($preventivo['quote_servizi_non_comprende']))
                            @foreach ($preventivo['quote_servizi_non_comprende'] as $qnc)
                                {!! $cleanHtml($qnc) !!}
                            @endforeach
                        @endif
                         @if (!empty($preventivo['quota_non_comprende_generico']))
    {!! $cleanHtml($preventivo['quota_non_comprende_generico']) !!}
@endif
                    </ul>


                </div>
            </div>
        </section>

        <section class="mb-8">
@if (!empty($preventivo['campo_attenzione']))
  <div class="text-xs italic text-gray-600 ml-12 w-9/12">
    <div class="flex">
      <span class="mr-1 flex-shrink-0">*</span>
      <div class="campo-attenzione-content">
        {!! $preventivo['campo_attenzione'] !!}
      </div>
    </div>
  </div>
@endif

            <div
                class="border-2 border-[#8ebf22] bg-[#8ebf22]/30 rounded-lg p-4 mt-4 mx-12 flex flex-col items-center justify-center text-center">
                <span class="font-semibold">Quota di partecipazione per persona</span>
                @if (empty($preventivo['prezzo_forzato']))
                    <span class="text-xl font-bold">€ {{ $preventivo['prezzo_per_persona'] }}</span>
                @else
                    <span class="text-xl font-bold">€ {{ $preventivo['prezzo_forzato'] }}</span>
                @endif
                {{--  @if (!empty($preventivo['quote_scorporate']))
    <div class="mt-4 mx-12 text-sm">
        <p class="font-semibold mb-2">Dettaglio quota individuale:</p>
        <ul class="list-disc pl-6">
            @foreach ($preventivo['quote_scorporate'] as $item)
                <li>
                    {{ $item['label'] }}:
                    <span class="font-semibold">€ {{ number_format($item['prezzo'], 2, ',', '.') }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif --}}
                @if (!empty($preventivo['note_scorporo_trasporti']))
                    <p class="mt-2 text-center text-sm italic text-gray-700">
                        {{ $preventivo['note_scorporo_trasporti'] }}
                    </p>
                @endif

                @if (!empty($preventivo['note_scorporo_servizi']))
                    <p class="text-center text-sm italic text-gray-700">
                        {{ $preventivo['note_scorporo_servizi'] }}
                    </p>
                @endif


            </div>

            <p class="mt-2  text-center">
                <span class="font-semibold">Offerta valida fino al:</span>
                {{ \Carbon\Carbon::parse($preventivo['scadenza'])->format('d/m/Y') }}
            </p>
            <p class="mt-2  text-center">Preventivo realizzato da <b>{{ $preventivo['creato_da'] }}</b> –
                contatto
                telefonico
                <b>{{ $preventivo['telefono'] }}</b>
            </p>
        </section>






        <div class="page-break"></div>
        <!-- CONTATTI -->

        <section class="flex mb-6">
            <div class="w-1/5 md:w-1/3"></div>
            <div class="w-4/5 md:w-2/3 text-right pr-6 bg-[#385B9B] text-white font-semibold p-2">
                <h2 class="text-xl">Assistenza prima, dopo e durante il viaggio</h2>
            </div>
        </section>
        <section class="flex mb-6">
            <div class="pl-6 md:w-1/3"></div>
            <div class="md:w-2/3 flex items-center justify-center">
                <img src="{{ $preventivo['assistenza_icon'] }}" alt="store" class="h-12 pr-6">
                <div class="pr-6">
                    <p><span class="font-semibold">Assistenza</span> in fase di realizzazione del viaggio e
                        itinerario
                        da parte del nostro personale qualificato.</p>
                    <p><span class="font-semibold">Assistenza Centrale operativa</span> 24h su 24 e 365 giorni
                        l’anno
                        per qualsiasi esigenza medica <span class="font-semibold">Help-Line attiva</span> 24h su 24
                        tutti i giorni da parte del nostro personale via telefono
                        e anche tramite <span class="font-semibold">app iOS e Android La Bussola on the road</span>
                    </p>
                </div>
            </div>
        </section>

        <section class="flex mb-6">
            <div class="w-1/5 md:w-1/3"></div>
            <div class="w-4/5 md:w-2/3 text-right pr-6 bg-[#385B9B] text-white font-semibold p-2">
                <h2 class="text-xl">Il tuo viaggio in un’unica app con 4Guest</h2>
            </div>
        </section>
        <div class="text-mobile text" >Scarica 4Guest e lasciati guidare lungo il tuo percorso, con informazioni, tappe e dettagli che rendono ogni esperienza di viaggio ancora più speciale.</div>
<section class="flex mb-6">
    <!-- Colonna sinistra: due store uno sotto l'altro -->
                 <div class="w-1/3"></div>
                 <div class="w-2/3 nascondi"></div>

    <div class="flex flex-col w-1/3 items-center justify-center">
        <a href="https://play.google.com/store/search?q=4guest&c=apps&hl=it">
            <img src="{{ $preventivo['google'] }}" alt="google" class="h-11 mb-2">
        </a>
        <a href="https://apps.apple.com/it/app/4guest/id6449675980">
            <img src="{{ $preventivo['store'] }}" alt="store" class="h-11">
        </a>
    </div>

    <!-- Colonna destra: icona affiancata -->
    <div class="w-1/3 flex items-center justify-center">
        <a href="https://apps.apple.com/it/app/4guest/id6449675980">
            <img src="{{ $preventivo['icona'] }}" alt="icona" class="h-18">
        </a>
    </div>
</section>

        <section class="flex">
            <div class="w-1/5 md:w-1/3"></div>
            <div class="w-4/5 md:w-2/3 text-right pr-6 bg-[#385B9B] text-white font-semibold p-2">
                <h2 class="text-xl">I nostri contatti</h2>
            </div>
        </section>

        <div class="text-right">

            <p class="p-6"><span class="font-semibold">LA BUSSOLA srl – Agenzia viaggi e tour
                    operator</span><br>
                Via Altaguardia,1 – 20135 – Milano IT<br>
                Cod. Fisc. / P. IVA 08114120960 – REA: MI – 2003676 – Capitale Sociale: € 10.000<br>
                TEL +39 02 8219 6055 – WA +39 02 8088 6574<br>
                EMAIL preventivi@labussola.it – PEC labussolamilano@pec.it</p>
        </div>

    </main>

</body>

</html>
