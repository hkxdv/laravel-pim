<?php

declare(strict_types=1);

namespace Modules\Sales\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int|string>>
     */
    public function rules(): array
    {
        return [
            'client_id' => [
                'nullable',
                'integer',
            ],
            'status' => [
                'nullable',
                'string',
                'in:draft,requested,prepared,delivered',
            ],
            'items' => [
                'required',
                'array',
                'min:1',
            ],
            'items.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
            ],
            'items.*.qty' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }
}
