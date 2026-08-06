<?php

namespace App\Http\Resources;

use App\Models\Quote;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Quote
 */
class QuoteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipment_id' => $this->shipment_id,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'status' => $this->status->value,
            'estimated_pickup_at' => $this->estimated_pickup_at?->toIso8601String(),
            'estimated_delivery_at' => $this->estimated_delivery_at?->toIso8601String(),
            'notes' => $this->notes,
            'transporter' => $this->whenLoaded('transporterProfile', fn (): array => [
                'name' => $this->transporterProfile->user->name,
                'rating_avg' => $this->transporterProfile->rating_avg,
                'rating_count' => $this->transporterProfile->rating_count,
            ]),
            'truck' => $this->whenLoaded('truck', fn (): ?string => $this->truck
                ? $this->truck->truckType?->name.' · '.$this->truck->plate_number
                : null),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
