<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppTable extends Model
{
    public function getRouteKeyName(): string
    {
        return 'name';
    }

    public function resolveRouteBinding($value, $field = null): ?self
    {
        // If the value is numeric, resolve by id (used by TableBuilder admin routes)
        // Otherwise resolve by name (used by /tables/{name}/records mobile/API routes)
        return is_numeric($value)
            ? static::where('id', $value)->firstOrFail()
            : static::where('name', $value)->firstOrFail();
    }

    protected $fillable = [
        'name', 'label', 'table_type', 'detail_of_table_id', 'parent_id',
        'schema_table',
        // Promotion
        'promote_to_table_id', 'promote_field_map', 'promote_label',
        'promote_status_field', 'promote_status_value',
        // Controls
        'extensible', 'live_feed', 'auto_number', 'auto_number_prefix',
        'auto_number_base', 'auto_number_padding', 'auto_number_suffix', 'auto_number_field',
        'create_access_controls', 'user_role',
        // Application Access
        'accessible_from', 'can_read', 'can_create', 'can_update', 'can_delete',
        'allow_web_services', 'allow_configuration',
        // WhatsApp Broadcast
        'whatsapp_broadcast', 'whatsapp_phone_field',
    ];

    protected $casts = [
        'extensible'             => 'boolean',
        'live_feed'              => 'boolean',
        'auto_number'            => 'boolean',
        'auto_number_base'       => 'integer',
        'auto_number_padding'    => 'integer',
        'create_access_controls' => 'boolean',
        'can_read'               => 'boolean',
        'can_create'             => 'boolean',
        'can_update'             => 'boolean',
        'can_delete'             => 'boolean',
        'allow_web_services'     => 'boolean',
        'allow_configuration'    => 'boolean',
        'promote_field_map'      => 'array',
    ];

    public function detailTables(): HasMany
    {
        return $this->hasMany(AppTable::class, 'detail_of_table_id');
    }

    public function headerTable(): BelongsTo
    {
        return $this->belongsTo(AppTable::class, 'detail_of_table_id');
    }

    public function isHeader(): bool  { return $this->table_type === 'header'; }
    public function isDetail(): bool  { return $this->table_type === 'detail'; }
    public function isFooter(): bool  { return $this->table_type === 'footer'; }
    public function isStandard(): bool { return $this->table_type === 'standard'; }

    public function tablePermissions(): HasMany
    {
        return $this->hasMany(TablePermission::class);
    }

    /** Get the permission row for a specific role, or null */
    public function permissionForRole(string $role): ?TablePermission
    {
        return $this->tablePermissions()->where('role', $role)->first();
    }

    public function fields(): HasMany
    {
        return $this->hasMany(AppField::class)->orderBy('order');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AppTable::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(AppTable::class, 'parent_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AppRecord::class);
    }

    public function formLayouts(): HasMany
    {
        return $this->hasMany(FormLayout::class);
    }

    // Returns all fields including inherited from parent
    public function allFields()
    {
        $fields = collect();
        if ($this->parent_id) {
            $parent = AppTable::with('fields')->find($this->parent_id);
            if ($parent) {
                $fields = $fields->merge($parent->fields);
            }
        }
        return $fields->merge($this->fields);
    }

    // Generate next auto-number for this table
    public function nextAutoNumber(): string
    {
        if (!$this->auto_number) return '';

        // Find the highest existing number by scanning records
        $prefix  = $this->auto_number_prefix ?? '';
        $suffix  = $this->auto_number_suffix ?? '';
        $base    = $this->auto_number_base ?? 1000;
        $padding = $this->auto_number_padding ?? 7;
        $field   = $this->auto_number_field ?? 'admission_no';

        $max = $base;
        $this->records()->whereNull('parent_record_id')->each(function ($record) use ($prefix, $suffix, $base, &$max) {
            $val = $record->data[$this->auto_number_field ?? 'admission_no'] ?? '';
            // Strip prefix and suffix, parse the numeric part
            $stripped = $val;
            if ($prefix && str_starts_with($stripped, $prefix)) {
                $stripped = substr($stripped, strlen($prefix));
            }
            if ($suffix && str_ends_with($stripped, $suffix)) {
                $stripped = substr($stripped, 0, -strlen($suffix));
            }
            $num = (int) $stripped;
            if ($num > $max) $max = $num;
        });

        $next   = $max + 1;
        $padded = str_pad($next, $padding, '0', STR_PAD_LEFT);
        return $prefix . $padded . $suffix;
    }
}
