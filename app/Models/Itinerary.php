<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Itinerary extends Model
{
    protected $casts = [
        'itinerario' => 'array',
        'immagini' => 'array',
    ];

    public function preventives()
    {
        return $this->hasMany(Preventive::class);
    }
}
