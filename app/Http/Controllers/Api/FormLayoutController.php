<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AppTable;
use App\Models\FormLayout;
use Illuminate\Http\Request;

class FormLayoutController extends Controller
{
    // Returns form layout grouped by sections
    public function show(AppTable $appTable)
    {
        $layouts = FormLayout::where('app_table_id', $appTable->id)
            ->with('field.referenceTable')
            ->orderBy('section_order')
            ->orderBy('field_order')
            ->get();

        if ($layouts->isEmpty()) {
            return response()->json([]);
        }

        // Group by section, preserving order
        $sections = $layouts->groupBy('section_name')->map(function ($items, $sectionName) {
            return [
                'name'          => $sectionName,
                'section_order' => $items->first()->section_order,
                'fields'        => $items->map(function ($l) {
                    $f = $l->field;
                    if (!$f) return null;
                    return [
                        'id'                  => $f->id,
                        'name'                => $f->name,
                        'label'               => $f->label,
                        'type'                => $f->type,
                        'mandatory'           => (bool) $f->mandatory,
                        'readonly'            => (bool) $f->readonly,
                        'default_value'       => $f->default_value,
                        'choices'             => $f->choices ?? [],
                        'reference_table_id'  => $f->reference_table_id,
                        'reference_qualifier' => $f->reference_qualifier,
                        'ref_display_field'   => $f->ref_display_field ?? null,
                        'ref_value_field'     => $f->ref_value_field ?? null,
                        'multiple'            => (bool) ($f->multiple ?? false),
                        'max_length'          => $f->max_length,
                        'auto_number_prefix'  => $f->auto_number_prefix ?? null,
                        'auto_number_suffix'  => $f->auto_number_suffix ?? null,
                        'auto_number_padding' => $f->auto_number_padding ?? null,
                        'auto_number_base'    => $f->auto_number_base ?? null,
                        // Query value
                        'query_value'         => (bool) ($f->query_value ?? false),
                        'query_value_sql'     => $f->query_value_sql ?? null,
                        'query_display_field' => $f->query_display_field ?? null,
                        'query_value_field'   => $f->query_value_field ?? null,
                        // Layout
                        'col_span'            => $l->col_span ?? 6,
                        'row_span'            => $l->row_span ?? 1,
                        // Calculated
                        'calculated'          => (bool) ($f->calculated ?? false),
                        'calculated_value'    => $f->calculated_value ?? null,
                        // Dependent
                        'dependent_field_id'  => $f->dependent_field_id ?? null,
                        'dependent_value'     => $f->dependent_value ?? null,
                        'app_table_id'        => $f->app_table_id,
                    ];
                })->filter()->values(),
            ];
        })->sortBy('section_order')->values();

        return response()->json($sections);
    }

    // Save full layout (replace existing)
    public function save(Request $request, AppTable $appTable)
    {
        $data = $request->validate([
            'sections'                         => 'required|array',
            'sections.*.name'                  => 'required|string',
            'sections.*.section_order'         => 'integer',
            'sections.*.fields'                => 'array',
            'sections.*.fields.*.id'           => 'required|exists:app_fields,id',
            'sections.*.fields.*.field_order'  => 'integer',
            'sections.*.fields.*.col_span'     => 'integer|min:1|max:12',
            'sections.*.fields.*.row_span'     => 'integer|min:1|max:6',
        ]);

        FormLayout::where('app_table_id', $appTable->id)->delete();

        foreach ($data['sections'] as $sIdx => $section) {
            foreach ($section['fields'] ?? [] as $fIdx => $fieldData) {
                FormLayout::create([
                    'app_table_id'  => $appTable->id,
                    'section_name'  => $section['name'],
                    'section_order' => $section['section_order'] ?? $sIdx,
                    'app_field_id'  => $fieldData['id'],
                    'field_order'   => $fieldData['field_order'] ?? $fIdx,
                    'col_span'      => $fieldData['col_span'] ?? 6,
                    'row_span'      => $fieldData['row_span'] ?? 1,
                ]);
            }
        }

        return $this->show($appTable);
    }
}
