<?php

namespace App\Http\Controllers;

use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobController extends Controller
{
    /**
     * The transporter's assigned shipments: active jobs first, then a page
     * of recently finished ones.
     */
    public function index(Request $request): Response
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        $jobs = Shipment::query()
            ->where('assigned_transporter_id', $transporter->id)
            ->with(['originCity:id,name', 'destinationCity:id,name', 'acceptedQuote:id,amount,currency', 'customer:id,name'])
            ->orderByRaw("status = 'completed', pickup_date")
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Shipment $shipment) => [
                'id' => $shipment->id,
                'status' => $shipment->status->value,
                'next_status' => $this->nextStatus($shipment->status)?->value,
                'origin_city' => $shipment->originCity?->name,
                'destination_city' => $shipment->destinationCity?->name,
                'pickup_date' => $shipment->pickup_date->toDateString(),
                'cargo_type' => $shipment->cargo_type,
                'customer_name' => $shipment->customer->name,
                'amount' => $shipment->acceptedQuote?->amount,
                'currency' => $shipment->currency,
            ]);

        return Inertia::render('jobs/Index', [
            'jobs' => $jobs,
        ]);
    }

    /**
     * The single forward step a transporter may take from each status.
     */
    private function nextStatus(ShipmentStatus $status): ?ShipmentStatus
    {
        return match ($status) {
            ShipmentStatus::Accepted => ShipmentStatus::DriverAssigned,
            ShipmentStatus::DriverAssigned => ShipmentStatus::PickedUp,
            ShipmentStatus::PickedUp => ShipmentStatus::InTransit,
            ShipmentStatus::InTransit => ShipmentStatus::Delivered,
            default => null,
        };
    }
}
