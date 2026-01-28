<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Model;

class DocumentsGridWidget extends Widget
{
    protected static string $view = 'filament.widgets.documents-grid-widget';

    public ?Model $record = null;

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isAdministration() || $user?->isStaff();
    }
}
