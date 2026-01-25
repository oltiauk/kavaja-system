<?php

namespace App\Providers;

use App\Models\DischargePaper;
use App\Models\Document;
use App\Models\Encounter;
use App\Models\Patient;
use App\Observers\DischargePaperObserver;
use App\Observers\DocumentObserver;
use App\Observers\EncounterObserver;
use App\Observers\PatientObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Patient::observe(PatientObserver::class);
        Encounter::observe(EncounterObserver::class);
        Document::observe(DocumentObserver::class);
        DischargePaper::observe(DischargePaperObserver::class);
    }
}
