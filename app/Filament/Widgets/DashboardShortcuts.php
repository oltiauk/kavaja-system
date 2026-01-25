<?php

namespace App\Filament\Widgets;

use App\Filament\Pages\MonthlyReports;
use App\Filament\Pages\StaffQueue;
use App\Filament\Resources\PatientResource;
use App\Filament\Resources\UserResource;
use Filament\Widgets\Widget;

class DashboardShortcuts extends Widget
{
    protected static string $view = 'filament.widgets.dashboard-shortcuts';

    protected int|string|array $columnSpan = 'full';

    /**
     * @return array<int, array{label: string, url: string}>
     */
    public function getShortcuts(): array
    {
        $user = auth()->user();

        if ($user?->isAdmin()) {
            return [
                ['label' => __('app.actions.manage_users'), 'url' => UserResource::getUrl()],
                ['label' => __('app.actions.register_patient'), 'url' => PatientResource::getUrl('create')],
                ['label' => __('app.actions.staff_queue'), 'url' => StaffQueue::getUrl()],
                ['label' => __('app.actions.monthly_reports'), 'url' => MonthlyReports::getUrl()],
            ];
        }

        if ($user?->isAdministration()) {
            return [
                ['label' => __('app.actions.register_patient'), 'url' => PatientResource::getUrl('create')],
                ['label' => __('app.actions.search_patient'), 'url' => PatientResource::getUrl()],
            ];
        }

        return [
            ['label' => __('app.actions.staff_queue'), 'url' => StaffQueue::getUrl()],
            ['label' => __('app.actions.search_patient'), 'url' => PatientResource::getUrl()],
        ];
    }
}
