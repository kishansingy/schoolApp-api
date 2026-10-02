<?php

namespace App\Contracts;

use App\Models\AppField;
use App\Models\AppTable;
use Illuminate\Support\Collection;

interface FieldServiceInterface
{
    public function forTable(AppTable $table): Collection;

    public function create(AppTable $table, array $data): AppField;

    public function update(AppField $field, array $data): AppField;

    public function delete(AppField $field): void;
}
