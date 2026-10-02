<?php

namespace App\Http\Requests;

use App\FieldValidators\FieldTypeValidatorRegistry;
use Illuminate\Foundation\Http\FormRequest;

class StoreFieldRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $base = $this->baseRules();

        // Merge type-specific rules from the registry (Open/Closed)
        $registry = app(FieldTypeValidatorRegistry::class);
        return $registry->rulesFor($this->input('type'), $base);
    }

    private function baseRules(): array
    {
        return [
            'name'                => 'required|string|regex:/^[a-z_][a-z0-9_]*$/',
            'label'               => 'required|string',
            'type'                => 'required|in:string,integer,boolean,text,date,datetime,reference,choice,email,url,phone,currency,percent,html',
            'reference_table_id'  => 'nullable|exists:app_tables,id',
            'mandatory'           => 'boolean',
            'readonly'            => 'boolean',
            'active'              => 'boolean',
            'display'             => 'boolean',
            'function_field'      => 'boolean',
            'max_length'          => 'nullable|integer|min:1|max:65535',
            'default_value'       => 'nullable|string',
            'attributes'          => 'nullable|string',
            'choices'             => 'nullable|array',
            'choices.*.label'     => 'required_with:choices|string',
            'choices.*.value'     => 'required_with:choices|string',
            'dependent_field_id'  => 'nullable|exists:app_fields,id',
            'dependent_value'     => 'nullable|string',
            'calculated'          => 'boolean',
            'calculated_value'    => 'nullable|string',
            'reference_qualifier' => 'nullable|string',
            'order'               => 'integer',
        ];
    }
}
