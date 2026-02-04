<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HospitalizationResource\Pages;
use App\Filament\Resources\HospitalizationResource\RelationManagers\DocumentsRelationManager;
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
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class HospitalizationResource extends Resource
{
    protected static ?string $model = Encounter::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office';

    protected static ?string $navigationLabel = 'Hospitalizations';

    public static function getNavigationLabel(): string
    {
        return __('app.labels.hospitalizations');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.hospitalization');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.hospitalizations');
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
            ->with(['patient.medicalInfo'])
            ->where('type', 'hospitalization')
            ->where('status', 'active');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('app.labels.patient_information'))
                    ->collapsible()
                    ->collapsed(fn (?Encounter $record): bool => (bool) $record)
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
                Forms\Components\Section::make(__('app.labels.clinical_details'))
                    ->collapsible()
                    ->collapsed(fn (?Encounter $record): bool => (bool) $record)
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('doctor_choice')
                                    ->label(__('app.labels.doctor'))
                                    ->options(fn () => static::doctorOptions())
                                    ->searchable()
                                    ->preload()
                                    ->optionsLimit(50)
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
                                    ->visible(fn (Get $get) => $get('doctor_choice') === 'other'),
                            ]),
                        Forms\Components\Textarea::make('main_complaint')
                            ->label(__('app.labels.main_complaint'))
                            ->required()
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        DiagnosisInput::make('diagnosis')
                            ->label(__('app.labels.diagnosis'))
                            ->required()
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('treatment')
                            ->label(__('app.labels.treatment'))
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make(__('app.labels.room_selection'))
                    ->collapsible()
                    ->collapsed(fn (?Encounter $record): bool => (bool) $record)
                    ->schema([
                        Forms\Components\ViewField::make('room_number')
                            ->hiddenLabel()
                            ->view('filament.forms.room-selector'),
                    ]),
                Forms\Components\Section::make(__('app.labels.file_uploads'))
                    ->collapsible()
                    ->collapsed(fn (?Encounter $record): bool => (bool) $record)
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isStaff())
                    ->schema([
                        Forms\Components\FileUpload::make('lab_results')
                            ->label(__('app.labels.lab_results').' — '.__('app.labels.lab_results_upload'))
                            ->disk('local')
                            ->directory(fn (?Encounter $record) => $record
                                ? "lab-results/{$record->patient_id}/{$record->id}"
                                : 'tmp/lab-results')
                            ->storeFileNamesIn('lab_results_original_filename')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/jpeg',
                                'image/png',
                            ])
                            ->maxSize(20480)
                            ->visibility('private')
                            ->openable()
                            ->downloadable()
                            ->previewable()
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('operative_work')
                            ->label(__('app.labels.operative_procedure').' — '.__('app.labels.operative_work_upload'))
                            ->disk('local')
                            ->directory(fn (?Encounter $record) => $record
                                ? "operative-work/{$record->patient_id}/{$record->id}"
                                : 'tmp/operative-work')
                            ->storeFileNamesIn('operative_work_original_filename')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'application/msword',
                                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                'image/jpeg',
                                'image/png',
                            ])
                            ->maxSize(20480)
                            ->visibility('private')
                            ->openable()
                            ->downloadable()
                            ->previewable()
                            ->live()
                            ->columnSpanFull(),
                        Forms\Components\FileUpload::make('surgical_notes')
                            ->label(__('app.labels.imaging_rtg').' — '.__('app.labels.imaging_rtg_upload'))
                            ->disk('local')
                            ->directory(fn (?Encounter $record) => $record
                                ? "surgical-notes/{$record->patient_id}/{$record->id}"
                                : 'tmp/surgical-notes')
                            ->storeFileNamesIn('surgical_notes_original_filename')
                            ->acceptedFileTypes([
                                'application/pdf',
                                'image/jpeg',
                                'image/png',
                                'video/mp4',
                                'video/quicktime',
                                'video/x-msvideo',
                                'video/webm',
                            ])
                            ->maxSize(102400)
                            ->visibility('private')
                            ->openable()
                            ->downloadable()
                            ->previewable()
                            ->live()
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make(__('app.labels.dates_status'))
                    ->collapsible()
                    ->collapsed(fn (?Encounter $record): bool => (bool) $record)
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label(__('app.labels.status'))
                            ->options([
                                'active' => __('app.labels.active'),
                                'discharged' => __('app.labels.discharged'),
                            ])
                            ->default('active')
                            ->required()
                            ->live(),
                        Forms\Components\DateTimePicker::make('admission_date')
                            ->label(__('app.labels.admission_date'))
                            ->required()
                            ->default(now())
                            ->live(onBlur: true),
                        Forms\Components\DateTimePicker::make('discharge_date')
                            ->label(__('app.labels.discharge_date'))
                            ->live(onBlur: true),
                        Forms\Components\Toggle::make('medical_info_complete')
                            ->label(__('app.labels.medical_info_complete'))
                            ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isStaff())
                            ->default(false)
                            ->live()
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make(__('app.labels.medical_information'))
                    ->collapsible()
                    ->collapsed(fn (?Encounter $record): bool => (bool) $record)
                    ->statePath('medical_info')
                    ->visible(fn (Get $get) => filled($get('patient_id')) && (auth()->user()?->isAdmin() || auth()->user()?->isStaff()))
                    ->schema([
                        Forms\Components\Select::make('blood_type')
                            ->label(__('app.labels.blood_type'))
                            ->options([
                                'A+' => 'A+',
                                'A-' => 'A-',
                                'B+' => 'B+',
                                'B-' => 'B-',
                                'AB+' => 'AB+',
                                'AB-' => 'AB-',
                                'O+' => 'O+',
                                'O-' => 'O-',
                                'unknown' => __('app.labels.unknown'),
                            ])
                            ->live(),
                        Forms\Components\TextInput::make('height_cm')
                            ->label(__('app.labels.height_cm'))
                            ->numeric()
                            ->live(onBlur: true),
                        Forms\Components\TextInput::make('weight_kg')
                            ->label(__('app.labels.weight_kg'))
                            ->numeric()
                            ->live(onBlur: true),
                        Forms\Components\Toggle::make('has_allergies')
                            ->label(__('app.labels.has_allergies'))
                            ->live()
                            ->inline(false)
                            ->default(false)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('allergies')
                            ->label(__('app.labels.allergies'))
                            ->visible(fn (Get $get) => $get('has_allergies'))
                            ->required(fn (Get $get) => $get('has_allergies'))
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        Forms\Components\Select::make('smoking_status')
                            ->label(__('app.labels.smoking'))
                            ->options([
                                'smoker' => __('app.smoking_status.smoker'),
                                'non_smoker' => __('app.smoking_status.non_smoker'),
                                'former_smoker' => __('app.smoking_status.former_smoker'),
                            ])
                            ->live(),
                        Forms\Components\TextInput::make('alcohol_use')
                            ->label(__('app.labels.alcohol'))
                            ->live(onBlur: true),
                        Forms\Components\Textarea::make('drug_use_history')
                            ->label(__('app.labels.drug_history'))
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('pacemaker_implants')
                            ->label(__('app.labels.pacemaker_implants'))
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('anesthesia_reactions')
                            ->label(__('app.labels.anesthesia_reactions'))
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('current_medications')
                            ->label(__('app.labels.current_medications'))
                            ->live(onBlur: true)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make(__('app.labels.discharge_paper'))
                    ->collapsible()
                    ->collapsed(fn (?Encounter $record): bool => (bool) $record)
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
                Stack::make([
                    // Header: Patient name + Room + Status badge
                    Split::make([
                        Tables\Columns\TextColumn::make('patient.full_name')
                            ->label(__('app.labels.patient'))
                            ->weight(FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                            ->searchable(['first_name', 'last_name'])
                            ->sortable(query: fn (Builder $query, string $direction) => $query
                                ->join('patients', 'encounters.patient_id', '=', 'patients.id')
                                ->orderBy('patients.last_name', $direction)
                                ->orderBy('patients.first_name', $direction)
                                ->select('encounters.*')),
                        Tables\Columns\TextColumn::make('room_number')
                            ->label(__('app.labels.room_number'))
                            ->badge()
                            ->color('info')
                            ->icon('heroicon-m-home')
                            ->grow(false)
                            ->formatStateUsing(fn (?string $state): ?string => static::formatRoomNumber($state))
                            ->placeholder(null),
                        Tables\Columns\TextColumn::make('status')
                            ->label(__('app.labels.status'))
                            ->badge()
                            ->color(fn (string $state) => $state === 'active' ? 'success' : 'gray')
                            ->formatStateUsing(fn (string $state) => $state === 'active' ? __('app.labels.active') : __('app.labels.discharged'))
                            ->grow(false),
                    ]),

                    // Diagnosis + Allergies (right aligned)
                    Split::make([
                        Tables\Columns\TextColumn::make('diagnosis')
                            ->label(__('app.labels.diagnosis'))
                            ->icon('heroicon-m-clipboard-document-list')
                            ->limit(80)
                            ->color('primary')
                            ->placeholder('—'),
                        Tables\Columns\TextColumn::make('patient.medicalInfo.allergies')
                            ->icon('heroicon-m-exclamation-triangle')
                            ->color('danger')
                            ->weight(FontWeight::SemiBold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                            ->limit(60)
                            ->formatStateUsing(fn (?string $state) => $state ? "! {$state}" : null)
                            ->grow(false)
                            ->placeholder(''),
                    ]),

                    // Doctor
                    Split::make([
                        Tables\Columns\TextColumn::make('doctor_name')
                            ->label(__('app.labels.doctor'))
                            ->icon('heroicon-m-user')
                            ->searchable(),
                    ]),

                    // Dates
                    Split::make([
                        Tables\Columns\TextColumn::make('admission_date')
                            ->label(__('app.labels.admission_date'))
                            ->icon('heroicon-m-calendar')
                            ->dateTime('d M Y, H:i')
                            ->sortable(),
                        Tables\Columns\TextColumn::make('discharge_date')
                            ->label(__('app.labels.discharge_date'))
                            ->icon('heroicon-m-arrow-right-on-rectangle')
                            ->dateTime('d M Y, H:i')
                            ->sortable()
                            ->placeholder('—'),
                    ]),
                ])->space(2),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([])
            ->emptyStateHeading(__('app.empty.no_hospitalizations'))
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
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHospitalizations::route('/'),
            'create' => Pages\CreateHospitalization::route('/create'),
            'edit' => Pages\EditHospitalization::route('/{record}/edit'),
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

    /**
     * Format room number for display.
     *
     * Format options:
     * - 'compact': K1: 4/2 (Floor 1, Room 4, Bed 2)
     * - 'dash': 1K - 4/2 (Floor 1, Room 4, Bed 2)
     * - 'parentheses': 4/2 (K1) (Room 4, Bed 2, Floor 1)
     * - 'minimal': 1-4/2 (Floor 1, Room 4, Bed 2)
     * - 'space': 1K 4/2 (Floor 1, Room 4, Bed 2)
     * - 'full': Floor 1: 4/2 (Floor 1, Room 4, Bed 2)
     */
    protected static function formatRoomNumber(?string $state, string $format = 'compact'): ?string
    {
        if (! $state) {
            return null;
        }

        // Handle TD (Treatment Department) case
        if ($state === 'TD') {
            return 'TD';
        }

        $parts = explode('-', $state);
        $floor = $parts[0] ?? null;
        $room = $parts[1] ?? null;
        $bed = $parts[2] ?? null;

        if (! $floor || ! $room) {
            return $state;
        }

        // Build room/bed part: 4/2 format
        $roomBed = $room;
        if ($bed) {
            $roomBed .= '/'.$bed;
        }

        // Apply selected format
        return match ($format) {
            'compact' => "K{$floor}: {$roomBed}",           // K1: 4/2
            'dash' => "{$floor}K - {$roomBed}",             // 1K - 4/2
            'parentheses' => "{$roomBed} (K{$floor})",      // 4/2 (K1)
            'minimal' => "{$floor}-{$roomBed}",             // 1-4/2
            'space' => "{$floor}K {$roomBed}",              // 1K 4/2
            'full' => "Floor {$floor}: {$roomBed}",         // Floor 1: 4/2
            default => "K{$floor}: {$roomBed}",            // Default to compact
        };
    }

    private static function storeDischargePaper(Encounter $encounter, array $data, bool $replaceExisting): void
    {
        $encounter->loadMissing(['patient', 'dischargePaper']);
        $patient = $encounter->patient;

        // Extract file path from array structure (Filament FileUpload may return array with UUID keys)
        $fileData = $data['file'];
        if (is_array($fileData) && ! empty($fileData)) {
            // If first element is an array/object, extract the file path from it
            if (is_array($fileData[0] ?? null)) {
                $filePath = array_values($fileData[0])[0] ?? null;
            } else {
                // If it's a simple array, get the first element
                $filePath = reset($fileData);
            }
        } else {
            $filePath = $fileData;
        }

        if (empty($filePath) || ! is_string($filePath)) {
            throw new \RuntimeException('Invalid file data provided. Expected file path string or array with file path.');
        }

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

        // Verify file exists before moving
        if (! Storage::disk('local')->exists($filePath)) {
            $fullTempPath = Storage::disk('local')->path($filePath);
            throw new \RuntimeException("Uploaded file not found at: {$filePath} (full path: {$fullTempPath})");
        }

        // Get full paths for debugging
        $fullTempPath = Storage::disk('local')->path($filePath);
        $fullOriginalPath = Storage::disk('local')->path($originalPath);

        // Move the file
        $moved = Storage::disk('local')->move($filePath, $originalPath);

        if (! $moved) {
            throw new \RuntimeException("Failed to move file from {$filePath} to {$originalPath}");
        }

        // Verify file was moved successfully using both Storage and filesystem
        if (! Storage::disk('local')->exists($originalPath)) {
            throw new \RuntimeException("File move reported success but file not found at: {$originalPath} (full path: {$fullOriginalPath})");
        }

        if (! file_exists($fullOriginalPath)) {
            throw new \RuntimeException("File exists in Storage but not on filesystem at: {$fullOriginalPath}");
        }

        // Double-check file exists right before processing
        if (! Storage::disk('local')->exists($originalPath)) {
            throw new \RuntimeException("File disappeared before processing at: {$originalPath}");
        }

        $qrService = app(QrCodeService::class);
        $qrImage = $qrService->generate(config('app.url')."/patient/{$token}");

        $dischargeService = app(DischargePaperService::class);

        try {
            $dischargeService->addQrCode($originalPath, $qrPath, $qrImage);
        } catch (\RuntimeException $e) {
            // Clean up the moved file if QR code addition fails
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
