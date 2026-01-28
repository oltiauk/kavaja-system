<?php

namespace App\Services;

use App\Models\Encounter;
use App\Models\Patient;
use Illuminate\Support\Carbon;

class ReportService
{
    public function generateMonthlyReport(int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        return [
            'period' => [
                'month' => $month,
                'year' => $year,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'totals' => [
                'new_patients' => Patient::whereBetween('created_at', [$startDate, $endDate])->count(),
                'total_visits' => Encounter::where('type', 'visit')
                    ->whereBetween('admission_date', [$startDate, $endDate])
                    ->count(),
                'total_hospitalizations' => Encounter::where('type', 'hospitalization')
                    ->whereBetween('admission_date', [$startDate, $endDate])
                    ->count(),
                'total_surgeries' => Encounter::whereNotNull('surgical_notes_file_path')
                    ->whereBetween('admission_date', [$startDate, $endDate])
                    ->count(),
                'total_discharges' => Encounter::where('status', 'discharged')
                    ->whereBetween('discharge_date', [$startDate, $endDate])
                    ->count(),
            ],
            'patients_per_day' => $this->getPatientsPerDay($startDate, $endDate),
            'common_diagnoses' => $this->getCommonDiagnoses($startDate, $endDate),
            'doctors_by_patients' => $this->getDoctorsByPatients($startDate, $endDate),
            'busiest_days' => $this->getBusiestDays($startDate, $endDate),
        ];
    }

    private function getPatientsPerDay(Carbon $start, Carbon $end): array
    {
        return Encounter::selectRaw('DATE(admission_date) as date, type, COUNT(*) as count')
            ->whereBetween('admission_date', [$start, $end])
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->toArray();
    }

    private function getCommonDiagnoses(Carbon $start, Carbon $end): array
    {
        return Encounter::selectRaw('diagnosis, COUNT(*) as count')
            ->whereNotNull('diagnosis')
            ->whereBetween('admission_date', [$start, $end])
            ->groupBy('diagnosis')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getDoctorsByPatients(Carbon $start, Carbon $end): array
    {
        return Encounter::selectRaw('doctor_name, type, COUNT(*) as count')
            ->whereBetween('admission_date', [$start, $end])
            ->groupBy('doctor_name', 'type')
            ->orderByDesc('count')
            ->get()
            ->groupBy('doctor_name')
            ->toArray();
    }

    private function getBusiestDays(Carbon $start, Carbon $end): array
    {
        return Encounter::selectRaw('DAYNAME(admission_date) as day_name, COUNT(*) as count')
            ->whereBetween('admission_date', [$start, $end])
            ->groupBy('day_name')
            ->orderByDesc('count')
            ->get()
            ->toArray();
    }
}
