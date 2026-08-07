<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Loads\FindAvailableLoadsAction;
use App\Actions\Quotes\SubmitQuoteAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreQuoteRequest;
use App\Http\Resources\QuoteResource;
use App\Http\Resources\ShipmentResource;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class LoadController extends Controller
{
    public function index(Request $request, FindAvailableLoadsAction $findLoads): AnonymousResourceCollection
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        $loads = $findLoads->execute($transporter, ! $request->boolean('all'))
            ->with(['originCity:id,name', 'destinationCity:id,name', 'truckType:id,name'])
            ->withCount('quotes')
            ->paginate(15);

        return ShipmentResource::collection($loads);
    }

    public function show(Request $request, Shipment $shipment): ShipmentResource
    {
        Gate::authorize('viewAsLoad', $shipment);

        $shipment->load([
            'originCity:id,name',
            'destinationCity:id,name',
            'truckType:id,name',
            'customer:id,name',
            'media',
        ]);
        $shipment->loadCount('quotes');

        $myQuote = $shipment->quotes()
            ->where('transporter_profile_id', $request->user()->transporterProfile->id)
            ->first();

        $shipment->setAttribute('has_my_quote', $myQuote !== null);
        $shipment->setAttribute('my_quote', $myQuote === null ? null : [
            'amount' => $myQuote->amount,
            'currency' => $myQuote->currency,
            'status' => $myQuote->status->value,
        ]);

        return new ShipmentResource($shipment);
    }

    public function quote(StoreQuoteRequest $request, Shipment $shipment, SubmitQuoteAction $submitQuote): QuoteResource
    {
        Gate::authorize('viewAsLoad', $shipment);

        $quote = $submitQuote->execute(
            $request->user()->transporterProfile,
            $shipment,
            $request->validated(),
        );

        return new QuoteResource($quote);
    }
}
