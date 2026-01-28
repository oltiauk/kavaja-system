<?php

namespace App\Filament\Resources\EncounterResource\Pages;

use App\Filament\Resources\EncounterResource;
use App\Models\PatientMedicalInfo;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CreateEncounter extends CreateRecord
{
    protected static string $resource = EncounterResource::class;

    protected array $medicalInfo = [];

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->medicalInfo = $data['medical_info'] ?? [];
        unset($data['medical_info']);

        // Handle surgical notes file upload - will be moved to final location in afterCreate
        if (isset($data['surgical_notes']) && filled($data['surgical_notes'])) {
            $filePath = $data['surgical_notes'];
            $data['surgical_notes_file_path'] = $filePath;
            $data['surgical_notes_mime_type'] = Storage::disk('local')->mimeType($filePath);
            $data['surgical_notes_file_size'] = Storage::disk('local')->size($filePath);
        }
        unset($data['surgical_notes']);

        if (($data['type'] ?? null) === 'visit') {
            $data['medical_info_complete'] = true;
        }

        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->persistMedicalInfo($this->record->patient_id, $this->medicalInfo);

        // Move surgical notes file from tmp to final location if it exists
        if ($this->record->surgical_notes_file_path && str_starts_with($this->record->surgical_notes_file_path, 'tmp/surgical-notes')) {
            $finalPath = "surgical-notes/{$this->record->patient_id}/{$this->record->id}/".basename($this->record->surgical_notes_file_path);
            Storage::disk('local')->move($this->record->surgical_notes_file_path, $finalPath);
            $this->record->update(['surgical_notes_file_path' => $finalPath]);
        }
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
