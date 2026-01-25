<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Documents';

    public static function getNavigationLabel(): string
    {
        return __('app.labels.documents');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.document');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.documents');
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

        return $user?->isAdmin() || $user?->isStaff();
    }

    public static function canEdit($record): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isStaff();
    }

    public static function canDelete($record): bool
    {
        $user = auth()->user();

        return $user?->isAdmin() || $user?->isStaff();
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->isAdmin() || auth()->user()?->isStaff();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('encounter_id')
                    ->label(__('app.labels.encounter'))
                    ->relationship('encounter', 'id')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('patient_id')
                    ->label(__('app.labels.patient'))
                    ->relationship('patient', 'last_name')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('type')
                    ->label(__('app.labels.document_type'))
                    ->options([
                        'diagnostic_image' => __('app.labels.diagnostic_image'),
                        'report' => __('app.labels.report'),
                        'other' => __('app.labels.other'),
                    ])
                    ->required(),
                Forms\Components\FileUpload::make('upload')
                    ->label(__('app.labels.file'))
                    ->required(fn (string $context) => $context === 'create')
                    ->disabled(fn (string $context) => $context === 'edit')
                    ->maxSize(20480)
                    ->disk('local')
                    ->directory(fn (Get $get) => "documents/{$get('patient_id')}/{$get('encounter_id')}")
                    ->storeFileNamesIn('original_filename')
                    ->visibility('private')
                    ->acceptedFileTypes([
                        'image/jpeg',
                        'image/png',
                        'image/gif',
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('patient.full_name')
                    ->label(__('app.labels.patient'))
                    ->sortable()
                    ->searchable(['patient.first_name', 'patient.last_name']),
                Tables\Columns\TextColumn::make('encounter.type')
                    ->label(__('app.labels.encounter'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'visit' ? __('app.labels.visit') : __('app.labels.hospitalization')),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('app.labels.document_type'))
                    ->badge()
                    ->formatStateUsing(function (string $state) {
                        return match ($state) {
                            'diagnostic_image' => __('app.labels.diagnostic_image'),
                            'report' => __('app.labels.report'),
                            default => __('app.labels.other'),
                        };
                    }),
                Tables\Columns\TextColumn::make('original_filename')
                    ->label(__('app.labels.filename'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('uploadedBy.name')
                    ->label(__('app.labels.uploaded_by'))
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
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('app.labels.document_type'))
                    ->options([
                        'diagnostic_image' => __('app.labels.diagnostic_image'),
                        'report' => __('app.labels.report'),
                        'other' => __('app.labels.other'),
                    ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label(__('app.actions.download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Document $record) => route('documents.download', $record))
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDocuments::route('/'),
            'create' => Pages\CreateDocument::route('/create'),
            'edit' => Pages\EditDocument::route('/{record}/edit'),
        ];
    }
}
