<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;

class ResolveTenant
{
    public function __construct(protected TenantManager $tenant) {}

    public function handle(Request $request, Closure $next)
    {
        $company = null;

        // 1. Try X-Tenant header (useful for local dev & mobile apps)
        $tenantHeader = $request->header('X-Tenant');
        if ($tenantHeader) {
            $company = Company::findBySubdomain($tenantHeader);
        }

        // 2. Try subdomain from host
        if (!$company) {
            $company = $this->tenant->resolveFromHost($request->getHost());
        }

        if (!$company) {
            $host  = $request->getHost();
            $parts = explode('.', $host);
            // Only reject if it looks like a subdomain request
            if (count($parts) >= 3 || $tenantHeader) {
                return response()->json(['message' => 'Company not found.'], 404);
            }
            // Main domain — no tenant switching, continue (central admin)
        }

        return $next($request);
    }
}
