<!DOCTYPE html>
<html lang="it">

<head>
    <meta charset="UTF-8">
    <title>Credenziali Agente</title>
</head>

<body>
    <p style="text-align:center;">
        <!-- Richiama l'immagine incorporata tramite il suo CID -->
        <img src="cid:logo" alt="Logo La Bussola" width="150">
    </p>

    <p style="margin-top: 50px">Gentile {{ $data['nome'] }} {{ $data['cognome'] }},<br>
        ecco le credenziali per accedere all'app "La Bussola":</p><br>

    <p>Email: {{ $data['email'] }}</p>
    <p>Password: {{ $data['password'] }}</p><br>
    <p><em>Questa è una password temporanea: per motivi di sicurezza dovrà essere cambiata al primo accesso in app.</em>
    </p>

    <p>Grazie</p>
</body>

</html>
