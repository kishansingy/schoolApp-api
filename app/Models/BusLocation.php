<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusLocation extends Model
{
    protected $fillable = ['bus_id', 'latitude', 'longitude', 'speed', 'heading', 'is_active', 'located_at'];

    protected $casts = [
        'latitude'   => 'float',
        'longitude'  => 'float',
        'speed'      => 'float',
        'heading'    => 'float',
        'is_active'  => 'boolean',
        'located_at' => 'datetime',
    ];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }
}
