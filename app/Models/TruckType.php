<?php

namespace App\Models;

use Database\Factories\TruckTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 */
#[Fillable(['name', 'slug', 'description'])]
class TruckType extends Model
{
    /** @use HasFactory<TruckTypeFactory> */
    use HasFactory;

    /**
     * @return HasMany<Truck, $this>
     */
    public function trucks(): HasMany
    {
        return $this->hasMany(Truck::class);
    }
}
