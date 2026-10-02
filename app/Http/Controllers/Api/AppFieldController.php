<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppField;
use App\Models\AppTable;
use App\Services\SchemaTableManager;
use Illuminate\Http\Request;

class AppFieldController extends Controller
{
    public function index(AppTable $appTable)
    {
        return $appTable->fields()->with('referenceTable', 'dependentField')->get();
    }

    public function store(Request $request, AppTable $appTable)
    {
        $data = $request->validate([
            'name'                 => 'required|string|regex:/^[a-z_][a-z0-9_]*$/',
            'label'                => 'required|string',
            'type'                 => 'required|in:string,integer,boolean,text,date,datetime,reference,choice,email,url,phone,currency,percent,html,image,auto_number',
            'reference_table_id'   => 'nullable|exists:app_tables,id',
            'mandatory'            => 'boolean',
            'readonly'             => 'boolean',
            'active'               => 'boolean',
            'display'              => 'boolean',
            'function_field'       => 'boolean',
            'max_length'           => 'nullable|integer|min:1|max:65535',
            'default_value'        => 'nullable|string',
            'attributes'           => 'nullable|string',
            'choices'              => 'nullable|array',
            'choices.*.label'      => 'required|string',
            'choices.*.value'      => 'required|string',
            'dependent_field_id'   => 'nullable|exists:app_fields,id',
            'dependent_value'      => 'nullable|string',
            'calculated'           => 'boolean',
            'calculated_value'     => 'nullable|string',
            'reference_qualifier'  => 'nullable|string',
            'order'                => 'integer',
            'auto_number_prefix'   => 'nullable|string',
            'auto_number_suffix'   => 'nullable|string',
            'auto_number_padding'  => 'nullable|integer|min:1|max:20',
            'auto_number_base'     => 'nullable|integer|min:0',
            'multiple'             => 'boolean',
            'query_value'          => 'boolean',
            'query_value_sql'      => 'nullable|string',
            'query_table'          => 'nullable|string',
            'query_filter'         => 'nullable|string',
            'query_display_field'  => 'nullable|string',
            'query_value_field'    => 'nullable|string',
            'ref_display_field'    => 'nullable|string',
            'ref_value_field'      => 'nullable|string',
        ]);

        $exists = $appTable->fields()->where('name', $data['name'])->exists();
        if ($exists) {
            return response()->json(['message' => 'Field name already exists in this table.'], 422);
        }

        $field = $appTable->fields()->create($data);

        // Sync schema table to add the new column
        app(SchemaTableManager::class)->sync($appTable);

        return $field->load('referenceTable', 'dependentField');
    }

    public function show(AppTable $appTable, AppField $appField)
    {
        return $appField->load('referenceTable', 'dependentField');
    }

    public function update(Request $request, AppTable $appTable, AppField $appField)
    {
        $data = $request->validate([
            'label'                => 'sometimes|string',
            'type'                 => 'sometimes|in:string,integer,boolean,text,date,datetime,reference,choice,email,url,phone,currency,percent,html,image,auto_number',
            'reference_table_id'   => 'nullable|exists:app_tables,id',
            'mandatory'            => 'boolean',
            'readonly'             => 'boolean',
            'active'               => 'boolean',
            'display'              => 'boolean',
            'function_field'       => 'boolean',
            'max_length'           => 'nullable|integer|min:1|max:65535',
            'default_value'        => 'nullable|string',
            'attributes'           => 'nullable|string',
            'choices'              => 'nullable|array',
            'choices.*.label'      => 'required_with:choices|string',
            'choices.*.value'      => 'required_with:choices|string',
            'dependent_field_id'   => 'nullable|exists:app_fields,id',
            'dependent_value'      => 'nullable|string',
            'calculated'           => 'boolean',
            'calculated_value'     => 'nullable|string',
            'reference_qualifier'  => 'nullable|string',
            'order'                => 'integer',
            'auto_number_prefix'   => 'nullable|string',
            'auto_number_suffix'   => 'nullable|string',
            'auto_number_padding'  => 'nullable|integer|min:1|max:20',
            'auto_number_base'     => 'nullable|integer|min:0',
            'multiple'             => 'boolean',
            'query_value'          => 'boolean',
            'query_value_sql'      => 'nullable|string',
            'query_table'          => 'nullable|string',
            'query_filter'         => 'nullable|string',
            'query_display_field'  => 'nullable|string',
            'query_value_field'    => 'nullable|string',
            'ref_display_field'    => 'nullable|string',
            'ref_value_field'      => 'nullable|string',
        ]);

        $appField->update($data);
        return $appField->load('referenceTable', 'dependentField');
    }

    public function destroy(AppTable $appTable, AppField $appField)
    {
        $appField->delete();
        return response()->noContent();
    }

    /**
     * GET /tables/{table}/fields/{field}/next-number
     * Returns the next auto-generated value for preview in the form.
     */
    public function nextNumber(AppTable $appTable, AppField $appField)
    {
        $prefix  = $appField->auto_number_prefix ?? '';
        $suffix  = $appField->auto_number_suffix ?? '';
        $base    = $appField->auto_number_base    ?? 1000;
        $padding = $appField->auto_number_padding ?? 4;

        $max = $base;
        \App\Models\AppRecord::where('app_table_id', $appTable->id)
            ->whereNull('parent_record_id')
            ->each(function ($record) use ($appField, $prefix, $suffix, &$max) {
                $val = $record->data[$appField->name] ?? '';
                $stripped = $val;
                if ($prefix && str_starts_with($stripped, $prefix)) {
                    $stripped = substr($stripped, strlen($prefix));
                }
                if ($suffix && str_ends_with($stripped, $suffix)) {
                    $stripped = substr($stripped, 0, -strlen($suffix));
                }
                $num = (int) $stripped;
                if ($num > $max) $max = $num;
            });

        $next   = $max + 1;
        $padded = str_pad($next, $padding, '0', STR_PAD_LEFT);

        return response()->json(['value' => $prefix . $padded . $suffix]);
    }

    /**
     * POST /tables/{table}/fields/{field}/query-value
     * Executes query_value_sql with {param} substitution from posted form data.
     * Returns [{label, value}] for select/multiselect.
     */
    public function runQuery(Request $request, AppTable $appTable, AppField $appField)
    {
        if (!$appField->query_value || !$appField->query_value_sql) {
            return response()->json([]);
        }

        $params = $request->input('params', []);

        try {
            $rows = app(\App\Services\SchemaTableManager::class)->runQuery(
                $appField->query_value_sql,
                $params
            );
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if (empty($rows)) return response()->json([]);

        $cols         = array_keys($rows[0]);
        $displayField = $appField->query_display_field ?: ($cols[0] ?? null);
        $valueField   = $appField->query_value_field   ?: ($cols[1] ?? $cols[0] ?? null);

        return response()->json(array_map(fn($row) => [
            'label' => (string)($row[$displayField] ?? ''),
            'value' => (string)($row[$valueField]   ?? ''),
        ], $rows));
    }
}