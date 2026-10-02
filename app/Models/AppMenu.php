<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppMenu extends Model
{
    protected $fillable = ['label', 'icon', 'order', 'active'];
    protected $casts    = ['active' => 'boolean'];

    public function items(): HasMany
    {
        return $this->hasMany(AppMenuItem::class, 'menu_id')->orderBy('order');
    }
}
