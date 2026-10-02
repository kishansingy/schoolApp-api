<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportDefinition extends Model
{
    protected $fillable = [
        'name', 'category', 'description', 'icon',
        'query_type', 'sql_query', 'table_name',
        'filters', 'columns', 'active', 'order',
    ];

    protected $casts = [
        'filters' => 'array',
        'columns' => 'array',
        'active'  => 'boolean',
    ];
}
