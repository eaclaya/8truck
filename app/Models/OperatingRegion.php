<?php

namespace App\Models;

use Database\Factories\OperatingRegionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

/**
 * @property int $id
 * @property int $transporter_profile_id
 * @property int|null $city_id
 * @property string $name
 * @property Point $center
 * @property int $radius_m
 */
#[Fillable(['transporter_profile_id', 'city_id', 'name', 'center', 'radius_m'])]
class OperatingRegion extends Model
{
    /** @use HasFactory<OperatingRegionFactory> */
    use HasFactory, HasSpatial;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'center' => Point::class,
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
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
