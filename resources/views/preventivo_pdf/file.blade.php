<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Preventivo La Bussola</title>
    <style>
        @page {
            margin: 0;
        }
        

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #333;
            padding-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td,
        th {
            vertical-align: top;
            padding: 4px;
        }

        .header {
            padding: 40px 20px;
        }

        .header td {
            font-size: 12px;
        }

        .title {
            background-color: #2b6cb0;
            color: #fff;
            font-weight: bold;
            text-align: right;
            padding: 6px;
            margin-top: 20px;
        }

        .section-title {
            background-color: #8ebf22;
            color: #000;
            font-weight: bold;
            padding: 4px;
            margin-top: 10px;
        }

        .box {
            border: 1px solid #8ebf22;
            margin: 20px;
            margin-bottom: 10px;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .mt-2 {
            margin-top: 10px;
        }

        .mb-2 {
            margin-bottom: 10px;
        }

        .page-break {
            page-break-before: avoid;
        }

        .page-break-always {
            page-break-before: always;
        }

        ul {
            margin: 0;
            padding-left: 18px;
        }
 .descrizione {
        font-weight: normal !important;
        font-size: 14px;
    }
    
    .descrizione * {
        font-weight: normal !important;
        font-size: 14px;
    }
        .foto-introduttiva {
            width: 100%;
            height: 560px;
            object-fit: cover;
            display: block;
        }

        .ul-comprende {
            margin: 0 !important;
            padding-left: 10px !important;
            /* sposta il puntino più a sinistra */
            list-style-position: inside !important;
        }

        .ul-comprende li {
            margin-bottom: 3px;
        }




        .container-left-transport {
            border-radius: 10px;
            overflow: hidden;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #e0efc0;
        }

        .no-full table {
            width: auto !important;
        }

        .no-full td {
            padding: 0 !important;
        }
    </style>
</head>

<body>

    <!-- HEADER -->
    <table class="header mb-2">
        <tr>
            <td>
                <img src="{{ $preventivo['logo'] }}" height="60">
            </td>
            <td class="text-right" style="font-size: 14px;">
                <div><b>Preventivo n. {{ $preventivo['numero'] }}/{{ $preventivo['anno'] }}</b></div>
                <div>del {{ \Carbon\Carbon::parse($preventivo['data_preventivo'])->format('d/m/Y') }}</div>
                <div class="mt-2">
                    @if ($preventivo['init'] === 'privato')
                        @if ($preventivo['genere'] === 'donna')
                            Gent.ma <b class="font-semibold">{{ $preventivo['cliente'] }}</b>
                        @else
                            Gent.mo <b class="font-semibold">{{ $preventivo['cliente'] }}</b>
                        @endif
                    @else
                        Spett.le <b class="font-semibold">{{ $preventivo['cliente'] }}</b>
                    @endif

                </div>
            </td>
        </tr>
    </table>

    <!-- FOTO INTRODUTTIVA -->


    <div
        style=" position: relative;
            width: 100%;
            overflow: hidden;
            margin: 0;
            padding: 0px;">
        <img src="{{ $preventivo['foto_introduttiva'] }}" class="foto-introduttiva">
        <div
            style="position: absolute;
           top: 20%;
           left: 20%;
           transform: translate(-30%, -70%);
           background-color: rgba(31, 130, 135, 0.3);
           color: #ffffff;
           border-radius: 15px;
           padding: 20px 30px;
           text-align: center;
           width: auto;
           display: inline-block;">
            <h2
                style="margin: 0;
               font-size: 24px;
               font-weight: bold;
               font-family: 'Liberation Serif', 'DejaVu Serif', serif;">
                {{ $preventivo['titolo'] }}
            </h2>
        </div>



    </div>


    <!-- INFO GIORNI/NOTTI -->

    <table class="mb-2" style="width:100%; border-collapse:collapse; text-align:center; margin-top:-70px;">
        <tr>
            <td>
                <div style="display:flex; align-items:center; justify-content:center;">
                    <img src="{{ $preventivo['sole'] }}" style="height:25px; margin:0; padding:0;">
                    <span style="font-size:30px; font-weight:bold; color:#8ebf22; margin-left:5px;">
                        {{ $preventivo['giorni'] }}
                    </span>
                </div>
                <div style="font-weight:bold;">
                    @if ($preventivo['giorni'] > 1 || $preventivo['giorni'] == 0)
                        giorni
                    @else
                        giorno
                    @endif
                </div>
            </td>
            <td>
                <div style="display:flex; align-items:center; justify-content:center;">
                    <img src="{{ $preventivo['luna'] }}" style="height:25px; margin:0; padding:0;">
                    <span style="font-size:30px; font-weight:bold; color:#8ebf22; margin-left:5px;">
                        {{ $preventivo['notti'] }}
                    </span>
                </div>
                <div style="font-weight:bold;">
                    @if ($preventivo['notti'] > 1 || $preventivo['notti'] == 0)
                        notti
                    @else
                        notte
                    @endif
                </div>
            </td>
            <td>
                <div style="display:flex; align-items:center; justify-content:center;">
                    <img src="{{ $preventivo['gente'] }}" style="height:25px; margin:0; padding:0;">
                    <span style="font-size:30px; font-weight:bold; color:#8ebf22; margin-left:5px;">
                        {{ $preventivo['paganti'] }}
                    </span>
                </div>
                <div style="font-weight:bold;">
                    @if ($preventivo['paganti'] > 1 || $preventivo['paganti'] == 0)
                        paganti
                    @else
                        pagante
                    @endif
                </div>
            </td>
            <td>
                <div style="display:flex; align-items:center; justify-content:center;">
                    <img src="{{ $preventivo['pig'] }}" style="height:25px; margin:0; padding:0;">
                    <span style="font-size:30px; font-weight:bold; color:#8ebf22; margin-left:5px;">
                        {{ $preventivo['gratuita'] }}
                    </span>
                </div>
                <div style="font-weight:bold;">gratuità</div>
            </td>
        </tr>
    </table>


    <!-- SEZIONE TRASPORTO -->
    <table width="100%" cellpadding="10" cellspacing="0"
        style="width:calc(100% - 40px); margin:0px 20px; border-collapse:collapse;">
        <tr>
            @if (
                $preventivo['bagaglio_mano_andata'] !== null ||
                    $preventivo['bagaglio_stiva_andata'] !== null ||
                    $preventivo['bagaglio_mano_rientro'] !== null ||
                    $preventivo['bagaglio_stiva_rientro'] !== null ||
                    $preventivo['misura_bg_a_mano_andata'] !== null ||
                    $preventivo['misura_bg_a_mano_rientro'] !== null)
                <!-- Colonna bagagli -->
               <td width="30%" valign="top" style="font-size:12px;">
    {{-- BAGAGLIO A MANO --}}
    <div class="container-left-transport">
        <b>Bagaglio a mano</b><br>

        <table width="100%" border="0" cellspacing="0" cellpadding="2" style="font-size:12px; border-collapse: collapse;">
            <thead>
                <tr style="font-weight:bold; border-bottom:1px solid #ccc;">
                    <th align="left">Tratta</th>
                    <th align="left">Peso</th>
                    <th align="left">Misura</th>
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

    {{-- BAGAGLIO IN STIVA --}}
        @if($preventivo['bagaglio_stiva_andata'] !== null && $preventivo['bagaglio_stiva_andata'] > 0 || $preventivo['bagaglio_stiva_rientro'] !== null && $preventivo['bagaglio_stiva_rientro'] > 0)

    <div class="container-left-transport" style="margin-top:10px;">
        <b>Bagaglio in stiva</b><br>

        <table width="100%" border="0" cellspacing="0" cellpadding="2" style="font-size:12px; border-collapse: collapse;">
            <thead>
                <tr style="font-weight:bold; border-bottom:1px solid #ccc;">
                    <th align="left">Tratta</th>
                    <th align="left">Peso</th>
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
</td>

            @else
                <!-- Colonna bagagli vuota -->
                <td width="30%"></td>
            @endif
            <!-- Colonna trasporto -->
            <td width="70%" valign="top">
                <div style="border:2px solid #8ebf22; border-radius:10px; padding:10px;">
                    <table width="100%" cellpadding="6" cellspacing="0">
                        <!-- Andata -->
                        <tr>
                            <td colspan="2" style="font-weight:bold;">


                                @if (!empty($preventivo['trasporto_andata_tipo']))
                                    @if ($preventivo['trasporto_andata_tipo'] === 'bus')
                                        Andata in Bus - {{ $preventivo['andata']['data'] ?? '' }}
                                        <img src="{{ $preventivo['bus'] }}" style="height:15px; float:right;">
                                    @elseif ($preventivo['trasporto_andata_tipo'] === 'aereo')
                                        Andata in Aereo - {{ $preventivo['andata']['data'] ?? '' }}
                                        <img src="{{ $preventivo['aereo'] }}" style="height:15px; float:right;">
                                    @elseif ($preventivo['trasporto_andata_tipo'] === 'treno')
                                        Andata in Treno - {{ $preventivo['andata']['data'] ?? '' }}
                                        <img src="{{ $preventivo['treno'] }}" style="height:15px; float:right;">
                                        @elseif ($preventivo['trasporto_andata_tipo'] === 'nave')
                                        Andata in Nave - {{ $preventivo['andata']['data'] ?? '' }}
                                        <img src="{{ $preventivo['nave'] }}" style="height:15px; float:right;">
                                        @elseif ($preventivo['trasporto_andata_tipo'] === 'traghetto')
                                        Andata in Traghetto - {{ $preventivo['andata']['data'] ?? '' }}
                                        <img src="{{ $preventivo['traghetto'] }}" style="height:15px; float:right;">
                                    @endif
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td>{{ $preventivo['andata']['da'] ?? '' }}</td>
                            <td align="right">{{ $preventivo['andata']['ora_partenza'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding:0;">
                                <div style="border-bottom:1px solid #8ebf22;"></div>
                            </td>
                        </tr>
                        <tr>
                            <td>{{ $preventivo['andata']['a'] ?? '' }}</td>
                            <td align="right">{{ $preventivo['andata']['ora_arrivo'] ?? '' }}</td>
                        </tr>
                      
                         @if (!empty($preventivo['trasporto_company_andata']))
    <tr>
        <td colspan="2" style="position: relative; padding-bottom:20px;">
            {{ $preventivo['trasporto_company_andata'] }}

            @if (!empty($preventivo['trasporto_andata_foto']))
                <img src="{{ $preventivo['trasporto_andata_foto'] }}" 
                     height="30" 
                     width="94"
                     style="float: right; max-height: 30px; max-width: 94px;">
            @endif
        </td>
    </tr>
@endif

                        <!-- Trasporti intermedi -->
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
                                    <tr>
                                        <td colspan="2" style="padding-top:20px;">
                                            <div style="border-bottom:1px solid #8ebf22;"></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="2" style="font-weight:bold; padding-top:10px;">


                                            @if (!empty($ti['tipo']))
                                                @if ($ti['tipo'] === 'bus')
                                                    Trasporto intermedio in Bus
                                                    <img src="{{ $preventivo['bus'] }}"
                                                        style="height:15px; float:right;">
                                                @elseif ($ti['tipo'] === 'treno')
                                                    Trasporto intermedio in Treno
                                                    <img src="{{ $preventivo['treno'] }}"
                                                        style="height:15px; float:right;">
                                                @elseif ($ti['tipo'] === 'aereo')
                                                    Trasporto intermedio in Aereo
                                                    <img src="{{ $preventivo['aereo'] }}"
                                                        style="height:15px; float:right;">
                                                @endif
                                            @endif


                                        </td>
                                    </tr>
                                    @if (!empty($ti['azienda']))
                                        <tr>
                                            <td colspan="2">
                                                {{ $ti['azienda'] }}


                                                @if (!empty($ti['foto']))
                                                    <img src="{{ $ti['foto'] }}" height="22" style="float:right;">
                                                @endif
                                            </td>

                                        </tr>
                                    @endif

                                 
                                    <tr>
                                        <td colspan="2">
                                            <div style="border-bottom:1px solid #8ebf22;"></div>
                                        </td>
                                    </tr>
                                @endif
                            @endforeach
                        @endif

                        <!-- Ritorno -->
                        <tr>
                            <td colspan="2" style="font-weight:bold;">
                                <div style="margin-top: 10px">
                                    @if (!empty($preventivo['trasporto_rientro_tipo']))
                                        @if ($preventivo['trasporto_rientro_tipo'] === 'bus')
                                            Ritorno in Bus - {{ $preventivo['rientro']['data'] ?? '' }}
                                            <img src="{{ $preventivo['bus'] }}" style="height:15px; float:right;">
                                        @elseif ($preventivo['trasporto_rientro_tipo'] === 'aereo')
                                            Ritorno in Aereo - {{ $preventivo['rientro']['data'] ?? '' }}
                                            <img src="{{ $preventivo['aereo'] }}" style="height:15px; float:right;">
                                        @elseif ($preventivo['trasporto_rientro_tipo'] === 'treno')
                                            Ritorno in Treno - {{ $preventivo['rientro']['data'] ?? '' }}
                                            <img src="{{ $preventivo['treno'] }}" style="height:15px; float:right;">
                                             @elseif ($preventivo['trasporto_rientro_tipo'] === 'nave')
                                            Ritorno in Nave - {{ $preventivo['rientro']['data'] ?? '' }}
                                            <img src="{{ $preventivo['nave'] }}" style="height:15px; float:right;">
                                             @elseif ($preventivo['trasporto_rientro_tipo'] === 'traghetto')
                                            Ritorno in Traghetto - {{ $preventivo['rientro']['data'] ?? '' }}
                                            <img src="{{ $preventivo['traghetto'] }}" style="height:15px; float:right;">
                                        @endif
                                    @endif
                                </div>

                            </td>
                        </tr>
                        <tr>
                            <td>{{ $preventivo['rientro']['da'] ?? '' }}</td>
                            <td align="right">{{ $preventivo['rientro']['ora_partenza'] ?? '' }}</td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding:0;">
                                <div style="border-bottom:1px solid #8ebf22;"></div>
                            </td>
                        </tr>
                        <tr>
                            <td>{{ $preventivo['rientro']['a'] ?? '' }}</td>
                            <td align="right">{{ $preventivo['rientro']['ora_arrivo'] ?? '' }}</td>
                        </tr>
                       @if (!empty($preventivo['trasporto_company_rientro']))
    <tr>
        <td colspan="2" style="position: relative; padding-bottom:20px;">
            {{ $preventivo['trasporto_company_rientro'] }}

            @if (!empty($preventivo['trasporto_rientro_foto']))
                <img src="{{ $preventivo['trasporto_rientro_foto'] }}" 
                     height="32" 
                     width="96"
                     style="float: right; max-height: 32px; max-width: 96px;">
            @endif
        </td>
    </tr>
@endif

                    </table>
                </div>
            </td>
        </tr>
    </table>






    <div class="page-break-always"></div>

    <!-- PROGRAMMA VIAGGIO -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px; border-collapse:collapse;">
        <tr>
            <td width="33%"></td>
            <td width="67%" align="right"
                style="background-color:#385B9B; color:#fff; font-weight:bold; padding:10px 20px; font-size:18px;">
                Programma di viaggio
            </td>
        </tr>
    </table>

    @if (!empty($preventivo['itinerario']))
        @foreach ($preventivo['itinerario'] as $itinerario)
            <table width="100%" cellpadding="0" cellspacing="0"
                style="margin-bottom:15px; border-collapse:collapse;">
                <tr>
                    <td width="67%"
                        style="background-color:#d8ecb3; font-weight:bold; padding:8px 20px; font-size:16px;">
                        {{ $itinerario['titolo'] }}
                    </td>
                    <td width="33%"></td>
                </tr>
            </table>
            <div style="padding:0px 20px; margin-bottom:10px; font-size:14px;">
                {!! $itinerario['descrizione'] !!}
            </div>



            @if (!empty($itinerario['immagini']))
                <table width="100%" cellpadding="5" cellspacing="0"
                    style="border-collapse:collapse; margin-bottom:10px; padding:6px 20px;">
                    <tr>
                        @foreach (array_reverse($itinerario['immagini']) as $foto)
                            <td width="33%" valign="top">
                                <img src="{{ $foto }}"
                                    style="width:100%; height:200px; object-fit:cover; border-radius:6px;">
                            </td>
                        @endforeach

                    </tr>
                </table>
            @endif
        @endforeach
    @endif

    <!-- Galleria immagini -->
    @if (!empty($preventivo['immagini']))
        <table width="100%" cellpadding="5" cellspacing="0"
            style="border-collapse:collapse; margin-top:10px; padding: 20px;">
            @foreach (array_chunk($preventivo['immagini'], 2) as $row)
                <tr>
                    @foreach ($row as $immagine)
                        <td width="50%" valign="top" style="padding:5px;">
                            <img src="{{ $immagine }}" alt=""
                                style="width:100%; height:200px; object-fit:cover; border-radius:6px;">
                        </td>
                    @endforeach
                    @if (count($row) < 2)
                        <td width="50%"></td>
                    @endif
                </tr>
            @endforeach
        </table>
    @endif
  {{--   <div class="page-break"></div> --}}

 @if ($preventivo['gita_giornaliera'] === 0)
    <!-- SISTEMAZIONI -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px; margin-top:30px; border-collapse:collapse;">
        <tr>
            <td width="33%"></td>
            <td width="67%" align="right"
                style="background-color:#385B9B; color:#fff; font-weight:bold; padding:10px 20px; font-size:18px;">
                Sistemazioni
            </td>
        </tr>
    </table>

    @if (!empty($preventivo['hotels']))
        @foreach ($preventivo['hotels'] as $hotel)
            <!-- TITOLO HOTEL -->
            <table width="100%" cellpadding="0" cellspacing="0"
                style="margin-bottom:10px; border-collapse:collapse;">
                <tr>
                    <td width="80%"
                        style="background-color:#d8ecb3; font-weight:bold; padding:8px 20px; font-size:16px;">
                        {{ $hotel['nome'] }}
                    </td>
                    <td width="20%" align="right" style="background-color:#d8ecb3; padding:10px 20px;">
                        @if (!empty($hotel['stelle']))
                            <img src="{{ $hotel['stelle'] }}" height="20" alt="stelle">
                        @endif
                    </td>
                </tr>
            </table>
            <div style="margin:0 10px 10px 10px; font-size:14px; padding:10px 20px;">
                <b>{{ $hotel['indirizzo'] }}</b><br>
            </div>
            <!-- FOTO HOTEL -->
            @if (!empty($hotel['foto']))
                <table width="100%" cellpadding="5" cellspacing="0"
                    style="border-collapse:collapse; margin-bottom:10px; padding:6px 20px;">
                    <tr>
                        @foreach ($hotel['foto'] as $foto)
                            <td width="33%" valign="top">
                                <img src="{{ $foto }}"
                                    style="width:100%; height:200px; object-fit:cover; border-radius:6px;">
                            </td>
                        @endforeach
                        @if (count($hotel['foto']) < 3)
                            @for ($i = count($hotel['foto']); $i < 3; $i++)
                                <td width="33%"></td>
                            @endfor
                        @endif
                    </tr>
                </table>
            @endif

            <!-- DESCRIZIONE -->

            <div style="margin:0 10px 10px 10px; font-size:14px; padding:0px 20px;">
                {!! $hotel['descrizione'] !!}
            </div>

            <!-- INDIRIZZO E CAMERE -->
            <table width="100%" cellpadding="6" cellspacing="0"
                style="border-top:2px solid #8ebf22; margin-bottom:20px; padding:6px 20px;">
                <tr>
                    <!-- Indirizzo -->
                    <td width="70%" valign="top" style="font-size:14px; padding-top:40px;">

                    </td> @php
                        // Filtra gli eventuali elementi vuoti
                        $hotel_rooms = array_filter($hotel['rooms'] ?? [], function ($ti) {
                            return !empty($ti['tipologia_stanza']) ||
                                !empty($ti['num_camere']) ||
                                !empty($ti['num_persone']) ||
                                !empty($ti['gratuite']);
                        });
                    @endphp
 @if (!empty($hotel_rooms))
    <table width="100%" cellpadding="6" cellspacing="0">
        @php
            $all_boxes = [];
            // Prima raccogli tutte le camere paganti
            foreach ($hotel_rooms as $room) {
                if ($room['paganti'] > 0 && !empty($room['num_persone_paganti'])) {
                    $all_boxes[] = [
                        'tipo' => $room['tipologia_stanza'],
                        'camere' => $room['num_camere'] ?? null,
                        'persone' => $room['num_persone_paganti'],
                        'gratuita' => false
                    ];
                }
            }
            // Poi raccogli le camere gratuite
            foreach ($hotel_rooms as $room) {
                if (!empty($room['num_persone_gratuita']) && $room['gratuite'] > 0) {
                    $all_boxes[] = [
                        'tipo' => $room['tipologia_stanza'],
                        'camere' => null,
                        'persone' => $room['num_persone_gratuita'],
                        'gratuita' => true
                    ];
                }
            }
            // Dividi in righe da massimo 4 box
            $rows = array_chunk($all_boxes, 4);
        @endphp

        @foreach ($rows as $row)
            <tr>
                @foreach ($row as $box)
                    <td style="padding:20px; text-align:center; vertical-align:top; width:25%;">
                        <div style="border:1px solid #8ebf22; border-radius:6px;
                                    background-color:#ffffff; padding:10px;
                                    font-size:12px; width:90px; height:80px; 
                                    text-align:center; margin:0 auto;">
                            <div style="font-weight:bold; font-size:16px; color:#8ebf22;">
                                {{ $box['tipo'] }}
                            </div>
                            @if (!empty($box['camere']))
                                <div style="font-size:15px; color:#333;">
                                    <strong>Camere:</strong> {{ $box['camere'] }}
                                </div>
                            @endif
                            <div style="font-size:15px; color:#333;">
                                <strong>Persone:</strong> {{ $box['persone'] }}
                            </div>
                            @if ($box['gratuita'])
                                <div style="font-size:12px; font-weight:600; color:#666;">
                                    (Gratuite)
                                </div>
                            @endif
                        </div>
                    </td>
                @endforeach
                @if (count($row) < 4)
                    @for ($i = count($row); $i < 4; $i++)
                        <td style="width:25%;"></td>
                    @endfor
                @endif
            </tr>
        @endforeach
    </table>
@endif




                    {{--  <!-- Camere -->
                    <td width="30%" valign="top" align="right" style="padding:0; margin:0;">
                        <div style="font-size:0; margin:0; padding:0;">
                            @foreach ($hotel['rooms'] as $room)
                                <div
                                    style="display:inline-block; text-align:center; margin:0; padding:0; line-height:1; font-size:14px; vertical-align:top; margin-right:20px; margin-top:16px;">
                                    <div
                                        style="font-size:25px; font-weight:bold; line-height:1; color:{{ $room['totale'] ? '#8ebf22' : '#ccc' }};">
                                        {{ $room['totale'] ?: 'NO' }}
                                    </div>
                                    <div style="font-size:14px; font-weight:bold; line-height:1.2;">
                                        {{ $room['tipo'] }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </td> --}}

                </tr>
            </table>
        @endforeach
    @endif
@endif


    <div class="page-break-always"></div>

    <!-- SERVIZI EXTRA -->
    <table width="100%" cellpadding="0" cellspacing="0"
        style="margin-bottom:20px; padding-top: 0px; border-collapse:collapse;">
        <tr>
            <td width="33%"></td>
            <td width="67%" align="right"
                style="background-color:#385B9B; color:#fff; font-weight:bold; padding:10px 20px; font-size:18px;">
                Servizi Extra
            </td>
        </tr>
    </table>

    @if (!empty($preventivo['servizi']))
        @foreach ($preventivo['servizi'] as $servizio)
            <!-- Titolo servizio -->
            <table border="0" cellspacing="0" cellpadding="0"
                style="border-collapse:collapse; margin-bottom:8px; width:67%; margin-top:10px; ">
                <tr>

                    <td style="background-color:#d8ecb3; font-weight:bold; padding:8px 20px; font-size:16px;">
                        {{ $servizio['tipo'] }}
                    </td>
                </tr>
            </table>

            <!-- Icona + descrizione affiancati -->

            <table cellspacing="0" cellpadding="0"
                style="border-collapse:collapse; margin:0; padding:0; table-layout:fixed; width:100%; padding:0 20px;">
                <tr>
                    <!-- Colonna icona -->
                    <td style="width:5%; vertical-align:top; padding-top:4px;">
                        @if (!empty($servizio['icona']))
                            <img src="{{ $servizio['icona'] }}" alt="{{ $servizio['tipo'] }}"
                                style="height:22px; margin-top:3px;">
                        @endif
                    </td>

                    <!-- Colonna descrizione -->
                    <td
                        style="padding-left:8px; font-size:13px; text-align:left; vertical-align:top; line-height:1.2;">
                        @if (!empty($servizio['nome']))
                            <p style="font-weight:bold; margin:0; padding:0;">{{ $servizio['nome'] }}</p>
                        @endif

                        @if (!empty($servizio['descrizione']))
                            <div style="margin:2px 0 5px 0; padding:0; line-height:1.2;" class="descrizione">
                                {!! preg_replace(
                                    '/<p([^>]*)>/',
                                    '<p$1 style="margin:0; padding:0; line-height:1.2;">',
                                    $servizio['descrizione'],
                                ) !!}
                            </div>
                        @endif

                        {{--    @if (!empty($servizio['allegati']))
                            @foreach ($servizio['allegati'] as $url)
                                @php
                                    // Se è un path tipo "/storage/allegati_servizi/...", genera l'URL assoluto
                                    $absoluteUrl = asset(ltrim($url, '/'));
                                    // Escapa per sicurezza HTML
                                    $safeUrl = e($absoluteUrl);
                                @endphp

                                <div style="margin:3px 0; padding:0;">
                                    <p style="margin:0; padding:0;">Per visionare,</p>
                                    <a href="{{ $safeUrl }}" target="_blank" rel="noopener noreferrer"
                                        style="color:#0066cc; text-decoration:underline;">
                                        <img src="{{ $preventivo['link'] }}"
                                            style="height:25px; margin:0; padding:0;">

                                    </a>
                                </div>
                            @endforeach
                        @endif --}}

                        {{-- test --}}

                    </td>
                </tr>
            </table>
        @endforeach
    @endif





    <div class="page-break"></div>

    <!-- RIEPILOGO E QUOTE -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:20px; margin-top:30px; border-collapse:collapse;">
        <tr>
            <td width="33%"></td>
            <td width="67%" align="right"
                style="background-color:#385B9B; color:#fff; font-weight:bold; padding:10px 20px; font-size:18px;">
                Riepilogo e quote
            </td>
        </tr>
    </table>

    <!-- QUOTA COMPRENDE -->
   <table cellpadding="6" cellspacing="0" style="border-collapse:collapse; margin-bottom:10px; width:67%;">
    <tr>
        <td colspan="2"
            style="background-color:#d8ecb3; font-weight:bold; font-size:12px; padding:8px 20px; font-size:16px;">
            La quota comprende
        </td>
    </tr>
</table>
<table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse; margin-bottom:15px;">
    <tr>
        <td style="padding: 0; vertical-align: middle; position: relative; width:30px;">
            <div style="background-color:#d8ecb3; width:20px; height:50px; position:absolute; left:0; top:0;">
            </div>
        </td>
        <td style="padding:0 20px 0 20px; vertical-align: top;">
            <div style="font-size: 14px;">
                @foreach ($preventivo['quote_hotel_comprende'] ?? [] as $qc)
                    {!! preg_replace('/<ul[^>]*>/', '<ul style="margin-left:0;padding-left:20px;list-style-position:outside;">', $qc) !!}
                @endforeach
                @foreach ($preventivo['quote_andata_comprende'] ?? [] as $qc)
                    {!! preg_replace('/<ul[^>]*>/', '<ul style="margin-left:0;padding-left:20px;list-style-position:outside;">', $qc) !!}
                @endforeach
                @foreach ($preventivo['quote_rientro_comprende'] ?? [] as $qc)
                    {!! preg_replace('/<ul[^>]*>/', '<ul style="margin-left:0;padding-left:20px;list-style-position:outside;">', $qc) !!}
                @endforeach
                @foreach ($preventivo['quote_trasporto_intermedio_comprende'] ?? [] as $qc)
                    {!! preg_replace('/<ul[^>]*>/', '<ul style="margin-left:0;padding-left:20px;list-style-position:outside;">', $qc) !!}
                @endforeach
                @foreach ($preventivo['quote_servizi_comprende'] ?? [] as $qc)
                    {!! preg_replace('/<ul[^>]*>/', '<ul style="margin-left:0;padding-left:20px;list-style-position:outside;">', $qc) !!}
                @endforeach
                @if (!empty($preventivo['quota_comprende_generico']))
                    {!! preg_replace(
                        '/<ul[^>]*>/',
                        '<ul style="margin-left:0;padding-left:20px;list-style-position:outside;">',
                        $preventivo['quota_comprende_generico'],
                    ) !!}
                @endif
            </div>
        </td>
    </tr>
</table>

<!-- QUOTA NON COMPRENDE -->
<table cellpadding="6" cellspacing="0" style="border-collapse:collapse; margin-bottom:10px; width:67%;">
    <tr>
        <td colspan="2"
            style="background-color:#fca5a5; font-weight:bold; font-size:12px; padding:8px 20px; font-size:16px;">
            La quota non comprende
        </td>
    </tr>
</table>
<table width="100%" cellpadding="6" cellspacing="0" style="border-collapse:collapse; margin-bottom:15px;">
    <tr>
        <td style="padding:0; vertical-align:middle; position:relative; width:30px;">
            <div style="background-color:#fca5a5; width:20px; height:50px; position:absolute; left:0; top:0;"></div>
        </td>
        <td style="padding:0 20px 0 0; vertical-align:top;">
            <div style="font-size: 14px;">
                @php
                $cleanHtml = function ($html) {
                    return trim(preg_replace([
                        '#<ul[^>]*>#i',
                        '#</ul>#i',
                        '#<p><br></p>#i',
                        '#<p>&nbsp;</p>#i',
                        '#^\s*<br\s*/?>#i',
                    ], '', $html));
                };
                @endphp

                <ul style="margin:0; padding-left:20px; list-style-position:outside;">
                    @foreach (($preventivo['quote_hotel_non_comprende'] ?? []) as $qnc)
                        {!! $cleanHtml($qnc) !!}
                    @endforeach

                    @foreach (($preventivo['quote_andata_non_comprende'] ?? []) as $qnc)
                        {!! $cleanHtml($qnc) !!}
                    @endforeach

                    @foreach (($preventivo['quote_rientro_non_comprende'] ?? []) as $qnc)
                        {!! $cleanHtml($qnc) !!}
                    @endforeach

                    @foreach (($preventivo['quote_intermedio_non_comprende'] ?? []) as $qnc)
                        {!! $cleanHtml($qnc) !!}
                    @endforeach

                    @foreach (($preventivo['quote_servizi_non_comprende'] ?? []) as $qnc)
                        {!! $cleanHtml($qnc) !!}
                    @endforeach

                    @if (!empty($preventivo['quota_non_comprende_generico']))
                        {!! $cleanHtml($preventivo['quota_non_comprende_generico']) !!}
                    @endif
                </ul>
            </div>
        </td>
    </tr>
</table>



  <!-- NOTE -->
@if (!empty($preventivo['campo_attenzione']))
    <div style="margin-top: 20px; margin-left: 40px; margin-right: 40px; font-size: 11px; color: #555; font-style: italic;">
        <style>
            .campo-attenzione ul { 
                margin: 5px 0 5px 20px; 
                padding: 0;
                list-style-type: disc; 
            }
            .campo-attenzione ol { 
                margin: 5px 0 5px 20px; 
                padding: 0;
                list-style-type: decimal; 
            }
            .campo-attenzione li { 
                margin-bottom: 3px;
                padding-left: 5px;
            }
            .campo-attenzione p {
                margin: 0;
                padding: 0;
            }
        </style>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 10px; vertical-align: top; padding: 0;">*&nbsp;</td>
                <td style="line-height: 1.4; padding: 0;">
                    <div class="campo-attenzione">
                        {!! $preventivo['campo_attenzione'] !!}
                    </div>
                </td>
            </tr>
        </table>
    </div>
@endif

<!-- QUOTA FINALE -->
<div
    style="
         border:2px solid #8ebf22;
        background-color:#d9e9b3;
        border-radius:8px;
        padding:20px 20px 10px 20px;
        margin:40px auto 15px auto;
        width:80%;
        page-break-inside: avoid;
    ">
    <table width="100%" style="border-collapse:collapse;">
        <tr>
            <td colspan="2" style="text-align:center; padding:8px 0px;">
                <div style="font-weight:bold; font-size:14px;">
                    Quota di partecipazione per persona
                </div>
                <div style="font-size:20px; font-weight:bold; margin-top:4px;">
                    @if (empty($preventivo['prezzo_forzato']))
                        € {{ number_format($preventivo['prezzo_per_persona'], 2, ',', '.') }}
                    @else
                        € {{ number_format($preventivo['prezzo_forzato'], 2, ',', '.') }}
                    @endif
                </div>
            </td>
        </tr>

        <tr>
            <td colspan="2"
                style="text-align:center; padding-top:6px; font-style:italic; font-size:12px; color:#444;">
                @if (!empty($preventivo['note_scorporo_trasporti']))
                    {{ $preventivo['note_scorporo_trasporti'] }}
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="2" style="text-align:center; padding-top:2px; font-style:italic; font-size:12px; color:#444;">
                @if (!empty($preventivo['note_scorporo_servizi']))
                    {{ $preventivo['note_scorporo_servizi'] }}
                @endif
            </td>
        </tr>
    </table>
</div>

<p style="text-align:center; font-size:12px; margin-top:6px; margin-bottom:3px;">
    <b>Offerta valida fino al:</b> {{ \Carbon\Carbon::parse($preventivo['scadenza'])->format('d/m/Y') }}
</p>
<p style="text-align:center; font-size:12px; margin-top:3px; margin-bottom:0;">
    Preventivo realizzato da <b>{{ $preventivo['creato_da'] }}</b> – contatto telefonico
    <b>{{ $preventivo['telefono'] }}</b>
</p>




 
<div
    style="
       
       margin-top:40px;
        page-break-inside: avoid;
    ">
    <!-- ASSISTENZA -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:15px; border-collapse:collapse;">
        <tr>
            <td width="33%"></td>
            <td width="67%" align="right"
                style="background-color:#385B9B; color:#fff; font-weight:bold; padding:10px 20px; font-size:18px;">
                Assistenza prima, dopo e durante il viaggio
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="10" cellspacing="0" style="margin-bottom:20px; border-collapse:collapse;">
        <tr>
            <td width="33%"></td>
            <td width="67%" valign="top">
                <table cellpadding="6" cellspacing="0" style="border-collapse:collapse;">
                    <tr>
                        <td valign="top" width="60">
                            <img src="{{ $preventivo['assistenza_icon'] }}" alt="Assistenza" style="height:40px;">
                        </td>
                        <td valign="top" style="font-size:12px; line-height:1.4;">
                            <p><b>Assistenza</b> in fase di realizzazione del viaggio e itinerario da parte del nostro
                                personale qualificato.</p>
                            <p><b>Assistenza Centrale operativa</b> 24h su 24 e 365 giorni l’anno per qualsiasi esigenza
                                medica,
                                <b>Help-Line attiva</b> 24h su 24 tutti i giorni da parte del nostro personale via
                                telefono
                                e anche tramite <b>app iOS e Android La Bussola on the road</b>.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- APP -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:15px;  border-collapse:collapse;">
        <tr>
            <td width="33%"></td>
            <td width="67%" align="right"
                style="background-color:#385B9B; color:#fff; font-weight:bold; padding:10px 20px; font-size:18px;">
                Scarica la nostra App "La Bussola on the road"
            </td>
        </tr>
    </table>

    <table width="100%" cellpadding="10" cellspacing="0"
        style="margin-bottom:20px; margin-top:40px; border-collapse:collapse;">
        <tr>
            <td width="33%"></td>
            <td width="67%" align="center">
                <img src="{{ $preventivo['store'] }}" alt="store" style="height:50px; margin-right:10px;">
                <img src="{{ $preventivo['icona'] }}" alt="icona" style="height:50px;">
            </td>
        </tr>
    </table>

    <!-- CONTATTI -->
    <table width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:15px; border-collapse:collapse;">
        <tr>
            <td width="33%"></td>
            <td width="67%" align="right"
                style="background-color:#385B9B; color:#fff; font-weight:bold; padding:10px 20px; font-size:18px;">
                I nostri contatti
            </td>
        </tr>
    </table>

    <div style="text-align:right; padding:10px; font-size:12px; line-height:1.5;">
        <p><b>LA BUSSOLA srl – Agenzia viaggi e tour operator</b><br>
            Via Altaguardia,1 – 20135 – Milano IT<br>
            Cod. Fisc. / P. IVA 08114120960 – REA: MI – 2003676 – Capitale Sociale: € 10.000<br>
            TEL +39 02 8219 6055 – WA +39 02 8088 6574<br>
            EMAIL: preventivi@labussola.it – PEC: labussolamilano@pec.it
        </p>
    </div>
    </div>


</body>

</html>
