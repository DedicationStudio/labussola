<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta charset="UTF-8">
    <title>Alert Preventivo - La Bussola On The Road</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #333;">

    {{--   <p style="text-align:center;">
        <!-- Richiama l'immagine incorporata tramite il suo CID -->
        <img src="cid:logo" alt="Logo La Bussola" width="150">
    </p> --}}

    <h4>Oggetto: Scadenza offerta - Preventivo n.
        {{ $preventive->numero }}/{{ $preventive->anno }} in attesa di riscontro</h4>

    @if ($preventive->customer->tipo_cliente === 'privato')
        @if ($preventive->customer->genere === 'uomo')
            <p>Gent.mo {{ $preventive->customer->nome }} {{ $preventive->customer->cognome }},</p>
        @else
            <p>Gent.ma {{ $preventive->customer->nome }} {{ $preventive->customer->cognome }},</p>
        @endif
    @else
        <p>Spett.le {{ $preventive->customer->nome }},</p>
    @endif

    <p>in data {{ Carbon\Carbon::parse($preventive->data_invio)->format('d/m/Y') }} Le abbiamo inviato il preventivo
        relativo
        al viaggio da Lei richiesto.</p>

    <div style="margin: 25px 0;">
        <p style="margin-bottom: 15px; color: #333; font-size: 14px;">
            Clicchi sul pulsante per visualizzare il preventivo nel Suo browser
        </p>
        @if ($preventive->allego_file)
            <a href="{{ route('preventivo.show.allegato', ['cod_alfa' => $preventive->cod_alfa]) }}"
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
            <a href="{{ url('/preventivi/' . $preventive->cod_alfa) }}"
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

    <p>Non avendo ancora ricevuto un Suo gentile riscontro, desideriamo
        informarla che l'offerta è in prossimità di scadenza e che la disponibilità
        dei servizi proposti dovrà essere nuovamente verificata al momento di una eventuale conferma.</p>

    <p>Le saremmo grati se potesse indicarci brevemente il motivo della mancata risposta, così da
        poter migliorare il nostro servizio e, se necessario, riformulare la proposta secondo le Sue esigenze.
    </p>

    <p>
        La data di scadenza del preventivo è:
        <strong>{{ \Carbon\Carbon::parse($preventive->date_expiration)->format('d/m/Y') }}</strong>.
    </p>


    <hr style="margin: 30px 0;">








    <p>
        <a href="{{ route('preventivo.risposta', $preventive->cod_alfa) }}"
            style="display:inline-block;padding:12px 20px;margin:5px 0;background:#4caf50;color:#fff;
              text-decoration:none;border-radius:5px; font-weight:bold;">
            Clicchi qui per dare il Suo riscontro
        </a>
    </p>

    <hr style="margin: 30px 0;">

    <p style="margin-top: 40px; font-size: 0.9em; color: #777;">
        Questo è un promemoria automatico: se ha già risposto, può ignorare questa email.
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
