<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ExtraService extends Model
{
     protected $casts = [
        'allegati' => 'array',
    ];
    public function getAllegatiUrlsAttribute()
{
    return collect($this->allegati ?? [])
        ->map(function ($file) {
            $cleanPath = str_replace('storage/', '', ltrim($file, '/'));
            return Storage::url($cleanPath);
        })
        ->toArray();
}
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function preventive_items()
    {
        return $this->hasMany(PreventiveExtraService::class);
    }

    public function category()
    {
        return $this->belongsTo(CategoryExtraService::class, 'category_extra_service_id');
    }

public function duplicatedAttributes(): array
{
    return [
        'supplier_id' => $this->supplier_id,

        // SAFE JSON (evita crash se null o già array)
        'tipo' => is_array($this->tipo)
            ? $this->tipo
            : json_decode($this->tipo ?? '[]', true),

        'nome' => $this->nome,

        'descrizione_servizio' => $this->descrizione_servizio,

        'icon' => $this->icon,

        // IMPORTANTISSIMO se sono file upload (Filament)
        'allegati' => is_array($this->allegati)
            ? $this->allegati
            : json_decode($this->allegati ?? '[]', true),
    ];
}

}
