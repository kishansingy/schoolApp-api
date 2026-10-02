<?php

namespace App\Contracts;

use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Support\Collection;

interface RecordServiceInterface
{
    public function forTable(AppTable $table): Collection;

    public function create(AppTable $table, array $data): AppRecord;

    public function update(AppRecord $record, AppTable $table, array $data): AppRecord;

    public function delete(AppRecord $record): void;

    public function lookup(AppTable $table): Collection;
}
