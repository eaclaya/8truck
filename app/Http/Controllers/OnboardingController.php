<?php

namespace App\Http\Controllers;

use App\Enums\DocumentType;
use App\Models\City;
use App\Models\Document;
use App\Models\OperatingRegion;
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
            'documents' => $transporter->documents()->get()
                ->map(fn (Document $document) => [
                    'id' => $document->id,
                    'type' => $document->type->value,
                    'status' => $document->status->value,
                ]),
            'cities' => City::query()->orderBy('name')->get(['id', 'name', 'department']),
            'documentTypes' => array_column(DocumentType::cases(), 'value'),
        ]);
    }
}
