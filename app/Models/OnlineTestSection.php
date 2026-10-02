<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OnlineTestSection extends Model
{
    protected $fillable = ['test_id', 'name', 'time_limit', 'marks_per_question', 'order'];

    public function test(): BelongsTo
    {
        return $this->belongsTo(OnlineTest::class, 'test_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(OnlineTestQuestion::class, 'section_id')->orderBy('order');
    }
}
