<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Type extends Model
{
    public function supplier()
    {
        return $this->belongsToMany(Supplier::class);
    }
}
