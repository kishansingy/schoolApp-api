<?php

namespace App\Providers;

use App\Contracts\AccountsServiceInterface;
use App\Contracts\BusTrackingServiceInterface;
use App\Contracts\DashboardServiceInterface;
use App\Contracts\FeeRepositoryInterface;
use App\Contracts\RazorpayServiceInterface;
use App\Contracts\StudentRepositoryInterface;
use App\Contracts\WhatsappServiceInterface;
use App\Repositories\FeeRepository;
use App\Repositories\StudentRepository;
use App\Services\AccountsService;
use App\Services\BusTrackingService;
use App\Services\DashboardService;
use App\Services\RazorpayService;
use App\Services\TenantManager;
use App\Services\WhatsappService;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->loadMigrationsFrom(database_path('migrations/school'));

        // ── Tenant manager as singleton ───────────────────────────────────────
        $this->app->singleton(TenantManager::class);

        // ── Interface → Implementation bindings ───────────────────────────────
        $this->app->bind(RazorpayServiceInterface::class,   RazorpayService::class);
        $this->app->bind(FeeRepositoryInterface::class,     FeeRepository::class);
        $this->app->bind(StudentRepositoryInterface::class, StudentRepository::class);
        $this->app->bind(AccountsServiceInterface::class,   AccountsService::class);
        $this->app->bind(DashboardServiceInterface::class,  DashboardService::class);
        $this->app->bind(WhatsappServiceInterface::class,   WhatsappService::class);
        $this->app->bind(BusTrackingServiceInterface::class, BusTrackingService::class);
    }

    public function boot(): void
    {
        $this->callAfterResolving('db', function () {
            try {
                foreach (['admin', 'manager', 'editor', 'viewer'] as $role) {
                    Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
                }
            } catch (\Throwable) {
                // Silently skip during migrations when tables don't exist yet
            }
        });
    }
}
