<?php

namespace App\Observers;

use App\Models\Encounter;

class EncounterObserver extends BaseObserver
{
    public function created(Encounter $encounter): void
    {
        $this->logAction($encounter, 'create');
    }

    public function updated(Encounter $encounter): void
    {
        $this->logAction($encounter, 'update');
    }
}
