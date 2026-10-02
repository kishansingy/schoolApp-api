<?php

namespace App\Contracts;

interface StudentRepositoryInterface
{
    /**
     * Find a student record by ID and return formatted data, or null.
     */
    public function findById(int|string $studentId): ?array;
}
