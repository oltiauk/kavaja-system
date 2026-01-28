<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\DischargePaper;
use App\Models\Encounter;
use App\Services\DischargePaperService;
use App\Services\QrCodeService;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label(__('app.actions.upload_document')),
            Actions\Action::make('uploadDischargePaper')
                ->label(__('app.actions.upload_discharge_paper'))
                ->icon('heroicon-o-arrow-up-on-square-stack')
                ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isStaff())
                ->form([
                    Forms\Components\Select::make('encounter_id')
                        ->label(__('app.labels.hospitalization'))
                        ->options(fn () => Encounter::hospitalizations()->active()->with('patient')->get()->mapWithKeys(
                            fn ($encounter) => [$encounter->id => "{$encounter->patient->full_name} (#{$encounter->id})"]
                        ))
                        ->searchable()
                        ->optionsLimit(5)
                        ->required(),
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
                    $encounter = Encounter::with(['patient', 'dischargePaper'])
                        ->where('type', 'hospitalization')
                        ->findOrFail($data['encounter_id']);

                    $patient = $encounter->patient;
                    $filePath = $data['file'];
                    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
                    $originalFilename = $data['original_filename'] ?? basename($filePath);
                    $pathBase = "discharge-papers/{$patient->id}/{$encounter->id}";
                    $originalPath = "{$pathBase}/original.{$extension}";
                    $qrPath = "{$pathBase}/with-qr.{$extension}";
                    $token = $encounter->dischargePaper?->qr_token ?? Str::random(64);

                    if ($encounter->dischargePaper) {
                        Storage::disk('local')->delete([$encounter->dischargePaper->original_file_path, $encounter->dischargePaper->qr_file_path]);
                        $encounter->dischargePaper->delete();
                    }

                    Storage::disk('local')->makeDirectory($pathBase);
                    Storage::disk('local')->move($filePath, $originalPath);

                    $qrService = app(QrCodeService::class);
                    $qrImage = $qrService->generate(config('app.url')."/patient/{$token}");

                    $dischargeService = app(DischargePaperService::class);
                    $dischargeService->addQrCode($originalPath, $qrPath, $qrImage);

                    DischargePaper::create([
                        'encounter_id' => $encounter->id,
                        'patient_id' => $patient->id,
                        'original_file_path' => $originalPath,
                        'original_filename' => $originalFilename,
                        'qr_file_path' => $qrPath,
                        'qr_token' => $token,
                        'mime_type' => Storage::disk('local')->mimeType($originalPath),
                        'uploaded_by' => Auth::id(),
                    ]);
                }),
        ];
    }
}
