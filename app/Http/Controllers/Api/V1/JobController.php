<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Shipments\TransitionShipmentStatusAction;
use App\Enums\ShipmentStatus;
use App\Events\ShipmentStatusAdvanced;
use App\Http\Controllers\Controller;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class JobController extends Controller
{
    private const ALLOWED_TARGETS = [
        ShipmentStatus::DriverAssigned,
        ShipmentStatus::PickedUp,
        ShipmentStatus::InTransit,
        ShipmentStatus::Delivered,
    ];

    public function index(Request $request): AnonymousResourceCollection
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        $jobs = Shipment::query()
            ->where('assigned_transporter_id', $transporter->id)
            ->with(['originCity:id,name', 'destinationCity:id,name', 'customer:id,name'])
            ->orderByRaw("status = 'completed', pickup_date")
            ->paginate(15);

        return ShipmentResource::collection($jobs);
    }

    public function uploadPod(Request $request, Shipment $shipment): ShipmentResource
    {
        Gate::authorize('uploadPod', $shipment);

        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:5'],
            'photos.*' => ['image', 'max:5120'],
        ]);

        foreach ($request->file('photos', []) as $photo) {
            $shipment->addMedia($photo)->toMediaCollection('pod');
        }

        return new ShipmentResource($shipment->load('media'));
    }

    public function advance(Request $request, Shipment $shipment, TransitionShipmentStatusAction $transition): ShipmentResource
    {
        Gate::authorize('advance', $shipment);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_column(self::ALLOWED_TARGETS, 'value'))],
        ]);

        $target = ShipmentStatus::from($validated['status']);

        $transition->execute($shipment, $target, $request->user());

        if ($target === ShipmentStatus::Delivered) {
            $shipment->delivered_at = now();
            $shipment->save();
        }

        ShipmentStatusAdvanced::dispatch($shipment);

        return new ShipmentResource($shipment);
    }
}
