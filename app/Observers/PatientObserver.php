<?php

namespace App\Observers;

use App\Models\Patient;

class PatientObserver extends BaseObserver
{
    public function created(Patient $patient): void
    {
        $this->logAction($patient, 'create');
    }

    public function updated(Patient $patient): void
    {
        $this->logAction($patient, 'update');
    }

    public function deleted(Patient $patient): void
    {
        $this->logAction($patient, 'delete');
    }
}
