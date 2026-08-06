<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTruckRequest;
use App\Http\Requests\UpdateTruckRequest;
use App\Models\Truck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class TruckController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        return response()->json([
            'data' => $transporter->trucks()->with('truckType:id,name')->latest()->get()
                ->map(fn (Truck $truck) => $this->payload($truck)),
        ]);
    }

    public function store(StoreTruckRequest $request): JsonResponse
    {
        Gate::authorize('create', Truck::class);

        $truck = $request->user()->transporterProfile->trucks()->create($request->validated());

        return response()->json(['data' => $this->payload($truck->load('truckType:id,name'))], 201);
    }

    public function update(UpdateTruckRequest $request, Truck $truck): JsonResponse
    {
        Gate::authorize('update', $truck);

        $truck->update($request->validated());

        return response()->json(['data' => $this->payload($truck->load('truckType:id,name'))]);
    }

    public function destroy(Request $request, Truck $truck): JsonResponse
    {
        Gate::authorize('delete', $truck);

        $truck->delete();

        return response()->json(['message' => 'ok']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Truck $truck): array
    {
        return [
            'id' => $truck->id,
            'plate_number' => $truck->plate_number,
            'truck_type' => $truck->truckType?->name,
            'truck_type_id' => $truck->truck_type_id,
            'capacity_kg' => $truck->capacity_kg,
            'availability' => $truck->availability->value,
            'insurance_expires_at' => $truck->insurance_expires_at?->toDateString(),
        ];
    }
}
