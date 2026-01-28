<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ArchivedHospitalizationResource\Pages;
use App\Models\Encounter;
use App\Models\Patient;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ArchivedHospitalizationResource extends Resource
{
    protected static ?string $model = Patient::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?int $navigationSort = 2;

    public static function getNavigationLabel(): string
    {
        return __('app.labels.archived_hospitalizations');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.patient');
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
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('encounters', fn (Builder $query) => $query->where('status', 'discharged'))
            ->withMax(['encounters as last_discharge_date' => fn ($q) => $q->where('status', 'discharged')], 'discharge_date')
            ->withCount(['encounters as discharged_encounters_count' => fn ($q) => $q->where('status', 'discharged')]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make(__('app.labels.patient_information'))
                    ->schema([
                        Infolists\Components\TextEntry::make('full_name')
                            ->label(__('app.labels.name')),
                        Infolists\Components\TextEntry::make('date_of_birth')
                            ->label(__('app.labels.date_of_birth'))
                            ->date(),
                        Infolists\Components\TextEntry::make('phone_number')
                            ->label(__('app.labels.phone')),
                        Infolists\Components\TextEntry::make('national_id')
                            ->label(__('app.labels.national_id')),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        Tables\Columns\TextColumn::make('full_name')
                            ->label(__('app.labels.patient'))
                            ->weight(FontWeight::Bold)
                            ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                            ->searchable(['first_name', 'last_name'])
                            ->sortable(query: fn (Builder $query, string $direction) => $query
                                ->orderBy('last_name', $direction)
                                ->orderBy('first_name', $direction)),
                        Tables\Columns\TextColumn::make('discharged_encounters_count')
                            ->label(__('app.labels.encounters'))
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn (int $state) => trans_choice('app.labels.encounter_count', $state, ['count' => $state]))
                            ->grow(false),
                    ]),
                    Split::make([
                        Tables\Columns\TextColumn::make('date_of_birth')
                            ->label(__('app.labels.date_of_birth'))
                            ->icon('heroicon-m-cake')
                            ->date('d M Y')
                            ->color('gray'),
                        Tables\Columns\TextColumn::make('age')
                            ->label(__('app.labels.age'))
                            ->formatStateUsing(fn (int $state) => trans_choice('app.labels.years_old', $state, ['age' => $state]))
                            ->color('gray'),
                    ]),
                    Split::make([
                        Tables\Columns\TextColumn::make('phone_number')
                            ->label(__('app.labels.phone'))
                            ->icon('heroicon-m-phone')
                            ->color('gray')
                            ->placeholder('—'),
                    ]),
                    Split::make([
                        Tables\Columns\TextColumn::make('last_discharge_date')
                            ->label(__('app.labels.last_discharge'))
                            ->icon('heroicon-m-arrow-right-on-rectangle')
                            ->dateTime('d M Y, H:i')
                            ->color('gray')
                            ->sortable(),
                    ]),
                ])->space(2),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 3,
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gender')
                    ->label(__('app.labels.gender'))
                    ->options([
                        'male' => __('app.gender.male'),
                        'female' => __('app.gender.female'),
                    ]),
                Tables\Filters\SelectFilter::make('doctor')
                    ->label(__('app.labels.doctor'))
                    ->options(fn () => Encounter::query()
                        ->where('status', 'discharged')
                        ->whereNotNull('doctor_name')
                        ->distinct()
                        ->pluck('doctor_name', 'doctor_name')
                        ->toArray())
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['value'],
                        fn (Builder $q) => $q->whereHas(
                            'encounters',
                            fn (Builder $eq) => $eq->where('status', 'discharged')->where('doctor_name', $data['value'])
                        )
                    )),
                Filter::make('discharge_date')
                    ->label(__('app.labels.discharge_date'))
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('app.labels.from')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('app.labels.until')),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $q) => $q->whereHas(
                                    'encounters',
                                    fn (Builder $eq) => $eq->where('status', 'discharged')->whereDate('discharge_date', '>=', $data['from'])
                                )
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $q) => $q->whereHas(
                                    'encounters',
                                    fn (Builder $eq) => $eq->where('status', 'discharged')->whereDate('discharge_date', '<=', $data['until'])
                                )
                            );
                    }),
            ])
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label(__('app.labels.filter'))
            )
            ->emptyStateHeading(__('app.empty.no_archived_hospitalizations'))
            ->defaultSort('last_discharge_date', 'desc')
            ->actions([
                Tables\Actions\Action::make('readmit')
                    ->label(__('app.actions.readmit'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->url(fn (Patient $record) => HospitalizationResource::getUrl('create', [
                        'patient_id' => $record->id,
                    ])),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\ArchivedHospitalizationResource\RelationManagers\EncountersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArchivedHospitalizations::route('/'),
            'view' => Pages\ViewArchivedHospitalization::route('/{record}'),
        ];
    }
}
