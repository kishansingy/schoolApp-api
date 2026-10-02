<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppTable;

class TableStructureController extends Controller
{
    /**
     * Returns the full structure of a header table:
     * - header fields + form layout
     * - linked detail tables (with their fields)
     * - linked footer table (with its fields)
     */
    public function show(AppTable $appTable)
    {
        $appTable->load(['fields', 'detailTables.fields', 'detailTables.formLayouts.field']);

        $detailTables = $appTable->detailTables->where('table_type', 'detail')->values();
        $footerTable  = $appTable->detailTables->firstWhere('table_type', 'footer');

        return response()->json([
            'table'        => $appTable,
            'detail_tables'=> $detailTables,
            'footer_table' => $footerTable,
        ]);
    }
}
