<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveSessionSignal extends Model
{
    protected $table = 'sch_live_session_signals';
    protected $fillable = ['session_id', 'from_user_id', 'to_user_id', 'type', 'payload', 'consumed'];
}
