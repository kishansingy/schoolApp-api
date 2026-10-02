<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    protected $fillable = ['name', 'number_plate', 'driver_name', 'driver_phone', 'driver_user_id', 'is_active'];

    public function location()
    {
        return $this->hasOne(BusLocation::class);
    }

    public function assignments()
    {
        return $this->hasMany(BusAssignment::class);
    }
}
