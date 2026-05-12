<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        $userId = $this->route('user')->id;

        return [
            'name'                   => ['required', 'string', 'max:255'],
            'email'                  => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($userId)],
            'phone'                  => ['nullable', 'string', 'max:20'],
            'whatsapp_notifications' => ['boolean'],
            'password'               => ['nullable', 'confirmed', Password::defaults()],
            'role_id'                => ['required', 'exists:roles,id'],
            'validation_level_id'    => ['nullable', 'exists:validation_levels,id'],
            'boutique_id'         => [
                Rule::requiredIf(fn () => in_array(Role::whereKey($this->input('role_id'))->value('slug'), ['demandeur', 'caissier'])),
                'nullable',
                'exists:boutiques,id',
            ],
        ];
    }
}
