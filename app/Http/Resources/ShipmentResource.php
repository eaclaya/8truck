<?php

namespace App\Http\Resources;

use App\Models\Shipment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Shipment
 */
class ShipmentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status->value,
            'origin_address' => $this->origin_address,
            'origin_city' => $this->whenLoaded('originCity', fn () => $this->originCity?->name),
            'origin' => ['lat' => $this->origin->latitude, 'lng' => $this->origin->longitude],
            'destination_address' => $this->destination_address,
            'destination_city' => $this->whenLoaded('destinationCity', fn () => $this->destinationCity?->name),
            'destination' => ['lat' => $this->destination->latitude, 'lng' => $this->destination->longitude],
            'pickup_date' => $this->pickup_date->toDateString(),
            'cargo_type' => $this->cargo_type,
            'truck_type' => $this->whenLoaded('truckType', fn () => $this->truckType?->name),
            'truck_type_id' => $this->truck_type_id,
            'weight_kg' => $this->weight_kg,
            'budget_amount' => $this->budget_amount,
            'currency' => $this->currency,
            'special_instructions' => $this->special_instructions,
            'customer_name' => $this->whenLoaded('customer', fn () => $this->customer->name),
            'quotes_count' => $this->whenCounted('quotes'),
            'has_my_quote' => $this->when($this->has_my_quote !== null, fn (): bool => (bool) $this->has_my_quote),
            'quotes' => QuoteResource::collection($this->whenLoaded('quotes')),
            'photos' => $this->whenLoaded('media', fn () => $this->getMedia('cargo')->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
            ])->values()),
            'pod_photos' => $this->whenLoaded('media', fn () => $this->getMedia('pod')->map(fn ($media) => [
                'id' => $media->id,
                'url' => $media->getUrl(),
                'thumb' => $media->getUrl('thumb'),
            ])->values()),
            'return_trips' => $this->when($this->getAttribute('return_trips') !== null, fn () => $this->getAttribute('return_trips')),
            'can' => $this->when($request->user() !== null, fn (): array => [
                'publish' => $request->user()->can('publish', $this->resource),
                'accept_quote' => $request->user()->can('acceptQuote', $this->resource),
                'complete' => $request->user()->can('complete', $this->resource),
                'rate' => $request->user()->can('rate', $this->resource),
                'advance' => $request->user()->can('advance', $this->resource),
                'upload_pod' => $request->user()->can('uploadPod', $this->resource),
            ]),
            'published_at' => $this->published_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
