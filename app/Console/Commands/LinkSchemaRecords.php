<?php

namespace App\Console\Commands;

use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Links app_records.schema_row_id to existing sch_* rows by matching field values.
 * Run this once to fix records that were synced without schema_row_id being set.
 */
class LinkSchemaRecords extends Command
{
    protected $signature   = 'schema:link {--table= : Link only this table name}';
    protected $description = 'Link app_records to existing sch_* rows by matching unique fields';

    // Priority order of fields to use for matching
    private array $matchFields = [
        'staff_no', 'admission_no', 'receipt_no', 'route_no',
        'isbn', 'email', 'phone', 'name', 'title',
        'first_name', // fallback with last_name combo
    ];

    public function handle(): void
    {
        $tableName = $this->option('table');
        $query = AppTable::whereNotNull('schema_table');
        if ($tableName) $query->where('name', $tableName);

        $tables = $query->get();
        $totalLinked = 0;

        foreach ($tables as $appTable) {
            $linked = $this->linkTable($appTable);
            $totalLinked += $linked;
        }

        $this->info("Total linked: {$totalLinked}");
    }

    private function linkTable(AppTable $appTable): int
    {
        $schTable = $appTable->schema_table;

        // Get unlinked records
        $unlinked = AppRecord::where('app_table_id', $appTable->id)
            ->whereNull('schema_row_id')
            ->get();

        if ($unlinked->isEmpty()) return 0;

        $linked = 0;
        $columns = Schema::getColumnListing($schTable);

        foreach ($unlinked as $record) {
            $data = $record->data ?? [];
            $schRow = null;

            // Try single-field match first
            foreach ($this->matchFields as $field) {
                if (empty($data[$field])) continue;
                if (!in_array($field, $columns)) continue;

                $schRow = DB::table($schTable)->where($field, $data[$field])->first();
                if ($schRow) break;
            }

            // Try first_name + last_name combo
            if (!$schRow && !empty($data['first_name']) && !empty($data['last_name'])
                && in_array('first_name', $columns) && in_array('last_name', $columns)) {
                $schRow = DB::table($schTable)
                    ->where('first_name', $data['first_name'])
                    ->where('last_name', $data['last_name'])
                    ->first();
            }

            if ($schRow) {
                $record->update(['schema_row_id' => $schRow->id]);
                $linked++;
            }
        }

        if ($linked > 0) {
            $this->info("[{$appTable->name}] linked {$linked} / {$unlinked->count()} records");
        }

        return $linked;
    }
}
