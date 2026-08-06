<?php

namespace App\Http\Controllers;

use App\Actions\Loads\FindAvailableLoadsAction;
use App\Enums\QuoteStatus;
use App\Enums\ShipmentStatus;
use App\Models\Shipment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const ACTIVE_STATUSES = [
        ShipmentStatus::Accepted,
        ShipmentStatus::DriverAssigned,
        ShipmentStatus::PickedUp,
        ShipmentStatus::InTransit,
    ];

    public function __invoke(Request $request, FindAvailableLoadsAction $findLoads): Response
    {
        $user = $request->user();

        $customer = [
            'stats' => [
                'draft' => $user->shipments()->where('status', ShipmentStatus::Draft)->count(),
                'awaiting' => $user->shipments()->whereIn('status', [ShipmentStatus::Published, ShipmentStatus::Quoted])->count(),
                'inProgress' => $user->shipments()->whereIn('status', [...self::ACTIVE_STATUSES, ShipmentStatus::Delivered])->count(),
                'completed' => $user->shipments()->where('status', ShipmentStatus::Completed)->count(),
            ],
            'attention' => $user->shipments()
                ->whereIn('status', [ShipmentStatus::Quoted, ShipmentStatus::Delivered])
                ->with(['originCity:id,name', 'destinationCity:id,name'])
                ->withCount(['quotes as pending_quotes_count' => fn ($query) => $query->where('status', QuoteStatus::Pending)])
                ->latest('updated_at')
                ->take(5)
                ->get()
                ->map(fn (Shipment $shipment) => [
                    'id' => $shipment->id,
                    'origin_city' => $shipment->originLabel(),
                    'destination_city' => $shipment->destinationLabel(),
                    'status' => $shipment->status->value,
                    'pending_quotes' => $shipment->pending_quotes_count,
                    'action' => $shipment->status === ShipmentStatus::Quoted ? 'review_quotes' : 'confirm_delivery',
                ]),
        ];

        $transporter = null;
        $profile = $user->transporterProfile;

        if ($profile !== null) {
            $loads = $findLoads->execute($profile)
                ->with(['originCity:id,name', 'destinationCity:id,name'])
                ->take(5)
                ->get();

            $jobs = Shipment::query()
                ->where('assigned_transporter_id', $profile->id)
                ->whereIn('status', [...self::ACTIVE_STATUSES, ShipmentStatus::Delivered])
                ->with(['originCity:id,name', 'destinationCity:id,name'])
                ->orderBy('pickup_date')
                ->take(5)
                ->get();

            $transporter = [
                'stats' => [
                    'loads' => $findLoads->execute($profile)->count(),
                    'pendingQuotes' => $profile->quotes()->where('status', QuoteStatus::Pending)->count(),
                    'activeJobs' => Shipment::query()
                        ->where('assigned_transporter_id', $profile->id)
                        ->whereIn('status', [...self::ACTIVE_STATUSES, ShipmentStatus::Delivered])
                        ->count(),
                    'completedJobs' => Shipment::query()
                        ->where('assigned_transporter_id', $profile->id)
                        ->where('status', ShipmentStatus::Completed)
                        ->count(),
                ],
                'jobs' => $jobs->map(fn (Shipment $shipment) => [
                    'id' => $shipment->id,
                    'origin_city' => $shipment->originLabel(),
                    'destination_city' => $shipment->destinationLabel(),
                    'status' => $shipment->status->value,
                    'pickup_date' => $shipment->pickup_date->toDateString(),
                ]),
                'loads' => $loads->map(fn (Shipment $shipment) => [
                    'id' => $shipment->id,
                    'origin_city' => $shipment->originLabel(),
                    'destination_city' => $shipment->destinationLabel(),
                    'pickup_date' => $shipment->pickup_date->toDateString(),
                    'budget_amount' => $shipment->budget_amount,
                    'currency' => $shipment->currency,
                ]),
            ];
        }

        return Inertia::render('Dashboard', [
            'customer' => $customer,
            'transporter' => $transporter,
        ]);
    }
}
