<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppRecord;
use App\Models\AppTable;
use App\Services\SchemaTableManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Handles "promoting" a record from one table to another.
 *
 * Config lives on the SOURCE app_table:
 *   promote_to_table_id   → target AppTable id
 *   promote_field_map     → [{"from":"source_field","to":"target_field"}, ...]
 *                           use "*" as a wildcard to copy all matching field names
 *   promote_label         → button label, e.g. "Confirm Admission"
 *   promote_status_field  → field to stamp on source after promotion, e.g. "status"
 *   promote_status_value  → value to set,  e.g. "confirmed"
 */
class TablePromotionController extends Controller
{
    public function __construct(private SchemaTableManager $schema) {}
    /**
     * GET /tables/{appTable}/records/{appRecord}/promote-preview
     * Returns what data will be transferred and whether it was already promoted.
     */
    public function preview(AppTable $appTable, AppRecord $appRecord)
    {
        $this->assertPromotable($appTable);

        $targetTable = AppTable::findOrFail($appTable->promote_to_table_id);
        $mapped      = $this->buildMappedData($appTable, $appRecord->data ?? []);
        $promoted    = $this->isAlreadyPromoted($appTable, $appRecord);

        return response()->json([
            'source_table'  => $appTable->label,
            'target_table'  => $targetTable->label,
            'promote_label' => $appTable->promote_label ?? 'Promote',
            'already_promoted' => $promoted,
            'promoted_record_id' => $appRecord->data['_promoted_id'] ?? null,
            'mapped_data'   => $mapped,
            'field_map'     => $appTable->promote_field_map,
        ]);
    }

    /**
     * POST /tables/{appTable}/records/{appRecord}/promote
     * Copies the record into the target table using the field map.
     */
    public function promote(Request $request, AppTable $appTable, AppRecord $appRecord)
    {
        $this->assertPromotable($appTable);

        if ($this->isAlreadyPromoted($appTable, $appRecord)) {
            return response()->json([
                'message'    => 'This record has already been promoted.',
                'promoted_id' => $appRecord->data['_promoted_id'] ?? null,
            ], 409);
        }

        $targetTable = AppTable::findOrFail($appTable->promote_to_table_id);
        $sourceData  = $appRecord->data ?? [];
        $mapped      = $this->buildMappedData($appTable, $sourceData);

        // Allow caller to override / supplement mapped data
        $overrides = $request->input('data', []);
        $mapped    = array_merge($mapped, $overrides);

        // Always set student status to active on promotion
        $mapped['status'] = 'active';

        DB::beginTransaction();
        try {
            // Auto-number on target if configured
            if ($targetTable->auto_number && $targetTable->auto_number_field) {
                $field = $targetTable->auto_number_field;
                if (empty($mapped[$field])) {
                    $mapped[$field] = $targetTable->nextAutoNumber();
                }
            }

            $newRecord = AppRecord::create([
                'app_table_id' => $targetTable->id,
                'data'         => $mapped,
            ]);

            // If target has a schema table, insert there too and link
            if ($targetTable->schema_table) {
                $rowId = $this->schema->insert($targetTable->schema_table, $mapped);
                $newRecord->update(['schema_row_id' => $rowId]);
            }

            // Stamp source record: status + reference back to new record
            $updatedData = array_merge($sourceData, ['_promoted_id' => $newRecord->id]);
            if ($appTable->promote_status_field) {
                $updatedData[$appTable->promote_status_field] = $appTable->promote_status_value ?? 'promoted';
            }
            $appRecord->update(['data' => $updatedData]);

            // Sync status back to source schema table if it exists
            if ($appTable->schema_table && $appRecord->schema_row_id) {
                $this->schema->update($appTable->schema_table, $appRecord->schema_row_id, $updatedData);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }

        return response()->json([
            'message'        => 'Record promoted successfully.',
            'promoted_id'    => $newRecord->id,
            'target_table'   => $targetTable->name,
            'data'           => $newRecord->data,
        ], 201);
    }

    /**
     * GET /tables/{appTable}/promotion-config
     * Returns the promotion config for the UI to render the button/form.
     */
    public function config(AppTable $appTable)
    {
        if (!$appTable->promote_to_table_id) {
            return response()->json(['enabled' => false]);
        }

        $targetTable = AppTable::find($appTable->promote_to_table_id);

        return response()->json([
            'enabled'             => true,
            'promote_label'       => $appTable->promote_label ?? 'Promote',
            'target_table_id'     => $appTable->promote_to_table_id,
            'target_table_name'   => $targetTable?->name,
            'target_table_label'  => $targetTable?->label,
            'field_map'           => $appTable->promote_field_map ?? [],
            'status_field'        => $appTable->promote_status_field,
            'status_value'        => $appTable->promote_status_value,
        ]);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function assertPromotable(AppTable $table): void
    {
        abort_unless($table->promote_to_table_id, 400, 'This table has no promotion target configured.');
    }

    private function isAlreadyPromoted(AppTable $table, AppRecord $record): bool
    {
        $data = $record->data ?? [];

        // Check _promoted_id stamp
        if (!empty($data['_promoted_id'])) return true;

        // Check status field
        if ($table->promote_status_field) {
            $field = $table->promote_status_field;
            $value = $table->promote_status_value ?? 'promoted';
            if (($data[$field] ?? null) === $value) return true;
        }

        return false;
    }

    /**
     * Build target data from source using the field map.
     * Map entry: { "from": "source_field", "to": "target_field" }
     * Wildcard:  { "from": "*", "to": "*" }  → copy all fields with same name
     */
    private function buildMappedData(AppTable $table, array $sourceData): array
    {
        $map    = $table->promote_field_map ?? [];
        $result = [];

        // Check for wildcard entry
        $hasWildcard = collect($map)->contains(fn($m) => ($m['from'] ?? '') === '*');

        if ($hasWildcard || empty($map)) {
            // Copy all fields that exist in source (excluding internal ones)
            foreach ($sourceData as $key => $val) {
                if (!str_starts_with($key, '_')) {
                    $result[$key] = $val;
                }
            }
        }

        // Apply explicit mappings (can override wildcard copies)
        foreach ($map as $entry) {
            $from = $entry['from'] ?? null;
            $to   = $entry['to']   ?? null;
            if (!$from || !$to || $from === '*') continue;

            if ($to === '_skip') {
                // Explicitly exclude this field
                unset($result[$from]);
                continue;
            }

            if (array_key_exists($from, $sourceData)) {
                $result[$to] = $sourceData[$from];
                // If "to" differs from "from", remove the old key if it came from wildcard
                if ($to !== $from) unset($result[$from]);
            }
        }

        return $result;
    }
}
