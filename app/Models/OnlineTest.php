<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineTest extends Model
{
    protected $fillable = [
        'title', 'instructions', 'subject_id', 'class_id',
        'total_time', 'max_marks', 'status',
        'available_from', 'available_until', 'created_by',
    ];

    protected $casts = [
        'available_from'  => 'datetime',
        'available_until' => 'datetime',
    ];

    public function sections(): HasMany
    {
        return $this->hasMany(OnlineTestSection::class, 'test_id')->orderBy('order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(OnlineTestQuestion::class, 'test_id')->orderBy('order');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(OnlineTestAttempt::class, 'test_id');
    }
}
