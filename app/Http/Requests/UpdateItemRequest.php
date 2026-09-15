<?php

namespace App\Http\Requests;

use App\Models\Item;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->isMagazynier();
    }

    /** Patrz StoreItemRequest — "item_name" w formularzu przemapowane na "name" przed walidacją. */
    protected function prepareForValidation(): void
    {
        if ($this->has('item_name')) {
            $this->merge(['name' => $this->input('item_name')]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'ean' => ['nullable', 'string', 'max:32', 'regex:/^[0-9]{6,14}$/'],
            'description' => ['nullable', 'string'],
            'value' => ['nullable', 'numeric', 'min:0'],
            'purchased_at' => ['nullable', 'date'],
            'condition' => ['required', Rule::in(array_keys(Item::CONDITIONS))],
            'status' => ['required', Rule::in(array_keys(Item::STATUSES))],
            'category_id' => ['nullable', 'exists:categories,id'],
            'storage_location_id' => ['nullable', 'exists:storage_locations,id'],
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:8192'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['file', 'max:20480'],
            'attachment_labels' => ['nullable', 'array'],
            'attachment_labels.*' => ['nullable', 'string', 'max:255'],
        ];
    }
}
