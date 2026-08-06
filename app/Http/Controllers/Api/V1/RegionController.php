<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOperatingRegionRequest;
use App\Models\City;
use App\Models\OperatingRegion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        return response()->json([
            'data' => $transporter->operatingRegions()->with('city:id,name,department')->get()
                ->map(fn (OperatingRegion $region) => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'city_id' => $region->city_id,
                    'radius_km' => (int) round($region->radius_m / 1000),
                ]),
        ]);
    }

    public function store(StoreOperatingRegionRequest $request): JsonResponse
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        $city = City::query()->findOrFail((int) $request->validated()['city_id']);

        $region = $transporter->operatingRegions()->firstOrCreate(
            ['city_id' => $city->id],
            [
                'name' => $city->name,
                'center' => $city->location,
                'radius_m' => $request->validated()['radius_km'] * 1000,
            ],
        );

        return response()->json(['data' => ['id' => $region->id, 'name' => $region->name]], 201);
    }

    public function destroy(Request $request, OperatingRegion $operatingRegion): JsonResponse
    {
        abort_unless(
            $request->user()->transporterProfile?->id === $operatingRegion->transporter_profile_id,
            403,
        );

        $operatingRegion->delete();

        return response()->json(['message' => 'ok']);
    }
}
