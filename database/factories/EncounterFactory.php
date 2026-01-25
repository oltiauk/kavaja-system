<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Encounter>
 */
class EncounterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = fake()->randomElement(['visit', 'hospitalization']);
        $admission = Carbon::now()->subDays(fake()->numberBetween(0, 10));
        $discharged = fake()->boolean(30);

        return [
            'patient_id' => Patient::factory(),
            'type' => $type,
            'status' => $discharged ? 'discharged' : 'active',
            'main_complaint' => fake()->sentence(),
            'doctor_name' => fake()->name(),
            'diagnosis' => fake()->boolean(70) ? fake()->sentence() : null,
            'treatment' => fake()->boolean(70) ? fake()->sentence() : null,
            'surgical_notes' => $type === 'hospitalization' && fake()->boolean(20) ? fake()->paragraph() : null,
            'admission_date' => $admission,
            'discharge_date' => $discharged ? $admission->copy()->addDays(fake()->numberBetween(1, 5)) : null,
            'medical_info_complete' => $type === 'hospitalization' ? fake()->boolean(70) : true,
            'created_by' => null,
            'updated_by' => null,
        ];
    }
}
