<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppRecord;
use App\Models\AppTable;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    /**
     * POST /tables/{table}/import
     * Accepts JSON rows array, inserts as records.
     */
    public function store(Request $request, AppTable $appTable)
    {
        $request->validate(['rows' => 'required|array', 'rows.*' => 'array']);

        $fields  = $appTable->allFields();
        $created = 0;
        $errors  = [];

        foreach ($request->input('rows') as $i => $row) {
            // Apply defaults
            foreach ($fields as $f) {
                if (!isset($row[$f->name]) && $f->default_value !== null) {
                    $row[$f->name] = $f->default_value;
                }
            }
            // Validate mandatory
            foreach ($fields as $f) {
                if ($f->mandatory && empty($row[$f->name])) {
                    $errors[] = "Row " . ($i + 1) . ": '{$f->label}' is required.";
                }
            }
            if (empty($errors)) {
                AppRecord::create(['app_table_id' => $appTable->id, 'data' => $row]);
                $created++;
            }
        }

        return response()->json([
            'created' => $created,
            'errors'  => $errors,
        ], empty($errors) ? 201 : 422);
    }
}
