<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableDependent extends Model
{
    protected $fillable = [
        'source_table_id', 'target_table_id', 'label',
        'field_map', 'target_fk_field', 'active',
    ];

    protected $casts = [
        'field_map' => 'array',
        'active'    => 'boolean',
    ];

    public function sourceTable(): BelongsTo
    {
        return $this->belongsTo(AppTable::class, 'source_table_id');
    }

    public function targetTable(): BelongsTo
    {
        return $this->belongsTo(AppTable::class, 'target_table_id');
    }
}
