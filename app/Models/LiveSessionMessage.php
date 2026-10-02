<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveSessionMessage extends Model
{
    protected $table = 'sch_live_session_messages';
    protected $fillable = ['session_id', 'user_id', 'message'];

    public function user() { return $this->belongsTo(User::class); }
}
