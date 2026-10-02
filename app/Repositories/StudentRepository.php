<?php

namespace App\Repositories;

use App\Contracts\StudentRepositoryInterface;
use App\Models\AppRecord;
use App\Models\AppTable;

class StudentRepository implements StudentRepositoryInterface
{
    public function findById(int|string $studentId): ?array
    {
        $tableId = AppTable::where('name', 'students')->value('id');
        if (!$tableId) return null;

        $record = AppRecord::where('app_table_id', $tableId)->find($studentId);
        if (!$record) return null;

        $d = $record->data;
        return [
            'id'           => $record->id,
            'name'         => trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? '')),
            'admission_no' => $d['admission_no'] ?? '',
            'email'        => $d['email']        ?? '',
        ];
    }
}
