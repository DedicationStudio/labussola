<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HotelPreventiveRoom extends Model
{
        protected $table = 'hotel_preventive_rooms';

       
    public function hotel_preventive()
    {
        return $this->belongsTo(HotelPreventive::class);
    }
}
