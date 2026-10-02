<?php

namespace App\Services;

use App\Contracts\FormLayoutServiceInterface;
use App\Models\AppTable;
use App\Models\FormLayout;
use Illuminate\Support\Collection;

class FormLayoutService implements FormLayoutServiceInterface
{
    public function getForTable(AppTable $table): Collection
    {
        $layouts = FormLayout::where('app_table_id', $table->id)
            ->with('field.referenceTable')
            ->orderBy('section_order')
            ->orderBy('field_order')
            ->get();

        return $layouts->groupBy('section_name')->map(fn($items, $name) => [
            'name'          => $name,
            'section_order' => $items->first()->section_order,
            'fields'        => $items->map(fn($l) => $l->field)->filter()->values(),
        ])->values();
    }

    public function saveForTable(AppTable $table, array $sections): Collection
    {
        FormLayout::where('app_table_id', $table->id)->delete();

        foreach ($sections as $sIdx => $section) {
            foreach ($section['fields'] ?? [] as $fIdx => $fieldData) {
                FormLayout::create([
                    'app_table_id'  => $table->id,
                    'section_name'  => $section['name'],
                    'section_order' => $section['section_order'] ?? $sIdx,
                    'app_field_id'  => $fieldData['id'],
                    'field_order'   => $fieldData['field_order'] ?? $fIdx,
                ]);
            }
        }

        return $this->getForTable($table);
    }
}
