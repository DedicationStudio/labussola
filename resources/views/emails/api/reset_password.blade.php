<!DOCTYPE html>
<!-- Force the light theme: -->
<html data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css">


</head>

<body>
    <p style="text-align:center;">
        <!-- Richiama l'immagine incorporata tramite il suo CID -->
        <img src="cid:logo" alt="Logo La Bussola" width="150">
    </p>
    <section class="hero is-hero-bar" style="margin-top: 50px;">
        <div class="hero-body">





            <div class="level">
                <div class="level-left">
                    <div class="level-item">
                        <p>
                            Hai ricevuto questa email, perché hai inviato una richiesta di reimpostazione della
                            password.<br />
                            Clicca in basso per resettarla
                        </p>
                    </div>
                </div>
            </div>
            <div style="text-align:center; margin:30px 0;">
                <a href="{{ $resetUrl }}"
                    style="background-color:#28a745;
            color:#ffffff;
            padding:10px 10px;
            text-decoration:none;
            border-radius:5px;
            display:inline-block;
            font-weight:bold;">
                    Reset Password
                </a>

            </div>
            <div class="level">
                <div class="level-left">
                    <div class="level-item">
                        <p>
                            Questo link per reimpostare la password scadrà tra 60 minuti.
                        </p>
                    </div>
                </div>
            </div>
            <div class="level">
                <div class="level-left">
                    <div class="level-item">
                        <p>
                            Saluti,<br />Team La Bussola
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
</body>

</html>
