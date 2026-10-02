<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TablePermission extends Model
{
    protected $fillable = ['app_table_id', 'role', 'can_read', 'can_create', 'can_update', 'can_delete'];

    protected $casts = [
        'can_read'   => 'boolean',
        'can_create' => 'boolean',
        'can_update' => 'boolean',
        'can_delete' => 'boolean',
    ];

    public function appTable(): BelongsTo
    {
        return $this->belongsTo(AppTable::class);
    }
}
