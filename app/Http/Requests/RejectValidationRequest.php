<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectValidationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Le motif de refus est obligatoire.',
            'comment.min'      => 'Le motif doit contenir au moins 10 caractères.',
        ];
    }
}
