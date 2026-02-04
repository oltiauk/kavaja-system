<?php

namespace App\Filament\Resources\HospitalizationResource\Pages;

use App\Filament\Resources\HospitalizationResource;
use App\Models\PatientMedicalInfo;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class EditHospitalization extends EditRecord
{
    protected static string $resource = HospitalizationResource::class;

    protected array $medicalInfo = [];

    protected bool $isAutoSaving = false;

    public function updated($property): void
    {
        // Only auto-save for form data changes, not actions
        if (! str($property)->startsWith('data.')) {
            return;
        }

        $this->autoSave();
    }

    protected function autoSave(): void
    {
        $this->isAutoSaving = true;

        try {
            $this->save();
        } catch (ValidationException) {
            // Form incomplete — user will save manually when ready
            $this->resetErrorBag();
        } catch (\Exception) {
            // Silent fail
        } finally {
            $this->isAutoSaving = false;
        }
    }

    protected function getSavedNotification(): ?Notification
    {
        if ($this->isAutoSaving) {
            return null;
        }

        return parent::getSavedNotification();
    }

    protected function getRedirectUrl(): ?string
    {
        return null;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('downloadLabResults')
                ->label(__('app.labels.lab_results'))
                ->icon('heroicon-o-beaker')
                ->color('gray')
                ->visible(fn () => (bool) $this->record->lab_results_file_path)
                ->url(fn () => route('encounters.lab-results', $this->record), true),
            Actions\Action::make('downloadOperativeWork')
                ->label(__('app.labels.operative_procedure'))
                ->icon('heroicon-o-scissors')
                ->color('gray')
                ->visible(fn () => (bool) $this->record->operative_work_file_path)
                ->url(fn () => route('encounters.operative-work', $this->record), true),
            Actions\Action::make('downloadSurgicalNotes')
                ->label(__('app.labels.imaging_rtg'))
                ->icon('heroicon-o-photo')
                ->color('gray')
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

        // Load file paths for FileUpload components
        if ($this->record->surgical_notes_file_path) {
            $data['surgical_notes'] = $this->record->surgical_notes_file_path;
        }
        if ($this->record->lab_results_file_path) {
            $data['lab_results'] = $this->record->lab_results_file_path;
        }
        if ($this->record->operative_work_file_path) {
            $data['operative_work'] = $this->record->operative_work_file_path;
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

        // Handle file uploads (lab results, operative work, surgical notes)
        $data = $this->handleFileUpload($data, 'lab_results', 'lab-results');
        $data = $this->handleFileUpload($data, 'operative_work', 'operative-work');
        $data = $this->handleFileUpload($data, 'surgical_notes', 'surgical-notes');

        $data['type'] = 'hospitalization';
        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function afterSave(): void
    {
        $this->persistMedicalInfo($this->record->patient_id, $this->medicalInfo);
    }

    private function handleFileUpload(array $data, string $field, string $directory): array
    {
        $pathField = "{$field}_file_path";
        $nameField = "{$field}_original_filename";
        $mimeField = "{$field}_mime_type";
        $sizeField = "{$field}_file_size";

        if (isset($data[$field])) {
            if (filled($data[$field])) {
                $filePath = $data[$field];

                if (str_starts_with($filePath, "tmp/{$directory}")) {
                    $finalPath = "{$directory}/{$this->record->patient_id}/{$this->record->id}/".basename($filePath);
                    Storage::disk('local')->move($filePath, $finalPath);
                    $filePath = $finalPath;
                }

                if ($this->record->$pathField
                    && $filePath !== $this->record->$pathField
                    && Storage::disk('local')->exists($this->record->$pathField)) {
                    Storage::disk('local')->delete($this->record->$pathField);
                }

                $data[$pathField] = $filePath;
                $data[$mimeField] = Storage::disk('local')->mimeType($filePath);
                $data[$sizeField] = Storage::disk('local')->size($filePath);
            } else {
                if ($this->record->$pathField && Storage::disk('local')->exists($this->record->$pathField)) {
                    Storage::disk('local')->delete($this->record->$pathField);
                }
                $data[$pathField] = null;
                $data[$nameField] = null;
                $data[$mimeField] = null;
                $data[$sizeField] = null;
            }
        }
        unset($data[$field]);

        return $data;
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
