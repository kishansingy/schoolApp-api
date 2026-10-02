<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TableLink extends Model
{
    protected $fillable = [
        'name', 'source_table_id', 'target_table_id',
        'header_field_map', 'line_field_map',
        'source_detail_table_id', 'target_detail_table_id',
        'qty_field', 'track_qty',
        'source_status_field', 'source_status_done_value', 'source_status_partial_value',
        'source_filter_field', 'source_filter_value',
        'pull_button_label', 'allow_multi_select', 'active',
    ];

    protected $casts = [
        'header_field_map' => 'array',
        'line_field_map'   => 'array',
        'track_qty'        => 'boolean',
        'allow_multi_select' => 'boolean',
        'active'           => 'boolean',
    ];

    public function sourceTable(): BelongsTo   { return $this->belongsTo(AppTable::class, 'source_table_id'); }
    public function targetTable(): BelongsTo   { return $this->belongsTo(AppTable::class, 'target_table_id'); }
    public function sourceDetailTable(): BelongsTo { return $this->belongsTo(AppTable::class, 'source_detail_table_id'); }
    public function targetDetailTable(): BelongsTo { return $this->belongsTo(AppTable::class, 'target_detail_table_id'); }
    public function queue(): HasMany           { return $this->hasMany(TableLinkQueue::class); }
}
