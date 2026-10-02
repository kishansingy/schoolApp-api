<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppMenuItem extends Model
{
    protected $fillable = ['menu_id', 'label', 'icon', 'app_table_id', 'custom_url', 'order', 'active'];
    protected $casts    = ['active' => 'boolean'];

    public function menu(): BelongsTo
    {
        return $this->belongsTo(AppMenu::class, 'menu_id');
    }

    public function appTable(): BelongsTo
    {
        return $this->belongsTo(AppTable::class, 'app_table_id');
    }
}
