<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        // ── Super Admin (no company) ──────────────────────────────────────────
        Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@platform.com'],
            [
                'name'       => 'Super Admin',
                'password'   => Hash::make('SuperAdmin@1234'),
                'company_id' => null,   // null = sees all companies
            ]
        );
        $superAdmin->syncRoles(['admin']);
        $this->command->info('✓ Super Admin: superadmin@platform.com / SuperAdmin@1234');

        // ── Demo Company 1: School ────────────────────────────────────────────
        $school = Company::firstOrCreate(
            ['slug' => 'greenwood-school'],
            [
                'name'     => 'Greenwood School',
                'email'    => 'admin@greenwood.edu',
                'phone'    => '9000000001',
                'timezone' => 'Asia/Kolkata',
                'plan'     => 'pro',
                'is_active'=> true,
            ]
        );

        $schoolAdmin = User::firstOrCreate(
            ['email' => 'admin@greenwood.edu'],
            [
                'name'       => 'School Admin',
                'password'   => Hash::make('Test@1234'),
                'company_id' => $school->id,
            ]
        );
        $schoolAdmin->syncRoles(['admin']);
        $this->command->info("✓ Company: {$school->name} (ID: {$school->id})");
        $this->command->info("  Admin: admin@greenwood.edu / Test@1234");

        // ── Demo Company 2: Another Business ─────────────────────────────────
        $biz = Company::firstOrCreate(
            ['slug' => 'sunrise-academy'],
            [
                'name'     => 'Sunrise Academy',
                'email'    => 'admin@sunrise.edu',
                'phone'    => '9000000002',
                'timezone' => 'Asia/Kolkata',
                'plan'     => 'basic',
                'is_active'=> true,
            ]
        );

        $bizAdmin = User::firstOrCreate(
            ['email' => 'admin@sunrise.edu'],
            [
                'name'       => 'Sunrise Admin',
                'password'   => Hash::make('Test@1234'),
                'company_id' => $biz->id,
            ]
        );
        $bizAdmin->syncRoles(['admin']);
        $this->command->info("✓ Company: {$biz->name} (ID: {$biz->id})");
        $this->command->info("  Admin: admin@sunrise.edu / Test@1234");

        $this->command->newLine();
        $this->command->info('Super admin sees ALL companies. Company admins see only their own data.');
    }
}
