<?php

namespace App\Http\Controllers;

use App\Actions\Loads\FindAvailableLoadsAction;
use App\Models\Shipment;
use App\Models\TruckType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LoadController extends Controller
{
    public function index(Request $request, FindAvailableLoadsAction $findLoads): Response
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        $onlyMyRegions = ! $request->boolean('all');

        $loads = $findLoads->execute($transporter, $onlyMyRegions)
            ->with(['originCity:id,name', 'destinationCity:id,name', 'truckType:id,name'])
            ->withCount('quotes')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Shipment $shipment) => [
                'id' => $shipment->id,
                'status' => $shipment->status->value,
                'origin_city' => $shipment->originCity?->name,
                'destination_city' => $shipment->destinationCity?->name,
                'pickup_date' => $shipment->pickup_date->toDateString(),
                'cargo_type' => $shipment->cargo_type,
                'weight_kg' => $shipment->weight_kg,
                'truck_type' => $shipment->truckType?->name,
                'budget_amount' => $shipment->budget_amount,
                'currency' => $shipment->currency,
                'quotes_count' => $shipment->quotes_count,
                'has_my_quote' => (bool) $shipment->has_my_quote,
            ]);

        return Inertia::render('loads/Index', [
            'loads' => $loads,
            'onlyMyRegions' => $onlyMyRegions,
            'hasRegions' => $transporter->operatingRegions()->exists(),
        ]);
    }

    public function show(Request $request, Shipment $shipment): Response
    {
        Gate::authorize('viewAsLoad', $shipment);

        $transporter = $request->user()->transporterProfile;

        $shipment->load([
            'originCity:id,name',
            'destinationCity:id,name',
            'truckType:id,name',
            'customer:id,name',
        ]);
        $shipment->loadCount('quotes');

        $myQuote = $shipment->quotes()
            ->where('transporter_profile_id', $transporter->id)
            ->first();

        return Inertia::render('loads/Show', [
            'load' => [
                'id' => $shipment->id,
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
                'customer_name' => $shipment->customer->name,
                'quotes_count' => $shipment->quotes_count,
            ],
            'myQuote' => $myQuote ? [
                'amount' => $myQuote->amount,
                'currency' => $myQuote->currency,
                'status' => $myQuote->status->value,
                'estimated_pickup_at' => $myQuote->estimated_pickup_at?->toDateTimeString(),
                'estimated_delivery_at' => $myQuote->estimated_delivery_at?->toDateTimeString(),
                'notes' => $myQuote->notes,
            ] : null,
            'trucks' => $transporter->trucks()->with('truckType:id,name')->orderBy('id')->get()
                ->map(fn ($truck) => [
                    'id' => $truck->id,
                    'label' => $truck->truckType?->name.' · '.$truck->plate_number,
                ]),
            'truckTypes' => TruckType::query()->orderBy('name')->get(['id', 'name']),
            'canQuote' => $shipment->status->isOpenForQuotes() && $myQuote === null,
        ]);
    }
}
