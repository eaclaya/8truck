<?php

namespace App\Http\Controllers;

use App\Actions\Regions\AddOperatingRegionsAction;
use App\Http\Requests\StoreOperatingRegionRequest;
use App\Models\City;
use App\Models\OperatingRegion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OperatingRegionController extends Controller
{
    public function index(Request $request): Response
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        return Inertia::render('regions/Index', [
            'regions' => $transporter->operatingRegions()->with('city:id,name,department')->get()
                ->map(fn (OperatingRegion $region) => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'department' => $region->city?->department,
                    'radius_km' => (int) round($region->radius_m / 1000),
                ]),
            'cities' => City::query()->orderBy('name')->get(['id', 'name', 'department']),
        ]);
    }

    public function store(StoreOperatingRegionRequest $request, AddOperatingRegionsAction $addRegions): RedirectResponse
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        $addRegions->execute(
            $transporter,
            $request->cityIds(),
            (int) $request->validated()['radius_km'],
        );

        return back();
    }

    public function destroy(Request $request, OperatingRegion $operatingRegion): RedirectResponse
    {
        abort_unless(
            $request->user()->transporterProfile?->id === $operatingRegion->transporter_profile_id,
            403,
        );

        $operatingRegion->delete();

        return back();
    }
}
