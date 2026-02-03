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

        // Handle file uploads - will be moved to final location in afterCreate
        foreach (['lab_results', 'operative_work', 'surgical_notes'] as $field) {
            if (isset($data[$field]) && filled($data[$field])) {
                $filePath = $data[$field];
                $data["{$field}_file_path"] = $filePath;
                $data["{$field}_mime_type"] = Storage::disk('local')->mimeType($filePath);
                $data["{$field}_file_size"] = Storage::disk('local')->size($filePath);
            }
            unset($data[$field]);
        }

        $data['type'] = 'hospitalization';
        $data['status'] = $data['status'] ?? 'active';
        $data['medical_info_complete'] = $data['medical_info_complete'] ?? false;
        $data['created_by'] = Auth::id();

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->persistMedicalInfo($this->record->patient_id, $this->medicalInfo);

        // Move uploaded files from tmp to final location
        $fileFields = [
            'lab_results_file_path' => 'lab-results',
            'operative_work_file_path' => 'operative-work',
            'surgical_notes_file_path' => 'surgical-notes',
        ];

        $updates = [];
        foreach ($fileFields as $pathField => $directory) {
            if ($this->record->$pathField && str_starts_with($this->record->$pathField, "tmp/{$directory}")) {
                $finalPath = "{$directory}/{$this->record->patient_id}/{$this->record->id}/".basename($this->record->$pathField);
                Storage::disk('local')->move($this->record->$pathField, $finalPath);
                $updates[$pathField] = $finalPath;
            }
        }

        if ($updates !== []) {
            $this->record->update($updates);
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
