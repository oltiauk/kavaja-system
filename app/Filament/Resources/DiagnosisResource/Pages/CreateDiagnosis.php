<?php

namespace App\Filament\Resources\DiagnosisResource\Pages;

use App\Filament\Resources\DiagnosisResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiagnosis extends CreateRecord
{
    protected static string $resource = DiagnosisResource::class;

    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
