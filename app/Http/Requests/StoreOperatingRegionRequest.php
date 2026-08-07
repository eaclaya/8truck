<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOperatingRegionRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'city_id' => ['required_without:city_ids', 'integer', 'exists:cities,id'],
            'city_ids' => ['required_without:city_id', 'array', 'min:1'],
            'city_ids.*' => ['integer', 'exists:cities,id'],
            'radius_km' => ['required', 'integer', 'min:10', 'max:300'],
        ];
    }

    /**
     * The web form posts a multi-select; the mobile client still posts one city.
     *
     * @return array<int, int>
     */
    public function cityIds(): array
    {
        $validated = $this->validated();

        return array_values(array_unique(array_map(
            intval(...),
            $validated['city_ids'] ?? [$validated['city_id']],
        )));
    }
}
