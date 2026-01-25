<?php

namespace App\Http\Controllers;

use App\Models\DischargePaper;
use App\Models\Document;
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
            'encounters' => fn ($query) => $query->orderByDesc('admission_date')->with('documents'),
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
