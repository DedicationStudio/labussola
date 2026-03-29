<?php

namespace App\Models;

use App\QuoteRequestStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuoteRequest extends Model
{
    protected $casts = [
        'tipo_richieste' => 'array',
        'foto_introduttiva' => 'array',
        'sono_gia_disponibile' => 'boolean',
        'cliente_gestito_da_me' => 'boolean',
        'stato_richiesta' => QuoteRequestStatus::class,
    ];



     protected static function booted()
    {
        // Imposta created_by SOLO durante la creazione
        static::creating(function ($req) {
            if (empty($req->created_by)) {
                $req->created_by = auth()->id();
            }
        });

        // IMPORTANTE: Previeni modifiche a created_by durante l'update
        static::updating(function ($req) {
            // Se created_by sta per essere cambiato, ripristina il valore originale
            if ($req->isDirty('created_by') && $req->getOriginal('created_by')) {
                $req->created_by = $req->getOriginal('created_by');
            }
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assegnata_da');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function disponibilita(): HasMany
    {
        return $this->hasMany(AgentAvailability::class, 'quote_request_id', 'id');
    }


    public function emails(): HasMany
    {
        return $this->hasMany(Email::class);
    }
    public function getTipiRichiesteNomiAttribute(): string
    {
        if (!$this->tipo_richieste) {
            return '';
        }

        $records = \App\Models\TypeRequest::whereIn('id', $this->tipo_richieste)->pluck('nome');
        return $records->join(', ');
    }

    public function agenti_inviati(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'quote_request_user_sent_to');
    }

    public function agenti_gestori(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'quote_request_user_manage_from');
    }


    public function preventives(): HasMany
    {
        return $this->hasMany(Preventive::class);
    }

    public function requestWp()
    {
        return $this->belongsTo(\App\Models\RequestWp::class, 'request_wp_id');
    }

    public function duplicatedAttributes(): array
    {
        return [
            'customer_id' => $this->customer_id,
            'type_request_id' => $this->type_request_id,
            'oggetto' => $this->oggetto,
            'email_cliente' => $this->email_cliente,
            'meta_viaggio' => $this->meta_viaggio,
            'sono_gia_disponibile' => $this->sono_gia_disponibile,
            'cliente_gestito_da_me' => $this->cliente_gestito_da_me,
            'messaggio_wp' => $this->messaggio_wp,
            'note' => $this->note,
            'foto_introduttiva' => $this->foto_introduttiva,
            'data_ricezione_richiesta' => $this->data_ricezione_richiesta,
            'tipo_richieste' => $this->tipo_richieste,
        ];
    }
public function duplicateRequestWithRelations(): self
{
    // Duplica il record principale
    $new = $this->replicate();

    // Campi di sistema / workflow da resettare
    $new->request_wp_id = null;
    $new->stato_richiesta = 'in lavorazione';
    $new->created_by = auth()->id();
    $new->data_ricezione_richiesta = now();
    $new->created_at = now();
    $new->updated_at = now();

    // Salva il nuovo record principale
    $new->save();

    // ===== DUPLICA RELAZIONI =====
    // Se hai relazioni tipo note, allegati, messaggi
    if (!empty($this->notes)) {
        foreach ($this->notes as $note) {
            $newNote = $note->replicate();
            $newNote->request_wp_id = $new->id;
            $newNote->save();
        }
    }

    if (!empty($this->attachments)) {
        foreach ($this->attachments as $file) {
            $newFile = $file->replicate();
            $newFile->request_wp_id = $new->id;
            $newFile->save();
        }
    }

    if (!empty($this->messages)) {
        foreach ($this->messages as $msg) {
            $newMsg = $msg->replicate();
            $newMsg->request_wp_id = $new->id;
            $newMsg->save();
        }
    }

    // Eventuali altre relazioni simili
    // $this->altra_relazione, $this->ancora_un_altra, ecc.

    return $new;
}
public function managedByUsers()
{
    return $this->belongsToMany(
        User::class,
        'quote_request_user_manage_from',
        'quote_request_id',
        'user_id'
    );
}

public function agentAvailabilities()
{
    return $this->hasMany(AgentAvailability::class);
}
    public static function createInternalFromWeb(RequestWp $requestWp, ?int $customerId = null, ?int $typeRequestId = null, ?int $assegnataDa = null): self
    {
        if (!$customerId) {
            $customer = Customer::createFromWebRequest($requestWp);
            $customerId = $customer->id;
        }

            $new = new self();

    // ===== Campi di sistema / workflow =====
    $new->request_wp_id = $requestWp->id;
    $new->stato_richiesta = 'in lavorazione';
    $new->created_by = auth()->id();
    $new->data_ricezione_richiesta = $requestWp->created_at ?? now();
    $new->data_assegnazione = $requestWp->data_assegnazione ?? null;
    $new->customer_id = $customerId;
    $new->type_request_id = $typeRequestId;
    $new->assegnata_da = $assegnataDa;

    // ===== Mappatura diretta campi web -> interno =====
    $mapping = [
        'oggetto'               => 'viaggio',
        'email_cliente'         => 'email',
        'meta_viaggio'          => 'meta_viaggio',
        'messaggio_wp'          => 'messaggio',
        'foto_introduttiva'     => 'foto_introduttiva',
        'sono_gia_disponibile'  => 'is_gia_disponibile',
        'cliente_gestito_da_me' => 'cliente_gestito_da_me',
        'tipo_richieste'        => 'tipo_richieste',
        'motivazione_archivio'  => 'motivazione_archivio',
    ];

    $numericFields = [
        'sono_gia_disponibile',
        'cliente_gestito_da_me',
        'customer_id',
        'type_request_id',
        'assegnata_da',
    ];

    
foreach ($mapping as $internalField => $webField) {
    $value = $requestWp->$webField ?? null;

    if (in_array($internalField, $numericFields, true)) {
        $new->$internalField = is_numeric($value) ? (int) $value : null;
    } else {
        $new->$internalField = filled($value) ? $value : null;
    }
}
    


    $customerFields = [
        'nome',
        'cognome',
        'telefono',
        'email',
        'genere',
        'scuola',
        'citta_scuola',
    ];

    // Campi tecnici / inutili
    $technicalFields = [
        'id',
        'submission_id',
        'acc_privacy',
        'acc_newsletter',
        'acc_whatsapp',
    ];

    // Campi realmente utili per l’operatore
    $usefulFields = [
        'classe',
        'grado',
        'ruolo',
        'durata',
        'trasporto',
        'citta_partenza',
        'num_studenti',
        'num_docenti',
        'disabili',
        'periodo',
        'altre_info',
    ];

foreach ($usefulFields as $field) {
    if (filled($requestWp->$field)) {
        $label = ucfirst(str_replace('_', ' ', $field));
        $notes[] = "{$label}: {$requestWp->$field}";
    }
}

if (filled($requestWp->messaggio)) {
    $notes[] = "Messaggio WP: {$requestWp->messaggio}";
}

if (filled($requestWp->motivazione_archivio)) {
    $notes[] = "Motivazione archivio: {$requestWp->motivazione_archivio}";
}

// Se non c'è nulla di utile → null
$new->note = !empty($notes) ? implode("\n", $notes) : null;

// ===== Salvataggio =====
$new->save();

// ===== Duplica relazioni se presenti =====
foreach (['notes', 'attachments', 'messages'] as $relation) {
    if (isset($requestWp->$relation) && $requestWp->$relation->isNotEmpty()) {
        foreach ($requestWp->$relation as $item) {
            $newItem = $item->replicate();
            $newItem->quote_request_id = $new->id;
            $newItem->save();
        }
    }
}

return $new;
    }

}
