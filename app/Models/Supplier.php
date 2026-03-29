<?php

namespace App\Models;

use App\ReliabilitySupplierStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $casts = [
        'email'      => 'array',
        'telefono'   => 'array',
        'sito_web'   => 'array',
        'portale_web'=> 'array',
        'allegati'   => 'array',
    ];


    public function transports(): HasMany
    {
        return $this->hasMany(Transport::class);
    }

    public function hotels(): HasMany
    {
        return $this->hasMany(Hotel::class);
    }

    public function reliability()
    {
        return $this->belongsTo(Reliability::class);
    }

    public function type_supplier()
    {
        return $this->belongsToMany(Type::class);
    }

    public function competence_area()
    {
        return $this->belongsToMany(CompetenceGeographic::class);
    }
}
