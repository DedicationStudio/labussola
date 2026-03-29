<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Preventivo La Bussola</title>
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

                margin: 0mm;
                /* margini in millimetri */
            }

            .pdf-header {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                height: 30mm;
                /* altezza effettiva del tuo header */
            }

            .pdf-content {
                margin-top: 35mm;
                /* leggermente > altezza header per non sovrapporre */
            }


            .page-break {
                page-break-before: always;
                break-before: page;

            }
        }

        @media screen {
            html {
                background-color: #e2e8f0;
            }

            body {
                max-width: 210mm;
                background-color: white;
                margin: auto;
            }

        }
    </style>

</head>

<body class="font-sans text-gray-900 text-sm leading-relaxed">
    <!-- HEADER -->
    <header class="flex justify-between items-start p-4 pdf-header">

        <div class="">
            <img src="{{ asset('pdf_images/logo.png') }}" alt="Logo La Bussola" class="h-22">
        </div>
        <div class="text-right">
            <div class="font-semibold">Preventivo n. 2138/2025</div>
            <div class="">del 08/05/2025</div>
            <p class="mt-2">Spett.le <span class="font-semibold">Riccardo De Santis</span></p>
            <p class="">Istituto Zaccaria</p>
        </div>
    </header>


    <main class="pdf-content">


        <section class="bg-cover bg-center w-full flex items-center"
            style="background-image: url('{{ asset('pdf_images/grecia.png') }}');">
            <div class="container mx-auto grid grid-cols-2 gap-8 p-8">

                <div class="bg-green-600/30 p-6 rounded-2xl text-white min-h-99 flex items-center justify-center">
                    <h2 class="text-2xl font-bold mb-4">Tesori della Grecia<br />Classica e Bizantina</h2>
                </div>

            </div>
        </section>

        <section class="px-6 pt-6 pb-0">
            <div class="grid grid-cols-4 gap-4">
                <div class="text-center flex flex-col items-center">
                    <div class="text-3xl font-bold flex justify-center items-center text-[#8ebf22]">
                        <img src="{{ asset('pdf_images/sole.png') }}" class="h-7 mr-1 ">
                        5
                    </div>
                    <div class="font-semibold">giorni</div>
                </div>
                <div class="text-center flex flex-col items-center">
                    <div class="text-3xl font-bold flex justify-center items-center text-[#8ebf22]">
                        <img src="{{ asset('pdf_images/luna.png') }}" class="h-7 mr-1 ">
                        6
                    </div>
                    <div class="font-semibold">notti</div>
                </div>
                <div class="text-center flex flex-col items-center">
                    <div class="text-3xl font-bold flex justify-center items-center text-[#8ebf22]">
                        <img src="{{ asset('pdf_images/gente.png') }}" class="h-7 mr-1 ">
                        50
                    </div>
                    <div class="font-semibold">paganti</div>
                </div>
                <div class="text-center flex flex-col items-center">
                    <div class="text-3xl font-bold flex justify-center items-center text-[#8ebf22]">
                        <img src="{{ asset('pdf_images/pig.png') }}" class="h-7 mr-1 ">
                        5
                    </div>
                    <div class="font-semibold">gratuità</div>
                </div>
            </div>
        </section>

        <section>
            <div class="flex gap-4 p-6 mb-8">
                <div class="text-sm w-1/3">
                    <p class="p-4 bg-[#8ebf22]/30 mb-4 rounded-lg"><span class="font-semibold">Bagaglio a
                            mano</span><br />Andata
                        Kg 8<br />Ritorno Kg 8</p>
                    <p class="p-4 bg-[#8ebf22]/30 rounded-lg"><span class="font-semibold">Bagaglio in
                            stiva</span><br />Andata Kg
                        23<br />Ritorno Kg 23</p>
                </div>
                <div class="w-2/3">
                    <div class="border-2 border-[#8ebf22] rounded-lg p-4 mb-4">
                        <div class=" flex items-center justify-between">
                            <h3 class="font-semibold">Andata 05/10/2025</h3>
                            <img src="{{ asset('pdf_images/aegean.png') }}" class="h-6">
                        </div>
                        <div class="flex justify-between border-b border-[#8ebf22] py-1">
                            <span>Milano Malpensa</span><span>10:15</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span>Atene</span><span>13:40</span>
                        </div>
                        <div class=" flex items-center justify-between mt-4">
                            <h3 class="font-semibold">Ritorno 10/10/2025</h3>
                            <img src="{{ asset('pdf_images/aegean.png') }}" class="h-6 ml-3">
                        </div>
                        <div class="flex justify-between border-b border-[#8ebf22] py-1">
                            <span>Atene</span><span>19:45</span>
                        </div>
                        <div class="flex justify-between py-1">
                            <span>Milano Malpensa</span><span>21:25</span>
                        </div>
                    </div>

                </div>
        </section>

        <div class="page-break"></div>
        <!-- PROGRAMMA VIAGGIO -->

        <section class="flex mb-6">
            <div class="w-1/3"></div>
            <div class="w-2/3 text-right pr-6 bg-blue-500 text-white font-semibold p-2">
                <h2 class="text-xl">Programma di viaggio</h2>
            </div>
        </section>

        <section class="mb-5">
            <div class="w-2/3 bg-[#8ebf22]/30 font-semibold p-1 mb-4">
                <h3 class="text-lg pl-6">Partenza Domenica 5 Ottobre</h3>
                <div class="w-1/3"></div>
            </div>
            <div class="pl-6">
                <p>Partenza: Milano Malpensa (MXP) – ore 10:15 – Compagnia: Aegeans Airlines</p>
                <p>Arrivo: Atene (ATH) – ore 13:40</p>
                <p>Incontro con l’autista e partenza in bus privato verso il Peloponneso.</p>
            </div>
        </section>

        <section class="mb-5">
            <div class="w-2/3 bg-[#8ebf22]/30 font-semibold p-1 mb-4">
                <h3 class="text-lg pl-6">Giorno 1</h3>
                <div class="w-1/3"></div>
            </div>
            <div class="pl-6">
                <p>Partenza: Milano Malpensa (MXP) – ore 10:15 – Compagnia: Aegeans Airlines</p>
                <p>Arrivo: Atene (ATH) – ore 13:40</p>
                <p>Incontro con l’autista e partenza in bus privato verso il Peloponneso.</p>
            </div>
        </section>

        <section class="mb-5">
            <div class="w-2/3 bg-[#8ebf22]/30 font-semibold p-1 mb-4">
                <h3 class="text-lg pl-6">Giorno 2</h3>
                <div class="w-1/3"></div>
            </div>
            <div class="pl-6">
                <p>Partenza: Milano Malpensa (MXP) – ore 10:15 – Compagnia: Aegeans Airlines</p>
                <p>Arrivo: Atene (ATH) – ore 13:40</p>
                <p>Incontro con l’autista e partenza in bus privato verso il Peloponneso.</p>
            </div>
        </section>

        <section class="mb-5">
            <div class="w-2/3 bg-[#8ebf22]/30 font-semibold p-1 mb-4">
                <h3 class="text-lg pl-6">Rientro</h3>
                <div class="w-1/3"></div>
            </div>
            <div class="pl-6">
                <p>Partenza: Milano Malpensa (MXP) – ore 10:15 – Compagnia: Aegeans Airlines</p>
                <p>Arrivo: Atene (ATH) – ore 13:40</p>
                <p>Incontro con l’autista e partenza in bus privato verso il Peloponneso.</p>
            </div>
        </section>

        <section>
            <div class="grid grid-cols-2 gap-2 p-6 mb-8">
                <div class="h-64">
                    <img class="w-full h-full rounded-lg object-cover" src="{{ asset('pdf_images/grecia1.webp') }}"
                        alt="">
                </div>
                <div class="h-64">
                    <img class="w-full h-full rounded-lg object-cover" src="{{ asset('pdf_images/grecia2.jpg') }}"
                        alt="">
                </div>
                <div class="h-64">
                    <img class="w-full h-full rounded-lg object-cover" src="{{ asset('pdf_images/grecia3.webp') }}"
                        alt="">
                </div>
                <div class="h-64">
                    <img class="w-full h-full rounded-lg object-cover" src="{{ asset('pdf_images/grecia4.jpg') }}"
                        alt="">
                </div>
            </div>
        </section>



        <div class="page-break"></div>
        <!-- SISTEMAZIONI -->

        <section class="flex mb-6">
            <div class="w-1/3"></div>
            <div class="w-2/3 text-right pr-6 bg-blue-500 text-white font-semibold p-2">
                <h2 class="text-xl">Sistemazioni</h2>
            </div>
        </section>

        <section class="mb-5">
            <div class="w-full bg-[#8ebf22]/30 font-semibold p-1 mb-4 flex items-center justify-between">
                <h3 class="text-lg pl-6">Astoria Hotel</h3>
                <div class="pr-6"><img src="{{ asset('pdf_images/4stelle.png') }}" alt="4 stelle"></div>
            </div>
            <div class="grid grid-cols-3 gap-2 px-6 mb-4">
                <div class="h-64">
                    <img class="w-full h-full rounded-lg object-cover" src="{{ asset('pdf_images/astoria1.JPEG') }}"
                        alt="">
                </div>
                <div class="h-64">
                    <img class="w-full h-full rounded-lg object-cover" src="{{ asset('pdf_images/astoria2.JPEG') }}"
                        alt="">
                </div>
                <div class="h-64">
                    <img class="w-full h-full rounded-lg object-cover" src="{{ asset('pdf_images/astoria3.jpg') }}"
                        alt="">
                </div>
            </div>
            <div class="px-6 mb-4">
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi eget turpis sed nulla tempor commodo.
                    Phasellus vitae lacinia dui. Donec rhoncus nibh vitae augue fringilla, nec eleifend risus tristique.
                    Mauris commodo odio enim, et feugiat purus aliquet sit amet. Donec consequat laoreet vulputate.
                    Pellentesque suscipit ex in dolor mattis, nec congue erat eleifend. Donec nulla tellus, venenatis
                    sed dolor quis, accumsan molestie lectus.</p>
            </div>
            <div class="px-6">
                <div class="w-full border-t-[#8ebf22] border-t-2 p-1 py-4 flex items-center justify-between">
                    <div class="w-1/2">
                        <p class="font-semibold">8 Karaiskaki street, Tolo, 21056, Grecia</p>
                        <p>Include trattamento di mezza pensione. Esclusa tassa di soggiorno da pagare sul posto (euro
                            10 per camera e a notte).</p>
                    </div>
                    <div class="pr-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center flex flex-col items-center">
                                <div class="text-3xl font-bold flex justify-center items-center text-gray-300">
                                    NO
                                </div>
                                <div class="font-semibold">Multipla</div>
                            </div>
                            <div class="text-center flex flex-col items-center">
                                <div class="text-3xl font-bold flex justify-center items-center text-[#8ebf22]">
                                    5
                                </div>
                                <div class="font-semibold">Singola</div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mb-5">
            <div class="w-full bg-[#8ebf22]/30 font-semibold p-1 mb-4 flex items-center justify-between">
                <h3 class="text-lg pl-6">Astoria Hotel</h3>
                <div class="pr-6"><img src="{{ asset('pdf_images/4stelle.png') }}" alt="4 stelle"></div>
            </div>
            <div class="grid grid-cols-3 gap-2 px-6 mb-4">
                <div class="h-64">
                    <img class="w-full h-full rounded-lg object-cover" src="{{ asset('pdf_images/astoria1.JPEG') }}"
                        alt="">
                </div>
                <div class="h-64">
                    <img class="w-full h-full rounded-lg object-cover" src="{{ asset('pdf_images/astoria2.JPEG') }}"
                        alt="">
                </div>
                <div class="h-64">
                    <img class="w-full h-full rounded-lg object-cover" src="{{ asset('pdf_images/astoria3.jpg') }}"
                        alt="">
                </div>
            </div>
            <div class="px-6 mb-4">
                <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Morbi eget turpis sed nulla tempor commodo.
                    Phasellus vitae lacinia dui. Donec rhoncus nibh vitae augue fringilla, nec eleifend risus tristique.
                    Mauris commodo odio enim, et feugiat purus aliquet sit amet. Donec consequat laoreet vulputate.
                    Pellentesque suscipit ex in dolor mattis, nec congue erat eleifend. Donec nulla tellus, venenatis
                    sed dolor quis, accumsan molestie lectus.</p>
            </div>
            <div class="px-6">
                <div class="w-full border-t-[#8ebf22] border-t-2 p-1 py-4 flex items-center justify-between">
                    <div class="w-1/2">
                        <p class="font-semibold">8 Karaiskaki street, Tolo, 21056, Grecia</p>
                        <p>Include trattamento di mezza pensione. Esclusa tassa di soggiorno da pagare sul posto (euro
                            10 per camera e a notte).</p>
                    </div>
                    <div class="pr-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center flex flex-col items-center">
                                <div class="text-3xl font-bold flex justify-center items-center text-gray-300">
                                    NO
                                </div>
                                <div class="font-semibold">Multipla</div>
                            </div>
                            <div class="text-center flex flex-col items-center">
                                <div class="text-3xl font-bold flex justify-center items-center text-[#8ebf22]">
                                    5
                                </div>
                                <div class="font-semibold">Singola</div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </section>



        <div class="page-break"></div>
        <!-- SERVIZI & EXTRA -->

        <section class="flex mb-6">
            <div class="w-1/3"></div>
            <div class="w-2/3 text-right pr-6 bg-blue-500 text-white font-semibold p-2">
                <h2 class="text-xl">Servizi & Extra</h2>
            </div>
        </section>

        <section class="mb-5">
            <div class="w-2/3 bg-[#8ebf22]/30 font-semibold p-1 mb-4">
                <h3 class="text-lg pl-6">Guida turistica</h3>
                <div class="w-1/3"></div>
            </div>
            <div class="flex items-center">
                <img src="{{ asset('pdf_images/guida.png') }}" alt="Guida turistica" class="h-6 pl-6">
                <div class="ml-6">
                    <p class="font-semibold">Quantità 1</p>
                    <p>Una guida turistica per tutta la durata del tour</p>
                </div>
            </div>
        </section>

        <section class="mb-5">
            <div class="w-2/3 bg-[#8ebf22]/30 font-semibold p-1 mb-4">
                <h3 class="text-lg pl-6">Bus 55 posti</h3>
                <div class="w-1/3"></div>
            </div>
            <div class="flex items-center">
                <img src="{{ asset('pdf_images/bus.png') }}" alt="Bus 55" class="h-6 pl-6">
                <div class="ml-6">
                    <p class="font-semibold">Quantità 1</p>
                    <p>Una guida turistica per tutta la durata del tour</p>
                </div>
            </div>
        </section>

        <section class="mb-5">
            <div class="w-2/3 bg-[#8ebf22]/30 font-semibold p-1 mb-4">
                <h3 class="text-lg pl-6">Ingresso Museo</h3>
                <div class="w-1/3"></div>
            </div>
            <div class="flex items-center">
                <img src="{{ asset('pdf_images/museo.png') }}" alt="museo" class="h-6 pl-6">
                <div class="ml-6">
                    <p class="font-semibold">Quantità 1</p>
                    <p>Una guida turistica per tutta la durata del tour</p>
                </div>
            </div>
        </section>

        <section class="mb-8">
            <div class="w-2/3 bg-[#8ebf22]/30 font-semibold p-1 mb-4">
                <h3 class="text-lg pl-6">Polizza UnipolSai Student Trip</h3>
                <div class="w-1/3"></div>
            </div>
            <div class="flex items-center">
                <img src="{{ asset('pdf_images/polizza.png') }}" alt="polizza" class="h-6 pl-6">
                <div class="ml-6">
                    <p class="font-semibold">Quantità 1</p>
                    <p>Una guida turistica per tutta la durata del tour</p>
                </div>
            </div>
        </section>



        <div class="page-break"></div>
        <!-- RIEPILOGO E QUOTE -->

        <section class="flex mb-6">
            <div class="w-1/3"></div>
            <div class="w-2/3 text-right pr-6 bg-blue-500 text-white font-semibold p-2">
                <h2 class="text-xl">Riepilogo e quote</h2>
            </div>
        </section>

        <section class="mb-5">
            <div class="flex items-center">
                <div class="w-6 h-10 bg-[#8ebf22]/30"></div>
                <div class="ml-6">
                    <p class="font-semibold">Asteria Hotel</p>
                    <p>Multipla, Singola con una quantità 5</p>
                </div>
            </div>
        </section>
        <section class="mb-5">
            <div class="flex items-center">
                <div class="w-6 h-10 bg-[#8ebf22]/30"></div>
                <div class="ml-6">
                    <p class="font-semibold">Polizza UnipolSai Student Trip</p>
                    <p>Quantità: 1 - Polizza viaggio con copertura annullamento viaggio</p>
                </div>
            </div>
        </section>
        <section class="mb-5">
            <div class="flex items-center">
                <div class="w-6 h-10 bg-[#8ebf22]/30"></div>
                <div class="ml-6">
                    <p class="font-semibold">Ingresso museo</p>
                    <p>Quantità: 1 - Ingressi a musei e siti archeologici</p>
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
                    <p class="font-semibold">Bevande</p>
                    <p>Nessun tipo di bevande è incluso</p>
                </div>
            </div>
        </section>

        <section class="mb-8">

            <p class="text-xs text-gray-600 mt-2 px-12">*Attenzione: tutte le tariffe proposte, essendo contingentate,
                sono
                soggette a disponibilità e riconferma all'atto della conferma del preventivo. <span
                    class="font-semibold">L'offerta è subordinata al raggiungimento di un numero minimo di 50 studenti
                    paganti e potrà essere oggetto di modifiche qualora le adesioni risultassero inferiori.</span></p>

            <div
                class="border-2 border-[#8ebf22] bg-[#8ebf22]/30 rounded-lg p-4 mt-4 mx-12 flex justify-between items-center">
                <span class="font-semibold">Quota di partecipazione per persona</span>
                <span class="text-xl font-bold">€ 995</span>
            </div>
            <p class="mt-2 text-sm text-center">Preventivo realizzato da <b>Sergio Colavecchia</b> – contatto telefonico
                <b>3477559985</b>
            </p>
        </section>

        <div class="page-break"></div>
        <!-- CONTATTI -->

        <section class="flex mb-6">
            <div class="w-1/3"></div>
            <div class="w-2/3 text-right pr-6 bg-blue-500 text-white font-semibold p-2">
                <h2 class="text-xl">Assistenza prima, dopo e durante il viaggio</h2>
            </div>
        </section>
        <section class="flex mb-6">
            <div class="w-1/3"></div>
            <div class="w-2/3 flex items-center justify-center">
                <img src="{{ asset('pdf_images/assistenza.png') }}" alt="store" class="h-12 pr-6">
                <div class="pr-6">
                    <p><span class="font-semibold">Assistenza</span> in fase di realizzazione del viaggio e itinerario
                        da parte del nostro personale qualificato.</p>
                    <p><span class="font-semibold">Assistenza Centrale operativa</span> 24h su 24 e 365 giorni l’anno
                        per qualsiasi esigenza medica <span class="font-semibold">Help-Line attiva</span> 24h su 24
                        tutti i giorni da parte del nostro personale via telefono
                        e anche tramite <span class="font-semibold">app iOS e Android La Bussola on the road</span></p>
                </div>
            </div>
        </section>

        <section class="flex mb-6">
            <div class="w-1/3"></div>
            <div class="w-2/3 text-right pr-6 bg-blue-500 text-white font-semibold p-2">
                <h2 class="text-xl">Scarica la nostra App "La Bussola on the road"</h2>
            </div>
        </section>
        <section class="flex mb-6">
            <div class="w-1/3"></div>
            <div class="w-2/3 flex items-center justify-center">
                <a href="https://apps.apple.com/it/app/4guest/id6449675980"><img src="{{ asset('pdf_images/store.png') }}" alt="store" class="h-22 pl-6"></a>
                <img src="{{ asset('pdf_images/icona.png') }}" alt="icona" class="h-22 pl-6">
            </div>
        </section>

        <section class="flex">
            <div class="w-1/3"></div>
            <div class="w-2/3 text-right pr-6 bg-blue-500 text-white font-semibold p-2">
                <h2 class="text-xl">I nostri contatti</h2>
            </div>
        </section>

        <div class="text-right">

            <p class="p-6"><span class="font-semibold">LA BUSSOLA srl – Agenzia viaggi e tour operator</span><br>
                Via Altaguardia,1 – 20135 – Milano IT<br>
                Cod. Fisc. / P. IVA 08114120960 – REA: MI – 2003676 – Capitale Sociale: € 10.000<br>
                TEL +39 02 8219 6055 – WA +39 02 8088 6574<br>
                EMAIL preventivi@labussola.it – PEC labussolamilano@pec.it</p>
        </div>

    </main>

</body>

</html>