<?php

namespace App\Observers;

use App\Models\Document;

class DocumentObserver extends BaseObserver
{
    public function created(Document $document): void
    {
        $this->logAction($document, 'create');
    }

    public function deleted(Document $document): void
    {
        $this->logAction($document, 'delete');
    }
}
