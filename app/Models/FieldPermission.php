<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldPermission extends Model
{
    protected $fillable = ['app_field_id', 'role', 'visible', 'editable', 'mandatory'];

    protected $casts = [
        'visible'   => 'boolean',
        'editable'  => 'boolean',
        'mandatory' => 'boolean',
    ];

    public function appField(): BelongsTo
    {
        return $this->belongsTo(AppField::class);
    }
}
