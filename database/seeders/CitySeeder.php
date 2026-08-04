<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use MatanYadaev\EloquentSpatial\Objects\Point;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = [
            ['Tegucigalpa', 'Francisco Morazán', 14.0723, -87.1921],
            ['San Pedro Sula', 'Cortés', 15.5042, -88.0250],
            ['Choloma', 'Cortés', 15.6144, -87.9530],
            ['La Ceiba', 'Atlántida', 15.7597, -86.7822],
            ['El Progreso', 'Yoro', 15.4000, -87.8067],
            ['Choluteca', 'Choluteca', 13.3007, -87.1908],
            ['Comayagua', 'Comayagua', 14.4602, -87.6476],
            ['Puerto Cortés', 'Cortés', 15.8256, -87.9256],
            ['Danlí', 'El Paraíso', 14.0333, -86.5833],
            ['Juticalpa', 'Olancho', 14.6664, -86.2186],
            ['Siguatepeque', 'Comayagua', 14.6013, -87.8355],
            ['Santa Rosa de Copán', 'Copán', 14.7681, -88.7786],
            ['Tocoa', 'Colón', 15.6836, -86.0028],
            ['Olanchito', 'Yoro', 15.4794, -86.5744],
            ['Villanueva', 'Cortés', 15.3167, -87.9833],
        ];

        foreach ($cities as [$name, $department, $lat, $lng]) {
            City::updateOrCreate(
                ['name' => $name, 'department' => $department],
                ['location' => new Point($lat, $lng)],
            );
        }
    }
}
