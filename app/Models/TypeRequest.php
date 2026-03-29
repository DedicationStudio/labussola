<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeRequest extends Model
{
    public function users()
    {
        return $this->belongsToMany(User::class, 'type_request_user');
    }
}
