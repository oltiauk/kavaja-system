<?php

namespace Database\Seeders;

use App\Models\Doctor;
use Illuminate\Database\Seeder;

class DoctorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $doctors = config('hospital.doctors', []);

        foreach ($doctors as $index => $doctorName) {
            if (filled($doctorName)) {
                Doctor::firstOrCreate(
                    ['name' => $doctorName],
                    [
                        'is_active' => true,
                        'sort_order' => $index,
                    ]
                );
            }
        }
    }
}
