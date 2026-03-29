<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PreventiveExtraService extends Model
{
     protected $casts = [
        'file_fornitore_servizi_extra' => 'array',
    ];

    public function preventive()
    {
        return $this->belongsTo(Preventive::class);
    }

    public function extra_service()
    {
        return $this->belongsTo(ExtraService::class, 'extra_service_id');
    }

    public function getSupplierAttribute()
    {
        return $this->extra_service?->supplier;
    }

    protected static function booted()
{
    static::saving(function ($service) {
        // Se c'è un extra_service_id, aggiorna automaticamente la tipologia
        if ($service->extra_service_id && empty($service->tipo)) {
            $service->tipo = $service->extra_service?->tipo;
        }

        
    });
}
}
