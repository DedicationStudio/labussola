<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentAvailability extends Model
{
    protected $appends = ['start_date_time', 'end_date_time'];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function quote_request(): BelongsTo
    {
        return $this->belongsTo(QuoteRequest::class, 'quote_request_id', 'id');
    }

    public function getStartDateTimeAttribute(): ?string
    {
        if (!$this->start_date) return null;
        $time = $this->start_time ?? '00:00:00';
        
        // Togli i secondi se presenti (es. "08:30:00" → "08:30")
        $time = substr($time, 0, 5);
        
        // USA SPAZIO invece di T
        return "{$this->start_date} {$time}:00";
    }

    public function getEndDateTimeAttribute(): ?string
    {
        if (!$this->end_date) return null;
        $time = $this->end_time ?? '00:00:00';
        $time = substr($time, 0, 5);
        
        // USA SPAZIO invece di T
        return "{$this->end_date} {$time}:00";
    }
}
