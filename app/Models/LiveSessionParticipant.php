<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveSessionParticipant extends Model
{
    protected $table = 'sch_live_session_participants';
    protected $fillable = ['session_id', 'user_id', 'joined_at', 'left_at'];
    protected $casts = ['joined_at' => 'datetime', 'left_at' => 'datetime'];

    public function user() { return $this->belongsTo(User::class); }
}
