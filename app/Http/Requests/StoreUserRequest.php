<?php

namespace App\Http\Requests;

use App\Models\Role;
use App\Models\ValidationLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isAdmin();
    }

    public function rules(): array
    {
        return [
            'name'                    => ['required', 'string', 'max:255'],
            'email'                   => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password'                => ['required', 'confirmed', Password::defaults()],
            'role_id'                 => ['required', 'exists:roles,id'],
            'validation_level_ids'    => ['nullable', 'array'],
            'validation_level_ids.*'  => ['integer', 'exists:validation_levels,id'],
            'boutique_id'             => [
                Rule::requiredIf(fn () => Role::whereKey($this->input('role_id'))->value('slug') === 'demandeur'),
                'nullable',
                'exists:boutiques,id',
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $ids = array_filter($this->input('validation_level_ids', []));

            if (empty($ids)) {
                return;
            }

            $circuitIds = ValidationLevel::whereIn('id', $ids)->pluck('circuit_id');

            if ($circuitIds->count() !== $circuitIds->unique()->count()) {
                $validator->errors()->add('validation_level_ids', 'Un seul niveau peut être assigné par circuit.');
            }
        });
    }
}
