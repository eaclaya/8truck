<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Models\City;
use App\Models\Document;
use App\Models\OperatingRegion;
use App\Models\Truck;
use App\Models\TruckType;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OnboardingController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $transporter = $request->user()->transporterProfile;

        abort_unless($transporter !== null, 403);

        return Inertia::render('onboarding/Index', [
            'regions' => $transporter->operatingRegions()->get()
                ->map(fn (OperatingRegion $region) => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'radius_km' => (int) round($region->radius_m / 1000),
                ]),
            'trucks' => $transporter->trucks()->with('truckType:id,name')->get()
                ->map(fn (Truck $truck) => [
                    'id' => $truck->id,
                    'plate_number' => $truck->plate_number,
                    'truck_type' => $truck->truckType?->name,
                    'capacity_kg' => $truck->capacity_kg,
                ]),
            'documents' => $transporter->documents()->get()
                ->map(fn (Document $document) => [
                    'id' => $document->id,
                    'type' => $document->type->value,
                    'status' => $document->status->value,
                ]),
            'cities' => City::query()->orderBy('name')->get(['id', 'name', 'department']),
            'truckTypes' => TruckType::query()->orderBy('name')->get(['id', 'name']),
            'documentTypes' => array_column(DocumentType::cases(), 'value'),
        ]);
    }
}
