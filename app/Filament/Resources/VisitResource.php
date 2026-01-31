<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Forms\Components\DiagnosisInput;
use App\Models\Doctor;
use App\Models\Encounter;
use App\Models\Patient;
use App\Services\DischargePaperService;
use App\Services\QrCodeService;
use Filament\Forms;
use Filament\Forms\Components\Actions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VisitResource extends Resource
{
    protected static ?string $model = Encounter::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Visits';

    public static function getNavigationLabel(): string
    {
        return __('app.labels.visits');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.visit');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.visits');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canViewAny();
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isAdministration() || $user?->isStaff();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isAdministration();
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isStaff();
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', 'visit')
            ->where('status', 'active');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('app.labels.patient_information'))
                    ->schema([
                        Forms\Components\Select::make('patient_id')
                            ->label(__('app.labels.patient'))
                            ->relationship(
                                name: 'patient',
                                titleAttribute: 'last_name',
                                modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query) => $query->orderBy('created_at', 'desc')
                            )
                            ->getOptionLabelFromRecordUsing(fn (Patient $record) => $record->full_name)
                            ->searchable(['first_name', 'last_name'])
                            ->preload()
                            ->optionsLimit(5)
                            ->required()
                            ->live()
                            ->hintAction(
                                FormAction::make('createPatient')
                                    ->label(__('app.actions.register_patient'))
                                    ->icon('heroicon-o-user-plus')
                                    ->url(PatientResource::getUrl('create'))
                                    ->openUrlInNewTab()
                            ),
                        Forms\Components\Placeholder::make('patient_info')
                            ->content(fn (Get $get) => view('filament.forms.patient-info-card', [
                                'patient' => Patient::with('medicalInfo')->find($get('patient_id')),
                            ]))
                            ->visible(fn (Get $get) => filled($get('patient_id'))),
                    ]),
                Forms\Components\Select::make('doctor_choice')
                    ->label(__('app.labels.doctor'))
                    ->options(fn () => static::doctorOptions())
                    ->searchable()
                    ->preload()
                    ->optionsLimit(20)
                    ->required()
                    ->live()
                    ->afterStateHydrated(function (?Encounter $record, $set): void {
                        if (! $record) {
                            return;
                        }

                        $options = array_keys(static::doctorOptions());
                        if (in_array($record->doctor_name, $options, true)) {
                            $set('doctor_choice', $record->doctor_name);
                        } else {
                            $set('doctor_choice', 'other');
                            $set('doctor_name', $record->doctor_name);
                        }
                    })
                    ->afterStateUpdated(function (?string $state, callable $set): void {
                        if ($state && $state !== 'other') {
                            $set('doctor_name', $state);
                        }
                    }),
                Forms\Components\TextInput::make('doctor_name')
                    ->label(__('app.labels.doctor_other'))
                    ->required(fn (Get $get) => $get('doctor_choice') === 'other')
                    ->visible(fn (Get $get) => $get('doctor_choice') === 'other')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('main_complaint')
                    ->label(__('app.labels.main_complaint'))
                    ->required()
                    ->columnSpanFull(),
                DiagnosisInput::make('diagnosis')
                    ->label(__('app.labels.diagnosis'))
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('treatment')
                    ->label(__('app.labels.treatment'))
                    ->columnSpanFull(),
                Forms\Components\DateTimePicker::make('admission_date')
                    ->label(__('app.labels.visit_date'))
                    ->required()
                    ->default(now()),
                Forms\Components\Section::make(__('app.labels.discharge_paper'))
                    ->key('discharge-paper')
                    ->visible(fn (?Encounter $record) => (bool) $record)
                    ->schema([
                        Forms\Components\Placeholder::make('discharge_status')
                            ->label(__('app.labels.status'))
                            ->content(fn (?Encounter $record) => $record?->dischargePaper ? __('app.labels.uploaded_status') : __('app.labels.not_uploaded')),
                        Forms\Components\Placeholder::make('discharge_filename')
                            ->label(__('app.labels.file'))
                            ->content(fn (?Encounter $record) => $record?->dischargePaper?->original_filename ?? '—'),
                        Forms\Components\Placeholder::make('discharge_uploaded_at')
                            ->label(__('app.labels.uploaded_at'))
                            ->content(fn (?Encounter $record) => $record?->dischargePaper?->created_at?->toDateTimeString() ?? '—'),
                        Forms\Components\Placeholder::make('discharge_uploaded_by')
                            ->label(__('app.labels.uploaded_by'))
                            ->content(fn (?Encounter $record) => $record?->dischargePaper?->uploadedBy?->name ?? '—'),
                        Actions::make([
                            FormAction::make('uploadDischargePaper')
                                ->label(__('app.actions.upload_discharge_paper'))
                                ->icon('heroicon-o-arrow-up-on-square-stack')
                                ->visible(function (?Encounter $record) {
                                    $user = auth()->user();

                                    return ($user?->isAdmin() || $user?->isStaff())
                                        && $record
                                        && ! $record->dischargePaper;
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
                                ->successNotificationTitle(__('app.notifications.discharge_paper_uploaded'))
                                ->action(function (array $data, Encounter $record, $livewire): void {
                                    try {
                                        static::storeDischargePaper($record, $data, false);
                                        $record->refresh();
                                        $livewire->dispatch('$refresh');
                                    } catch (\RuntimeException $e) {
                                        throw ValidationException::withMessages([
                                            'file' => __('app.errors.file_processing_failed').': '.$e->getMessage(),
                                        ]);
                                    }
                                }),
                            FormAction::make('replaceDischargePaper')
                                ->label(__('app.actions.replace_discharge_paper'))
                                ->icon('heroicon-o-arrow-path')
                                ->visible(function (?Encounter $record) {
                                    $user = auth()->user();

                                    return ($user?->isAdmin() || $user?->isStaff())
                                        && $record
                                        && $record->dischargePaper;
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
                                ->successNotificationTitle(__('app.notifications.discharge_paper_replaced'))
                                ->action(function (array $data, Encounter $record, $livewire): void {
                                    try {
                                        static::storeDischargePaper($record, $data, true);
                                        $record->refresh();
                                        $livewire->dispatch('$refresh');
                                    } catch (\RuntimeException $e) {
                                        throw ValidationException::withMessages([
                                            'file' => __('app.errors.file_processing_failed').': '.$e->getMessage(),
                                        ]);
                                    }
                                }),
                            FormAction::make('downloadDischargeOriginal')
                                ->label(__('app.actions.download_discharge_original'))
                                ->icon('heroicon-o-arrow-down-tray')
                                ->visible(fn (?Encounter $record) => (bool) $record?->dischargePaper)
                                ->url(fn (Encounter $record) => route('discharge-papers.original', $record->dischargePaper), true),
                            FormAction::make('downloadDischargeQr')
                                ->label(__('app.actions.download_discharge_qr'))
                                ->icon('heroicon-o-qr-code')
                                ->visible(fn (?Encounter $record) => (bool) $record?->dischargePaper)
                                ->url(fn (Encounter $record) => route('discharge-papers.with-qr', $record->dischargePaper), true),
                        ])->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.full_name')
                    ->label(__('app.labels.patient'))
                    ->searchable(['patients.first_name', 'patients.last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('doctor_name')
                    ->label(__('app.labels.doctor'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('main_complaint')
                    ->label(__('app.labels.main_complaint'))
                    ->limit(30),
                Tables\Columns\TextColumn::make('admission_date')
                    ->label(__('app.labels.visit_date'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading(__('app.empty.no_visits'))
            ->defaultSort('admission_date', 'desc')
            ->actions([
                Tables\Actions\Action::make('discharge')
                    ->label(__('app.actions.discharge'))
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading(__('app.actions.discharge_patient'))
                    ->modalDescription(__('app.messages.confirm_discharge'))
                    ->visible(fn (Encounter $record) => $record->status === 'active')
                    ->action(function (Encounter $record): void {
                        $record->update([
                            'status' => 'discharged',
                            'discharge_date' => now(),
                        ]);
                    }),
                Tables\Actions\Action::make('print')
                    ->label(__('app.actions.print_visit'))
                    ->icon('heroicon-o-printer')
                    ->url(fn (Encounter $record) => route('visits.print', $record))
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisits::route('/'),
            'create' => Pages\CreateVisit::route('/create'),
            'edit' => Pages\EditVisit::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected static function doctorOptions(): array
    {
        $options = [];

        foreach (Doctor::active()->ordered()->get() as $doctor) {
            $options[$doctor->name] = $doctor->name;
        }

        $options['other'] = '— '.__('app.labels.other').' —';

        return $options;
    }

    private static function storeDischargePaper(Encounter $encounter, array $data, bool $replaceExisting): void
    {
        $encounter->loadMissing(['patient', 'dischargePaper']);
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

        if (! Storage::disk('local')->exists($filePath)) {
            $fullTempPath = Storage::disk('local')->path($filePath);
            throw new \RuntimeException("Uploaded file not found at: {$filePath} (full path: {$fullTempPath})");
        }

        $fullOriginalPath = Storage::disk('local')->path($originalPath);

        $moved = Storage::disk('local')->move($filePath, $originalPath);

        if (! $moved) {
            throw new \RuntimeException("Failed to move file from {$filePath} to {$originalPath}");
        }

        if (! Storage::disk('local')->exists($originalPath)) {
            throw new \RuntimeException("File move reported success but file not found at: {$originalPath} (full path: {$fullOriginalPath})");
        }

        if (! file_exists($fullOriginalPath)) {
            throw new \RuntimeException("File exists in Storage but not on filesystem at: {$fullOriginalPath}");
        }

        if (! Storage::disk('local')->exists($originalPath)) {
            throw new \RuntimeException("File disappeared before processing at: {$originalPath}");
        }

        $qrService = app(QrCodeService::class);
        $qrImage = $qrService->generate(config('app.url')."/patient/{$token}");

        $dischargeService = app(DischargePaperService::class);

        try {
            $dischargeService->addQrCode($originalPath, $qrPath, $qrImage);
        } catch (\RuntimeException $e) {
            Storage::disk('local')->delete($originalPath);
            throw $e;
        }

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
