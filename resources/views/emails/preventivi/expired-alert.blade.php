<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Preventivo Scaduto - La Bussola On The Road</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
</head>

<body style="font-family: Arial, sans-serif; line-height: 1.5; color: #333; background-color: #f9fafb; padding: 16px;">
    <div
        style="max-width: 672px; margin: 0 auto; background-color: #ffffff; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); border-radius: 8px; padding: 24px;">

        @if ($preventive->customer->tipo_cliente === 'privato')
            @if ($preventive->customer->genere === 'uomo')
                <p style="margin-bottom: 16px;">Gent.mo {{ $preventive->customer->nome }}
                    {{ $preventive->customer->cognome }},</p>
            @else
                <p style="margin-bottom: 16px;">Gent.ma {{ $preventive->customer->nome }}
                    {{ $preventive->customer->cognome }},</p>
            @endif
        @else
            <p style="margin-bottom: 16px;">Spett.le {{ $preventive->customer->nome }},</p>
        @endif

        <div
            style="background-color: #fef2f2; border-left: 4px solid #dc2626; padding: 16px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0; color: #991b1b; font-weight: bold;">
                Il Preventivo .
                {{ $preventive->numero }}/{{ $preventive->anno }} è scaduto
            </p>
        </div>

        <p style="margin-bottom: 16px;">
            Le scriviamo per informarLa che l'offerta relativa al preventivo in oggetto è scaduta
            in data <strong style="color: #dc2626;">{{ $preventive->date_expiration->format('d/m/Y') }}</strong>.
        </p>

        <p style="margin-bottom: 16px;">
            Se questa proposta di viaggio è ancora nel Suo interesse, saremo lieti di:
        </p>

        <ul style="margin-bottom: 24px; color: #374151; line-height: 1.8;">
            <li>Verificare la disponibilità aggiornata</li>
            <li>Proporle eventuali nuove tariffe</li>
            <li>Elaborare un nuovo preventivo personalizzato</li>
        </ul>

        <div style="margin: 25px 0;">
            <p style="margin-bottom: 15px; color: #333; font-size: 14px;">
                Può ancora visualizzare il preventivo originale cliccando qui
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


        <hr style="margin: 32px 0; border: 0; border-top: 1px solid #d1d5db;">

        <div
            style="background-color: #f0fdf4; padding: 16px; border-radius: 8px; border-left: 4px solid #22c55e; margin-bottom: 24px;">
            <p style="margin: 0;">
                {{--                 <strong style="display: block; margin-bottom: 8px;">È ancora interessato/a?</strong>
 --}} La preghiamo di contattarci per verificare la disponibilità e ricevere un nuovo
                preventivo aggiornato.
            </p>
        </div>

        <p style="margin-bottom: 24px;">
            <a href="{{ route('preventivo.risposta', $preventive->cod_alfa) }}"
                style="display: inline-block; padding: 12px 24px; background-color: #16a34a; color: #ffffff; font-weight: bold; text-decoration: none; border-radius: 8px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); transition: background-color 0.3s;">
                Clicchi qui per comunicarci il Suo interesse
            </a>
        </p>

        <hr style="margin: 32px 0; border: 0; border-top: 1px solid #d1d5db;">

        <p style="margin-bottom: 24px; color: #374151;">
            Restiamo a Sua completa disposizione per qualsiasi informazione o per elaborare una nuova proposta.
        </p>

        <div style="font-size: 14px; color: #374151; line-height: 1.6; margin-bottom: 24px;">
            <p style="margin-bottom: 8px;">Cordiali saluti,</p>
            <p style="font-weight: bold; margin-bottom: 4px;">{{ $referente }}</p>
            <p>Contatto telefonico: {{ $telefono_referente }}</p>
        </div>

        <div style="text-align: center; margin-top: 32px;">
            <img src="{{ $message->embed(public_path('images/logo.png')) }}" alt="Logo La Bussola"
                style="width: 144px; margin: 0 auto 24px;">
        </div>

        <!-- INTESTAZIONE DATI AZIENDA -->
        <div
            style="text-align: center; font-size: 13px; color: #6b7280; line-height: 1.6; border-top: 1px solid #d1d5db; padding-top: 24px; margin-top: 24px;">
            <p style="font-weight: bold; margin-bottom: 4px;">LA BUSSOLA srl – Agenzia viaggi e tour operator</p>
            <p style="margin-bottom: 4px;">Via Altaguardia, 1 – 20135 – Milano IT</p>
            <p style="margin-bottom: 8px;">Cod. Fisc. / P. IVA 08114120960 – REA: MI – 2003676 – Capitale Sociale: €
                10.000</p>
            <p style="margin-bottom: 4px;">
                <strong>Tel:</strong> +39 02 8219 6055 – <strong>WhatsApp:</strong> <a href="https://wa.me/390280886574"
                    target="_blank">+39 02 8088 6574</a><br>
            </p>
            <p>
                <strong>Email:</strong>
                <a href="mailto:preventivi@labussola.it" style="color: #16a34a; text-decoration: none;">
                    preventivi@labussola.it
                </a>
                –
                <strong>PEC:</strong>
                <a href="mailto:labussolamilano@pec.it" style="color: #16a34a; text-decoration: none;">
                    labussolamilano@pec.it
                </a>
            </p>
        </div>

    </div>
</body>

</html>
