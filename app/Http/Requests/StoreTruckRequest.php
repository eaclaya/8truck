<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTruckRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'truck_type_id' => ['required', 'exists:truck_types,id'],
            'plate_number' => ['required', 'string', 'max:20', Rule::unique('trucks', 'plate_number')],
            'capacity_kg' => ['required', 'integer', 'min:100', 'max:60000'],
            'length_cm' => ['nullable', 'integer', 'min:1', 'max:3000'],
            'width_cm' => ['nullable', 'integer', 'min:1', 'max:400'],
            'height_cm' => ['nullable', 'integer', 'min:1', 'max:500'],
            'insurance_expires_at' => ['nullable', 'date'],
        ];
    }
}
