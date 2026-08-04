<?php

namespace App\Models;

use Database\Factories\CityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use MatanYadaev\EloquentSpatial\Objects\Point;
use MatanYadaev\EloquentSpatial\Traits\HasSpatial;

/**
 * @property int $id
 * @property string $name
 * @property string $department
 * @property Point $location
 */
#[Fillable(['name', 'department', 'location'])]
class City extends Model
{
    /** @use HasFactory<CityFactory> */
    use HasFactory, HasSpatial;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'location' => Point::class,
        ];
    }

    /**
     * @return HasMany<Shipment, $this>
     */
    public function shipmentsFrom(): HasMany
    {
        return $this->hasMany(Shipment::class, 'origin_city_id');
    }

    /**
     * @return HasMany<Shipment, $this>
     */
    public function shipmentsTo(): HasMany
    {
        return $this->hasMany(Shipment::class, 'destination_city_id');
    }
}
