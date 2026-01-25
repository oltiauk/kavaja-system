<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PatientResource\Pages;
use App\Models\Patient;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PatientResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Patients';

    public static function getNavigationLabel(): string
    {
        return __('app.labels.patients');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.patient');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.patients');
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
        return auth()->user()?->canRegisterPatients() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->canRegisterPatients() ?? false;
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
                Forms\Components\TextInput::make('first_name')
                    ->label(__('app.labels.first_name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->label(__('app.labels.last_name'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\DatePicker::make('date_of_birth')
                    ->label(__('app.labels.date_of_birth'))
                    ->required(),
                Forms\Components\Select::make('gender')
                    ->label(__('app.labels.gender'))
                    ->options([
                        'male' => __('app.gender.male'),
                        'female' => __('app.gender.female'),
                        'other' => __('app.gender.other'),
                    ])
                    ->required(),
                Forms\Components\TextInput::make('phone_number')
                    ->label(__('app.labels.phone'))
                    ->tel()
                    ->maxLength(50),
                Forms\Components\TextInput::make('national_id')
                    ->label(__('app.labels.national_id'))
                    ->maxLength(100),
                Forms\Components\TextInput::make('residency')
                    ->label(__('app.labels.residency'))
                    ->maxLength(255),
                Forms\Components\Fieldset::make(__('app.labels.emergency_contact'))
                    ->schema([
                        Forms\Components\TextInput::make('emergency_contact_name')
                            ->label(__('app.labels.emergency_contact_name'))
                            ->maxLength(255),
                        Forms\Components\TextInput::make('emergency_contact_phone')
                            ->label(__('app.labels.emergency_contact_phone'))
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('emergency_contact_relationship')
                            ->label(__('app.labels.emergency_contact_relationship'))
                            ->maxLength(100),
                    ]),
                Forms\Components\TextInput::make('health_insurance_number')
                    ->label(__('app.labels.insurance'))
                    ->maxLength(100),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('app.labels.name'))
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->label(__('app.labels.date_of_birth'))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone_number')
                    ->label(__('app.labels.phone'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('national_id')
                    ->label(__('app.labels.national_id'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('residency')
                    ->label(__('app.labels.residency'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->emptyStateHeading(__('app.empty.no_patients'))
            ->filters([
            ])
            ->defaultSort('last_name')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPatients::route('/'),
            'create' => Pages\CreatePatient::route('/create'),
            'edit' => Pages\EditPatient::route('/{record}/edit'),
        ];
    }
}
