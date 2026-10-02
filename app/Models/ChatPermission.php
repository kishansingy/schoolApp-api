<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatPermission extends Model
{
    protected $table = 'sch_chat_permissions';
    protected $fillable = ['from_role', 'to_role', 'enabled'];
    protected $casts = ['enabled' => 'boolean'];
}
