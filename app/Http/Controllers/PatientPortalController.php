<?php

namespace App\Http\Controllers;

use App\Models\DischargePaper;
use App\Models\Document;
use App\Models\Encounter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class PatientPortalController extends Controller
{
    public function showVerify(string $token)
    {
        $paper = $this->getDischargePaper($token);

        return view('patient-portal.verify', [
            'token' => $token,
            'patientName' => $paper->patient->full_name,
        ]);
    }

    public function verify(string $token, Request $request)
    {
        $paper = $this->getDischargePaper($token);

        $validated = $request->validate([
            'date_of_birth' => ['required', 'date'],
        ]);

        $matches = $paper->patient->date_of_birth->isSameDay($request->date('date_of_birth'));

        if (! $matches) {
            return back()->withErrors([
                'date_of_birth' => __('app.errors.dob_mismatch'),
            ])->withInput();
        }

        Session::put($this->sessionKey($token), true);

        return redirect()->route('patient.records', ['token' => $token]);
    }

    public function records(string $token)
    {
        $paper = $this->getDischargePaper($token);
        $this->ensureVerified($token);

        $patient = $paper->patient->load([
            'medicalInfo',
            'encounters' => fn ($query) => $query->orderByDesc('admission_date')->with(['documents', 'dischargePaper']),
        ]);

        return view('patient-portal.records', [
            'token' => $token,
            'patient' => $patient,
            'documents' => $patient->documents,
        ]);
    }

    public function downloadDocument(string $token, Document $document)
    {
        $paper = $this->getDischargePaper($token);
        $this->ensureVerified($token);

        abort_unless($document->patient_id === $paper->patient_id, 403);

        return Storage::download($document->file_path, $document->original_filename);
    }

    public function previewDocument(string $token, Document $document)
    {
        $paper = $this->getDischargePaper($token);
        $this->ensureVerified($token);

        abort_unless($document->patient_id === $paper->patient_id, 403);

        $previewableMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
        ];

        abort_unless(in_array($document->mime_type, $previewableMimes), 404);

        return response()->file(
            Storage::disk('local')->path($document->file_path),
            ['Content-Type' => $document->mime_type]
        );
    }

    public function downloadLabResults(string $token, Encounter $encounter): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->downloadEncounterFile($token, $encounter, 'lab_results_file_path', 'lab_results_original_filename', 'lab-results');
    }

    public function downloadOperativeWork(string $token, Encounter $encounter): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->downloadEncounterFile($token, $encounter, 'operative_work_file_path', 'operative_work_original_filename', 'operative-work');
    }

    public function downloadSurgicalNotes(string $token, Encounter $encounter): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return $this->downloadEncounterFile($token, $encounter, 'surgical_notes_file_path', 'surgical_notes_original_filename', 'surgical-notes');
    }

    public function previewEncounterFile(string $token, Encounter $encounter, string $fileType): \Illuminate\Http\Response
    {
        $paper = $this->getDischargePaper($token);
        $this->ensureVerified($token);

        abort_unless($encounter->patient_id === $paper->patient_id, 403);

        $pathField = match ($fileType) {
            'lab-results' => 'lab_results_file_path',
            'operative-work' => 'operative_work_file_path',
            'surgical-notes' => 'surgical_notes_file_path',
            default => abort(404),
        };

        abort_unless($encounter->$pathField, 404);
        abort_unless(Storage::disk('local')->exists($encounter->$pathField), 404);

        $mimeType = Storage::disk('local')->mimeType($encounter->$pathField);
        $previewableMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        abort_unless(in_array($mimeType, $previewableMimes), 404);

        return response()->file(
            Storage::disk('local')->path($encounter->$pathField),
            ['Content-Type' => $mimeType]
        );
    }

    public function downloadDischargePaper(string $token, Encounter $encounter): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        $paper = $this->getDischargePaper($token);
        $this->ensureVerified($token);

        abort_unless($encounter->patient_id === $paper->patient_id, 403);

        $dischargePaper = $encounter->dischargePaper;
        abort_unless($dischargePaper, 404);

        $path = Storage::disk('local')->path($dischargePaper->original_file_path);
        abort_unless(file_exists($path), 404);

        return response()->download($path, $dischargePaper->original_filename)->deleteFileAfterSend(false);
    }

    private function downloadEncounterFile(string $token, Encounter $encounter, string $pathField, string $nameField, string $fallbackName): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $paper = $this->getDischargePaper($token);
        $this->ensureVerified($token);

        abort_unless($encounter->patient_id === $paper->patient_id, 403);
        abort_unless($encounter->$pathField, 404);
        abort_unless(Storage::disk('local')->exists($encounter->$pathField), 404);

        return Storage::disk('local')->download(
            $encounter->$pathField,
            $encounter->$nameField ?? "{$fallbackName}.pdf"
        );
    }

    private function sessionKey(string $token): string
    {
        return "patient_portal_verified_{$token}";
    }

    private function ensureVerified(string $token): void
    {
        abort_unless(Session::get($this->sessionKey($token)), 403);
    }

    private function getDischargePaper(string $token): DischargePaper
    {
        return DischargePaper::with(['patient.documents', 'patient.medicalInfo', 'patient.encounters'])
            ->where('qr_token', $token)
            ->firstOrFail();
    }
}
