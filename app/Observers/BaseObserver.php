<?php

namespace App\Observers;

use App\Services\AuditService;
use Illuminate\Database\Eloquent\Model;

abstract class BaseObserver
{
    protected function logAction(Model $model, string $action): void
    {
        $audit = app(AuditService::class);
        $oldValues = $action === 'update' ? $model->getOriginal() : null;
        $newValues = $action === 'delete' ? null : $model->getAttributes();

        $audit->log($model, $action, $oldValues, $newValues);
    }
}
