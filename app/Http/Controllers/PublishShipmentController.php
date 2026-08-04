<?php

namespace App\Http\Controllers;

use App\Actions\Shipments\PublishShipmentAction;
use App\Exceptions\ShipmentException;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PublishShipmentController extends Controller
{
    public function __invoke(Request $request, Shipment $shipment, PublishShipmentAction $publishShipment): RedirectResponse
    {
        Gate::authorize('publish', $shipment);

        try {
            $publishShipment->execute($shipment, $request->user());
        } catch (ShipmentException $exception) {
            return back()->withErrors(['shipment' => $exception->getMessage()]);
        }

        return back();
    }
}
