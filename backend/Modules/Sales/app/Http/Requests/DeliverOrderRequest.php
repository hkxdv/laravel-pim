<?php

declare(strict_types=1);

namespace Modules\Sales\App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class DeliverOrderRequest extends FormRequest
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
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }
}
