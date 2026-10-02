<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TableLinkQueue extends Model
{
    protected $table = 'table_link_queue';

    protected $fillable = [
        'table_link_id', 'source_record_id', 'source_detail_record_id',
        'qty_original', 'qty_transferred', 'qty_pending', 'status',
        'last_target_record_id',
    ];

    protected $casts = [
        'qty_original'    => 'float',
        'qty_transferred' => 'float',
        'qty_pending'     => 'float',
    ];

    public function tableLink(): BelongsTo    { return $this->belongsTo(TableLink::class); }
    public function sourceRecord(): BelongsTo { return $this->belongsTo(AppRecord::class, 'source_record_id'); }
}
