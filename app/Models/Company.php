<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Lives in the CENTRAL database.
 * Stores tenant registry — db credentials per company.
 */
class Company extends Model
{
    protected $connection = 'central'; // always uses central DB

    protected $fillable = [
        'name', 'slug', 'domain',
        'db_name', 'db_host', 'db_port', 'db_username', 'db_password',
        'email', 'phone', 'plan', 'is_active',
    ];

    protected $hidden = ['db_password'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public static function findBySubdomain(string $subdomain): ?self
    {
        return static::where('slug', $subdomain)->where('is_active', true)->first();
    }

    public static function findByDomain(string $domain): ?self
    {
        return static::where('domain', $domain)->where('is_active', true)->first();
    }
}
