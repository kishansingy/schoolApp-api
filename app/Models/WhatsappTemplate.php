<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappTemplate extends Model
{
    protected $fillable = ['name', 'category', 'body', 'active', 'meta_template_name', 'meta_lang_code', 'meta_params_map'];
    protected $casts    = [
        'active'          => 'boolean',
        'meta_params_map' => 'array',
    ];
}
