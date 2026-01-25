<?php

namespace App\Filament\Resources\EncounterResource\Pages;

use App\Filament\Resources\EncounterResource;
use App\Models\PatientMedicalInfo;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateEncounter extends CreateRecord
{
    protected static string $resource = EncounterResource::class;

    protected array $medicalInfo = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->medicalInfo = $data['medical_info'] ?? [];
        unset($data['medical_info']);

        if (($data['type'] ?? null) === 'visit') {
            $data['medical_info_complete'] = true;
        }

        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->persistMedicalInfo($this->record->patient_id, $this->medicalInfo);
    }

    private function persistMedicalInfo(int $patientId, array $medicalInfo): void
    {
        if (! $this->record->isHospitalization()) {
            return;
        }

        $filtered = array_filter($medicalInfo, fn ($value) => filled($value));

        if ($filtered === []) {
            return;
        }

        PatientMedicalInfo::updateOrCreate(
            ['patient_id' => $patientId],
            array_merge($medicalInfo, ['updated_by' => Auth::id()])
        );
    }
}
