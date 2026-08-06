<?php

namespace App\Http\Controllers;

use App\Models\Shipment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UploadProofOfDeliveryController extends Controller
{
    public function __invoke(Request $request, Shipment $shipment): RedirectResponse
    {
        Gate::authorize('uploadPod', $shipment);

        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:5'],
            'photos.*' => ['image', 'max:5120'],
        ]);

        foreach ($request->file('photos', []) as $photo) {
            $shipment->addMedia($photo)->toMediaCollection('pod');
        }

        return back();
    }
}
