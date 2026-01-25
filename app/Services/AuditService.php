<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    public function log(Model $model, string $action, ?array $oldValues = null, ?array $newValues = null): void
    {
        $exclude = ['password', 'remember_token', 'updated_at'];

        if ($oldValues) {
            $oldValues = array_diff_key($oldValues, array_flip($exclude));
        }

        if ($newValues) {
            $newValues = array_diff_key($newValues, array_flip($exclude));
        }

        $user = Auth::user();
        $ip = app()->runningInConsole() ? null : request()?->ip();
        $userAgent = app()->runningInConsole() ? null : request()?->userAgent();

        AuditLog::create([
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action' => $action,
            'model_type' => $model::class,
            'model_id' => (int) $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }
}
