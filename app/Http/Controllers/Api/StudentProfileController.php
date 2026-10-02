<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentProfileController extends Controller
{
    public function show(Request $request, $studentId)
    {
        // $studentId is app_record.id — resolve to sch_students.id
        $studentTable = AppTable::where('name', 'students')->first();
        if (!$studentTable) return response()->json(['error' => 'Students table not found'], 404);

        $appRecord = AppRecord::where('app_table_id', $studentTable->id)->find($studentId);
        if (!$appRecord) return response()->json(['error' => 'Student not found'], 404);

        // Get data from sch_students if linked, else fall back to app_record.data
        $schStudentId = $appRecord->schema_row_id;
        $studentData  = $schStudentId && $studentTable->schema_table
            ? (array) DB::table($studentTable->schema_table)->find($schStudentId)
            : ($appRecord->data ?? []);

        $profile = [
            'student'    => $studentData,
            'student_id' => $studentId,
            'sch_id'     => $schStudentId,
        ];

        // Use sch_id for related table queries when available
        $lookupId = $schStudentId ?? $studentId;

        // ── Attendance ────────────────────────────────────────────────────────
        $profile['attendance'] = $this->getRelated('student_attendance', 'student_id', $lookupId, $schStudentId !== null);

        // ── Marks ─────────────────────────────────────────────────────────────
        $profile['marks'] = $this->getRelated('marks', 'student_id', $lookupId, $schStudentId !== null);

        // ── Fee payments ──────────────────────────────────────────────────────
        $profile['fees'] = $this->getRelated('fee_payments', 'student_id', $lookupId, $schStudentId !== null);

        // ── Transport ─────────────────────────────────────────────────────────
        $profile['transport'] = $this->getRelated('student_transport', 'student_id', $lookupId, $schStudentId !== null);

        // ── Timetable ─────────────────────────────────────────────────────────
        $profile['timetable'] = $this->getTimetable($studentData);

        // ── Library ───────────────────────────────────────────────────────────
        $profile['library'] = $this->getRelated('library_issues', 'student_id', $lookupId, $schStudentId !== null);

        // ── Parents ───────────────────────────────────────────────────────────
        $profile['parents'] = $this->getParents($studentData, $lookupId, $schStudentId !== null);

        // ── Syllabus ──────────────────────────────────────────────────────────
        try {
            $profile['syllabus'] = $this->getSyllabus($studentData);
        } catch (\Throwable $e) {
            $profile['syllabus'] = [];
        }

        // ── Labels ────────────────────────────────────────────────────────────
        $profile['labels'] = $this->buildLabelMaps();

        return response()->json($profile);
    }

    public function me(Request $request)
    {
        $user = $request->user();
        $studentTable = AppTable::where('name', 'students')->first();
        if (!$studentTable) return response()->json(['error' => 'Students table not found'], 404);

        // Search in sch_students first (faster, correct)
        $rec = null;
        if ($studentTable->schema_table) {
            $schRow = DB::table($studentTable->schema_table)->where('email', $user->email)->first();
            if ($schRow) {
                $rec = AppRecord::where('app_table_id', $studentTable->id)
                    ->where('schema_row_id', $schRow->id)->first();
            }
        }

        // Fallback to JSON search in app_records
        if (!$rec) {
            $rec = AppRecord::where('app_table_id', $studentTable->id)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.email')) = ?", [$user->email])
                ->first();
        }

        if (!$rec) return response()->json(['error' => 'Student record not found for this account'], 404);

        return $this->show($request, $rec->id);
    }

    public function search(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) return response()->json([]);

        $studentTable = AppTable::where('name', 'students')->first();
        if (!$studentTable) return response()->json([]);

        // Search in sch_students if available
        if ($studentTable->schema_table) {
            $rows = DB::table($studentTable->schema_table)
                ->where(function ($query) use ($q) {
                    $query->where('first_name', 'like', "%{$q}%")
                          ->orWhere('last_name', 'like', "%{$q}%")
                          ->orWhere('admission_no', 'like', "%{$q}%")
                          ->orWhereRaw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,'')) LIKE ?", ["%{$q}%"]);
                })->limit(10)->get();

            // Map sch_id → app_record.id
            $schIds = $rows->pluck('id')->toArray();
            $appMap = AppRecord::where('app_table_id', $studentTable->id)
                ->whereIn('schema_row_id', $schIds)
                ->pluck('id', 'schema_row_id')->toArray();

            return response()->json($rows->map(fn($r) => [
                'id'           => $appMap[$r->id] ?? $r->id,
                'name'         => trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')),
                'admission_no' => $r->admission_no ?? '—',
                'class_id'     => $r->class_id ?? null,
                'photo'        => $r->photo ?? null,
                'status'       => $r->status ?? 'active',
            ]));
        }

        // Fallback
        return response()->json(
            AppRecord::where('app_table_id', $studentTable->id)
                ->where(function ($q2) use ($q) {
                    $q2->whereRaw("JSON_EXTRACT(data, '$.first_name') LIKE ?", ["%{$q}%"])
                       ->orWhereRaw("JSON_EXTRACT(data, '$.admission_no') LIKE ?", ["%{$q}%"]);
                })->limit(10)->get()->map(fn($r) => [
                    'id'           => $r->id,
                    'name'         => trim(($r->data['first_name'] ?? '') . ' ' . ($r->data['last_name'] ?? '')),
                    'admission_no' => $r->data['admission_no'] ?? '—',
                    'status'       => $r->data['status'] ?? 'active',
                ])
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function getRelated(string $tableName, string $fkField, $id, bool $useSchTable): array
    {
        $t = AppTable::where('name', $tableName)->first();
        if (!$t) return [];

        if ($useSchTable && $t->schema_table) {
            return DB::table($t->schema_table)
                ->where($fkField, $id)
                ->latest('id')->limit(100)->get()
                ->map(fn($r) => (array) $r)->toArray();
        }

        return AppRecord::where('app_table_id', $t->id)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.{$fkField}')) = ?", [$id])
            ->latest()->limit(100)->get()
            ->map(fn($r) => $r->data)->toArray();
    }

    private function getParents(array $studentData, $lookupId, bool $useSchTable): array
    {
        $parentTable = AppTable::where('name', 'parents')->first();
        if (!$parentTable) return [];

        $results = [];

        // Strategy 1: father_id / mother_id stored directly on the student record
        $refFields = ['father_id' => 'Father', 'mother_id' => 'Mother', 'guardian_id' => 'Guardian'];
        foreach ($refFields as $field => $relation) {
            $refId = $studentData[$field] ?? null;
            if (!$refId) continue;

            $row = null;

            if ($useSchTable && $parentTable->schema_table) {
                $found = DB::table($parentTable->schema_table)->where('id', $refId)->first();

                if (!$found) {
                    $appRec = AppRecord::where('app_table_id', $parentTable->id)->find($refId);
                    if ($appRec?->schema_row_id) {
                        $found = DB::table($parentTable->schema_table)->where('id', $appRec->schema_row_id)->first();
                    }
                    if (!$found && $appRec) {
                        $row = $appRec->data ?? [];
                    }
                }

                if ($found) $row = (array) $found;
            } else {
                $rec = AppRecord::where('app_table_id', $parentTable->id)->find($refId);
                $row = $rec?->data ?? null;
            }

            if (!empty($row)) {
                $row['_relation'] = $relation;
                $results[] = $row;
            }
        }

        if (!empty($results)) return $results;

        // Strategy 2: student_parent_link table (student_id + parent_id)
        $linkTable = AppTable::where('name', 'student_parent_link')->first();
        if ($linkTable) {
            $parentIds = [];

            if ($linkTable->schema_table) {
                $links = DB::table($linkTable->schema_table)->where('student_id', $lookupId)->get();
                $parentIds = $links->pluck('parent_id')->filter()->values()->toArray();
            } else {
                $links = AppRecord::where('app_table_id', $linkTable->id)
                    ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.student_id')) = ?", [$lookupId])
                    ->get();
                $parentIds = $links->map(fn($r) => $r->data['parent_id'] ?? null)->filter()->values()->toArray();
            }

            foreach ($parentIds as $parentId) {
                $row = null;
                if ($useSchTable && $parentTable->schema_table) {
                    // parentId may be app_record.id — resolve to schema row
                    $found = DB::table($parentTable->schema_table)->where('id', $parentId)->first();
                    if (!$found) {
                        $appRec = AppRecord::where('app_table_id', $parentTable->id)->find($parentId);
                        if ($appRec?->schema_row_id) {
                            $found = DB::table($parentTable->schema_table)->where('id', $appRec->schema_row_id)->first();
                        }
                        if (!$found && $appRec) $row = $appRec->data ?? [];
                    }
                    if ($found) $row = (array) $found;
                } else {
                    $appRec = AppRecord::where('app_table_id', $parentTable->id)->find($parentId);
                    $row = $appRec?->data ?? null;
                }

                if (!empty($row)) {
                    $results[] = $row;
                }
            }

            if (!empty($results)) return $results;
        }

        // Strategy 3: parents table has student_id FK pointing back to student
        if ($useSchTable && $parentTable->schema_table) {
            $rows = DB::table($parentTable->schema_table)->where('student_id', $lookupId)->get();
            return $rows->map(fn($r) => (array) $r)->toArray();
        }

        return AppRecord::where('app_table_id', $parentTable->id)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.student_id')) = ?", [$lookupId])
            ->get()->map(fn($r) => $r->data)->toArray();
    }

    private function getSyllabus(array $studentData): array
    {
        $classId = $studentData['class_id'] ?? null;

        $query = \App\Models\Syllabus::where('status', 'published');
        if ($classId) {
            $query->where('class_id', $classId);
        }

        $syllabuses = $query->orderBy('subject_id')->get();
        if ($syllabuses->isEmpty()) return [];

        // Group by subject_id
        $grouped = [];
        foreach ($syllabuses as $s) {
            $grouped[$s->subject_id][] = [
                'id'            => $s->id,
                'title'         => $s->title,
                'description'   => $s->description,
                'academic_year' => $s->academic_year,
                'topics'        => $s->topics ?? [],
            ];
        }

        // Build subject labels
        $subjectLabels = [];
        $subjectTable  = AppTable::where('name', 'subjects')->first();
        if ($subjectTable) {
            if ($subjectTable->schema_table) {
                $rows     = DB::table($subjectTable->schema_table)->get();
                $schIds   = $rows->pluck('id')->toArray();
                $appIdMap = AppRecord::where('app_table_id', $subjectTable->id)
                    ->whereIn('schema_row_id', $schIds)
                    ->pluck('id', 'schema_row_id')->toArray();
                foreach ($rows as $r) {
                    $appId = $appIdMap[$r->id] ?? $r->id;
                    $rArr  = (array) $r;
                    $subjectLabels[$appId] = $rArr['name'] ?? $rArr['subject_name'] ?? "Subject #{$r->id}";
                }
            } else {
                foreach (AppRecord::where('app_table_id', $subjectTable->id)->get() as $r) {
                    $subjectLabels[$r->id] = $r->data['name'] ?? "Subject #{$r->id}";
                }
            }
        }

        $result = [];
        foreach ($grouped as $subjectId => $items) {
            $result[] = [
                'subject_id'   => $subjectId,
                'subject_name' => $subjectLabels[$subjectId] ?? "Subject #{$subjectId}",
                'syllabuses'   => $items,
            ];
        }

        return $result;
    }

    private function getTimetable(array $studentData): array
    {
        $ttTable = AppTable::where('name', 'timetable')->first();
        if (!$ttTable || empty($studentData['section_id'])) return [];

        if ($ttTable->schema_table) {
            return DB::table($ttTable->schema_table)
                ->where('section_id', $studentData['section_id'])
                ->get()->map(fn($r) => (array) $r)->toArray();
        }

        return AppRecord::where('app_table_id', $ttTable->id)
            ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.section_id')) = ?", [$studentData['section_id']])
            ->get()->map(fn($r) => $r->data)->toArray();
    }

    private function buildLabelMaps(): array
    {
        $maps = [];
        $refTables = ['exams', 'subjects', 'classes', 'sections', 'fee_structures', 'transport_routes', 'staff'];

        foreach ($refTables as $name) {
            $t = AppTable::where('name', $name)->first();
            if (!$t) continue;

            if ($t->schema_table) {
                $schRows = DB::table($t->schema_table)->get();

                // Build schema_row_id → app_record.id map
                $schIds   = $schRows->pluck('id')->toArray();
                $appIdMap = AppRecord::where('app_table_id', $t->id)
                    ->whereIn('schema_row_id', $schIds)
                    ->pluck('id', 'schema_row_id')
                    ->toArray();

                $maps[$name] = $schRows->mapWithKeys(function ($r) use ($appIdMap) {
                    $label = $r->name ?? trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? ''));
                    // Key by app_record.id so reference fields resolve correctly
                    $appId = $appIdMap[$r->id] ?? $r->id;
                    return [$appId => trim($label)];
                })->toArray();
            } else {
                $maps[$name] = AppRecord::where('app_table_id', $t->id)->get()
                    ->mapWithKeys(function ($r) {
                        $d = $r->data;
                        $label = $d['name'] ?? trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));
                        return [$r->id => trim($label)];
                    })->toArray();
            }
        }

        return $maps;
    }
}
