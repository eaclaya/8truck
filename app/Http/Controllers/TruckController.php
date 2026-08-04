<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTruckRequest;
use App\Http\Requests\UpdateTruckRequest;
use App\Models\Truck;
use App\Models\TruckType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class TruckController extends Controller
{
    public function index(Request $request): Response
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        return Inertia::render('trucks/Index', [
            'trucks' => $transporter->trucks()->with('truckType:id,name')->latest()->get()
                ->map(fn (Truck $truck) => [
                    'id' => $truck->id,
                    'plate_number' => $truck->plate_number,
                    'truck_type' => $truck->truckType?->name,
                    'capacity_kg' => $truck->capacity_kg,
                    'availability' => $truck->availability->value,
                    'insurance_expires_at' => $truck->insurance_expires_at?->toDateString(),
                ]),
        ]);
    }

    public function create(Request $request): Response
    {
        Gate::authorize('create', Truck::class);

        return Inertia::render('trucks/Create', [
            'truckTypes' => TruckType::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreTruckRequest $request): RedirectResponse
    {
        Gate::authorize('create', Truck::class);

        $request->user()->transporterProfile->trucks()->create($request->validated());

        return to_route('trucks.index');
    }

    public function update(UpdateTruckRequest $request, Truck $truck): RedirectResponse
    {
        Gate::authorize('update', $truck);

        $truck->update($request->validated());

        return back();
    }

    public function destroy(Truck $truck): RedirectResponse
    {
        Gate::authorize('delete', $truck);

        $truck->delete();

        return to_route('trucks.index');
    }
}
