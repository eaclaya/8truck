<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreShipmentRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'origin_city_id' => ['required', 'exists:cities,id'],
            'origin_address' => ['required', 'string', 'max:255'],
            'destination_city_id' => ['required', 'exists:cities,id', 'different:origin_city_id'],
            'destination_address' => ['required', 'string', 'max:255'],
            'pickup_date' => ['required', 'date', 'after_or_equal:today'],
            'cargo_type' => ['required', Rule::in(config('marketplace.cargo_types'))],
            'weight_kg' => ['nullable', 'integer', 'min:1', 'max:40000'],
            'truck_type_id' => ['nullable', 'exists:truck_types,id'],
            'budget_amount' => ['nullable', 'numeric', 'min:0', 'max:1000000'],
            'special_instructions' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
