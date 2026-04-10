<?php

namespace App\Http\Requests;

use App\Models\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'            => ['required', 'confirmed', Password::defaults()],
            'role_id'             => ['required', 'exists:roles,id'],
            'validation_level_id' => ['nullable', 'exists:validation_levels,id'],
            'boutique_id'         => [
                Rule::requiredIf(fn () => Role::whereKey($this->input('role_id'))->value('slug') === 'demandeur'),
                'nullable',
                'exists:boutiques,id',
            ],
        ];
    }
}
