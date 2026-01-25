<?php

namespace Database\Factories;

use App\Models\Encounter;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DischargePaper>
 */
class DischargePaperFactory extends Factory
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
            'original_file_path' => 'discharge-papers/originals/'.Str::uuid().'.pdf',
            'original_filename' => fake()->word().'.pdf',
            'qr_file_path' => 'discharge-papers/with-qr/'.Str::uuid().'.pdf',
            'qr_token' => Str::random(64),
            'mime_type' => 'application/pdf',
            'uploaded_by' => null,
        ];
    }
}
