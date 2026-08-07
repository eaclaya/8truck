<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuoteRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:1', 'max:1000000'],
            'truck_id' => [
                'required',
                Rule::exists('trucks', 'id')->where(
                    'transporter_profile_id',
                    $this->user()->transporterProfile?->id,
                ),
            ],
            'estimated_pickup_at' => ['nullable', 'date'],
            'estimated_delivery_at' => ['nullable', 'date', 'after_or_equal:estimated_pickup_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
