<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppField extends Model
{
    protected $fillable = [
        'app_table_id', 'name', 'label', 'type',
        'reference_table_id', 'mandatory', 'readonly', 'default_value', 'order',
        'active', 'display', 'function_field', 'max_length', 'attributes',
        'choices', 'dependent_field_id', 'dependent_value',
        'calculated', 'calculated_value', 'reference_qualifier',
        'auto_number_prefix', 'auto_number_suffix', 'auto_number_padding', 'auto_number_base',
        'multiple',
        'query_value', 'query_value_sql', 'query_display_field', 'query_value_field',
        'ref_display_field', 'ref_value_field',
    ];

    protected $casts = [
        'mandatory'      => 'boolean',
        'readonly'       => 'boolean',
        'active'         => 'boolean',
        'display'        => 'boolean',
        'function_field' => 'boolean',
        'calculated'     => 'boolean',
        'choices'        => 'array',
        'max_length'     => 'integer',
        'auto_number_padding' => 'integer',
        'auto_number_base'    => 'integer',
        'multiple'            => 'boolean',
        'query_value'         => 'boolean',
    ];

    public function fieldPermissions(): HasMany
    {
        return $this->hasMany(FieldPermission::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(AppTable::class, 'app_table_id');
    }

    public function referenceTable(): BelongsTo
    {
        return $this->belongsTo(AppTable::class, 'reference_table_id');
    }

    public function dependentField(): BelongsTo
    {
        return $this->belongsTo(AppField::class, 'dependent_field_id');
    }
}
