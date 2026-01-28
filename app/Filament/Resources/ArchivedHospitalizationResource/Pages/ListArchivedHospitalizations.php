<?php

namespace App\Filament\Resources\ArchivedHospitalizationResource\Pages;

use App\Filament\Resources\ArchivedHospitalizationResource;
use Filament\Resources\Pages\ListRecords;

class ListArchivedHospitalizations extends ListRecords
{
    protected static string $resource = ArchivedHospitalizationResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
