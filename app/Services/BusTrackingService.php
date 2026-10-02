<?php

namespace App\Services;

use App\Contracts\BusTrackingServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BusTrackingService implements BusTrackingServiceInterface
{
    public function getStudentRecordIds(User $user): array
    {
        $roles = $user->roles->pluck('name')->toArray();

        if (in_array('student', $roles)) {
            return DB::table('app_records')
                ->join('app_tables', 'app_records.app_table_id', '=', 'app_tables.id')
                ->where('app_tables.name', 'students')
                ->where(function ($q) use ($user) {
                    $q->whereRaw("JSON_EXTRACT(app_records.data, '$.user_id') = ?", [$user->id])
                      ->orWhereRaw("JSON_EXTRACT(app_records.data, '$.email') = ?", [$user->email]);
                })
                ->pluck('app_records.id')
                ->toArray();
        }

        if (in_array('parent', $roles)) {
            $records = DB::table('app_records')
                ->join('app_tables', 'app_records.app_table_id', '=', 'app_tables.id')
                ->where('app_tables.name', 'parent_students')
                ->whereRaw("JSON_EXTRACT(app_records.data, '$.parent_user_id') = ?", [$user->id])
                ->pluck(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(app_records.data, '$.student_id'))"))
                ->toArray();

            if (empty($records)) {
                $records = DB::table('app_records as pr')
                    ->join('app_tables as pt', 'pr.app_table_id', '=', 'pt.id')
                    ->where('pt.name', 'parent_students')
                    ->whereRaw("JSON_EXTRACT(pr.data, '$.parent_email') = ?", [$user->email])
                    ->pluck(DB::raw("JSON_UNQUOTE(JSON_EXTRACT(pr.data, '$.student_id'))"))
                    ->toArray();
            }

            return array_map('intval', $records);
        }

        return [];
    }
}
