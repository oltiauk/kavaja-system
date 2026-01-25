<?php

namespace App\Http\Controllers;

use App\Models\DischargePaper;
use App\Models\Document;
use Illuminate\Support\Facades\Storage;

class DocumentDownloadController extends Controller
{
    public function document(Document $document)
    {
        $this->authorize('view', $document);

        return Storage::download($document->file_path, $document->original_filename);
    }

    public function dischargeOriginal(DischargePaper $dischargePaper)
    {
        $this->authorize('view', $dischargePaper);

        return Storage::download($dischargePaper->original_file_path, $dischargePaper->original_filename);
    }

    public function dischargeWithQr(DischargePaper $dischargePaper)
    {
        $this->authorize('view', $dischargePaper);

        $extension = pathinfo($dischargePaper->qr_file_path, PATHINFO_EXTENSION);
        $baseName = pathinfo($dischargePaper->original_filename, PATHINFO_FILENAME);
        $filename = "{$baseName}-with-qr.{$extension}";

        return Storage::download($dischargePaper->qr_file_path, $filename);
    }
}
