<?php

namespace App\Services;

use App\Models\AppField;
use App\Models\AppTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class SchemaTableManager
{
    // Map app field types to DB column types
    private static array $typeMap = [
        'string'     => 'string',
        'integer'    => 'bigInteger',
        'boolean'    => 'boolean',
        'text'       => 'text',
        'date'       => 'date',
        'datetime'   => 'dateTime',
        'reference'  => 'string',   // stores the referenced record id or value
        'choice'     => 'string',
        'email'      => 'string',
        'url'        => 'string',
        'phone'      => 'string',
        'currency'   => 'decimal',
        'percent'    => 'decimal',
        'html'       => 'text',
        'image'      => 'string',
        'auto_number'=> 'string',
    ];

    /**
     * Ensure the schema table exists and has all required columns.
     * Called when a table is created or a field is added/updated.
     */
    public function sync(AppTable $table): void
    {
        $schemaTable = $table->schema_table;
        if (!$schemaTable) return;

        $fields = $table->allFields();

        if (!Schema::hasTable($schemaTable)) {
            Schema::create($schemaTable, function (Blueprint $bp) use ($fields) {
                $bp->id();
                $bp->unsignedBigInteger('parent_row_id')->nullable()->index(); // for detail tables
                foreach ($fields as $field) {
                    $this->addColumn($bp, $field);
                }
                $bp->timestamps();
            });
        } else {
            // Add parent_row_id if missing (for pre-existing tables)
            if (!Schema::hasColumn($schemaTable, 'parent_row_id')) {
                Schema::table($schemaTable, function (Blueprint $bp) {
                    $bp->unsignedBigInteger('parent_row_id')->nullable()->after('id');
                });
            }
            // Add any missing columns
            Schema::table($schemaTable, function (Blueprint $bp) use ($schemaTable, $fields) {
                foreach ($fields as $field) {
                    if (!Schema::hasColumn($schemaTable, $field->name)) {
                        $this->addColumn($bp, $field);
                    }
                }
            });
        }
    }

    private function addColumn(Blueprint $bp, AppField $field): void
    {
        $dbType = self::$typeMap[$field->type] ?? 'string';
        $col = match ($dbType) {
            'decimal'    => $bp->decimal($field->name, 15, 2)->nullable()->default(null),
            'bigInteger' => $bp->bigInteger($field->name)->nullable()->default(null),
            'boolean'    => $bp->boolean($field->name)->nullable()->default(null),
            'text'       => $bp->text($field->name)->nullable(),
            'date'       => $bp->date($field->name)->nullable(),
            'dateTime'   => $bp->dateTime($field->name)->nullable(),
            default      => $bp->string($field->name, $field->max_length ?? 255)->nullable()->default(null),
        };
    }

    /**
     * Insert a row into the schema table. Returns the new row id.
     * Fills any NOT NULL columns that are missing with safe defaults.
     */
    public function insert(string $schemaTable, array $data, ?int $parentRowId = null): int
    {
        $row = $this->filterColumns($schemaTable, $data);
        if ($parentRowId) $row['parent_row_id'] = $parentRowId;
        $row['created_at'] = now();
        $row['updated_at'] = now();

        // Fill missing NOT NULL columns with safe defaults to avoid DB errors
        $row = $this->fillRequiredColumns($schemaTable, $row);

        return DB::table($schemaTable)->insertGetId($row);
    }

    /**
     * Update a row in the schema table.
     */
    public function update(string $schemaTable, int $rowId, array $data): void
    {
        $row = $this->filterColumns($schemaTable, $data);
        $row['updated_at'] = now();
        DB::table($schemaTable)->where('id', $rowId)->update($row);
    }

    /**
     * Delete a row from the schema table.
     */
    public function delete(string $schemaTable, int $rowId): void
    {
        DB::table($schemaTable)->where('id', $rowId)->delete();
    }

    /**
     * Get a single row as array.
     */
    public function find(string $schemaTable, int $rowId): ?array
    {
        $row = DB::table($schemaTable)->where('id', $rowId)->first();
        return $row ? (array) $row : null;
    }

    /**
     * Get all rows, optionally filtered.
     */
    public function all(string $schemaTable, array $filters = []): array
    {
        $q = DB::table($schemaTable);
        foreach ($filters as $col => $val) {
            if ($val !== null && $val !== '') {
                $q->where($col, $val);
            }
        }
        return array_map(fn($r) => (array) $r, $q->latest('id')->get()->all());
    }

    /**
     * Execute a raw query with named parameter substitution.
     * Params use {name} syntax — replaced with PDO bindings.
     */
    public function runQuery(string $sql, array $params = []): array
    {
        $bindings = [];
        $sql = preg_replace_callback('/\{(\w+)\}/', function ($m) use ($params, &$bindings) {
            $placeholder = ':qp_' . $m[1];
            $bindings[$placeholder] = $params[$m[1]] ?? null;
            return $placeholder;
        }, $sql);

        $rows = DB::select($sql, $bindings);
        return array_map(fn($r) => (array) $r, $rows);
    }

    /**
     * Only keep keys that are actual columns in the schema table.
     */
    private function filterColumns(string $schemaTable, array $data): array
    {
        $columns = Schema::getColumnListing($schemaTable);
        $skip    = ['id', 'created_at', 'updated_at', 'parent_row_id'];
        return array_filter(
            $data,
            fn($k) => in_array($k, $columns) && !in_array($k, $skip),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * For any NOT NULL column without a default that isn't in $row,
     * inject a safe empty value so the insert doesn't fail.
     */
    private function fillRequiredColumns(string $schemaTable, array $row): array
    {
        try {
            $conn    = DB::connection();
            $dbName  = $conn->getDatabaseName();
            $colInfo = DB::select("
                SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
                FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?
            ", [$dbName, $schemaTable]);

            foreach ($colInfo as $col) {
                $name = $col->COLUMN_NAME;
                if (in_array($name, ['id', 'created_at', 'updated_at', 'parent_row_id'])) continue;
                if (isset($row[$name])) continue;
                if ($col->IS_NULLABLE === 'YES' || $col->COLUMN_DEFAULT !== null) continue;

                // NOT NULL, no default, not in row — inject safe default
                $row[$name] = match(true) {
                    in_array($col->DATA_TYPE, ['int','bigint','smallint','tinyint','mediumint']) => 0,
                    in_array($col->DATA_TYPE, ['decimal','float','double'])                     => 0.00,
                    in_array($col->DATA_TYPE, ['date'])                                         => '0000-00-00',
                    in_array($col->DATA_TYPE, ['datetime','timestamp'])                         => '0000-00-00 00:00:00',
                    default => '',
                };
            }
        } catch (\Exception $e) {
            // If introspection fails, just proceed — DB will throw if truly required
        }

        return $row;
    }
}
