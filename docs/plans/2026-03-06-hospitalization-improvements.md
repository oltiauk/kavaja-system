# Hospitalization System Improvements Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Six improvements to the hospitalization system: discharge PDF preview, section reorder, unified documents display, card/row toggle, unified role permissions, and archive table layout.

**Architecture:** Changes span Filament resources, controllers, Blade views, a migration, and policies. PhpWord + Dompdf will handle .doc/.docx to PDF conversion at upload time.

**Tech Stack:** Laravel 12, Filament v3, PhpWord (installed), Dompdf (to install), Livewire v3, Tailwind v4, Alpine.js

---

## Task 1: Install Dompdf

**Files:**
- Modify: `composer.json`

**Step 1: Install barryvdh/laravel-dompdf**

Run: `composer require barryvdh/laravel-dompdf`

**Step 2: Commit**

```bash
git add composer.json composer.lock
git commit -m "chore: install barryvdh/laravel-dompdf for doc-to-pdf conversion"
```

---

## Task 2: Migration for preview_file_path on discharge_papers

**Files:**
- Create: `database/migrations/XXXX_add_preview_file_path_to_discharge_papers_table.php`

**Step 1: Create migration**

Run: `php artisan make:migration add_preview_file_path_to_discharge_papers_table --table=discharge_papers --no-interaction`

Migration content:
```php
public function up(): void
{
    Schema::table('discharge_papers', function (Blueprint $table) {
        $table->string('preview_file_path')->nullable()->after('qr_file_path');
    });
}

public function down(): void
{
    Schema::table('discharge_papers', function (Blueprint $table) {
        $table->dropColumn('preview_file_path');
    });
}
```

**Step 2: Run migration**

Run: `php artisan migrate --no-interaction`

**Step 3: Update DischargePaper model**

Add `preview_file_path` to `$fillable` in `app/Models/DischargePaper.php`.

**Step 4: Commit**

```bash
git add database/migrations/ app/Models/DischargePaper.php
git commit -m "feat: add preview_file_path column to discharge_papers"
```

---

## Task 3: Doc-to-PDF Conversion in DischargePaperService

**Files:**
- Modify: `app/Services/DischargePaperService.php`

**Step 1: Add convertToPreviewPdf method**

Add a public method `convertToPreviewPdf(string $inputPath, string $outputPdfPath): void` that:
- Gets the file extension
- If already PDF: copy the file as-is to outputPdfPath
- If doc/docx: use PhpWord to load, then PhpWord's PDF writer (backed by Dompdf) to save as PDF
- Store at the outputPdfPath on the `local` disk

```php
public function convertToPreviewPdf(string $inputPath, string $outputPdfPath): void
{
    $extension = strtolower(pathinfo($inputPath, PATHINFO_EXTENSION));
    $fullInputPath = Storage::disk('local')->path($inputPath);

    if (! Storage::disk('local')->exists($inputPath)) {
        throw new \RuntimeException("File not found at: {$inputPath}");
    }

    Storage::disk('local')->makeDirectory(dirname($outputPdfPath));
    $fullOutputPath = Storage::disk('local')->path($outputPdfPath);

    if ($extension === 'pdf') {
        Storage::disk('local')->copy($inputPath, $outputPdfPath);
        return;
    }

    if (in_array($extension, ['doc', 'docx'], true)) {
        \PhpOffice\PhpWord\Settings::setPdfRendererName(\PhpOffice\PhpWord\Settings::PDF_RENDERER_DOMPDF);
        \PhpOffice\PhpWord\Settings::setPdfRendererPath(base_path('vendor/dompdf/dompdf'));

        $phpWord = WordIOFactory::load($fullInputPath);
        $pdfWriter = WordIOFactory::createWriter($phpWord, 'PDF');
        $pdfWriter->save($fullOutputPath);
        return;
    }

    throw new \RuntimeException("Unsupported file type for preview: {$extension}");
}
```

**Step 2: Commit**

```bash
git add app/Services/DischargePaperService.php
git commit -m "feat: add doc-to-pdf conversion for discharge paper preview"
```

---

## Task 4: Store Preview PDF at Upload Time + Fix Overflow

**Files:**
- Modify: `app/Filament/Resources/HospitalizationResource.php` (the `storeDischargePaper` method and discharge section)

