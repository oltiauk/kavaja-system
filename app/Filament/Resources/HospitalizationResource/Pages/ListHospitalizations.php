<?php

namespace App\Filament\Resources\HospitalizationResource\Pages;

use App\Filament\Resources\HospitalizationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHospitalizations extends ListRecords
{
    protected static string $resource = HospitalizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
