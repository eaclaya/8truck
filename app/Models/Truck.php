<?php

namespace App\Models;

use App\Enums\TruckAvailability;
use Carbon\CarbonImmutable;
use Database\Factories\TruckFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property int $transporter_profile_id
 * @property int $truck_type_id
 * @property string $plate_number
 * @property int $capacity_kg
 * @property int|null $length_cm
 * @property int|null $width_cm
 * @property int|null $height_cm
 * @property TruckAvailability $availability
 * @property CarbonImmutable|null $insurance_expires_at
 */
#[Fillable([
    'transporter_profile_id', 'truck_type_id', 'plate_number', 'capacity_kg',
    'length_cm', 'width_cm', 'height_cm', 'availability', 'insurance_expires_at',
])]
class Truck extends Model
{
    /** @use HasFactory<TruckFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'availability' => TruckAvailability::class,
            'insurance_expires_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<TransporterProfile, $this>
     */
    public function transporterProfile(): BelongsTo
    {
        return $this->belongsTo(TransporterProfile::class);
    }

    /**
     * @return BelongsTo<TruckType, $this>
     */
    public function truckType(): BelongsTo
    {
        return $this->belongsTo(TruckType::class);
    }

    /**
     * @return MorphMany<Document, $this>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
