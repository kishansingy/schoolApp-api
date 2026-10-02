<?php

namespace App\Services;

use App\Contracts\FieldServiceInterface;
use App\FieldValidators\FieldTypeValidatorRegistry;
use App\Models\AppField;
use App\Models\AppTable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FieldService implements FieldServiceInterface
{
    public function __construct(
        private readonly FieldTypeValidatorRegistry $registry
    ) {}

    public function forTable(AppTable $table): Collection
    {
        return $table->fields()->with('referenceTable', 'dependentField')->get();
    }

    public function create(AppTable $table, array $data): AppField
    {
        $this->assertUniqueFieldName($table, $data['name']);
        $this->runTypeValidation($data);

        $field = $table->fields()->create($data);
        return $field->load('referenceTable', 'dependentField');
    }

    public function update(AppField $field, array $data): AppField
    {
        $this->runTypeValidation(array_merge($field->toArray(), $data));

        $field->update($data);
        return $field->load('referenceTable', 'dependentField');
    }

    public function delete(AppField $field): void
    {
        $field->delete();
    }

    private function assertUniqueFieldName(AppTable $table, string $name): void
    {
        if ($table->fields()->where('name', $name)->exists()) {
            throw ValidationException::withMessages([
                'name' => ['Field name already exists in this table.'],
            ]);
        }
    }

    private function runTypeValidation(array $data): void
    {
        $type      = $data['type'] ?? null;
        $validator = $this->registry->for($type);

        if ($validator) {
            $error = $validator->validate($data);
            if ($error) {
                throw ValidationException::withMessages(['type' => [$error]]);
            }
        }
    }
}
