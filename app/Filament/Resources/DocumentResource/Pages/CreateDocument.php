<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\Encounter;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $encounter = Encounter::findOrFail($data['encounter_id']);
        $data['patient_id'] = $encounter->patient_id;

        $path = $data['upload'];
        $data['file_path'] = $path;
        $data['stored_filename'] = basename($path);
        $data['mime_type'] = Storage::disk('local')->mimeType($path);
        $data['file_size'] = Storage::disk('local')->size($path);
        $data['uploaded_by'] = Auth::id();

        unset($data['upload']);

        return $data;
    }
}
