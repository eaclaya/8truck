<?php

namespace App\Http\Controllers;

use App\Actions\Shipments\TransitionShipmentStatusAction;
use App\Enums\ShipmentStatus;
use App\Exceptions\ShipmentException;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class AdvanceJobStatusController extends Controller
{
    /**
     * Statuses a transporter may move a job into.
     */
    private const ALLOWED_TARGETS = [
        ShipmentStatus::DriverAssigned,
        ShipmentStatus::PickedUp,
        ShipmentStatus::InTransit,
        ShipmentStatus::Delivered,
    ];

    public function __invoke(Request $request, Shipment $shipment, TransitionShipmentStatusAction $transition): RedirectResponse
    {
        Gate::authorize('advance', $shipment);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_column(self::ALLOWED_TARGETS, 'value'))],
        ]);

        $target = ShipmentStatus::from($validated['status']);

        try {
            $transition->execute($shipment, $target, $request->user());
        } catch (ShipmentException $exception) {
            return back()->withErrors(['status' => $exception->getMessage()]);
        }

        if ($target === ShipmentStatus::Delivered) {
            $shipment->delivered_at = now();
            $shipment->save();
        }

        return back();
    }
}
