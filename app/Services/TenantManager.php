<?php

namespace App\Services;

use App\Models\Company;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TenantManager
{
    protected ?Company $current = null;

    /**
     * Resolve tenant from request host and switch DB connection.
     * Returns the company or null if not found.
     */
    public function resolveFromHost(string $host): ?Company
    {
        // Strip port if present
        $host = explode(':', $host)[0];

        // Try exact domain match first (custom domains)
        $company = Company::findByDomain($host);

        // Fallback: extract subdomain (greenwood.yourdomain.com → greenwood)
        if (!$company) {
            $parts = explode('.', $host);
            if (count($parts) >= 3) {
                $subdomain = $parts[0];
                $company   = Company::findBySubdomain($subdomain);
            }
        }

        if ($company) {
            $this->switchTo($company);
        }

        return $company;
    }

    /**
     * Dynamically configure and switch to the tenant's database.
     */
    public function switchTo(Company $company): void
    {
        Config::set('database.connections.tenant', [
            'driver'    => 'mysql',
            'host'      => $company->db_host,
            'port'      => $company->db_port,
            'database'  => $company->db_name,
            'username'  => $company->db_username,
            'password'  => $company->db_password,
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => '',
            'strict'    => true,
            'engine'    => null,
        ]);

        // Purge any cached connection so Laravel uses the new config
        DB::purge('tenant');
        DB::reconnect('tenant');

        // Set as default so all models use it automatically
        Config::set('database.default', 'tenant');

        $this->current = $company;
    }

    public function current(): ?Company
    {
        return $this->current;
    }

    /**
     * Create a new tenant database and run migrations on it.
     */
    public function createTenantDatabase(Company $company): void
    {
        // Create the DB using root/central connection
        DB::connection('central')->statement(
            "CREATE DATABASE IF NOT EXISTS `{$company->db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        // Switch to new tenant DB and run migrations
        $this->switchTo($company);

        \Artisan::call('migrate', [
            '--database' => 'tenant',
            '--force'    => true,
        ]);
    }
}
