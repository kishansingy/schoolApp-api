<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateTenant extends Command
{
    protected $signature   = 'tenant:create {name} {slug} {db_name} {admin_email} {admin_password?}';
    protected $description = 'Create a new tenant company with its own database';

    public function handle(TenantManager $tenant): int
    {
        $name     = $this->argument('name');
        $slug     = $this->argument('slug');
        $dbName   = $this->argument('db_name');
        $email    = $this->argument('admin_email');
        $password = $this->argument('admin_password') ?? 'Admin@1234';

        // Create company record in central DB
        $company = Company::create([
            'name'        => $name,
            'slug'        => $slug,
            'db_name'     => $dbName,
            'db_host'     => env('DB_HOST', '127.0.0.1'),
            'db_port'     => env('DB_PORT', '3306'),
            'db_username' => env('DB_USERNAME', 'root'),
            'db_password' => env('DB_PASSWORD', ''),
            'email'       => $email,
            'is_active'   => true,
        ]);

        $this->info("✓ Company registered: {$name} (slug: {$slug})");

        // Create tenant DB and run migrations
        $tenant->createTenantDatabase($company);
        $this->info("✓ Database created: {$dbName}");

        // Seed roles and admin user in tenant DB
        \Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\TenantInitSeeder',
            '--force' => true,
        ]);

        // Create admin user in tenant DB
        $user = \App\Models\User::create([
            'name'     => $name . ' Admin',
            'email'    => $email,
            'password' => Hash::make($password),
        ]);
        $user->assignRole('admin');

        $this->info("✓ Admin user created: {$email} / {$password}");
        $this->newLine();
        $this->info("Tenant ready! Access via: http://{$slug}.yourdomain.com");

        return self::SUCCESS;
    }
}
