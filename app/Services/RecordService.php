<?php

namespace App\Services;

use App\Contracts\RecordServiceInterface;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RecordService implements RecordServiceInterface
{
    public function forTable(AppTable $table): Collection
    {
        return $table->records()->latest()->get();
    }

    public function create(AppTable $table, array $data): AppRecord
    {
        $fields = $table->allFields();

        $data = $this->applyDefaults($fields, $data);
        $data = $this->applyAutoNumber($table, $fields, $data);
        $this->validateMandatory($fields, $data);

        return AppRecord::create([
            'app_table_id' => $table->id,
            'data'         => $data,
        ]);
    }

    public function update(AppRecord $record, AppTable $table, array $data): AppRecord
    {
        $fields = $table->allFields();

        $data = $this->preserveReadonly($fields, $record->data, $data);
        $this->validateMandatory($fields, $data);

        $record->update(['data' => $data]);
        return $record->fresh();
    }

    public function delete(AppRecord $record): void
    {
        $record->delete();
    }

    public function lookup(AppTable $table): Collection
    {
        return $table->records()->latest()->get()->map(fn($record) => [
            'id'    => $record->id,
            'label' => collect($record->data)->first() ?? "Record #{$record->id}",
            'data'  => $record->data,
        ]);
    }

    // ── Private helpers ──────────────────────────────────────────────────────

    private function applyDefaults(Collection $fields, array $data): array
    {
        foreach ($fields as $field) {
            if (!isset($data[$field->name]) && $field->default_value !== null) {
                $data[$field->name] = $field->default_value;
            }
        }
        return $data;
    }

    private function applyAutoNumber(AppTable $table, Collection $fields, array $data): array
    {
        if (!$table->auto_number) {
            return $data;
        }

        $autoField = $fields->first(fn($f) => $f->name === 'number' || $f->readonly);
        if ($autoField) {
            $data[$autoField->name] = $table->nextAutoNumber();
        }

        return $data;
    }

    private function preserveReadonly(Collection $fields, array $original, array $data): array
    {
        foreach ($fields as $field) {
            if ($field->readonly) {
                $data[$field->name] = $original[$field->name] ?? $data[$field->name] ?? null;
            }
        }
        return $data;
    }

    private function validateMandatory(Collection $fields, array $data): void
    {
        $errors = [];

        foreach ($fields as $field) {
            $value = $data[$field->name] ?? null;
            if ($field->mandatory && ($value === null || $value === '') && $value !== '0') {
                $errors[$field->name] = ["The {$field->label} field is required."];
            }
        }

        if (!empty($errors)) {
            throw ValidationException::withMessages($errors);
        }
    }
}
