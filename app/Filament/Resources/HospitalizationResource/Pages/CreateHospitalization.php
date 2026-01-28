<?php

namespace App\Filament\Resources\HospitalizationResource\Pages;

use App\Filament\Resources\HospitalizationResource;
use App\Models\PatientMedicalInfo;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CreateHospitalization extends CreateRecord
{
    protected static string $resource = HospitalizationResource::class;

    protected array $medicalInfo = [];

    public function mount(): void
    {
        parent::mount();

        $patientId = request()->query('patient_id');

        if ($patientId) {
            $this->form->fill([
                'patient_id' => (int) $patientId,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->medicalInfo = $data['medical_info'] ?? [];
        unset($data['medical_info']);

        if (($data['doctor_choice'] ?? null) !== 'other') {
            $data['doctor_name'] = $data['doctor_choice'] ?? $data['doctor_name'] ?? '';
        }

        unset($data['doctor_choice']);

        // Handle surgical notes file upload - will be moved to final location in afterCreate
        if (isset($data['surgical_notes']) && filled($data['surgical_notes'])) {
            $filePath = $data['surgical_notes'];
            $data['surgical_notes_file_path'] = $filePath;
            $data['surgical_notes_mime_type'] = Storage::disk('local')->mimeType($filePath);
            $data['surgical_notes_file_size'] = Storage::disk('local')->size($filePath);
        }
        unset($data['surgical_notes']);

        $data['type'] = 'hospitalization';
        $data['status'] = $data['status'] ?? 'active';
        $data['medical_info_complete'] = $data['medical_info_complete'] ?? false;
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
