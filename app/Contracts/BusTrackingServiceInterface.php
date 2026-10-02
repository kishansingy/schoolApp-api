<?php

namespace App\Contracts;

use App\Models\User;

interface BusTrackingServiceInterface
{
    public function getStudentRecordIds(User $user): array;
}
