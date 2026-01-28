<?php

namespace App\Filament\Resources\ArchivedHospitalizationResource\RelationManagers;

use App\Filament\Resources\HospitalizationResource;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EncountersRelationManager extends RelationManager
{
    protected static string $relationship = 'encounters';

    public static function getTitle($ownerRecord, string $pageClass): string
    {
        return __('app.labels.encounter_history');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'discharged')->orderBy('discharge_date', 'desc'))
            ->columns([
                Stack::make([
                    Split::make([
                        Tables\Columns\TextColumn::make('type')
                            ->label(__('app.labels.type'))
                            ->badge()
                            ->color(fn (string $state) => $state === 'hospitalization' ? 'warning' : 'info')
                            ->formatStateUsing(fn (string $state) => $state === 'hospitalization' ? __('app.labels.hospitalization') : __('app.labels.visit'))
                            ->grow(false),
                        Tables\Columns\TextColumn::make('status')
                            ->label(__('app.labels.status'))
                            ->badge()
                            ->color('gray')
                            ->formatStateUsing(fn () => __('app.labels.discharged'))
                            ->grow(false),
                    ]),
                    Split::make([
                        Tables\Columns\TextColumn::make('diagnosis')
                            ->label(__('app.labels.diagnosis'))
                            ->icon('heroicon-m-clipboard-document-list')
                            ->limit(80)
                            ->color('primary')
                            ->placeholder('—'),
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
                            ->icon('heroicon-m-user'),
                    ]),
                    Split::make([
                        Tables\Columns\TextColumn::make('admission_date')
                            ->label(__('app.labels.admission_date'))
                            ->icon('heroicon-m-calendar')
                            ->dateTime('d M Y, H:i'),
                        Tables\Columns\TextColumn::make('discharge_date')
                            ->label(__('app.labels.discharge_date'))
                            ->icon('heroicon-m-arrow-right-on-rectangle')
                            ->dateTime('d M Y, H:i'),
                    ]),
                ])->space(2),
            ])
            ->contentGrid([
                'md' => 2,
                'xl' => 2,
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('app.labels.type'))
                    ->options([
                        'hospitalization' => __('app.labels.hospitalization'),
                        'visit' => __('app.labels.visit'),
                    ]),
                Tables\Filters\SelectFilter::make('doctor_name')
                    ->label(__('app.labels.doctor'))
                    ->searchable()
                    ->preload(),
                Filter::make('discharge_date')
                    ->label(__('app.labels.discharge_date'))
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label(__('app.labels.from')),
                        Forms\Components\DatePicker::make('until')
                            ->label(__('app.labels.until')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['from'], fn (Builder $q) => $q->whereDate('discharge_date', '>=', $data['from']))
                        ->when($data['until'], fn (Builder $q) => $q->whereDate('discharge_date', '<=', $data['until']))),
            ])
            ->filtersTriggerAction(
                fn (Tables\Actions\Action $action) => $action
                    ->button()
                    ->label(__('app.labels.filter'))
            )
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('readmit')
                    ->label(__('app.actions.readmit'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('success')
                    ->url(fn ($record) => HospitalizationResource::getUrl('create', [
                        'patient_id' => $record->patient_id,
                    ])),
            ])
            ->bulkActions([])
            ->emptyStateHeading(__('app.empty.no_encounters'));
    }
}
