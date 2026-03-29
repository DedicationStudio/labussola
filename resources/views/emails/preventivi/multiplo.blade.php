<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta charset="UTF-8">
    <title>Preventivo - La Bussola On The Road</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">

</head>

<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #333;">


    {{--  <p style="text-align:center;">
        <img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="Logo La Bussola" width="150">
    </p> --}}


    <p>{{ $titolo }} {{ $cliente }},</p>

    @if (!empty($corpo_email))
        {!! $corpo_email !!}
    @endif


    @if ($preventivi->isNotEmpty())

        @foreach ($preventivi as $preventivo)
            <h4>Preventivo n.
                {{ $preventivo->numero }}/{{ $preventivo->anno }}</h4>

            <div style="margin: 25px 0;">
                <p style="margin-bottom: 15px; color: #333; font-size: 14px;">
                    Clicchi sul pulsante per visualizzare il preventivo nel Suo browser
                </p>

                @if ($preventivo->allego_file)
                    <a href="{{ route('preventivo.show.allegato', ['cod_alfa' => $preventivo->cod_alfa]) }}"
                        style="display: block;
                  background-color: #1a73e8;
                  color: #fff;
                  text-decoration: none;
                  font-weight: bold;
                  padding: 14px 24px;
                  border-radius: 6px;
                  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                  text-align: center;
                  max-width: 200px;">
                        Visualizzi
                    </a>
                @else
                    <a href="{{ url('/preventivi/' . $preventivo->cod_alfa) }}"
                        style="display: block;
                  background-color: #1a73e8;
                  color: #fff;
                  text-decoration: none;
                  font-weight: bold;
                  padding: 14px 24px;
                  border-radius: 6px;
                  box-shadow: 0 2px 4px rgba(0,0,0,0.2);
                  text-align: center;
                  max-width: 200px;">
                        Visualizzi
                    </a>
                @endif

            </div>






            <hr style="margin: 30px 0;">

            <p>
                <a href="{{ route('preventivo.risposta', $preventivo->cod_alfa) }}"
                    style="display:inline-block;padding:12px 20px;margin:5px 0;background:#4caf50;color:#fff;
              text-decoration:none;border-radius:5px; font-weight:bold;">
                    Clicchi qui per dare il Suo riscontro
                </a>
            </p>
        @endforeach
    @endif
    {{-- SE NON CI SONO PREVENTIVI SELEZIONATI - MOSTRA LINK AGLI ALLEGATI --}}
    @if (!empty($allegati))
        <div style="margin: 25px 0;">
            <p style="color:#333; font-weight:bold;">Documenti allegati:</p>
            @foreach ($allegati as $allegato)
                <div style="margin: 10px 0;">
                    <a href="{{ url('storage/' . $allegato) }}" target="_blank"
                        style="display:inline-block;
                                  background-color:#1a73e8;
                                  color:#fff;
                                  text-decoration:none;
                                  font-weight:bold;
                                  padding:8px 18px;
                                  border-radius:6px;
                                  box-shadow:0 2px 4px rgba(0,0,0,0.2);">
                        Visualizzi
                    </a>
                </div>
            @endforeach
        </div>
    @endif
    <hr style="margin: 30px 0;">

    <p style="font-size: 14px; color: #333; line-height: 1.6;">
        Cordiali saluti,<br>


        <strong>Lo Staff La Bussola On The Road</strong><br>
    </p>



    <p style="text-align:center;">
        <img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="Logo La Bussola" width="150"
            style="margin-top:25px;">
    </p>

    <!-- INTESTAZIONE DATI AZIENDA -->
    <p style="text-align:center; font-size: 13px; color: #555; line-height: 1.6; margin-bottom:30px;">
        <strong>LA BUSSOLA srl – Agenzia viaggi e tour operator</strong><br>
        Via Altaguardia, 1 – 20135 – Milano IT<br>
        Cod. Fisc. / P. IVA 08114120960 – REA: MI – 2003676 – Capitale Sociale: € 10.000<br>
        <strong>Tel:</strong> +39 02 8219 6055 – <strong>WhatsApp:</strong> <a href="https://wa.me/390280886574"
            target="_blank">+39 02 8088 6574</a><br> <strong>Email:</strong>
        <a href="mailto:preventivi@labussola.it" style="color:#4caf50;text-decoration:none;">
            preventivi@labussola.it
        </a> –
        <strong>PEC:</strong>
        <a href="mailto:labussolamilano@pec.it" style="color:#4caf50;text-decoration:none;">
            labussolamilano@pec.it
        </a>
    </p>

</body>

</html>
