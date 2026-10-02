<?php

namespace App\FieldValidators;

use App\Contracts\FieldTypeValidatorInterface;

class ChoiceFieldValidator implements FieldTypeValidatorInterface
{
    public function supports(): string
    {
        return 'choice';
    }

    public function rules(): array
    {
        return [
            'choices'         => 'required|array|min:1',
            'choices.*.label' => 'required|string',
            'choices.*.value' => 'required|string',
        ];
    }

    public function validate(array $data): ?string
    {
        if (empty($data['choices'])) {
            return 'A choice field must have at least one choice option.';
        }
        return null;
    }
}
