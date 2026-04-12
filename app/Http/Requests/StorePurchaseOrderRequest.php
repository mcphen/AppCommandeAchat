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
            'amount'         => ['nullable', 'numeric', 'min:0'],
            'boutique_id'    => ['nullable', 'integer', 'exists:boutiques,id'],
            'fournisseur_id' => ['nullable', 'integer', 'exists:fournisseurs,id'],
            'and_submit'     => ['nullable', 'boolean'],
            'attachments'    => ['nullable', 'array', 'max:10'],
            'attachments.*'  => ['file', 'mimes:pdf', 'max:10240'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $lines = collect($this->input('lines', []));

            if ($lines->isEmpty()) {
                if ((float) ($this->amount ?? 0) <= 0) {
                    $validator->errors()->add('amount', 'Le montant estimé doit être supérieur à 0.');
                }
            } else {
                $total = $lines->sum(fn ($l) => (float) ($l['quantity'] ?? 1) * (float) ($l['unit_price'] ?? 0));
                if ($total <= 0) {
                    $validator->errors()->add('lines', 'Le total des lignes doit être supérieur à 0 FCFA.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'title.required'         => 'Le titre est obligatoire.',
            'description.required'   => 'La description est obligatoire.',
            'amount.numeric'         => 'Le montant doit être un nombre.',
            'amount.min'             => 'Le montant doit être positif.',
            'attachments.*.mimes'    => 'Seuls les fichiers PDF sont acceptés.',
            'attachments.*.max'      => 'Chaque fichier ne doit pas dépasser 10 Mo.',
        ];
    }
}
