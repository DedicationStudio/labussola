<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransportCompany extends Model
{
    public function preventive_transports()
    {
        return $this->hasMany(PreventiveTransport::class);
    }
}
