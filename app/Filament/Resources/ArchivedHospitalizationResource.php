<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArchivedHospitalizationResource\Pages;
use App\Models\Encounter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArchivedHospitalizationResource extends Resource
{
    protected static ?string $model = Encounter::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('app.labels.archived_hospitalizations');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.archived_hospitalization');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.archived_hospitalizations');
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
        return false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('type', 'hospitalization')
            ->where('status', 'discharged');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('app.labels.patient_information'))
                    ->schema([
                        Forms\Components\Placeholder::make('patient_name')
                            ->label(__('app.labels.patient'))
                            ->content(fn (?Encounter $record) => $record?->patient?->full_name ?? '—'),
                    ]),
                Forms\Components\Textarea::make('main_complaint')
                    ->label(__('app.labels.main_complaint'))
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('diagnosis')
                    ->label(__('app.labels.diagnosis'))
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('treatment')
                    ->label(__('app.labels.treatment'))
                    ->disabled()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('doctor_name')
                    ->label(__('app.labels.doctor'))
                    ->disabled(),
                Forms\Components\DateTimePicker::make('admission_date')
                    ->label(__('app.labels.admission_date'))
                    ->disabled(),
                Forms\Components\DateTimePicker::make('discharge_date')
                    ->label(__('app.labels.discharge_date'))
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        Tables\Columns\TextColumn::make('patient.full_name')
                            ->label(__('app.labels.patient'))
                            ->weight(FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                            ->searchable(['first_name', 'last_name'])
                            ->sortable(),
                        Tables\Columns\TextColumn::make('status')
                            ->label(__('app.labels.status'))
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn () => __('app.labels.discharged'))
                            ->grow(false),
                    ]),
                    Split::make([
                        Tables\Columns\TextColumn::make('main_complaint')
                            ->label(__('app.labels.main_complaint'))
                            ->limit(60)
                            ->color('gray'),
                    ]),
                    Split::make([
                        Tables\Columns\TextColumn::make('doctor_name')
                            ->label(__('app.labels.doctor'))
                            ->icon('heroicon-m-user')
                            ->searchable(),
                    ]),
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
                            ->sortable(),
                    ]),
                ])->space(2),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([])
            ->emptyStateHeading(__('app.empty.no_archived_hospitalizations'))
            ->defaultSort('discharge_date', 'desc')
            ->actions([
                Tables\Actions\Action::make('readmit')
                    ->label(__('app.actions.readmit'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->url(fn (Encounter $record) => HospitalizationResource::getUrl('create', [
                        'patient_id' => $record->patient_id,
                    ])),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArchivedHospitalizations::route('/'),
            'view' => Pages\ViewArchivedHospitalization::route('/{record}'),
        ];
    }
}
