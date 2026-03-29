<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Database Notification Model
    |--------------------------------------------------------------------------
    |
    | Qui diciamo a Laravel e Filament di usare lo stesso modello per le
    | notifiche salvate nel database, così il campanello mostrerà anche
    | quelle create via Filament\Notifications\Notification::make().
    |
    */

    'database' => [
        'table' => 'notifications',
        'model' => \Filament\Notifications\Models\DatabaseNotification::class,
    ],

];
