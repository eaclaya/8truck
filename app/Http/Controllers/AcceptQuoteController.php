<?php

namespace App\Http\Controllers;

use App\Actions\Quotes\AcceptQuoteAction;
use App\Exceptions\ShipmentException;
use App\Models\Quote;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AcceptQuoteController extends Controller
{
    public function __invoke(Request $request, Quote $quote, AcceptQuoteAction $acceptQuote): RedirectResponse
    {
        Gate::authorize('acceptQuote', $quote->shipment);

        try {
            $acceptQuote->execute($request->user(), $quote);
        } catch (ShipmentException $exception) {
            return back()->withErrors(['quote' => $exception->getMessage()]);
        }

        return back();
    }
}