**Step 1: Update storeDischargePaper to generate preview PDF**

After the QR code file is created, call `convertToPreviewPdf` on the QR version. Store preview at `{pathBase}/preview.pdf`. Save `preview_file_path` on the DischargePaper record.

In the `storeDischargePaper` method, after the `$encounter->dischargePaper()->create(...)` block, add preview generation:

```php
$previewPath = "{$pathBase}/preview.pdf";
$dischargeService->convertToPreviewPdf($qrPath, $previewPath);

// Update the record with preview path
$encounter->dischargePaper->update([
    'preview_file_path' => $previewPath,
]);
```

**Step 2: Fix the filename overflow in discharge section**

Change the `discharge_filename` Placeholder to truncate long filenames:
```php
Forms\Components\Placeholder::make('discharge_filename')
    ->label(__('app.labels.file'))
    ->content(fn (?Encounter $record) => $record?->dischargePaper?->original_filename
        ? \Illuminate\Support\Str::limit($record->dischargePaper->original_filename, 40)
        : '—'),
```

**Step 3: Change download buttons to preview + download**

Replace the two download FormActions with:
- `previewDischargeOriginal` — opens preview PDF in new tab (inline display)
- `previewDischargeQr` — opens QR version preview PDF in new tab (inline display)
- `downloadDischargeOriginal` — downloads original file
- `downloadDischargeQr` — downloads QR file

```php
FormAction::make('previewDischargeOriginal')
    ->label(__('app.actions.preview_discharge_original'))
    ->icon('heroicon-o-eye')
    ->visible(fn (?Encounter $record) => (bool) $record?->dischargePaper?->preview_file_path)
    ->url(fn (Encounter $record) => route('discharge-papers.preview', $record->dischargePaper), true),
FormAction::make('previewDischargeQr')
    ->label(__('app.actions.preview_discharge_qr'))
    ->icon('heroicon-o-eye')
    ->visible(fn (?Encounter $record) => (bool) $record?->dischargePaper?->preview_file_path)
    ->url(fn (Encounter $record) => route('discharge-papers.preview-qr', $record->dischargePaper), true),
FormAction::make('downloadDischargeOriginal')
    ->label(__('app.actions.download_discharge_original'))
    ->icon('heroicon-o-arrow-down-tray')
    ->visible(fn (?Encounter $record) => (bool) $record?->dischargePaper)
    ->url(fn (Encounter $record) => route('discharge-papers.original', $record->dischargePaper), true),
FormAction::make('downloadDischargeQr')
    ->label(__('app.actions.download_discharge_qr'))
    ->icon('heroicon-o-qr-code')
    ->visible(fn (?Encounter $record) => (bool) $record?->dischargePaper)
    ->url(fn (Encounter $record) => route('discharge-papers.with-qr', $record->dischargePaper), true),
```

**Step 4: Commit**

```bash
git add app/Filament/Resources/HospitalizationResource.php
git commit -m "feat: discharge paper preview with doc-to-pdf conversion, fix filename overflow"
```

---

## Task 5: Preview Routes + Controller Methods

**Files:**
- Modify: `app/Http/Controllers/DocumentDownloadController.php`
- Modify: `routes/web.php`

**Step 1: Add preview controller methods**

```php
public function dischargePreview(DischargePaper $dischargePaper)
{
    $this->authorize('view', $dischargePaper);

    $previewPath = $dischargePaper->preview_file_path;

    // Fallback to original if no preview (PDF originals)
    if (! $previewPath || ! Storage::disk('local')->exists($previewPath)) {
        $previewPath = $dischargePaper->original_file_path;
    }

    if (! Storage::disk('local')->exists($previewPath)) {
        abort(404);
    }

    return response()->file(
        Storage::disk('local')->path($previewPath),
        ['Content-Type' => 'application/pdf']
    );
}

public function dischargePreviewQr(DischargePaper $dischargePaper)
{
    $this->authorize('view', $dischargePaper);

    $previewPath = $dischargePaper->preview_file_path;

    if (! $previewPath || ! Storage::disk('local')->exists($previewPath)) {
        $previewPath = $dischargePaper->qr_file_path;
    }

    if (! Storage::disk('local')->exists($previewPath)) {
        abort(404);
    }

    return response()->file(
        Storage::disk('local')->path($previewPath),
        ['Content-Type' => 'application/pdf']
    );
}
```

