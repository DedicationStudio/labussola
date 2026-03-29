<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $casts = [
        'email_cc' => 'array',
        'allegati' => 'array',
        'is_draft' => 'boolean',
    ];

    // Nel file app/Models/Email.php

protected static function boot()
{
    parent::boot();
    
    static::creating(function ($email) {
        if (!$email->sent_by) {
            $email->sent_by = auth()->id();
        }
    });
}
    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function template_email()
    {
        return $this->belongsTo(EmailTemplate::class, 'email_template_id');
    }

    public function quote_request()
    {
        return $this->belongsTo(QuoteRequest::class, 'quote_request_id');
    }
    public function preventives()
    {
        return $this->belongsToMany(Preventive::class, 'email_preventive');
    }
}
