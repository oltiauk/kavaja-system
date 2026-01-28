<?php

namespace App\Filament\Resources\EncounterResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $recordTitleAttribute = 'original_filename';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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
                    ->directory(fn (Get $get, $livewire) => "documents/{$livewire->getOwnerRecord()->patient_id}/{$livewire->getOwnerRecord()->id}")
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
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
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
                'default' => 2,
                'sm' => 3,
                'md' => 4,
                'lg' => 5,
            ])
            ->defaultSort('created_at', 'desc')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('app.actions.upload_document'))
                    ->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isStaff())
                    ->mutateFormDataUsing(function (array $data): array {
                        $encounter = $this->getOwnerRecord();
                        $data['encounter_id'] = $encounter->id;
                        $data['patient_id'] = $encounter->patient_id;

                        $path = $data['upload'];
                        $data['file_path'] = $path;
                        $data['stored_filename'] = basename($path);
                        $data['mime_type'] = Storage::disk('local')->mimeType($path);
                        $data['file_size'] = Storage::disk('local')->size($path);
                        $data['uploaded_by'] = Auth::id();

                        unset($data['upload']);

                        return $data;
                    }),
            ]);
    }
}
