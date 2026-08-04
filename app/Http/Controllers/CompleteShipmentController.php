<?php

namespace App\Http\Controllers;

use App\Actions\Shipments\CompleteShipmentAction;
use App\Exceptions\ShipmentException;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CompleteShipmentController extends Controller
{
    public function __invoke(Request $request, Shipment $shipment, CompleteShipmentAction $completeShipment): RedirectResponse
    {
        Gate::authorize('complete', $shipment);

        try {
            $completeShipment->execute($shipment, $request->user());
        } catch (ShipmentException $exception) {
            return back()->withErrors(['shipment' => $exception->getMessage()]);
        }

        return back();
    }
}
