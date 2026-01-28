<?php

namespace App\Livewire;

use App\Models\Document;
use App\Models\Encounter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentsGrid extends Component
{
    use WithFileUploads;

    public Encounter $encounter;

    public $upload;

    public $type = 'diagnostic_image';

    public bool $showUploadModal = false;

    public function mount(Encounter $encounter): void
    {
        $this->encounter = $encounter;
    }

    public function openUploadModal(): void
    {
        $this->showUploadModal = true;
    }

    public function closeUploadModal(): void
    {
        $this->showUploadModal = false;
        $this->reset(['upload', 'type']);
    }

    public function save(): void
    {
        $this->validate([
            'upload' => 'required|file|max:20480|mimes:jpeg,png,gif,pdf,doc,docx',
            'type' => 'required|in:diagnostic_image,report,other',
        ]);

        $path = $this->upload->store(
            "documents/{$this->encounter->patient_id}/{$this->encounter->id}",
            'local'
        );

        Document::create([
            'encounter_id' => $this->encounter->id,
            'patient_id' => $this->encounter->patient_id,
            'type' => $this->type,
            'original_filename' => $this->upload->getClientOriginalName(),
            'stored_filename' => basename($path),
            'file_path' => $path,
            'mime_type' => $this->upload->getMimeType(),
            'file_size' => $this->upload->getSize(),
            'uploaded_by' => Auth::id(),
        ]);

        $this->closeUploadModal();
        $this->dispatch('document-uploaded');
    }

    public function deleteDocument(int $documentId): void
    {
        $document = Document::findOrFail($documentId);

        if (Storage::disk('local')->exists($document->file_path)) {
            Storage::disk('local')->delete($document->file_path);
        }

        $document->delete();
    }

    public function render()
    {
        return view('livewire.documents-grid', [
            'documents' => $this->encounter->documents()->with('uploadedBy')->latest()->get(),
        ]);
    }
}
