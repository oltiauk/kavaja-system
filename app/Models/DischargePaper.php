<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DischargePaper extends Model
{
    /** @use HasFactory<\Database\Factories\DischargePaperFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'encounter_id',
        'patient_id',
        'original_file_path',
        'original_filename',
        'qr_file_path',
        'qr_token',
        'mime_type',
        'uploaded_by',
    ];

    public function encounter(): BelongsTo
    {
        return $this->belongsTo(Encounter::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
