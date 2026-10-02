<?php

namespace App\Contracts;

/**
 * Open/Closed Principle: implement this to add validation rules
 * for a new field type without modifying existing code.
 */
interface FieldTypeValidatorInterface
{
    /** The field type this validator handles, e.g. 'reference' */
    public function supports(): string;

    /**
     * Return additional Laravel validation rules for this type.
     * Merged on top of the base field rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array;

    /**
     * Any extra business-level validation after Laravel validation passes.
     * Return an error message string, or null if valid.
     */
    public function validate(array $data): ?string;
}
