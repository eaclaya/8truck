<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Carbon\CarbonImmutable;
use Database\Factories\QuoteFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $shipment_id
 * @property int $transporter_profile_id
 * @property int|null $truck_id
 * @property numeric-string $amount
 * @property string $currency
 * @property CarbonImmutable|null $estimated_pickup_at
 * @property CarbonImmutable|null $estimated_delivery_at
 * @property string|null $notes
 * @property QuoteStatus $status
 */
#[Fillable([
    'shipment_id', 'transporter_profile_id', 'truck_id', 'amount', 'currency',
    'estimated_pickup_at', 'estimated_delivery_at', 'notes', 'status',
])]
class Quote extends Model
{
    /** @use HasFactory<QuoteFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => QuoteStatus::Pending->value,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => QuoteStatus::class,
            'amount' => 'decimal:2',
            'estimated_pickup_at' => 'datetime',
            'estimated_delivery_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Shipment, $this>
     */
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    /**
     * @return BelongsTo<TransporterProfile, $this>
     */
    public function transporterProfile(): BelongsTo
    {
        return $this->belongsTo(TransporterProfile::class);
    }

    /**
     * @return BelongsTo<Truck, $this>
     */
    public function truck(): BelongsTo
    {
        return $this->belongsTo(Truck::class);
    }
}
