<?php

declare(strict_types=1);

namespace Modules\Module01\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreStockMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $type = $this->input('type');

        $rules = [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'type' => ['required', 'string', 'in:in,out,adjust'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];

        if ($type === 'adjust') {
            $rules['new_stock'] = ['required', 'integer', 'min:0'];
        } else {
            $rules['quantity'] = ['required', 'integer', 'min:1'];
        }

        return $rules;
    }
}
