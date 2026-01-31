<?php

namespace App\Filament\Resources\DiagnosisResource\Pages;

use App\Filament\Resources\DiagnosisResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDiagnosis extends EditRecord
{
    protected static string $resource = DiagnosisResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(__('app.labels.delete_diagnosis')),
        ];
    }
}
