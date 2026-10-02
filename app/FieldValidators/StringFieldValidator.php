<?php

namespace App\FieldValidators;

use App\Contracts\FieldTypeValidatorInterface;

class StringFieldValidator implements FieldTypeValidatorInterface
{
    public function supports(): string
    {
        return 'string';
    }

    public function rules(): array
    {
        return [
            'max_length' => 'nullable|integer|min:1|max:65535',
        ];
    }

    public function validate(array $data): ?string
    {
        return null;
    }
}
