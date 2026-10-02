<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppRecord;
use App\Models\AppTable;
use App\Models\ReportDefinition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return ReportDefinition::where('active', true)
            ->orderBy('category')->orderBy('order')->orderBy('name')
            ->get();
    }

    public function run(Request $request, ReportDefinition $report)
    {
        $params = $request->input('filters', []);
        if ($report->query_type === 'sql') {
            return $this->runSql($report, $params);
        }
        return $this->runTable($report, $params);
    }

    public function custom(Request $request)
    {
        $tableId  = $request->input('table_id');
        $groupBy  = $request->input('group_by');
        $aggField = $request->input('agg_field');
        $aggFunc  = $request->input('agg_func', 'count');

        $appTable = AppTable::find($tableId);
        if (!$appTable) return response()->json(['error' => 'Table not found'], 404);

        $rows = AppRecord::where('app_table_id', $tableId)->get()->map(fn($r) => $r->data ?? []);

        if (!$groupBy) {
            return response()->json([
                'rows'    => $rows->take(100)->values(),
                'columns' => [],
                'total'   => $rows->count(),
            ]);
        }

        $grouped = [];
        foreach ($rows as $row) {
            $key = $row[$groupBy] ?? 'Unknown';
            if (!isset($grouped[$key])) {
                $grouped[$key] = ['_group' => $key, '_count' => 0, '_sum' => 0, '_values' => []];
            }
            $grouped[$key]['_count']++;
            if ($aggField && isset($row[$aggField])) {
                $grouped[$key]['_sum'] += (float)($row[$aggField] ?? 0);
                $grouped[$key]['_values'][] = (float)($row[$aggField] ?? 0);
            }
        }

        $result = array_values(array_map(function ($g) use ($aggFunc) {
            $val = match($aggFunc) {
                'sum'   => $g['_sum'],
                'avg'   => count($g['_values']) ? round($g['_sum'] / count($g['_values']), 2) : 0,
                default => $g['_count'],
            };
            return ['group' => $g['_group'], 'value' => $val, 'count' => $g['_count']];
        }, $grouped));

        usort($result, fn($a, $b) => $b['value'] <=> $a['value']);

        return response()->json([
            'rows'  => $result,
            'total' => count($result),
            'columns' => [
                ['key' => 'group', 'label' => ucfirst(str_replace('_', ' ', $groupBy))],
                ['key' => 'count', 'label' => 'Count'],
                ['key' => 'value', 'label' => ucfirst($aggFunc) . ($aggField ? " of {$aggField}" : '')],
            ],
        ]);
    }

    public function tables()
    {
        return AppTable::select('id', 'name', 'label')->orderBy('label')->get();
    }

    public function tableFields($tableId)
    {
        $t = AppTable::find($tableId);
        if (!$t) return response()->json([]);
        return $t->allFields()->map(fn($f) => [
            'name'  => $f->name,
            'label' => $f->label,
            'type'  => $f->type,
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function runSql(ReportDefinition $report, array $params): \Illuminate\Http\JsonResponse
    {
        $sql = $this->substituteTableIds($report->sql_query);

        $sql = preg_replace_callback(
            '/\s+AND\s*\(\s*:([a-zA-Z_]+)\s+IS\s+NULL\s+OR[^)]+\)/i',
            function ($m) use ($params) {
                $val = $params[$m[1]] ?? null;
                return ($val === null || $val === '') ? '' : $m[0];
            },
            $sql
        );

        $positional = [];
        $sql = preg_replace_callback('/:([a-zA-Z_]+)/', function ($m) use ($params, &$positional) {
            $val = $params[$m[1]] ?? null;
            $positional[] = ($val === '') ? null : $val;
            return '?';
        }, $sql);

        try {
            $rows = DB::select($sql, $positional);
            $columns = $report->columns ?? $this->inferColumns($rows);
            [$rows, $columns] = $this->resolveLabels($rows, $columns);
            return response()->json([
                'rows'    => $rows,
                'columns' => $columns,
                'total'   => count($rows),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    private function runTable(ReportDefinition $report, array $params): \Illuminate\Http\JsonResponse
    {
        $appTable = AppTable::where('name', $report->table_name)->first();
        if (!$appTable) return response()->json(['rows' => [], 'columns' => [], 'total' => 0]);

        $query = AppRecord::where('app_table_id', $appTable->id);

        foreach ($params as $field => $value) {
            if ($value === null || $value === '') continue;
            if (str_ends_with($field, '_from')) {
                $f = substr($field, 0, -5);
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.\"{$f}\"')) >= ?", [$value]);
            } elseif (str_ends_with($field, '_to')) {
                $f = substr($field, 0, -3);
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.\"{$f}\"')) <= ?", [$value]);
            } else {
                $query->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.\"{$field}\"')) = ?", [$value]);
            }
        }

        $rows = $query->latest()->get()->map(fn($r) => array_merge(['_id' => $r->id], $r->data ?? []))->toArray();
        $columns = $report->columns ?? [];
        [$rows, $columns] = $this->resolveLabels($rows, $columns);

        return response()->json([
            'rows'    => $rows,
            'columns' => $columns,
            'total'   => count($rows),
        ]);
    }

    private function substituteTableIds(string $sql): string
    {
        return preg_replace_callback('/\{\{table:(\w+)\}\}/', function ($m) {
            return AppTable::where('name', $m[1])->value('id') ?? 0;
        }, $sql);
    }

    private function inferColumns(array $rows): array
    {
        if (empty($rows)) return [];
        return array_map(
            fn($k) => ['key' => $k, 'label' => ucwords(str_replace('_', ' ', $k))],
            array_keys((array)$rows[0])
        );
    }

    /**
     * Post-process result rows: find any column ending in _id,
     * look up the referenced app_record label, replace value and rename column.
     */
    private function resolveLabels(array $rows, array $columns): array
    {
        if (empty($rows)) return [$rows, $columns];

        // Map: table_name -> { id -> label }
        $labelCache = [];

        // Known _id -> table name mappings
        $idToTable = [
            'student_id'  => 'students',
            'exam_id'     => 'exams',
            'subject_id'  => 'subjects',
            'class_id'    => 'classes',
            'section_id'  => 'sections',
            'book_id'     => 'books',
            'route_id'    => 'transport_routes',
            'staff_id'    => 'staff',
            'fee_structure_id' => 'fee_structures',
        ];

        // Find which _id columns are present in the result
        $firstRow   = (array) $rows[0];
        $idCols     = array_filter(array_keys($firstRow), fn($k) => isset($idToTable[$k]));

        if (empty($idCols)) return [$rows, $columns];

        // Build label caches for each referenced table
        foreach ($idCols as $col) {
            $tableName = $idToTable[$col];
            if (isset($labelCache[$tableName])) continue;

            $appTable = AppTable::where('name', $tableName)->first();
            if (!$appTable) { $labelCache[$tableName] = []; continue; }

            $labelCache[$tableName] = AppRecord::where('app_table_id', $appTable->id)
                ->get()
                ->mapWithKeys(function ($r) {
                    $d = $r->data ?? [];
                    // Build label: full name or first display field
                    $label = trim(($d['first_name'] ?? '') . ' ' . ($d['last_name'] ?? ''));
                    if (!$label) $label = $d['name'] ?? $d['title'] ?? $d['route_name'] ?? "#{$r->id}";
                    // Append admission_no for students
                    if (!empty($d['admission_no'])) $label .= ' (' . $d['admission_no'] . ')';
                    return [$r->id => $label];
                })->toArray();
        }

        // Replace _id values with labels in each row
        $resolved = array_map(function ($row) use ($idCols, $idToTable, $labelCache) {
            $row = (array) $row;
            foreach ($idCols as $col) {
                $tableName  = $idToTable[$col];
                $id         = $row[$col] ?? null;
                $labelKey   = str_replace('_id', '', $col); // e.g. student_id -> student
                $row[$labelKey] = $labelCache[$tableName][$id] ?? ($id ? "#{$id}" : '—');
                unset($row[$col]); // remove raw id column
            }
            return $row;
        }, $rows);

        // Update column definitions: replace _id columns with label columns
        $updatedCols = array_map(function ($col) use ($idCols) {
            if (in_array($col['key'], $idCols)) {
                $newKey = str_replace('_id', '', $col['key']);
                return ['key' => $newKey, 'label' => str_replace(' Id', '', $col['label'])];
            }
            return $col;
        }, $columns);

        return [$resolved, $updatedCols];
    }
}
