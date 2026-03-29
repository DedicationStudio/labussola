<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreventiveTransport extends Model
{
    protected $casts = [
    'data_ora_partenza_andata' => 'datetime',
    'data_ora_arrivo_andata' => 'datetime',
    'data_ora_partenza_rientro' => 'datetime',
    'data_ora_arrivo_rientro' => 'datetime',
    'file_fornitore_trasporto' => 'array',

];
   

    public function preventive()
    {
        return $this->belongsTo(Preventive::class);
    }

    public function transport()
    {
        return $this->belongsTo(Transport::class);
    }

    public function transport_company()
    {
        return $this->belongsTo(TransportCompany::class);
    }
}
