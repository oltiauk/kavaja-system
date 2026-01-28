<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DocumentResource\Pages;
use App\Models\Document;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\ViewColumn;
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
        return false;
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
                Forms\Components\Select::make('patient_id')
                    ->label(__('app.labels.patient'))
                    ->relationship(
                        name: 'patient',
                        titleAttribute: 'last_name',
                        modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query) => $query->orderBy('created_at', 'desc')
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->full_name)
                    ->searchable()
                    ->preload()
                    ->optionsLimit(5)
                    ->required(),
                Forms\Components\FileUpload::make('upload')
                    ->label(__('app.labels.file'))
                    ->required(fn (string $context) => $context === 'create')
                    ->disabled(fn (string $context) => $context === 'edit')
                    ->maxSize(20480)
                    ->disk('local')
                    ->directory(fn (Get $get) => "documents/{$get('patient_id')}")
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
            ->modifyQueryUsing(fn ($query) => $query->with('uploadedBy'))
            ->columns([
                Grid::make()
                    ->schema([
                        ViewColumn::make('document')
                            ->view('filament.tables.columns.document-card'),
                    ]),
            ])
            ->contentGrid([
                'sm' => 2,
                'md' => 3,
                'lg' => 4,
                'xl' => 5,
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
