<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExternalRecord extends Model
{
    protected $connection = 'second_db';

    protected $table = 'labuss_e_submissions_values';

    protected $fillable = [
        'submission_id',
        'key',
        'value',
    ];

    public $timestamps = false;

    
}
