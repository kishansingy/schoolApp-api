<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappLog extends Model
{
    protected $fillable = [
        'to_number', 'recipient_name', 'recipient_type', 'recipient_record_id',
        'template_name', 'message', 'status', 'wa_message_id', 'error', 'sent_by_user_id',
    ];
}
