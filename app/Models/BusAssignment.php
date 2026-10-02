<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusAssignment extends Model
{
    protected $fillable = ['bus_id', 'student_record_id', 'pickup_stop'];

    public function bus()
    {
        return $this->belongsTo(Bus::class);
    }
}
