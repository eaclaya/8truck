<?php

namespace App\Http\Requests;

use App\Enums\TruckAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTruckRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'availability' => ['required', Rule::enum(TruckAvailability::class)],
        ];
    }
}
