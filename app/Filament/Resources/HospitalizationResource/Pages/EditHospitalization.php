<?php

namespace App\Filament\Resources\HospitalizationResource\Pages;

use App\Filament\Resources\HospitalizationResource;
use App\Models\PatientMedicalInfo;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class EditHospitalization extends EditRecord
{
    protected static string $resource = HospitalizationResource::class;

    protected array $medicalInfo = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadSurgicalNotes')
                ->label(__('app.actions.download_surgical_notes'))
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => (bool) $this->record->surgical_notes_file_path)
                ->url(fn () => route('encounters.surgical-notes', $this->record), true),
            Actions\Action::make('dischargePatient')
                ->label(__('app.actions.discharge_patient'))
                ->icon('heroicon-o-arrow-up-right')
                ->visible(function () {
                    $user = auth()->user();

                    return ($user?->isAdmin() || $user?->isStaff())
                        && $this->record->isActive()
                        && $this->record->dischargePaper;
                })
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => 'discharged',
                        'discharge_date' => now(),
                        'updated_by' => Auth::id(),
                    ]);
                }),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $medicalInfo = $this->record->patient->medicalInfo;

        if ($medicalInfo) {
            $data['medical_info'] = [
                'blood_type' => $medicalInfo->blood_type,
                'height_cm' => $medicalInfo->height_cm,
                'weight_kg' => $medicalInfo->weight_kg,
                'allergies' => $medicalInfo->allergies,
                'smoking_status' => $medicalInfo->smoking_status,
                'alcohol_use' => $medicalInfo->alcohol_use,
                'drug_use_history' => $medicalInfo->drug_use_history,
                'pacemaker_implants' => $medicalInfo->pacemaker_implants,
                'anesthesia_reactions' => $medicalInfo->anesthesia_reactions,
                'current_medications' => $medicalInfo->current_medications,
            ];
        }

        // Load surgical notes file path for FileUpload component
        if ($this->record->surgical_notes_file_path) {
            $data['surgical_notes'] = $this->record->surgical_notes_file_path;
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->medicalInfo = $data['medical_info'] ?? [];
        unset($data['medical_info']);

        if (($data['doctor_choice'] ?? null) !== 'other') {
            $data['doctor_name'] = $data['doctor_choice'] ?? $data['doctor_name'] ?? '';
        }

        unset($data['doctor_choice']);

        // Handle surgical notes file upload
        if (isset($data['surgical_notes'])) {
            if (filled($data['surgical_notes'])) {
                $filePath = $data['surgical_notes'];

                // If file is in tmp directory, move it to final location
                if (str_starts_with($filePath, 'tmp/surgical-notes')) {
                    $finalPath = "surgical-notes/{$this->record->patient_id}/{$this->record->id}/".basename($filePath);
                    Storage::disk('local')->move($filePath, $finalPath);
                    $filePath = $finalPath;
                }

                // Delete old file if it exists and is different
                if ($this->record->surgical_notes_file_path
                    && $this->record->surgical_notes_file_path !== $filePath
                    && Storage::disk('local')->exists($this->record->surgical_notes_file_path)) {
                    Storage::disk('local')->delete($this->record->surgical_notes_file_path);
                }

                $data['surgical_notes_file_path'] = $filePath;
                $data['surgical_notes_mime_type'] = Storage::disk('local')->mimeType($filePath);
                $data['surgical_notes_file_size'] = Storage::disk('local')->size($filePath);
            } else {
                // If file is removed, delete old file and clear metadata
                if ($this->record->surgical_notes_file_path && Storage::disk('local')->exists($this->record->surgical_notes_file_path)) {
                    Storage::disk('local')->delete($this->record->surgical_notes_file_path);
                }
                $data['surgical_notes_file_path'] = null;
                $data['surgical_notes_original_filename'] = null;
                $data['surgical_notes_mime_type'] = null;
                $data['surgical_notes_file_size'] = null;
            }
        }
        unset($data['surgical_notes']);

        $data['type'] = 'hospitalization';
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function afterSave(): void
    {
        $this->persistMedicalInfo($this->record->patient_id, $this->medicalInfo);
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
