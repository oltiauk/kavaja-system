<?php

namespace App\Http\Controllers;

use App\Models\DischargePaper;
use App\Models\Document;
use App\Models\Encounter;
use Illuminate\Support\Facades\Storage;

class DocumentDownloadController extends Controller
{
    public function document(Document $document)
    {
        $this->authorize('view', $document);

        return Storage::disk('local')->download($document->file_path, $document->original_filename);
    }

    public function dischargeOriginal(DischargePaper $dischargePaper)
    {
        $this->authorize('view', $dischargePaper);

        $path = Storage::disk('local')->path($dischargePaper->original_file_path);

        if (! file_exists($path)) {
            abort(404);
        }

        return response()
            ->download($path, $dischargePaper->original_filename)
            ->deleteFileAfterSend(false);
    }

    public function dischargeWithQr(DischargePaper $dischargePaper)
    {
        $this->authorize('view', $dischargePaper);

        $path = Storage::disk('local')->path($dischargePaper->qr_file_path);

        if (! file_exists($path)) {
            abort(404);
        }

        $extension = pathinfo($dischargePaper->qr_file_path, PATHINFO_EXTENSION);
        $baseName = pathinfo($dischargePaper->original_filename, PATHINFO_FILENAME);
        $filename = "{$baseName}-with-qr.{$extension}";

        return response()
            ->download($path, $filename)
            ->deleteFileAfterSend(false);
    }

    public function preview(Document $document)
    {
        $this->authorize('view', $document);

        if (! str_starts_with($document->mime_type, 'image/')) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($document->file_path)) {
            abort(404);
        }

        return response()->file(
            Storage::disk('local')->path($document->file_path),
            ['Content-Type' => $document->mime_type]
        );
    }

    public function surgicalNotes(Encounter $encounter)
    {
        $this->authorize('view', $encounter);

        if (! $encounter->surgical_notes_file_path) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($encounter->surgical_notes_file_path)) {
            abort(404);
        }

        return Storage::disk('local')->download(
            $encounter->surgical_notes_file_path,
            $encounter->surgical_notes_original_filename ?? 'surgical-notes.pdf'
        );
    }
}
