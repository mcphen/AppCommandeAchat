<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('purchase_order');
        return $order && $order->isEditableBy($this->user());
    }

    public function rules(): array
    {
        return [
            'title'                   => ['required', 'string', 'max:255'],
            'description'             => ['required', 'string'],
            'amount'                  => ['required', 'numeric', 'min:0'],
            'order_date'              => ['nullable', 'date'],
            'attachments'             => ['nullable', 'array', 'max:10'],
            'attachments.*'           => ['file', 'mimes:pdf', 'max:10240'],
            'deleted_attachment_ids'  => ['nullable', 'array'],
            'deleted_attachment_ids.*'=> ['integer'],
        ];
    }
}
