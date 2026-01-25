<?php

namespace App\Providers;

use App\Models\DischargePaper;
use App\Models\Document;
use App\Models\Encounter;
use App\Models\Patient;
use App\Models\User;
use App\Policies\DischargePaperPolicy;
use App\Policies\DocumentPolicy;
use App\Policies\EncounterPolicy;
use App\Policies\PatientPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Patient::class => PatientPolicy::class,
        Encounter::class => EncounterPolicy::class,
        Document::class => DocumentPolicy::class,
        DischargePaper::class => DischargePaperPolicy::class,
    ];

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }
    }
}
