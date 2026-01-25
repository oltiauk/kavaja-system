<?php

namespace App\Filament\Resources\EncounterResource\Pages;

use App\Filament\Resources\EncounterResource;
use App\Models\PatientMedicalInfo;
use App\Services\DischargePaperService;
use App\Services\QrCodeService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EditEncounter extends EditRecord
{
    protected static string $resource = EncounterResource::class;

    protected array $medicalInfo = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('uploadDischargePaper')
                ->label(__('app.actions.upload_discharge_paper'))
                ->icon('heroicon-o-arrow-up-on-square-stack')
                ->visible(function () {
                    $user = auth()->user();

                    return ($user?->isAdmin() || $user?->isStaff())
                        && $this->record->isHospitalization()
                        && ! $this->record->dischargePaper;
                })
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label(__('app.labels.discharge_paper'))
                        ->required()
                        ->maxSize(20480)
                        ->disk('local')
                        ->directory('tmp/discharge')
                        ->storeFileNamesIn('original_filename')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ]),
                ])
                ->action(function (array $data) {
                    $this->storeDischargePaper($data, replaceExisting: false);
                }),
            Actions\Action::make('replaceDischargePaper')
                ->label(__('app.actions.replace_discharge_paper'))
                ->icon('heroicon-o-arrow-path')
                ->visible(function () {
                    $user = auth()->user();

                    return ($user?->isAdmin() || $user?->isStaff())
                        && $this->record->isHospitalization()
                        && $this->record->dischargePaper;
                })
                ->modalDescription(__('app.messages.discharge_replace_warning'))
                ->form([
                    Forms\Components\FileUpload::make('file')
                        ->label(__('app.labels.discharge_paper'))
                        ->required()
                        ->maxSize(20480)
                        ->disk('local')
                        ->directory('tmp/discharge')
                        ->storeFileNamesIn('original_filename')
                        ->acceptedFileTypes([
                            'application/pdf',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                        ]),
                ])
                ->action(function (array $data) {
                    $this->storeDischargePaper($data, replaceExisting: true);
                }),
            Actions\Action::make('convertToHospitalization')
                ->label(__('app.actions.convert_to_hospitalization'))
                ->icon('heroicon-o-arrow-path')
                ->visible(function () {
                    $user = auth()->user();

                    return ($user?->isAdmin() || $user?->isStaff()) && $this->record->canBeConverted();
                })
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'type' => 'hospitalization',
                        'medical_info_complete' => false,
                        'updated_by' => Auth::id(),
                    ]);
                }),
            Actions\Action::make('dischargePatient')
                ->label(__('app.actions.discharge_patient'))
                ->icon('heroicon-o-arrow-up-right')
                ->visible(function () {
                    $user = auth()->user();

                    return ($user?->isAdmin() || $user?->isStaff())
                        && $this->record->isHospitalization()
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
            Actions\Action::make('downloadDischargeOriginal')
                ->label(__('app.actions.download_discharge_original'))
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => (bool) $this->record->dischargePaper)
                ->url(fn () => route('discharge-papers.original', $this->record->dischargePaper), true),
            Actions\Action::make('downloadDischargeQr')
                ->label(__('app.actions.download_discharge_qr'))
                ->icon('heroicon-o-qr-code')
                ->visible(fn () => (bool) $this->record->dischargePaper)
                ->url(fn () => route('discharge-papers.with-qr', $this->record->dischargePaper), true),
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

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->medicalInfo = $data['medical_info'] ?? [];
        unset($data['medical_info']);

        if (($data['type'] ?? $this->record->type) === 'visit') {
            $data['medical_info_complete'] = true;
        }

        $data['updated_by'] = Auth::id();

        return $data;
    }

    protected function afterSave(): void
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

    private function storeDischargePaper(array $data, bool $replaceExisting): void
    {
        $encounter = $this->record->loadMissing(['patient', 'dischargePaper']);
        $patient = $encounter->patient;
        $filePath = $data['file'];
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $originalFilename = $data['original_filename'] ?? basename($filePath);
        $pathBase = "discharge-papers/{$patient->id}/{$encounter->id}";
        $originalPath = "{$pathBase}/original.{$extension}";
        $qrPath = "{$pathBase}/with-qr.{$extension}";

        $token = $encounter->dischargePaper?->qr_token ?? Str::random(64);

        if ($replaceExisting && $encounter->dischargePaper) {
            Storage::disk('local')->delete([
                $encounter->dischargePaper->original_file_path,
                $encounter->dischargePaper->qr_file_path,
            ]);
            $encounter->dischargePaper->delete();
        }

        Storage::disk('local')->makeDirectory($pathBase);
        Storage::disk('local')->move($filePath, $originalPath);

        $qrService = app(QrCodeService::class);
        $qrImage = $qrService->generate(config('app.url')."/patient/{$token}");

        $dischargeService = app(DischargePaperService::class);
        $dischargeService->addQrCode($originalPath, $qrPath, $qrImage);

        $encounter->dischargePaper()->create([
            'patient_id' => $patient->id,
            'original_file_path' => $originalPath,
            'original_filename' => $originalFilename,
            'qr_file_path' => $qrPath,
            'qr_token' => $token,
            'mime_type' => Storage::disk('local')->mimeType($originalPath),
            'uploaded_by' => Auth::id(),
        ]);
    }
}
