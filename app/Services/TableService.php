<?php

namespace App\Services;

use App\Contracts\TableServiceInterface;
use App\Models\AppTable;
use Illuminate\Support\Collection;

class TableService implements TableServiceInterface
{
    public function all(): Collection
    {
        return AppTable::with('parent')->get();
    }

    public function create(array $data): AppTable
    {
        return AppTable::create($data);
    }

    public function update(AppTable $table, array $data): AppTable
    {
        $table->update($data);
        return $table->fresh();
    }

    public function delete(AppTable $table): void
    {
        $table->delete();
    }

    public function allFields(AppTable $table): Collection
    {
        return $table->allFields()->values();
    }
}
