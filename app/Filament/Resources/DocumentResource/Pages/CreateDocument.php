<?php

namespace App\Filament\Resources\DocumentResource\Pages;

use App\Filament\Resources\DocumentResource;
use App\Models\Encounter;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CreateDocument extends CreateRecord
{
    protected static string $resource = DocumentResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $encounter = Encounter::hospitalizations()
            ->active()
            ->where('patient_id', $data['patient_id'])
            ->latest('admission_date')
            ->first();

        if (! $encounter) {
            throw ValidationException::withMessages([
                'patient_id' => __('app.errors.no_active_hospitalization'),
            ]);
        }

        $data['encounter_id'] = $encounter->id;

        $path = $data['upload'];
        $data['file_path'] = $path;
        $data['stored_filename'] = basename($path);
        $data['mime_type'] = Storage::disk('local')->mimeType($path);
        $data['file_size'] = Storage::disk('local')->size($path);
        $data['uploaded_by'] = Auth::id();
        $data['type'] = $this->resolveDocumentType($data['mime_type']);

        unset($data['upload']);

        return $data;
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
