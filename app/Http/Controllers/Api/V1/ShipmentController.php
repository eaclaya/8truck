<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Quotes\AcceptQuoteAction;
use App\Actions\Ratings\RateShipmentAction;
use App\Actions\Shipments\CompleteShipmentAction;
use App\Actions\Shipments\CreateShipmentAction;
use App\Actions\Shipments\PublishShipmentAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRatingRequest;
use App\Http\Requests\StoreShipmentRequest;
use App\Http\Resources\ShipmentResource;
use App\Models\City;
use App\Models\Quote;
use App\Models\Shipment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class ShipmentController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $shipments = $request->user()->shipments()
            ->with(['originCity:id,name', 'destinationCity:id,name', 'truckType:id,name'])
            ->withCount('quotes')
            ->latest()
            ->paginate(15);

        return ShipmentResource::collection($shipments);
    }

    public function store(StoreShipmentRequest $request, CreateShipmentAction $createShipment): ShipmentResource
    {
        Gate::authorize('create', Shipment::class);

        $validated = $request->validated();

        $originCity = City::query()->findOrFail((int) $validated['origin_city_id']);
        $destinationCity = City::query()->findOrFail((int) $validated['destination_city_id']);

        $shipment = $createShipment->execute($request->user(), [
            ...$validated,
            'origin' => $originCity->location,
            'destination' => $destinationCity->location,
        ]);

        return new ShipmentResource($shipment->load(['originCity:id,name', 'destinationCity:id,name']));
    }

    public function show(Request $request, Shipment $shipment): ShipmentResource
    {
        Gate::authorize('view', $shipment);

        $shipment->load([
            'originCity:id,name',
            'destinationCity:id,name',
            'truckType:id,name',
            'quotes' => fn ($query) => $query
                ->with(['transporterProfile.user:id,name', 'truck.truckType:id,name'])
                ->orderBy('amount'),
            'media',
        ]);

        return new ShipmentResource($shipment);
    }

    public function storePhotos(Request $request, Shipment $shipment): ShipmentResource
    {
        Gate::authorize('view', $shipment);

        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:5'],
            'photos.*' => ['image', 'max:5120'],
        ]);

        foreach ($request->file('photos', []) as $photo) {
            $shipment->addMedia($photo)->toMediaCollection('cargo');
        }

        return new ShipmentResource($shipment->load('media'));
    }

    public function publish(Request $request, Shipment $shipment, PublishShipmentAction $publishShipment): ShipmentResource
    {
        Gate::authorize('publish', $shipment);

        return new ShipmentResource($publishShipment->execute($shipment, $request->user()));
    }

    public function complete(Request $request, Shipment $shipment, CompleteShipmentAction $completeShipment): ShipmentResource
    {
        Gate::authorize('complete', $shipment);

        return new ShipmentResource($completeShipment->execute($shipment, $request->user()));
    }

    public function rate(StoreRatingRequest $request, Shipment $shipment, RateShipmentAction $rateShipment): JsonResponse
    {
        Gate::authorize('rate', $shipment);

        $validated = $request->validated();

        $rating = $rateShipment->execute(
            $request->user(),
            $shipment,
            (int) $validated['score'],
            $validated['comment'] ?? null,
        );

        return response()->json(['data' => ['id' => $rating->id, 'score' => $rating->score]], 201);
    }

    public function acceptQuote(Request $request, Quote $quote, AcceptQuoteAction $acceptQuote): ShipmentResource
    {
        Gate::authorize('acceptQuote', $quote->shipment);

        return new ShipmentResource($acceptQuote->execute($request->user(), $quote));
    }
}
