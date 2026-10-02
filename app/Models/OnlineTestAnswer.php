<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineTestAnswer extends Model
{
    protected $fillable = ['attempt_id', 'question_id', 'selected_answer', 'is_correct', 'marks_awarded'];

    public function question(): BelongsTo
    {
        return $this->belongsTo(OnlineTestQuestion::class, 'question_id');
    }
}
