<?php

namespace App\Observers;

use App\Models\Diagnosis;
use App\Models\Encounter;

class EncounterObserver extends BaseObserver
{
    public function created(Encounter $encounter): void
    {
        $this->logAction($encounter, 'create');
        $this->trackDiagnosis($encounter);
    }

    public function updated(Encounter $encounter): void
    {
        $this->logAction($encounter, 'update');

        if ($encounter->isDirty('diagnosis')) {
            $this->trackDiagnosis($encounter);
        }
    }

    /**
     * Track diagnosis usage for autocomplete suggestions.
     */
    private function trackDiagnosis(Encounter $encounter): void
    {
        if (filled($encounter->diagnosis)) {
            Diagnosis::findOrCreateByName($encounter->diagnosis);
        }
    }
}
