<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Risposta preventivo - La Bussola On The Road</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="font-sans text-gray-800 max-w-2xl mx-auto px-5 py-8 md:px-8">

    <h2 class="mb-6 text-2xl md:text-3xl font-semibold">Preventivo n.
                {{ $preventivo->numero }}/{{ $preventivo->anno }}</h2>

    @if ($preventivo->customer->tipo_cliente === 'privato')
        @if ($preventivo->customer->genere === 'uomo')
            <p class="text-lg mb-4">Gent.mo {{ $preventivo->customer->nome }} {{ $preventivo->customer->cognome }},</p>
        @else
            <p class="text-lg mb-4">Gent.ma {{ $preventivo->customer->nome }} {{ $preventivo->customer->cognome }},</p>
        @endif
    @else
        <p class="text-lg mb-4">Spett.le {{ $preventivo->customer->nome }},</p>
    @endif

    <p class="mb-8 text-lg">La preghiamo di indicarci la Sua decisione in merito al preventivo che Le abbiamo inviato:
    </p>

    @if (session('success'))
        <div class="bg-green-100 text-green-800 p-4 md:p-5 mb-6 rounded">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('preventivo.risposta.salva', $preventivo->cod_alfa) }}" method="POST" class="mb-16">
        @csrf

        <label class="flex items-start gap-3 mb-5 md:mb-6 cursor-pointer py-2 text-lg leading-relaxed">
            <input type="radio" name="stato" value="accettato" class="mt-1 w-5 h-5 flex-shrink-0">
            <span>Accetto il preventivo</span>
        </label>

        <label class="flex items-start gap-3 mb-5 md:mb-6 cursor-pointer py-2 text-lg leading-relaxed">
            <input type="radio" name="stato" value="interesse più tempo" class="mt-1 w-5 h-5 flex-shrink-0">
            <span>L'offerta è di interesse, ma ho bisogno di più tempo</span>
        </label>
        {{-- test --}}
        <label class="flex items-start gap-3 mb-5 md:mb-6 cursor-pointer py-2 text-lg leading-relaxed">
            <input type="radio" name="stato" value="superiore budget" class="mt-1 w-5 h-5 flex-shrink-0">
            <span>L'offerta risulta superiore al budget previsto</span>
        </label>

        <label class="flex items-start gap-3 mb-5 md:mb-6 cursor-pointer py-2 text-lg leading-relaxed">
            <input type="radio" name="stato" value="oltre tempi" class="mt-1 w-5 h-5 flex-shrink-0">
            <span>L'offerta è pervenuta oltre i tempi necessari alla valutazione</span>
        </label>

        <label class="flex items-start gap-3 mb-5 md:mb-6 cursor-pointer py-2 text-lg leading-relaxed">
            <input type="radio" name="stato" value="programma non interessa" class="mt-1 w-5 h-5 flex-shrink-0">
            <span>Il programma proposto non incontra i miei interessi</span>
        </label>

        <label class="flex items-start gap-3 mb-5 md:mb-6 cursor-pointer py-2 text-lg leading-relaxed">
            <input type="radio" name="stato" value="rivedere proposta" class="mt-1 w-5 h-5 flex-shrink-0">
            <span>Vorrei rivedere la proposta insieme a voi</span>
        </label>

        <label class="flex items-start gap-3 mb-3 cursor-pointer py-2 text-lg leading-relaxed">
            <input type="radio" name="stato" value="altro" class="mt-1 w-5 h-5 flex-shrink-0">
            <span>Altro:</span>
        </label>

        <textarea name="stato_altro_testo" id="altro_testo" rows="5"
            class="w-full text-lg p-4 md:p-5 border-2 border-gray-300 rounded-lg font-sans mb-6 focus:outline-none focus:border-blue-500">
        </textarea>

        <button type="submit"
            class="bg-blue-600 hover:bg-blue-700 text-white py-5 px-7 rounded-lg font-bold text-xl w-full transition-colors">
            Invia la risposta
        </button>
    </form>

    <!-- LOGO -->
    <div class="text-center my-16 border-t-2 border-gray-300 pt-10">
        <img src="{{ asset('images/logo.png') }}" alt="Logo La Bussola" class="w-40 mx-auto">
    </div>

    <!-- INTESTAZIONE DATI AZIENDA -->
    <div class="text-center text-lg text-gray-600 leading-relaxed mb-10">
        <strong>LA BUSSOLA srl – Agenzia viaggi e tour operator</strong><br>
        Via Altaguardia, 1 – 20135 – Milano IT<br>
        Cod. Fisc. / P. IVA 08114120960 – REA: MI – 2003676 – Capitale Sociale: € 10.000<br>
        <strong>Tel:</strong> +39 02 8219 6055 – <strong>WhatsApp:</strong> <a href="https://wa.me/390280886574"
            target="_blank">+39 02 8088 6574</a><br> <strong>Email:</strong>
        <a href="mailto:preventivi@labussola.it" class="text-green-600 hover:text-green-700 no-underline">
            preventivi@labussola.it
        </a> –
        <strong>PEC:</strong>
        <a href="mailto:labussolamilano@pec.it" class="text-green-600 hover:text-green-700 no-underline">
            labussolamilano@pec.it
        </a>
    </div>

</body>

</html>
