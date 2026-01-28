<?php

namespace App\Filament\Resources\ArchivedHospitalizationResource\Pages;

use App\Filament\Resources\ArchivedHospitalizationResource;
use App\Filament\Resources\HospitalizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewArchivedHospitalization extends ViewRecord
{
    protected static string $resource = ArchivedHospitalizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('readmit')
                ->label(__('app.actions.readmit'))
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('success')
                ->url(fn () => HospitalizationResource::getUrl('create', [
                    'patient_id' => $this->record->patient_id,
                ])),
        ];
    }
}
