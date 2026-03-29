<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubmissionNormalized extends Model
{
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
];


    public $timestamps = true; // perché vogliamo aggiornare updated_at
}
