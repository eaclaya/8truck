<?php

namespace App\Models;

use App\Enums\ShipmentStatus;
use Carbon\CarbonImmutable;
use Database\Factories\ShipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

/**
 * @property int $id
 * @property string $reference
 * @property int $customer_id
 * @property int|null $company_id
 * @property ShipmentStatus $status
 * @property int|null $truck_type_id
 * @property string $origin_address
 * @property int|null $origin_city_id
 * @property Point $origin
 * @property string $destination_address
 * @property int|null $destination_city_id
 * @property Point $destination
 * @property CarbonImmutable $pickup_date
 * @property string $cargo_type
 * @property int|null $weight_kg
 * @property int|null $length_cm
 * @property int|null $width_cm
 * @property int|null $height_cm
 * @property string|null $special_instructions
 * @property numeric-string|null $budget_amount
 * @property string $currency
 * @property int|null $assigned_transporter_id
 * @property int|null $assigned_truck_id
 * @property int|null $accepted_quote_id
 * @property CarbonImmutable|null $published_at
 * @property CarbonImmutable|null $expires_at
 * @property CarbonImmutable|null $delivered_at
 * @property CarbonImmutable|null $completed_at
 */
#[Fillable([
    'customer_id', 'company_id', 'truck_type_id',
    'origin_address', 'origin_city_id', 'origin',
    'destination_address', 'destination_city_id', 'destination',
    'pickup_date', 'cargo_type', 'weight_kg', 'length_cm', 'width_cm', 'height_cm',
    'special_instructions', 'budget_amount', 'currency',
])]
class Shipment extends Model
{
    /** @use HasFactory<ShipmentFactory> */
    use HasFactory, HasSpatial;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => ShipmentStatus::Draft->value,
        'currency' => 'HNL',
    ];

    protected static function booted(): void
    {
        static::creating(function (Shipment $shipment) {
            $shipment->reference ??= (string) Str::ulid();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ShipmentStatus::class,
            'origin' => Point::class,
            'destination' => Point::class,
            'pickup_date' => 'date',
            'budget_amount' => 'decimal:2',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'delivered_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<TruckType, $this>
     */
    public function truckType(): BelongsTo
    {
        return $this->belongsTo(TruckType::class);
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function originCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'origin_city_id');
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function destinationCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'destination_city_id');
    }

    /**
     * @return HasMany<Quote, $this>
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * @return BelongsTo<Quote, $this>
     */
    public function acceptedQuote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'accepted_quote_id');
    }

    /**
     * @return BelongsTo<TransporterProfile, $this>
     */
    public function assignedTransporter(): BelongsTo
    {
        return $this->belongsTo(TransporterProfile::class, 'assigned_transporter_id');
    }

    /**
     * @return BelongsTo<Truck, $this>
     */
    public function assignedTruck(): BelongsTo
    {
        return $this->belongsTo(Truck::class, 'assigned_truck_id');
    }

    /**
     * @return HasMany<ShipmentStatusHistory, $this>
     */
    public function statusHistories(): HasMany
    {
        return $this->hasMany(ShipmentStatusHistory::class);
    }

    /**
     * @return HasMany<Rating, $this>
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * @return HasOne<Commission, $this>
     */
    public function commission(): HasOne
    {
        return $this->hasOne(Commission::class);
    }
}