Also update the existing `preview` method to support PDFs too:
```php
public function preview(Document $document)
{
    $this->authorize('view', $document);

    if (! str_starts_with($document->mime_type, 'image/') && $document->mime_type !== 'application/pdf') {
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
```

**Step 2: Add routes**

In `routes/web.php`, inside the auth middleware group:
```php
Route::get('/discharge-papers/{dischargePaper}/preview', [DocumentDownloadController::class, 'dischargePreview'])->name('discharge-papers.preview');
Route::get('/discharge-papers/{dischargePaper}/preview-qr', [DocumentDownloadController::class, 'dischargePreviewQr'])->name('discharge-papers.preview-qr');
```

**Step 3: Commit**

```bash
git add app/Http/Controllers/DocumentDownloadController.php routes/web.php
git commit -m "feat: add discharge paper and document preview routes"
```

---

## Task 6: Reorder Form Sections

**Files:**
- Modify: `app/Filament/Resources/HospitalizationResource.php`

**Step 1: Reorder the sections in the `form()` method**

Move the sections in this order:
1. Patient Information (line ~101) — stays
2. Clinical Details (line ~131) — stays
3. Room Selection (line ~183) — stays
4. Medical Information (line ~290) — move up here
5. Dates & Status (line ~262) — move here
6. File Uploads (line ~191) — move here
7. Discharge Paper (line ~359) — stays last

This is a pure reorder of the Section blocks within the `schema([...])` array.

**Step 2: Commit**

```bash
git add app/Filament/Resources/HospitalizationResource.php
git commit -m "feat: reorder hospitalization form sections"
```

---

## Task 7: Documents Section Shows Encounter Files

**Files:**
- Modify: `app/Filament/Resources/EncounterResource/RelationManagers/DocumentsRelationManager.php`

**Step 1: Add encounter files as virtual entries in the table**

Override `table()` to prepend encounter file records. Use `modifyQueryUsing` to union encounter-level files, OR add a section above the table showing encounter files.

Best approach: Add a custom header view that shows encounter files (lab_results, operative_work, surgical_notes) as document cards before the relation table.

In the `table()` method, add encounter file cards as a header:

```php
->heading(__('app.labels.documents'))
->description(new \Illuminate\Support\HtmlString(
    view('filament.tables.encounter-files-header', [
        'encounter' => $this->getOwnerRecord(),
    ])->render()
))
```

**Step 2: Create the encounter-files-header view**

Create `resources/views/filament/tables/encounter-files-header.blade.php` that renders lab_results, operative_work, surgical_notes as document-like cards (using similar styling to the document-card component).

**Step 3: Commit**

```bash
git add app/Filament/Resources/EncounterResource/RelationManagers/DocumentsRelationManager.php resources/views/filament/tables/encounter-files-header.blade.php
git commit -m "feat: show encounter files in documents section"
```

---

## Task 8: Document Card Preview + Card/Row Toggle

**Files:**
- Modify: `resources/views/components/document-card.blade.php`
- Modify: `resources/views/filament/tables/columns/document-card.blade.php`
- Modify: `app/Filament/Resources/EncounterResource/RelationManagers/DocumentsRelationManager.php`

**Step 1: Make document card click open preview instead of download**

In `document-card.blade.php`, change the main `<a>` tag to:
- Images: open preview route in new tab
- PDFs: open preview route in new tab (inline display)
- Other files: keep download behavior

```php
$clickUrl = match (true) {
    $isImage, $isPdf => route('documents.preview', $document),
    default => route('documents.download', $document),
};
$openInNewTab = $isImage || $isPdf;
```

Update the `<a>` tags to use `$clickUrl` and `target="{{ $openInNewTab ? '_blank' : '_self' }}"`.

**Step 2: Add a row-view Blade component**

Create `resources/views/components/document-row.blade.php` — a compact horizontal row showing: file type icon, filename, type badge, uploader, date, download/preview action buttons.

**Step 3: Add card/row toggle to DocumentsRelationManager**

Add a Livewire property or Alpine.js toggle in the table header. Use Alpine `x-data` with a `viewMode` variable. The document-card view template switches between card and row rendering based on this.

