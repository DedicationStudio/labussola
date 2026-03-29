<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryExtraService extends Model
{
    public function extraServices()
    {
        return $this->hasMany(ExtraService::class);
    }
}
