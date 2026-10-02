<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveSession extends Model
{
    protected $table = 'sch_live_sessions';

    protected $fillable = [
        'title', 'description', 'teacher_id', 'class_name',
        'subject', 'scheduled_at', 'duration_minutes', 'status', 'room_code',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function participants()
    {
        return $this->hasMany(LiveSessionParticipant::class, 'session_id');
    }

    public function messages()
    {
        return $this->hasMany(LiveSessionMessage::class, 'session_id');
    }
}
