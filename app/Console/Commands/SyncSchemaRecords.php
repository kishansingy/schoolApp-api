<?php

namespace App\Console\Commands;

use App\Models\AppRecord;
use App\Models\AppTable;
use App\Services\SchemaTableManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SyncSchemaRecords extends Command
{
    protected $signature   = 'schema:sync {--table= : Sync only this table name}';
    protected $description = 'Sync app_records data into schema tables (sch_*)';

    private array $idRemap = []; // app_record.id → sch_*.id

    public function __construct(private SchemaTableManager $schema)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $tableName = $this->option('table');

        $query = AppTable::whereNotNull('schema_table');
        if ($tableName) $query->where('name', $tableName);

        $tables = $query->orderBy('id')->get();
        if ($tables->isEmpty()) { $this->warn('No tables found.'); return; }

        // Disable FK checks for the duration of sync — re-enabled at end
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Pass 1: load existing links
            $this->idRemap = AppRecord::whereNotNull('schema_row_id')
                ->pluck('schema_row_id', 'id')->toArray();
            $this->info('Pre-existing links: ' . count($this->idRemap));

            // Pass 2: link unlinked records to existing sch rows by unique fields
            foreach ($tables as $appTable) {
                $this->linkExisting($appTable);
            }

            // Rebuild remap after linking
            $this->idRemap = AppRecord::whereNotNull('schema_row_id')
                ->pluck('schema_row_id', 'id')->toArray();
            $this->info('After linking: ' . count($this->idRemap) . ' mapped records.');

            // Pass 3: insert truly missing records (FK checks off so order doesn't matter)
            foreach ($tables as $appTable) {
                $this->syncTable($appTable);
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }

        $this->info('Done.');
    }

    /**
     * Pass 2: match unlinked app_records to existing sch_* rows by display/unique fields.
     */
    private function linkExisting(AppTable $appTable): void
    {
        $schTable    = $appTable->schema_table;
        $uniqueField = $appTable->auto_number_field;

        // Also try common unique fields if no auto_number_field
        $candidateFields = array_filter([
            $uniqueField,
            'staff_no', 'admission_no', 'receipt_no', 'route_no', 'isbn',
            'name', 'title', 'email',
        ]);

        $unlinked = AppRecord::where('app_table_id', $appTable->id)
            ->whereNull('schema_row_id')
            ->whereNull('parent_record_id')
            ->get();

        if ($unlinked->isEmpty()) return;

        $linked = 0;
        foreach ($unlinked as $record) {
            $data = $record->data ?? [];

            // Try each candidate field to find a matching sch row
            foreach ($candidateFields as $field) {
                if (empty($data[$field])) continue;
                if (!Schema::hasColumn($schTable, $field)) continue;

                $existing = DB::table($schTable)->where($field, $data[$field])->first();
                if ($existing) {
                    $record->update(['schema_row_id' => $existing->id]);
                    $this->idRemap[$record->id] = $existing->id;
                    $linked++;
                    break;
                }
            }
        }

        if ($linked > 0) {
            $this->info("  [{$appTable->name}] linked {$linked} existing records.");
        }
    }

    /**
     * Pass 3: insert records that still have no schema_row_id.
     */
    private function syncTable(AppTable $appTable): void
    {
        $schTable = $appTable->schema_table;
        $this->info("Syncing [{$appTable->name}] → [{$schTable}]");

        $this->schema->sync($appTable);

        $records = AppRecord::where('app_table_id', $appTable->id)
            ->whereNull('parent_record_id')
            ->get();

        $inserted = 0;
        $skipped  = 0;
        $linked   = 0;

        foreach ($records as $record) {
            // Already linked and valid
            if ($record->schema_row_id) {
                if (DB::table($schTable)->where('id', $record->schema_row_id)->exists()) {
                    $skipped++;
                    continue;
                }
            }

            $data = $this->remapForeignKeys($record->data ?? []);
            $data = $this->sanitizeEnums($schTable, $data);

            try {
                $rowId = $this->schema->insert($schTable, $data);
                $record->update(['schema_row_id' => $rowId]);
                $this->idRemap[$record->id] = $rowId;
                $inserted++;
            } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
                $this->warn("  Duplicate skipped for record #{$record->id}");
                $skipped++;
            } catch (\Exception $e) {
                $this->error("  Failed record #{$record->id}: " . $e->getMessage());
                $skipped++;
            }
        }

        $this->info("  → inserted: {$inserted}, already linked: {$skipped}, linked: {$linked}");

        // Rebuild remap after each table so next table can use fresh mappings
        $newMappings = AppRecord::where('app_table_id', $appTable->id)
            ->whereNotNull('schema_row_id')
            ->pluck('schema_row_id', 'id')->toArray();
        $this->idRemap = array_merge($this->idRemap, $newMappings);
    }

    private function remapForeignKeys(array $data): array
    {
        foreach ($data as $key => $value) {
            if (!is_numeric($value) || (int)$value <= 0) continue;
            $intVal = (int)$value;
            if (isset($this->idRemap[$intVal])) {
                $data[$key] = $this->idRemap[$intVal];
            }
        }
        return $data;
    }

    private function sanitizeEnums(string $schTable, array $data): array
    {
        try {
            $dbName  = DB::connection()->getDatabaseName();
            $cols    = DB::select(
                "SELECT COLUMN_NAME, COLUMN_TYPE FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND DATA_TYPE = 'enum'",
                [$dbName, $schTable]
            );
            foreach ($cols as $col) {
                $name = $col->COLUMN_NAME;
                if (!isset($data[$name])) continue;
                preg_match_all("/'([^']+)'/", $col->COLUMN_TYPE, $m);
                $allowed = $m[1] ?? [];
                if ($allowed && !in_array($data[$name], $allowed)) {
                    $data[$name] = $allowed[0];
                }
            }
        } catch (\Exception $e) { /* ignore */ }
        return $data;
    }
}
