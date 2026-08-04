<?php

namespace App\Http\Controllers;

use App\Actions\Shipments\CreateShipmentAction;
use App\Http\Requests\StoreShipmentRequest;
use App\Models\City;
use App\Models\Quote;
use App\Models\Shipment;
use App\Models\ShipmentStatusHistory;
use App\Models\TruckType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ShipmentController extends Controller
{
    public function index(Request $request): Response
    {
        $shipments = $request->user()->shipments()
            ->with(['originCity:id,name', 'destinationCity:id,name'])
            ->withCount('quotes')
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Shipment $shipment) => [
                'id' => $shipment->id,
                'status' => $shipment->status->value,
                'origin_city' => $shipment->originCity?->name,
                'destination_city' => $shipment->destinationCity?->name,
                'pickup_date' => $shipment->pickup_date->toDateString(),
                'cargo_type' => $shipment->cargo_type,
                'quotes_count' => $shipment->quotes_count,
                'created_at' => $shipment->created_at?->toDateTimeString(),
            ]);

        return Inertia::render('shipments/Index', [
            'shipments' => $shipments,
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Shipment::class);

        return Inertia::render('shipments/Create', [
            'cities' => City::query()->orderBy('name')->get(['id', 'name', 'department']),
            'truckTypes' => TruckType::query()->orderBy('name')->get(['id', 'name']),
            'cargoTypes' => config('marketplace.cargo_types'),
        ]);
    }

    public function store(StoreShipmentRequest $request, CreateShipmentAction $createShipment): RedirectResponse
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

        return to_route('shipments.show', $shipment);
    }

    public function show(Request $request, Shipment $shipment): Response
    {
        Gate::authorize('view', $shipment);

        $shipment->load([
            'originCity:id,name',
            'destinationCity:id,name',
            'truckType:id,name',
            'quotes' => fn ($query) => $query->with([
                'transporterProfile.user:id,name',
                'truck.truckType:id,name',
            ])->orderBy('amount'),
            'statusHistories' => fn ($query) => $query->with('actor:id,name')->latest(),
            'ratings' => fn ($query) => $query->with('rater:id,name'),
        ]);

        return Inertia::render('shipments/Show', [
            'shipment' => [
                'id' => $shipment->id,
                'reference' => $shipment->reference,
                'status' => $shipment->status->value,
                'origin_city' => $shipment->originCity?->name,
                'origin_address' => $shipment->origin_address,
                'destination_city' => $shipment->destinationCity?->name,
                'destination_address' => $shipment->destination_address,
                'pickup_date' => $shipment->pickup_date->toDateString(),
                'cargo_type' => $shipment->cargo_type,
                'truck_type' => $shipment->truckType?->name,
                'weight_kg' => $shipment->weight_kg,
                'budget_amount' => $shipment->budget_amount,
                'currency' => $shipment->currency,
                'special_instructions' => $shipment->special_instructions,
                'published_at' => $shipment->published_at?->toDateTimeString(),
                'accepted_quote_id' => $shipment->accepted_quote_id,
            ],
            'quotes' => $shipment->quotes->map(fn (Quote $quote) => [
                'id' => $quote->id,
                'amount' => $quote->amount,
                'currency' => $quote->currency,
                'status' => $quote->status->value,
                'estimated_pickup_at' => $quote->estimated_pickup_at?->toDateTimeString(),
                'estimated_delivery_at' => $quote->estimated_delivery_at?->toDateTimeString(),
                'notes' => $quote->notes,
                'transporter_name' => $quote->transporterProfile->user->name,
                'transporter_rating' => $quote->transporterProfile->rating_avg,
                'transporter_rating_count' => $quote->transporterProfile->rating_count,
                'truck' => $quote->truck ? $quote->truck->truckType?->name.' · '.$quote->truck->plate_number : null,
            ]),
            'histories' => $shipment->statusHistories->map(fn (ShipmentStatusHistory $history) => [
                'id' => $history->id,
                'from_status' => $history->from_status?->value,
                'to_status' => $history->to_status->value,
                'actor' => $history->actor?->name,
                'notes' => $history->notes,
                'created_at' => $history->created_at?->toDateTimeString(),
            ]),
            'ratings' => $shipment->ratings->map(fn ($rating) => [
                'id' => $rating->id,
                'score' => $rating->score,
                'comment' => $rating->comment,
                'rater' => $rating->rater->name,
            ]),
            'can' => [
                'publish' => $request->user()->can('publish', $shipment),
                'acceptQuote' => $request->user()->can('acceptQuote', $shipment),
                'complete' => $request->user()->can('complete', $shipment),
                'rate' => $request->user()->can('rate', $shipment),
            ],
        ]);
    }
}
