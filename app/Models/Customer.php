<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;


class Customer extends Model
{
    public function quote_requests(): HasMany
    {
        return $this->hasMany(QuoteRequest::class);
    }

    public function preventives(): HasMany
    {
        return $this->hasMany(Preventive::class);
    }
    public function emails(): HasMany 
    {
        return $this->hasMany(Email::class);
    }

    public static function createFromWebRequest(RequestWp $requestWp): self
{
    $customer = new self();

    // ===== Logica tipo cliente =====
    // se c'è scuola -> scuola
    // se c'è ragione sociale -> azienda
    // altrimenti privato
    if (!empty($requestWp->scuola)) {
        $customer->tipo_cliente = 'scuola';
    } elseif (!empty($requestWp->ragione_sociale)) {
        $customer->tipo_cliente = 'azienda';
    } else {
        $customer->tipo_cliente = 'privato';
    }

    // ===== Dati anagrafici =====
    $customer->nome           = $requestWp->nome ?? null;
    $customer->cognome        = $requestWp->cognome ?? null;
    $customer->ragione_sociale= $requestWp->scuola ?? $requestWp->ragione_sociale ?? null;

    // ===== Contatti =====
    $customer->email          = $requestWp->email ?? null;
    $customer->telefono       = $requestWp->telefono ?? $requestWp->telefono_scuola ?? null;

    // ===== Localizzazione =====
    $customer->indirizzo      = null; // se non presente
    $customer->citta          = $requestWp->citta_scuola ?? null;
    $customer->cap            = null;
    $customer->provincia      = null;
    $customer->stato          = 'Italia';

    // ===== Fiscale =====
    $customer->piva_cf        = null;
    $customer->genere         = null;

    $customer->save();

    return $customer;
}

}
