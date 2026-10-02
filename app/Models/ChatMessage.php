<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $table = 'sch_chat_messages';
    protected $fillable = ['conversation_id', 'sender_id', 'message', 'read_at'];
    protected $casts = ['read_at' => 'datetime'];

    public function sender() { return $this->belongsTo(User::class, 'sender_id'); }
    public function conversation() { return $this->belongsTo(ChatConversation::class, 'conversation_id'); }
}
