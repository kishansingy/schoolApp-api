<?php

namespace App\Console\Commands;

use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Console\Command;

class DebugStudents extends Command
{
    protected $signature   = 'debug:students';
    protected $description = 'Debug student records';

    public function handle(): void
    {
        $t = AppTable::where('name', 'students')->first();
        if (!$t) { $this->error('Students table not found'); return; }

        $this->info("Table ID      : {$t->id}");
        $this->info("table_type    : {$t->table_type}");
        $this->info("schema_table  : " . ($t->schema_table ?? 'NULL'));
        $this->info("Total records : " . AppRecord::where('app_table_id', $t->id)->count());
        $this->info("No parent     : " . AppRecord::where('app_table_id', $t->id)->whereNull('parent_record_id')->count());

        // Check schema table row count
        if ($t->schema_table) {
            try {
                $count = \Illuminate\Support\Facades\DB::table($t->schema_table)->count();
                $this->info("sch table rows: {$count}");
                $sample = \Illuminate\Support\Facades\DB::table($t->schema_table)->first();
                $this->info("sch sample    : " . json_encode($sample));
            } catch (\Exception $e) {
                $this->error("sch table error: " . $e->getMessage());
            }
        }

        $sample = AppRecord::where('app_table_id', $t->id)->first();
        if ($sample) {
            $this->info("app_record data: " . json_encode($sample->data));
        }

        // Check staff table
        $staff = AppTable::where('name', 'staff')->first();
        if ($staff) {
            $this->info("\n--- Staff ---");
            $this->info("schema_table: " . ($staff->schema_table ?? 'NULL'));
            $rec1 = AppRecord::where('app_table_id', $staff->id)->first();
            if ($rec1) {
                $this->info("Record #1 schema_row_id: " . ($rec1->schema_row_id ?? 'NULL'));
                $this->info("Record #1 data: " . json_encode($rec1->data));
            }
            if ($staff->schema_table) {
                $schCount = \Illuminate\Support\Facades\DB::table($staff->schema_table)->count();
                $this->info("sch_staff rows: {$schCount}");
                $schSample = \Illuminate\Support\Facades\DB::table($staff->schema_table)->first();
                $this->info("sch_staff sample: " . json_encode($schSample));
            }
        }
    }
}
