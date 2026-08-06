<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\TruckType;
use Illuminate\Http\JsonResponse;

class CatalogController extends Controller
{
    public function cities(): JsonResponse
    {
        return response()->json([
            'data' => City::query()->orderBy('name')->get()
                ->map(fn (City $city) => [
                    'id' => $city->id,
                    'name' => $city->name,
                    'department' => $city->department,
                    'lat' => $city->location->latitude,
                    'lng' => $city->location->longitude,
                ]),
        ]);
    }

    public function truckTypes(): JsonResponse
    {
        return response()->json([
            'data' => TruckType::query()->orderBy('name')->get(['id', 'name', 'slug', 'description']),
        ]);
    }
}
