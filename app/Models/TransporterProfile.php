<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\TransporterProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $phone
 * @property string|null $driver_license_number
 * @property string|null $national_id
 * @property int $years_of_experience
 * @property numeric-string $rating_avg
 * @property int $rating_count
 * @property CarbonImmutable|null $verified_at
 */
#[Fillable(['user_id', 'phone', 'driver_license_number', 'national_id', 'years_of_experience'])]
class TransporterProfile extends Model
{
    /** @use HasFactory<TransporterProfileFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'rating_avg' => 'decimal:2',
        ];
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Truck, $this>
     */
    public function trucks(): HasMany
    {
        return $this->hasMany(Truck::class);
    }

    /**
     * @return HasMany<OperatingRegion, $this>
     */
    public function operatingRegions(): HasMany
    {
        return $this->hasMany(OperatingRegion::class);
    }

    /**
     * @return HasMany<Quote, $this>
     */
    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    /**
     * @return HasMany<Shipment, $this>
     */
    public function assignedShipments(): HasMany
    {
        return $this->hasMany(Shipment::class, 'assigned_transporter_id');
    }

    /**
     * @return MorphMany<Document, $this>
     */
    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }
}
