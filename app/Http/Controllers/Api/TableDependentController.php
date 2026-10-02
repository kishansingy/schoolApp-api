<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppTable;
use App\Models\TableDependent;
use Illuminate\Http\Request;

class TableDependentController extends Controller
{
    public function index(AppTable $appTable)
    {
        return response()->json(
            TableDependent::where('source_table_id', $appTable->id)
                ->with('targetTable:id,name,label')
                ->get()
        );
    }

    public function store(Request $request, AppTable $appTable)
    {
        $data = $request->validate([
            'target_table_id' => 'required|exists:app_tables,id',
            'label'           => 'nullable|string|max:100',
            'field_map'       => 'required|array|min:1',
            'target_fk_field' => 'required|string|max:100',
            'active'          => 'boolean',
        ]);

        $dep = TableDependent::create(array_merge($data, [
            'source_table_id' => $appTable->id,
        ]));

        return response()->json($dep->load('targetTable:id,name,label'), 201);
    }

    public function update(Request $request, AppTable $appTable, TableDependent $dependent)
    {
        $data = $request->validate([
            'target_table_id' => 'sometimes|exists:app_tables,id',
            'label'           => 'nullable|string|max:100',
            'field_map'       => 'sometimes|array|min:1',
            'target_fk_field' => 'sometimes|string|max:100',
            'active'          => 'boolean',
        ]);

        $dependent->update($data);
        return response()->json($dependent->load('targetTable:id,name,label'));
    }

    public function destroy(AppTable $appTable, TableDependent $dependent)
    {
        $dependent->delete();
        return response()->noContent();
    }
}
