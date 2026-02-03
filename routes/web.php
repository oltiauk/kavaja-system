<?php

use App\Http\Controllers\Api\DiagnosisController;
use App\Http\Controllers\DocumentDownloadController;
use App\Http\Controllers\PatientPortalController;
use App\Http\Controllers\VisitPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::prefix('patient/{token}')->group(function () {
    Route::get('/', [PatientPortalController::class, 'showVerify'])->name('patient.verify');
    Route::post('/verify', [PatientPortalController::class, 'verify'])->name('patient.verify.submit');
    Route::get('/records', [PatientPortalController::class, 'records'])->name('patient.records');
    Route::get('/documents/{document}', [PatientPortalController::class, 'downloadDocument'])->name('patient.documents.download');
    Route::get('/documents/{document}/preview', [PatientPortalController::class, 'previewDocument'])->name('patient.documents.preview');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/documents/{document}/preview', [DocumentDownloadController::class, 'preview'])->name('documents.preview');
    Route::get('/documents/{document}/download', [DocumentDownloadController::class, 'document'])->name('documents.download');
    Route::get('/discharge-papers/{dischargePaper}/original', [DocumentDownloadController::class, 'dischargeOriginal'])->name('discharge-papers.original');
    Route::get('/discharge-papers/{dischargePaper}/with-qr', [DocumentDownloadController::class, 'dischargeWithQr'])->name('discharge-papers.with-qr');
    Route::get('/encounters/{encounter}/surgical-notes', [DocumentDownloadController::class, 'surgicalNotes'])->name('encounters.surgical-notes');
    Route::get('/encounters/{encounter}/lab-results', [DocumentDownloadController::class, 'labResults'])->name('encounters.lab-results');
    Route::get('/encounters/{encounter}/operative-work', [DocumentDownloadController::class, 'operativeWork'])->name('encounters.operative-work');
    Route::get('/visits/{encounter}/print', [VisitPdfController::class, 'show'])->name('visits.print');

    // API routes for internal use
    Route::get('/api/diagnoses/search', [DiagnosisController::class, 'search'])->name('api.diagnoses.search');
    Route::post('/api/diagnoses', [DiagnosisController::class, 'store'])->name('api.diagnoses.store');
});
