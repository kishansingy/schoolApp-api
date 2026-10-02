<?php

namespace App\Http\Requests;

use App\FieldValidators\FieldTypeValidatorRegistry;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFieldRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $base = [
            'label'               => 'sometimes|string',
            'type'                => 'sometimes|in:string,integer,boolean,text,date,datetime,reference,choice,email,url,phone,currency,percent,html',
            'reference_table_id'  => 'nullable|exists:app_tables,id',
            'mandatory'           => 'boolean',
            'readonly'            => 'boolean',
            'active'              => 'boolean',
            'display'             => 'boolean',
            'function_field'      => 'boolean',
            'max_length'          => 'nullable|integer|min:1|max:65535',
            'default_value'       => 'nullable|string',
            'attributes'          => 'nullable|string',
            'choices'           