<?php

namespace App\Observers;

use App\Models\DischargePaper;

class DischargePaperObserver extends BaseObserver
{
    public function created(DischargePaper $dischargePaper): void
    {
        $this->logAction($dischargePaper, 'create');
    }

    public function updated(DischargePaper $dischargePaper): void
    {
        $this->logAction($dischargePaper, 'update');
    }

    public function deleted(DischargePaper $dischargePaper): void
    {
        $this->logAction($dischargePaper, 'delete');
    }
}
