<?php

namespace App\Http\Controllers;

use App\Actions\Ratings\RateShipmentAction;
use App\Exceptions\ShipmentException;
use App\Http\Requests\StoreRatingRequest;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class RateShipmentController extends Controller
{
    public function __invoke(StoreRatingRequest $request, Shipment $shipment, RateShipmentAction $rateShipment): RedirectResponse
    {
        Gate::authorize('rate', $shipment);

        $validated = $request->validated();

        try {
            $rateShipment->execute(
                $request->user(),
                $shipment,
                (int) $validated['score'],
                $validated['comment'] ?? null,
            );
        } catch (ShipmentException $exception) {
            return back()->withErrors(['rating' => $exception->getMessage()]);
        }

        return back();
    }
}
