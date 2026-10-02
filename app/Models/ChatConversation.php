<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    protected $table = 'sch_chat_conversations';
    protected $fillable = ['user_one_id', 'user_two_id', 'last_message_at'];

    public function userOne() { return $this->belongsTo(User::class, 'user_one_id'); }
    public function userTwo() { return $this->belongsTo(User::class, 'user_two_id'); }
    public function messages() { return $this->hasMany(ChatMessage::class, 'conversation_id')->orderBy('created_at'); }

    public function otherUser(int $myId): User
    {
        return $this->user_one_id === $myId ? $this->userTwo : $this->userOne;
    }

    public function unreadCount(int $myId): int
    {
        return $this->messages()->where('sender_id', '!=', $myId)->whereNull('read_at')->count();
    }
}
