<?php

namespace App\Filament\Widgets;

use App\Models\Encounter;
use App\Models\Patient;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class RoleStatsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $todayStart = Carbon::today();
        $todayEnd = Carbon::today()->endOfDay();

        if ($user?->isAdmin()) {
            return [
                Stat::make(__('app.dashboard.total_patients'), Patient::count()),
                Stat::make(__('app.dashboard.active_hospitalizations'), Encounter::hospitalizations()->active()->count()),
                Stat::make(
                    __('app.dashboard.discharges_today'),
                    Encounter::where('status', 'discharged')
                        ->whereBetween('discharge_date', [$todayStart, $todayEnd])
                        ->count()
                ),
            ];
        }

        if ($user?->isAdministration()) {
            return [
                Stat::make(
                    __('app.dashboard.patients_registered_today'),
                    Patient::whereBetween('created_at', [$todayStart, $todayEnd])->count()
                ),
                Stat::make(
                    __('app.dashboard.encounters_created_today'),
                    Encounter::whereBetween('created_at', [$todayStart, $todayEnd])->count()
                ),
            ];
        }

        return [
            Stat::make(
                __('app.dashboard.needs_medical_info'),
                Encounter::hospitalizations()
                    ->active()
                    ->where('medical_info_complete', false)
                    ->count()
            ),
            Stat::make(
                __('app.dashboard.ready_for_discharge'),
                Encounter::hospitalizations()
                    ->active()
                    ->where('medical_info_complete', true)
                    ->whereHas('dischargePaper')
                    ->count()
            ),
        ];
    }
}
