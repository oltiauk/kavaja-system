<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PatientMedicalInfo>
 */
class PatientMedicalInfoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'blood_type' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-', 'unknown']),
            'height_cm' => fake()->randomFloat(2, 140, 200),
            'weight_kg' => fake()->randomFloat(2, 45, 120),
            'allergies' => fake()->sentence(),
            'smoking_status' => fake()->randomElement(['smoker', 'non_smoker', 'former_smoker']),
            'alcohol_use' => fake()->randomElement(['none', 'occasionally', 'weekly']),
            'drug_use_history' => fake()->boolean(20) ? fake()->sentence() : null,
            'pacemaker_implants' => fake()->boolean(10) ? 'Pacemaker' : null,
            'anesthesia_reactions' => fake()->boolean(10) ? fake()->sentence() : null,
            'current_medications' => fake()->sentence(),
            'updated_by' => null,
        ];
    }
}
