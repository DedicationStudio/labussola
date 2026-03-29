<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RequestWp extends Model
{
    protected $casts = [
        'in_richiesta_interna' => 'boolean',
    ];


    protected $table = 'submissions_normalized';

    protected $fillable = [
    'submission_id',
    'nome',
    'cognome',
    'email',
    'telefono',
    'messaggio',
    'acc_privacy',
    'acc_newsletter',
    'acc_whatsapp',
    'citta_scuola',
    'classe',
    'grado',
    'telefono_scuola',
    'ruolo',
    'scuola',
    'durata',
    'trasporto',
    'meta_viaggio',
    'citta_partenza',
    'num_studenti',
    'num_docenti',
    'disabili',
    'periodo',
    'altre_info',
    'viaggio',
    'in_richiesta_interna',
];


    public $timestamps = true; // perché vogliamo aggiornare updated_at

}
