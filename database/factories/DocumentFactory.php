<?php

namespace Database\Factories;

use App\Models\Encounter;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'encounter_id' => Encounter::factory(),
            'patient_id' => function (array $attributes) {
                return Encounter::find($attributes['encounter_id'])?->patient_id;
            },
            'type' => fake()->randomElement(['diagnostic_image', 'report', 'other']),
            'original_filename' => fake()->word().'.pdf',
            'stored_filename' => Str::uuid().'.pdf',
            'file_path' => 'documents/'.Str::uuid().'.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => fake()->numberBetween(10_000, 500_000),
            'uploaded_by' => null,
        ];
    }
}
