<?php

namespace App\FieldValidators;

use App\Contracts\FieldTypeValidatorInterface;

/**
 * Holds all registered field-type validators.
 * New types are added by registering a new FieldTypeValidatorInterface — no existing code changes.
 */
class FieldTypeValidatorRegistry
{
    /** @var array<string, FieldTypeValidatorInterface> */
    private array $validators = [];

    public function register(FieldTypeValidatorInterface $validator): void
    {
        $this->validators[$validator->supports()] = $validator;
    }

    public function for(string $type): ?FieldTypeValidatorInterface
    {
        return $this->validators[$type] ?? null;
    }

    /** Merge base rules with type-specific rules */
    public function rulesFor(string $type, array $baseRules): array
    {
        $validator = $this->for($type);
        if (!$validator) {
            return $baseRules;
        }
        return array_merge($baseRules, $validator->rules());
    }
}
