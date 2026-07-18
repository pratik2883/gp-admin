<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class GpUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email,'.$id],
            'mobile' => ['sometimes', 'string', 'max:20', 'unique:users,mobile,'.$id],
            'password' => ['nullable', 'string', 'min:6'],
            'status' => ['nullable', 'in:active,inactive'],
        ];
    }
}
