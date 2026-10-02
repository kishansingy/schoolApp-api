<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineTestAttempt extends Model
{
    protected $fillable = [
        'test_id', 'student_id', 'started_at', 'submitted_at',
        'current_section_index', 'total_marks', 'max_marks', 'percentage', 'status',
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function test(): BelongsTo
    {
        return $this->belongsTo(OnlineTest::class, 'test_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(OnlineTestAnswer::class, 'attempt_id');
    }
}
