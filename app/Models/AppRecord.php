<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppRecord extends Model
{
    protected $fillable = ['app_table_id', 'parent_record_id', 'data'];

    protected $casts = [
        'data' => 'array',
    ];

    public function appTable(): BelongsTo
    {
        return $this->belongsTo(AppTable::class, 'app_table_id');
    }

    public function parentRecord(): BelongsTo
    {
        return $this->belongsTo(AppRecord::class, 'parent_record_id');
    }

    public function childRecords(): HasMany
    {
        return $this->hasMany(AppRecord::class, 'parent_record_id');
    }
}
