<?php

namespace Database\Seeders;

use App\Models\TruckType;
use Illuminate\Database\Seeder;

class TruckTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Camión Seco', 'slug' => 'camion-seco', 'description' => 'Caja cerrada para carga general'],
            ['name' => 'Refrigerado', 'slug' => 'refrigerado', 'description' => 'Caja refrigerada para perecederos'],
            ['name' => 'Plataforma', 'slug' => 'plataforma', 'description' => 'Plataforma abierta para carga voluminosa'],
            ['name' => 'Volqueta', 'slug' => 'volqueta', 'description' => 'Volteo para materiales de construcción'],
            ['name' => 'Cisterna', 'slug' => 'cisterna', 'description' => 'Tanque para líquidos'],
            ['name' => 'Ganadero', 'slug' => 'ganadero', 'description' => 'Jaula para transporte de ganado'],
            ['name' => 'Pick-up', 'slug' => 'pick-up', 'description' => 'Carga liviana y última milla'],
        ];

        foreach ($types as $type) {
            TruckType::updateOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
