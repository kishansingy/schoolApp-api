<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTableRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('appTable')?->id;

        return [
            'name'                   => "sometimes|string|unique:app_tables,name,{$id}|regex:/^[a-z_][a-z0-9_]*$/",
            'label'                  => 'sometimes|string',
            'parent_id'              => 'nullable|exists:app_tables,id',
            'extensible'             => 'boolean',
            'live_feed'              => 'boolean',
            'auto_number'            => 'boolean',
            'auto_number_prefix'     => 'nullable|string|max:10',
            'auto_number_base'       => 'integer|min:0',
            'auto_number_padding'    => 'integer|min:1|max:20',
            'create_access_controls' => 'boolean',
            'user_role'              => 'nullable|string',
            'accessible_from'        => 'string|in:all,this_app,subscriber',
            'can_read'               => 'boolean',
            'can_create'             => 'boolean',
            'can_update'             => 'boolean',
            'can_delete'             => 'boolean',
            'allow_web_services'     => 'boolean',
            'allow_configuration'    => 'boolean',
        ];
    }
}
