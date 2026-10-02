<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineTestQuestion extends Model
{
    protected $fillable = [
        'test_id', 'section_id', 'question_text', 'question_type',
        'option_a', 'option_b', 'option_c', 'option_d',
        'correct_answer', 'marks', 'order',
    ];

    public function section(): BelongsTo
    {
        return $this->belongsTo(OnlineTestSection::class, 'section_id');
    }
}
