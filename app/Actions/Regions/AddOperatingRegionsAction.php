<?php

namespace App\Actions\Regions;

use App\Models\City;
use App\Models\OperatingRegion;
use App\Models\TransporterProfile;
use Illuminate\Support\Collection;

class AddOperatingRegionsAction
{
    /**
     * Covers each city at the given radius, leaving already-covered cities as
     * they are so re-selecting one never rewrites the radius it was added with.
     *
     * @param  array<int, int>  $cityIds
     * @return Collection<int, OperatingRegion>
     */
    public function execute(TransporterProfile $transporter, array $cityIds, int $radiusKm): Collection
    {
        return City::query()->findMany($cityIds)->map(
            fn (City $city) => $transporter->operatingRegions()->firstOrCreate(
                ['city_id' => $city->id],
                [
                    'name' => $city->name,
                    'center' => $city->location,
                    'radius_m' => $radiusKm * 1000,
                ],
            ),
        )->values();
    }
}
