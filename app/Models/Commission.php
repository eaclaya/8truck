<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $shipment_id
 * @property int $transporter_profile_id
 * @property numeric-string $base_amount
 * @property numeric-string $rate
 * @property numeric-string $fee_amount
 * @property string $currency
 * @property string $status
 * @property CarbonImmutable|null $settled_at
 */
#[Fillable(['shipment_id', 'transporter_profile_id', 'base_amount', 'rate', 'fee_amount', 'currency', 'status'])]
class Commission extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'base_amount' => 'decimal:2',
            'rate' => 'decimal:4',
            'fee_amount' => 'decimal:2',
            'settled_at' => 'datetime',
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
}
