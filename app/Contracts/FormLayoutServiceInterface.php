<?php

namespace App\Contracts;

use App\Models\AppTable;
use Illuminate\Support\Collection;

interface FormLayoutServiceInterface
{
    public function getForTable(AppTable $table): Collection;

    public function saveForTable(AppTable $table, array $sections): Collection;
}
