<?php

namespace App\Http\Controllers\Api;

use App\Contracts\DashboardServiceInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardServiceInterface $dashboardService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return $this->dashboardService->forUser($request->user());
    }
}
