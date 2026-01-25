<?php

namespace App\Filament\Pages;

use App\Models\Encounter;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;

class StaffQueue extends Page implements HasTable
{
    use Tables\Concerns\InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Staff Queue';

    protected static string $view = 'filament.pages.staff-queue';

    public static function getNavigationLabel(): string
    {
        return __('app.actions.staff_queue');
    }

    public function getTitle(): string
    {
        return __('app.actions.staff_queue');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isStaff();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Encounter::hospitalizations()
                    ->active()
                    ->with(['patient', 'dischargePaper'])
            )
            ->defaultSort('admission_date')
            ->columns([
                TextColumn::make('patient.full_name')
                    ->label(__('app.labels.patient'))
                    ->sortable()
                    ->searchable(['patient.first_name', 'patient.last_name']),
                TextColumn::make('doctor_name')->label(__('app.labels.doctor'))->sortable(),
                TextColumn::make('main_complaint')->label(__('app.labels.complaint'))->limit(30),
                TextColumn::make('admission_date')->dateTime()->label(__('app.labels.admitted'))->sortable(),
                IconColumn::make('medical_info_complete')->label(__('app.labels.medical_info'))->boolean(),
                IconColumn::make('dischargePaper')->label(__('app.labels.discharge_paper'))->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('queue')
                    ->label(__('app.labels.queue'))
                    ->options([
                        'needs-info' => __('app.labels.needs_medical_info'),
                        'ready-discharge' => __('app.labels.ready_for_discharge'),
                        'all' => __('app.labels.all_active'),
                    ])
                    ->default('all')
                    ->query(function ($query, $state) {
                        if ($state === 'needs-info') {
                            $query->where('medical_info_complete', false);
                        } elseif ($state === 'ready-discharge') {
                            $query->where('medical_info_complete', true)->whereHas('dischargePaper');
                        }
                    }),
            ])
            ->emptyStateHeading(__('app.empty.no_queue'))
            ->paginated(false);
    }
}
