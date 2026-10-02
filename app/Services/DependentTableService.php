<?php

namespace App\Services;

use App\Models\AppRecord;
use App\Models\AppTable;
use App\Models\TableDependent;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DependentTableService
{
    public function __construct(private SchemaTableManager $schema) {}

    public function sync(AppTable $sourceTable, AppRecord $sourceRecord): void
    {
        $dependents = TableDependent::where('source_table_id', $sourceTable->id)
            ->where('active', true)
            ->with('targetTable')
            ->get();

        foreach ($dependents as $dep) {
            try {
                $this->syncOne($dep, $sourceRecord);
            } catch (\Throwable $e) {
                Log::warning("DependentTableService: failed for dep#{$dep->id}: " . $e->getMessage());
            }
        }
    }

    private function syncOne(TableDependent $dep, AppRecord $sourceRecord): void
    {
        $targetTable = $dep->targetTable;
        if (!$targetTable) return;

        $sourceData = $sourceRecord->data ?? [];
        $fkField    = $dep->target_fk_field;

        // Build target data from field map
        $targetData = [];
        foreach ($dep->field_map as $map) {
            $src = $map['source_field'] ?? null;
            $tgt = $map['target_field'] ?? null;
            if (!$src || !$tgt) continue;
            $targetData[$tgt] = ($src === '__static__')
                ? ($map['static_value'] ?? null)
                : ($sourceData[$src] ?? null);
        }

        // FK always points to source app_record.id
        $targetData[$fkField] = $sourceRecord->id;

        // ── Find existing record — check ALL possible stored values for the FK ──
        // The FK may have been stored as app_record.id OR schema_row_id (from older data)
        $possibleIds = array_unique(array_filter([
            $sourceRecord->id,
            $sourceRecord->schema_row_id,
        ]));

        $existing = null;

        // 1. Search app_records.data JSON for any of the possible FK values
        foreach ($possibleIds as $possibleId) {
            $existing = AppRecord::where('app_table_id', $targetTable->id)
                ->where(function ($q) use ($fkField, $possibleId) {
                    $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.\"{$fkField}\"')) = ?", [(string) $possibleId])
                      ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.\"{$fkField}\"')) = ?", [(int) $possibleId]);
                })->first();
            if ($existing) break;
        }

        // 2. If target has schema table, also search there directly
        if (!$existing && $targetTable->schema_table) {
            foreach ($possibleIds as $possibleId) {
                try {
                    $schRow = DB::table($targetTable->schema_table)
                        ->where($fkField, $possibleId)
                        ->first();
                    if ($schRow) {
                        // Find or create the app_record wrapper
                        $existing = AppRecord::where('app_table_id', $targetTable->id)
                            ->where('schema_row_id', $schRow->id)
                            ->first();
                        if (!$existing) {
                            $rowData = (array) $schRow;
                            unset($rowData['id'], $rowData['created_at'], $rowData['updated_at'], $rowData['parent_row_id']);
                            $existing = AppRecord::create([
                                'app_table_id'  => $targetTable->id,
                                'schema_row_id' => $schRow->id,
                                'data'          => $rowData,
                            ]);
                        }
                        break;
                    }
                } catch (\Throwable $e) { /* column may not exist */ }
            }
        }

        if ($existing) {
            // Normalise FK to app_record.id in case it was stored as schema_row_id
            $targetData[$fkField] = $sourceRecord->id;
            $merged = array_merge($existing->data ?? [], $targetData);
            if ($targetTable->schema_table && $existing->schema_row_id) {
                $this->schema->update($targetTable->schema_table, $existing->schema_row_id, $merged);
            }
            $existing->update(['data' => $merged]);
        } else {
            // Create new
            if ($targetTable->schema_table) {
                $rowId = $this->schema->insert($targetTable->schema_table, $targetData);
                AppRecord::create([
                    'app_table_id'  => $targetTable->id,
                    'schema_row_id' => $rowId,
                    'data'          => $targetData,
                ]);
            } else {
                AppRecord::create([
                    'app_table_id' => $targetTable->id,
                    'data'         => $targetData,
                ]);
            }
        }
    }
}
