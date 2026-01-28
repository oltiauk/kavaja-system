<?php

namespace App\Filament\Resources\HospitalizationResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
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
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('uploadedBy'))
            ->columns([
                ViewColumn::make('document')
                    ->view('filament.tables.columns.document-card'),
            ])
            ->contentGrid([
                'sm' => 2,
                'md' => 3,
                'lg' => 4,
                'xl' => 5,
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
                        $data['type'] = $this->resolveDocumentType($data['mime_type']);

                        unset($data['upload']);

                        return $data;
                    }),
            ]);
    }

    private function resolveDocumentType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'diagnostic_image';
        }

        if (in_array($mimeType, [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ], true)) {
            return 'report';
        }

        return 'other';
    }
}
