<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationMute extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

   public function isActive(): bool
{
    if (! $this->enabled) {// se enabled è false, ritorna falso
        return false;
    }

    if (! $this->start_at || ! $this->end_at) {// se sono nulli ritorna falso
        return false;
    }

    return now()->between($this->start_at, $this->end_at);
}

}
