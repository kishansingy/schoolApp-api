<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FormLayout extends Model
{
    protected $fillable = [
        'app_table_id', 'section_name', 'section_order',
        'app_field_id', 'field_order', 'col_span', 'row_span',
    ];

    protected $casts = [
        'col_span' => 'integer',
        'row_span' => 'integer',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(AppTable::class, 'app_table_id');
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(AppField::class, 'app_field_id');
    }
}
