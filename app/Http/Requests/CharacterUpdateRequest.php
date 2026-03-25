<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CharacterUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'           => 'required|string',
            'name'            => 'required|string',
            'event'           => 'required|string',
            'map_id'          => 'required|integer',
            'state'           => 'required|integer',
            'map_type'        => 'sometimes|integer',
            'profession'      => 'sometimes|integer',
            'elite_spec'      => 'sometimes|integer',
            'race'            => 'sometimes|integer',
            'group_type'      => 'sometimes|integer',
            'group_count'     => 'sometimes|integer',
            'commander'       => 'sometimes|boolean',
            'mount'           => 'sometimes|integer',
            'is_login'        => 'sometimes|boolean',
            'position.x'      => 'sometimes|numeric',
            'position.y'      => 'sometimes|numeric',
            'position.z'      => 'sometimes|numeric',
            'level'           => 'sometimes|integer',
            'effective_level' => 'sometimes|integer',
            'buff_id'         => 'sometimes|integer',
            'buff_name'       => 'sometimes|string',
        ];
    }
}
