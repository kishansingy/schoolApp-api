<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppRecord;
use App\Models\AppTable;
use App\Models\TableLink;
use App\Models\TableLinkQueue;
use App\Services\SchemaTableManager;
use App\Services\DependentTableService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppRecordController extends Controller
{
    private DependentTableService $dependents;

    public function __construct(private SchemaTableManager $schema)
    {
        $this->dependents = new DependentTableService($schema);
    }

    public function index(AppTable $appTable)
    {
        // Always serve from app_records — single source of truth
        $records = AppRecord::where('app_table_id', $appTable->id)
            ->whereNull('parent_record_id')
            ->latest()
            ->get();

        return response()->json($records->map(fn($r) => [
            'id'   => $r->id,
            'data' => $r->data ?? [],
        ]));
    }

    public function store(Request $request, AppTable $appTable)
    {
        $fields = $appTable->allFields();
        $data   = $request->input('data', []);
        $data   = $this->applyDefaults($fields, $data);
        $data   = $this->applyAutoNumber($appTable, $fields, $data);

        $errors = $this->validateMandatory($fields, $data);
        if ($errors) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $errors], 422);
        }

        // Create app_record first — this is the canonical record
        $record = AppRecord::create([
            'app_table_id'     => $appTable->id,
            'parent_record_id' => $request->input('parent_record_id'),
            'data'             => $data,
        ]);

        // Sync to schema table silently in background
        if ($appTable->schema_table) {
            try {
                $parentRowId = $request->input('parent_row_id');
                $rowId = $this->schema->insert($appTable->schema_table, $data, $parentRowId);
                $record->update(['schema_row_id' => $rowId]);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("schema insert failed: " . $e->getMessage());
            }
        }

        $this->autoEnqueueRecord($appTable, $record->fresh());
        $this->dependents->sync($appTable, $record->fresh());

        // Always return app_record.id and app_record.data
        return response()->json(['id' => $record->id, 'data' => $data], 201);
    }

    public function show(AppTable $appTable, AppRecord $appRecord)
    {
        if ($appRecord->app_table_id !== $appTable->id) {
            return response()->json(['error' => 'Record not found'], 404);
        }

        // Always serve from app_records.data — schema table is background only
        $data = $appRecord->data ?? [];

        // If schema table exists and data is empty, try to pull from schema as fallback
        if (empty($data) && $appTable->schema_table && $appRecord->schema_row_id) {
            $row = $this->schema->find($appTable->schema_table, $appRecord->schema_row_id);
            if ($row) {
                $data = array_diff_key($row, array_flip(['id', 'created_at', 'updated_at', 'parent_row_id']));
                $appRecord->update(['data' => $data]);
            }
        }

        return response()->json(['id' => $appRecord->id, 'data' => $data]);
    }

    public function update(Request $request, AppTable $appTable, AppRecord $appRecord)
    {
        $fields = $appTable->allFields();
        $data   = $request->input('data', []);

        // Preserve readonly field values from existing data
        $existing = $appRecord->data ?? [];
        foreach ($fields as $field) {
            if ($field->readonly) {
                $data[$field->name] = $existing[$field->name] ?? $data[$field->name] ?? null;
            }
        }

        $errors = $this->validateMandatory($fields, $data);
        if ($errors) {
            return response()->json(['message' => 'Validation failed.', 'errors' => $errors], 422);
        }

        // Update app_record.data — this is the source of truth
        $appRecord->update(['data' => $data]);

        // Sync to schema table silently in background
        if ($appTable->schema_table) {
            try {
                $schemaRowId = $appRecord->schema_row_id;

                if (!$schemaRowId) {
                    // Try to find existing schema row by unique fields
                    $schTable    = $appTable->schema_table;
                    $matchFields = ['admission_no', 'staff_no', 'receipt_no', 'route_no', 'isbn', 'email'];
                    foreach ($matchFields as $mf) {
                        if (empty($data[$mf])) continue;
                        try {
                            $found = DB::table($schTable)->where($mf, $data[$mf])->value('id');
                            if ($found) { $schemaRowId = $found; break; }
                        } catch (\Exception $e) { continue; }
                    }
                    if (!$schemaRowId && !empty($data['first_name']) && !empty($data['last_name'])) {
                        try {
                            $schemaRowId = DB::table($schTable)
                                ->where('first_name', $data['first_name'])
                                ->where('last_name',  $data['last_name'])
                                ->value('id');
                        } catch (\Exception $e) {}
                    }
                    if ($schemaRowId) {
                        $appRecord->update(['schema_row_id' => $schemaRowId]);
                    }
                }

                if ($schemaRowId) {
                    $this->schema->update($appTable->schema_table, $schemaRowId, $data);
                } else {
                    $rowId = $this->schema->insert($appTable->schema_table, $data);
                    $appRecord->update(['schema_row_id' => $rowId]);
                }
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("schema update failed for record #{$appRecord->id}: " . $e->getMessage());
            }
        }

        $this->autoEnqueueRecord($appTable, $appRecord->fresh());
        $this->dependents->sync($appTable, $appRecord->fresh());

        // Always return app_record.id and updated data
        return response()->json(['id' => $appRecord->id, 'data' => $data]);
    }

    public function destroy(AppTable $appTable, AppRecord $appRecord)
    {
        if ($appTable->schema_table && $appRecord->schema_row_id) {
            $this->schema->delete($appTable->schema_table, $appRecord->schema_row_id);
        }
        $appRecord->childRecords()->delete();
        $appRecord->delete();
        return response()->noContent();
    }

    public function lookup(AppTable $appTable, Request $request)
    {
        $displayField = $request->input('display_field') ?: null;

        if ($appTable->schema_table) {
            $q = DB::table($appTable->schema_table);
            foreach ($request->input('filter', []) as $field => $value) {
                if ($value === null || $value === '') continue;
                is_array($value) ? $q->whereIn($field, $value) : $q->where($field, $value);
            }
            $schRows = $q->latest('id')->get();

            // Build schema_row_id → app_record.id map so reference fields resolve correctly
            $schIds   = $schRows->pluck('id')->toArray();
            $appIdMap = AppRecord::where('app_table_id', $appTable->id)
                ->whereIn('schema_row_id', $schIds)
                ->pluck('id', 'schema_row_id')
                ->toArray();

            return $schRows->map(function ($r) use ($appTable, $displayField, $appIdMap) {
                $data  = (array) $r;
                $label = $this->buildLabelFromArray($appTable, $data, $displayField);
                // Always expose app_record.id so reference fields store/resolve correctly
                $appId = $appIdMap[$data['id']] ?? $data['id'];
                return ['id' => $appId, 'label' => $label, 'data' => $data];
            });
        }

        // Legacy app_records path (no schema table)
        $query = $appTable->records()->latest();
        foreach ($request->input('filter', []) as $field => $value) {
            if ($value !== null && $value !== '') {
                if (is_array($value) && count($value) > 0) {
                    $query->where(function ($q) use ($field, $value) {
                        foreach ($value as $v) {
                            $q->orWhereRaw("JSON_EXTRACT(data, '$.\"{$field}\"') = ?", [$v])
                              ->orWhereRaw("JSON_EXTRACT(data, '$.\"{$field}\"') = ?", [(int) $v]);
                        }
                    });
                } else {
                    $query->where(function ($q) use ($field, $value) {
                        $q->whereRaw("JSON_EXTRACT(data, '$.\"{$field}\"') = ?", [$value])
                          ->orWhereRaw("JSON_EXTRACT(data, '$.\"{$field}\"') = ?", [(int) $value]);
                    });
                }
            }
        }
        return $query->get()->map(function ($r) use ($appTable, $displayField) {
            $data  = $r->data ?? [];
            $label = $this->buildLabel($appTable, $data, $r->id, $displayField);
            return ['id' => $r->id, 'label' => $label, 'data' => $data];
        });
    }

    // ── Header/Detail ─────────────────────────────────────────────────────────

    public function detailRecords(AppTable $appTable, AppRecord $appRecord, AppTable $detailTable)
    {
        if ($detailTable->schema_table && $appRecord->schema_row_id) {
            $rows = DB::table($detailTable->schema_table)
                ->where('parent_row_id', $appRecord->schema_row_id)
                ->get();
            return response()->json($rows->map(fn($r) => $this->wrapRow((array)$r)));
        }
        $rows = AppRecord::where('app_table_id', $detailTable->id)
            ->where('parent_record_id', $appRecord->id)->get();
        return response()->json($rows);
    }

    public function saveDetailRecords(Request $request, AppTable $appTable, AppRecord $appRecord, AppTable $detailTable)
    {
        $rows   = $request->input('rows', []);
        $fields = $detailTable->allFields();

        if ($detailTable->schema_table && $appRecord->schema_row_id) {
            DB::table($detailTable->schema_table)->where('parent_row_id', $appRecord->schema_row_id)->delete();
            AppRecord::where('app_table_id', $detailTable->id)->where('parent_record_id', $appRecord->id)->delete();

            $saved = [];
            foreach ($rows as $rowData) {
                $rowData = $this->applyDefaults($fields, $rowData);
                $rowId   = $this->schema->insert($detailTable->schema_table, $rowData, $appRecord->schema_row_id);
                $record  = AppRecord::create([
                    'app_table_id'     => $detailTable->id,
                    'schema_row_id'    => $rowId,
                    'parent_record_id' => $appRecord->id,
                    'data'             => $rowData,
                ]);
                $saved[] = $this->wrapRow($this->schema->find($detailTable->schema_table, $rowId), $record->id);
            }
            return response()->json($saved, 201);
        }

        AppRecord::where('app_table_id', $detailTable->id)->where('parent_record_id', $appRecord->id)->delete();
        $saved = [];
        foreach ($rows as $rowData) {
            $rowData = $this->applyDefaults($fields, $rowData);
            $saved[] = AppRecord::create([
                'app_table_id'     => $detailTable->id,
                'parent_record_id' => $appRecord->id,
                'data'             => $rowData,
            ]);
        }
        return response()->json($saved, 201);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function wrapRow(?array $row, ?int $appRecordId = null): array
    {
        if (!$row) return [];
        $id   = $appRecordId ?? $row['id'];
        $data = array_diff_key($row, array_flip(['id', 'created_at', 'updated_at', 'parent_row_id']));
        return ['id' => $id, 'data' => $data, 'schema_row_id' => $row['id']];
    }

    private function applyDefaults($fields, array $data): array
    {
        foreach ($fields as $field) {
            if (!isset($data[$field->name]) && $field->default_value !== null) {
                $data[$field->name] = $field->default_value;
            }
        }
        return $data;
    }

    private function applyAutoNumber(AppTable $table, $fields, array $data): array
    {
        foreach ($fields as $field) {
            if ($field->type === 'auto_number' && empty($data[$field->name])) {
                $data[$field->name] = $this->generateAutoNumber($table, $field);
            }
        }
        if ($table->auto_number && $table->auto_number_field) {
            $targetField = $fields->first(fn($f) => $f->name === $table->auto_number_field);
            if ($targetField && empty($data[$targetField->name])) {
                $data[$targetField->name] = $table->nextAutoNumber();
            }
        }
        return $data;
    }

    private function generateAutoNumber(AppTable $table, $field): string
    {
        $prefix  = $field->auto_number_prefix ?? '';
        $suffix  = $field->auto_number_suffix ?? '';
        $base    = $field->auto_number_base    ?? 1000;
        $padding = $field->auto_number_padding ?? 4;
        $max     = $base;

        if ($table->schema_table) {
            foreach (DB::table($table->schema_table)->pluck($field->name) as $val) {
                $s = $val;
                if ($prefix && str_starts_with($s, $prefix)) $s = substr($s, strlen($prefix));
                if ($suffix && str_ends_with($s, $suffix))   $s = substr($s, 0, -strlen($suffix));
                if ((int)$s > $max) $max = (int)$s;
            }
        } else {
            AppRecord::where('app_table_id', $table->id)->each(function ($r) use ($field, $prefix, $suffix, &$max) {
                $s = $r->data[$field->name] ?? '';
                if ($prefix && str_starts_with($s, $prefix)) $s = substr($s, strlen($prefix));
                if ($suffix && str_ends_with($s, $suffix))   $s = substr($s, 0, -strlen($suffix));
                if ((int)$s > $max) $max = (int)$s;
            });
        }

        return $prefix . str_pad($max + 1, $padding, '0', STR_PAD_LEFT) . $suffix;
    }

    private function validateMandatory($fields, array $data): ?array
    {
        $errors = [];
        foreach ($fields as $field) {
            $val = $data[$field->name] ?? null;
            if ($field->mandatory && ($val === null || $val === '') && $val !== '0') {
                $errors[$field->name] = ["The {$field->label} field is required."];
            }
        }
        return empty($errors) ? null : $errors;
    }

    private function buildLabelFromArray(AppTable $table, array $data, ?string $displayField = null): string
    {
        // If caller explicitly specified which field to use as label, use it
        if ($displayField && isset($data[$displayField]) && $data[$displayField] !== '') {
            return (string) $data[$displayField];
        }

        $nameParts = array_filter([$data['first_name'] ?? null, $data['last_name'] ?? null]);
        if (!empty($nameParts)) return implode(' ', $nameParts);

        $displayFields = $table->allFields()
            ->where('display', true)
            ->whereNotIn('type', ['reference', 'integer', 'currency'])
            ->take(2);

        $parts = [];
        foreach ($displayFields as $f) {
            if (!empty($data[$f->name])) $parts[] = $data[$f->name];
        }
        if (!empty($parts)) return implode(' — ', $parts);

        foreach ($data as $key => $val) {
            if (!in_array($key, ['id','created_at','updated_at','parent_row_id']) && !empty($val) && !is_numeric($val)) {
                return (string) $val;
            }
        }
        return 'Record #' . ($data['id'] ?? '?');
    }

    private function buildLabel(AppTable $table, array $data, int $id, ?string $displayField = null): string
    {
        return $this->buildLabelFromArray($table, array_merge($data, ['id' => $id]), $displayField);
    }

    /**
     * If the table has a schema_table but this record has no schema_row_id,
     * first try to find an existing sch_* row by matching fields,
     * then insert only if truly not found.
     */
    private function syncToSchema(AppTable $appTable, AppRecord $record, array $data): void
    {
        if (!$appTable->schema_table) return;
        if ($record->schema_row_id) return;

        $schTable = $appTable->schema_table;

        // Try to find existing row by common unique fields
        $matchFields = ['staff_no', 'admission_no', 'receipt_no', 'route_no', 'isbn', 'email'];
        foreach ($matchFields as $field) {
            if (empty($data[$field])) continue;
            try {
                $existing = DB::table($schTable)->where($field, $data[$field])->first();
                if ($existing) {
                    $record->update(['schema_row_id' => $existing->id]);
                    return;
                }
            } catch (\Exception $e) { continue; }
        }

        // Try first_name + last_name
        if (!empty($data['first_name']) && !empty($data['last_name'])) {
            try {
                $existing = DB::table($schTable)
                    ->where('first_name', $data['first_name'])
                    ->where('last_name', $data['last_name'])
                    ->first();
                if ($existing) {
                    $record->update(['schema_row_id' => $existing->id]);
                    return;
                }
            } catch (\Exception $e) { /* ignore */ }
        }

        // Not found — insert new row
        try {
            $rowId = $this->schema->insert($schTable, $data);
            $record->update(['schema_row_id' => $rowId]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("syncToSchema failed for record #{$record->id}: " . $e->getMessage());
        }
    }

    private function autoEnqueueRecord(AppTable $appTable, AppRecord $record): void
    {
        $links = TableLink::where('source_table_id', $appTable->id)->where('active', true)->get();
        foreach ($links as $link) {
            // Respect source filter (e.g. only enqueue status=confirmed)
            if ($link->source_filter_field) {
                $val = $record->data[$link->source_filter_field] ?? null;
                if ($val !== $link->source_filter_value) continue;
            }

            $qty = $link->track_qty && $link->qty_field
                ? (float)($record->data[$link->qty_field] ?? 0)
                : 0;

            $existing = TableLinkQueue::where('table_link_id', $link->id)
                ->where('source_record_id', $record->id)
                ->whereNull('source_detail_record_id')
                ->first();

            if ($existing) {
                if ($existing->status !== 'done') {
                    $existing->update([
                        'qty_original' => $qty,
                        'qty_pending'  => max(0, $qty - $existing->qty_transferred),
                    ]);
                }
            } else {
                TableLinkQueue::create([
                    'table_link_id'    => $link->id,
                    'source_record_id' => $record->id,
                    'qty_original'     => $qty,
                    'qty_transferred'  => 0,
                    'qty_pending'      => $qty,
                    'status'           => 'pending',
                ]);
            }
        }
    }
}