Simplest approach: Use Alpine on the relation manager wrapper to toggle a CSS class that switches layout.

**Step 4: Commit**

```bash
git add resources/views/components/document-card.blade.php resources/views/components/document-row.blade.php resources/views/filament/tables/columns/document-card.blade.php app/Filament/Resources/EncounterResource/RelationManagers/DocumentsRelationManager.php
git commit -m "feat: document preview on click, card/row view toggle"
```

---

## Task 9: Unified Role Permissions for Hospitalizations

**Files:**
- Modify: `app/Filament/Resources/HospitalizationResource.php`
- Modify: `app/Policies/EncounterPolicy.php`
- Modify: `app/Policies/DocumentPolicy.php`
- Modify: `app/Policies/DischargePaperPolicy.php`

**Step 1: Update HospitalizationResource permissions**

```php
public static function canCreate(): bool
{
    $user = auth()->user();
    return $user?->isAdmin() || $user?->isAdministration() || $user?->isStaff();
}

public static function canEdit($record): bool
{
    $user = auth()->user();
    return $user?->isAdmin() || $user?->isAdministration() || $user?->isStaff();
}

public static function canDelete($record): bool
{
    $user = auth()->user();
    return $user?->isAdmin() || $user?->isAdministration() || $user?->isStaff();
}

public static function canDeleteAny(): bool
{
    $user = auth()->user();
    return $user?->isAdmin() || $user?->isAdministration() || $user?->isStaff();
}
```

**Step 2: Remove role checks on visibility of form sections**

In `form()`, change all `->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isStaff())` to include `isAdministration()`:

- File uploads section (line ~194)
- Medical info complete toggle (line ~285)
- Upload/replace discharge paper actions (lines ~382, ~418)

Change all to: `->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isAdministration() || auth()->user()?->isStaff())`

**Step 3: Update EncounterPolicy**

Make `create`, `update`, `delete`, `restore`, `forceDelete` all return `$user->isAdmin() || $user->isAdministration() || $user->isStaff()`.

**Step 4: Update DocumentPolicy**

Make `create`, `update`, `delete` return `$user->isAdmin() || $user->isAdministration() || $user->isStaff()`.

**Step 5: Update DischargePaperPolicy**

Make `create`, `update`, `delete` return `$user->isAdmin() || $user->isAdministration() || $user->isStaff()`.

**Step 6: Update DocumentsRelationManager create action visibility**

Change `->visible(fn () => auth()->user()?->isAdmin() || auth()->user()?->isStaff())` to include `isAdministration()`.

**Step 7: Commit**

```bash
git add app/Filament/Resources/HospitalizationResource.php app/Policies/EncounterPolicy.php app/Policies/DocumentPolicy.php app/Policies/DischargePaperPolicy.php app/Filament/Resources/EncounterResource/RelationManagers/DocumentsRelationManager.php
git commit -m "feat: unify role permissions for hospitalization section"
```

---

## Task 10: Archives Table Layout

**Files:**
- Modify: `app/Filament/Resources/ArchivedHospitalizationResource.php`

**Step 1: Change from card grid to standard table rows**

Replace the `Stack/Split` column layout with standard flat columns:

```php
->columns([
    Tables\Columns\TextColumn::make('full_name')
        ->label(__('app.labels.patient'))
        ->weight(FontWeight::Bold)
        ->searchable(['first_name', 'last_name'])
        ->sortable(query: fn (Builder $query, string $direction) => $query
            ->orderBy('last_name', $direction)
            ->orderBy('first_name', $direction)),
    Tables\Columns\TextColumn::make('date_of_birth')
        ->label(__('app.labels.date_of_birth'))
        ->date('d M Y'),
    Tables\Columns\TextColumn::make('phone_number')
        ->label(__('app.labels.phone'))
        ->placeholder('—'),
    Tables\Columns\TextColumn::make('discharged_encounters_count')
        ->label(__('app.labels.encounters'))
        ->badge()
        ->color('gray')
        ->formatStateUsing(fn (int $state) => trans_choice('app.labels.encounter_count', $state, ['count' => $state])),
    Tables\Columns\TextColumn::make('last_discharge_date')
        ->label(__('app.labels.last_discharge'))
        ->dateTime('d M Y, H:i')
        ->sortable(),
])
```

Remove the `->contentGrid([...])` call.

