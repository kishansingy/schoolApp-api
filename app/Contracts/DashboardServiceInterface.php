<?php

namespace App\Contracts;

use App\Models\User;
use Illuminate\Http\JsonResponse;

interface DashboardServiceInterface
{
    public function forUser(User $user): JsonResponse;
}
