<?php

namespace App\Http\Controllers;

use App\Actions\Quotes\SubmitQuoteAction;
use App\Exceptions\ShipmentException;
use App\Http\Requests\StoreQuoteRequest;
use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class SubmitQuoteController extends Controller
{
    public function __invoke(StoreQuoteRequest $request, Shipment $shipment, SubmitQuoteAction $submitQuote): RedirectResponse
    {
        Gate::authorize('viewAsLoad', $shipment);

        try {
            $submitQuote->execute($request->user()->transporterProfile, $shipment, $request->validated());
        } catch (ShipmentException $exception) {
            return back()->withErrors(['quote' => $exception->getMessage()]);
        }

        return back();
    }
}