**Step 2: Commit**

```bash
git add app/Filament/Resources/ArchivedHospitalizationResource.php
git commit -m "feat: change archives to table row layout"
```

---

## Task 11: Archive View Shows Hospitalization Cards Like Active

**Files:**
- Modify: `app/Filament/Resources/ArchivedHospitalizationResource/RelationManagers/EncountersRelationManager.php`

**Step 1: Match the active hospitalization card layout**

Update the EncountersRelationManager table columns to match the HospitalizationResource table layout exactly:

```php
->columns([
    Stack::make([
        Split::make([
            Tables\Columns\TextColumn::make('patient.full_name')
                ->label(__('app.labels.patient'))
                ->weight(FontWeight::Bold)
                ->size(Tables\Columns\TextColumn\TextColumnSize::Large)
                ->default(fn ($record) => $record->patient?->full_name ?? '—'),
            Tables\Columns\TextColumn::make('room_number')
                ->label(__('app.labels.room_number'))
                ->badge()
                ->color('info')
                ->icon('heroicon-m-home')
                ->grow(false)
                ->formatStateUsing(fn (?string $state): ?string => \App\Filament\Resources\HospitalizationResource::formatRoomNumber($state))
                ->placeholder(null),
            Tables\Columns\TextColumn::make('status')
                ->label(__('app.labels.status'))
                ->badge()
                ->color('gray')
                ->formatStateUsing(fn () => __('app.labels.discharged'))
                ->grow(false),
        ]),
        Split::make([
            Tables\Columns\TextColumn::make('diagnosis')
                ->label(__('app.labels.diagnosis'))
                ->icon('heroicon-m-clipboard-document-list')
                ->limit(80)
                ->color('primary')
                ->placeholder('—'),
            Tables\Columns\TextColumn::make('patient.medicalInfo.allergies')
                ->icon('heroicon-m-exclamation-triangle')
                ->color('danger')
                ->weight(FontWeight::SemiBold)
                ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                ->limit(60)
                ->formatStateUsing(fn (?string $state) => $state ? "! {$state}" : null)
                ->grow(false)
                ->placeholder(''),
        ]),
        Split::make([
            Tables\Columns\TextColumn::make('doctor_name')
                ->label(__('app.labels.doctor'))
                ->icon('heroicon-m-user')
                ->searchable(),
        ]),
        Split::make([
            Tables\Columns\TextColumn::make('admission_date')
                ->label(__('app.labels.admission_date'))
                ->icon('heroicon-m-calendar')
                ->dateTime('d M Y, H:i'),
            Tables\Columns\TextColumn::make('discharge_date')
                ->label(__('app.labels.discharge_date'))
                ->icon('heroicon-m-arrow-right-on-rectangle')
                ->dateTime('d M Y, H:i')
                ->placeholder('—'),
        ]),
    ])->space(2),
])
```

Also update `modifyQueryUsing` to eager load `patient.medicalInfo`:
```php
->modifyQueryUsing(fn (Builder $query) => $query->with(['dischargePaper', 'patient.medicalInfo'])->where('status', 'discharged')->orderBy('discharge_date', 'desc'))
```

Note: The `formatRoomNumber` method on HospitalizationResource needs to be made public/static accessible. It's already `protected static` — change to `public static`.

**Step 2: Commit**

```bash
git add app/Filament/Resources/ArchivedHospitalizationResource/RelationManagers/EncountersRelationManager.php app/Filament/Resources/HospitalizationResource.php
git commit -m "feat: archive encounter cards match active hospitalization layout"
```

---

## Task 12: Add Translation Keys

**Files:**
- Modify: Translation files (check `lang/` directory for existing keys)

**Step 1: Add missing translation keys**

Keys needed:
- `app.actions.preview_discharge_original`
- `app.actions.preview_discharge_qr`

Check existing translation files and add these keys.

**Step 2: Commit**

```bash
git add lang/
git commit -m "feat: add translation keys for discharge preview actions"
```

---

## Task 13: Run Pint + Final Verification

**Step 1: Run Pint**

Run: `vendor/bin/pint --dirty`

**Step 2: Run tests**

Run: `php artisan test --compact`

**Step 3: Final commit if needed**

```bash
git add -A
git commit -m "style: run pint formatting"
```
