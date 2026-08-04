<?php

namespace Database\Factories;

use App\Enums\DocumentStatus;
use App\Enums\DocumentType;
use App\Models\Document;
use App\Models\TransporterProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Document>
 */
class DocumentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'documentable_id' => TransporterProfile::factory(),
            'documentable_type' => TransporterProfile::class,
            'type' => DocumentType::DriverLicense,
            'path' => 'documents/'.fake()->uuid().'.jpg',
            'status' => DocumentStatus::Pending,
        ];
    }
}
