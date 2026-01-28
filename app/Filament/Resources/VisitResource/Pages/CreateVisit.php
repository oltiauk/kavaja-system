<?php

namespace App\Filament\Resources\VisitResource\Pages;

use App\Filament\Resources\VisitResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateVisit extends CreateRecord
{
    protected static string $resource = VisitResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['doctor_choice'] ?? null) !== 'other') {
            $data['doctor_name'] = $data['doctor_choice'] ?? $data['doctor_name'] ?? '';
        }

        unset($data['doctor_choice']);

        $data['type'] = 'visit';
        $data['status'] = 'active';
        $data['medical_info_complete'] = true;
        $data['created_by'] = Auth::id();

        return $data;
    }
}
