<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VisitResource\Pages;
use App\Forms\Components\DiagnosisInput;
use App\Models\Doctor;
use App\Models\Encounter;
use App\Models\Patient;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                        Forms\Components\Textarea::make('clinical_examination')
                            ->label(__('app.labels.clinical_examination'))
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
                    ]),
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
}
