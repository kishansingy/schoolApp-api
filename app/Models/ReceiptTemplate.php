<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceiptTemplate extends Model
{
    protected $fillable = [
        'name', 'description', 'app_table_id', 'related_tables',
        'html_template', 'sql_query', 'params', 'query_mode',
        'paper_size', 'orientation', 'active',
    ];

    protected $casts = [
        'active'         => 'boolean',
        'related_tables' => 'array',
        'params'         => 'array',
    ];

    public function appTable(): BelongsTo
    {
        return $this->belongsTo(AppTable::class, 'app_table_id');
    }
}
