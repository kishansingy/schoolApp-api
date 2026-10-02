<?php

namespace App\FieldValidators;

use App\Contracts\FieldTypeValidatorInterface;

class ReferenceFieldValidator implements FieldTypeValidatorInterface
{
    public function supports(): string
    {
        return 'reference';
    }

    public function rules(): array
    {
        return [
            'reference_table_id'  => 'required|exists:app_tables,id',
            'reference_qualifier' => 'nullable|string',
        ];
    }

    public function validate(array $data): ?string
    {
        if (empty($data['reference_table_id'])) {
            return 'A reference field must specify a reference table.';
        }
        return null;
    }
}
