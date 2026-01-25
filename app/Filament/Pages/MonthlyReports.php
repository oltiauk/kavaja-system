<?php

namespace App\Filament\Pages;

use App\Services\ReportService;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;

class MonthlyReports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-pie';

    protected static ?string $navigationLabel = 'Monthly Reports';

    protected static string $view = 'filament.pages.monthly-reports';

    public static function getNavigationLabel(): string
    {
        return __('app.actions.monthly_reports');
    }

    public function getTitle(): string
    {
        return __('app.actions.monthly_reports');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public int $month;

    public int $year;

    public array $report = [];

    public function mount(ReportService $service): void
    {
        $now = Carbon::now();
        $this->month = (int) $now->month;
        $this->year = (int) $now->year;

        $this->generate($service);
    }

    public function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('month')
                    ->label(__('app.labels.month'))
                    ->options([
                        1 => __('app.months.jan'),
                        2 => __('app.months.feb'),
                        3 => __('app.months.mar'),
                        4 => __('app.months.apr'),
                        5 => __('app.months.may'),
                        6 => __('app.months.jun'),
                        7 => __('app.months.jul'),
                        8 => __('app.months.aug'),
                        9 => __('app.months.sep'),
                        10 => __('app.months.oct'),
                        11 => __('app.months.nov'),
                        12 => __('app.months.dec'),
                    ])
                    ->required(),
                Forms\Components\Select::make('year')
                    ->label(__('app.labels.year'))
                    ->options($this->yearOptions())
                    ->required(),
            ]);
    }

    public function submit(ReportService $service): void
    {
        $this->form->validate();
        $this->generate($service);
    }

    private function generate(ReportService $service): void
    {
        $this->report = $service->generateMonthlyReport($this->month, $this->year);
    }

    private function yearOptions(): array
    {
        $current = Carbon::now()->year;
        $years = [];

        for ($i = $current; $i >= $current - 5; $i--) {
            $years[$i] = (string) $i;
        }

        return $years;
    }
}
