<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'          => ['required', 'string', 'max:255'],
            'description'    => ['required', 'string'],
            'amount'         => ['required', 'numeric', 'min:0'],
            'attachments'    => ['nullable', 'array', 'max:10'],
            'attachments.*'  => ['file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required'         => 'Le titre est obligatoire.',
            'description.required'   => 'La description est obligatoire.',
            'amount.required'        => 'Le montant est obligatoire.',
            'amount.numeric'         => 'Le montant doit être un nombre.',
            'amount.min'             => 'Le montant doit être positif.',
            'attachments.*.mimes'    => 'Seuls les fichiers PDF sont acceptés.',
            'attachments.*.max'      => 'Chaque fichier ne doit pas dépasser 10 Mo.',
        ];
    }
}
