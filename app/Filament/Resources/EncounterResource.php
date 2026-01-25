<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EncounterResource\Pages;
use App\Models\Encounter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EncounterResource extends Resource
{
    protected static ?string $model = Encounter::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Encounters';

    public static function getNavigationLabel(): string
    {
        return __('app.labels.encounters');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.encounter');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.encounters');
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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('patient_id')
                    ->label(__('app.labels.patient'))
                    ->relationship('patient', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label(__('app.labels.encounter_type'))
                    ->options([
                        'visit' => __('app.labels.visit'),
                        'hospitalization' => __('app.labels.hospitalization'),
                    ])
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label(__('app.labels.status'))
                    ->options([
                        'active' => __('app.labels.active'),
                        'discharged' => __('app.labels.discharged'),
                    ])
                    ->default('active')
                    ->required(),
                Forms\Components\Textarea::make('main_complaint')
                    ->label(__('app.labels.main_complaint'))
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('doctor_name')
                    ->label(__('app.labels.doctor'))
                    ->required(),
                Forms\Components\Textarea::make('diagnosis')
                    ->label(__('app.labels.diagnosis'))
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('treatment')
                    ->label(__('app.labels.treatment'))
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('surgical_notes')
                    ->label(__('app.labels.surgical_notes_hidden'))
                    ->columnSpanFull(),
                Forms\Components\Section::make(__('app.labels.medical_information'))
                    ->statePath('medical_info')
                    ->visible(fn (Get $get) => $get('type') === 'hospitalization' && (auth()->user()?->isAdmin() || auth()->user()?->isStaff()))
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
                            ]),
                        Forms\Components\TextInput::make('height_cm')
                            ->label(__('app.labels.height_cm'))
                            ->numeric(),
                        Forms\Components\TextInput::make('weight_kg')
                            ->label(__('app.labels.weight_kg'))
                            ->numeric(),
                        Forms\Components\Textarea::make('allergies')
                            ->label(__('app.labels.allergies'))
                            ->columnSpanFull(),
                        Forms\Components\Select::make('smoking_status')
                            ->label(__('app.labels.smoking'))
                            ->options([
                                'smoker' => __('app.smoking_status.smoker'),
                                'non_smoker' => __('app.smoking_status.non_smoker'),
                                'former_smoker' => __('app.smoking_status.former_smoker'),
                            ]),
                        Forms\Components\TextInput::make('alcohol_use')
                            ->label(__('app.labels.alcohol')),
                        Forms\Components\Textarea::make('drug_use_history')
                            ->label(__('app.labels.drug_history'))
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('pacemaker_implants')
                            ->label(__('app.labels.pacemaker_implants'))
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('anesthesia_reactions')
                            ->label(__('app.labels.anesthesia_reactions'))
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('current_medications')
                            ->label(__('app.labels.current_medications'))
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\DateTimePicker::make('admission_date')
                    ->label(__('app.labels.admission_date'))
                    ->required()
                    ->default(now()),
                Forms\Components\DateTimePicker::make('discharge_date')
                    ->label(__('app.labels.discharge_date')),
                Forms\Components\Toggle::make('medical_info_complete')
                    ->label(__('app.labels.medical_info_complete'))
                    ->visible(fn (Get $get) => $get('type') === 'hospitalization' && (auth()->user()?->isAdmin() || auth()->user()?->isStaff()))
                    ->default(false),
                Forms\Components\Section::make(__('app.labels.discharge_paper'))
                    ->visible(fn (Get $get) => $get('type') === 'hospitalization')
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
                    ->searchable(['patient.first_name', 'patient.last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('app.labels.encounter_type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'visit' ? __('app.labels.visit') : __('app.labels.hospitalization')),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'active' ? __('app.labels.active') : __('app.labels.discharged')),
                Tables\Columns\TextColumn::make('doctor_name')
                    ->label(__('app.labels.doctor'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('main_complaint')
                    ->label(__('app.labels.main_complaint'))
                    ->limit(30),
                Tables\Columns\TextColumn::make('admission_date')
                    ->label(__('app.labels.admission_date'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discharge_date')
                    ->label(__('app.labels.discharge_date'))
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\IconColumn::make('medical_info_complete')
                    ->label(__('app.labels.medical_info_complete'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('app.labels.encounter_type'))
                    ->options([
                        'visit' => __('app.labels.visit'),
                        'hospitalization' => __('app.labels.hospitalization'),
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options([
                        'active' => __('app.labels.active'),
                        'discharged' => __('app.labels.discharged'),
                    ]),
            ])
            ->emptyStateHeading(__('app.empty.no_encounters'))
            ->defaultSort('admission_date', 'desc')
            ->actions([
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
        return [
            \App\Filament\Resources\EncounterResource\RelationManagers\DocumentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEncounters::route('/'),
            'create' => Pages\CreateEncounter::route('/create'),
            'edit' => Pages\EditEncounter::route('/{record}/edit'),
        ];
    }
}
