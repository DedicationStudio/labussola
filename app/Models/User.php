<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Database\Eloquent\Casts\Attribute;




class User extends Authenticatable
{

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */


    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
    protected $appends = ['nPreventivi', 'tipoRichieste'];


    public function getTipoRichiesteAttribute()
{
    return $this->tipo_richieste()->get()->map(function($tipo) {
        return [
            'id' => $tipo->id,
            'nome' => $tipo->nome,
        ];
    });
}
    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role?->nome, ['superadmin', 'admin', 'agente']);
    }


    public function getNameAttribute(): string
    {
        return trim("{$this->nome} {$this->cognome}") ?: 'Utente';
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getNPreventiviAttribute()
    {
        return $this->attributes['n_preventivi_gestibili'] ?? 0;
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function hasRole(string $nome): bool
    {
        return $this->role?->nome === $nome;
    }

    public function hasAnyRole(array $roles): bool
{
    return in_array($this->role?->nome, $roles, true);
}

    public function tipo_richieste() //tipo richieste gestibili dall'agente
    {
        return $this->belongsToMany(TypeRequest::class, 'type_request_user');
    }

    public function richieste_assegnate()
    {
        return $this->hasMany(QuoteRequest::class, 'assegnata_da');
    }

    public function richieste_ricevute()
    {
        return $this->belongsToMany(QuoteRequest::class, 'quote_request_user_sent_to');
    }

    public function richieste_gestite()
    {
        return $this->belongsToMany(QuoteRequest::class, 'quote_request_user_manage_from');
    }

    public function customNotifications()
    {
        // Tutte le notifiche associate a questo utente
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function unreadNotifications()
    {
        // Solo le notifiche non lette
        return $this->morphMany(Notification::class, 'notifiable')
            ->where('is_read', false);
    }

    public function notificationMute()
    {
        return $this->hasOne(NotificationMute::class);
    }




    public function preventives()
    {
        return $this->hasMany(Preventive::class);
    }

    public function hasMuteNotifications()
    {
        if (!$this->notificationMute) {
            return false; //se la tabella non ha nessun record, ritorna false, perché non hai mai silenziato
        }


        return $this->notificationMute->enabled && now()->between($this->notificationMute->start_at, $this->notificationMute->end_at);
        //verifica se ha silenziato le notifiche(enabled = true) e controlla se oggi è compreso tra l'inizio e la fine
    }

    public function notifications()
    {
        return $this->morphMany(DatabaseNotification::class, 'notifiable')
            ->orderBy('created_at', 'desc');
    }

    protected function disponibilita(): Attribute
{
    return Attribute::make(
        get: fn () => $this->n_preventivi_gestibili > 0 ? 'Disponibile' : 'Non disponibile'
    );
}
}
