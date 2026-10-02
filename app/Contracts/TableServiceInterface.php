<?php

namespace App\Contracts;

use App\Models\AppTable;
use Illuminate\Support\Collection;

interface TableServiceInterface
{
    public function all(): Collection;

    public function create(array $data): AppTable;

    public function update(AppTable $table, array $data): AppTable;

    public function delete(AppTable $table): void;

    public function allFields(AppTable $table): Collection;
}
