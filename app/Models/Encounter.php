<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

class Encounter extends Model
{
    /** @use HasFactory<\Database\Factories\EncounterFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'patient_id',
        'type',
        'status',
        'main_complaint',
        'doctor_name',
        'diagnosis',
        'treatment',
        'surgical_notes',
        'admission_date',
        'discharge_date',
        'medical_info_complete',
        'created_by',
        'updated_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'admission_date' => 'datetime',
            'discharge_date' => 'datetime',
            'medical_info_complete' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function dischargePaper(): HasOne
    {
        return $this->hasOne(DischargePaper::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeVisits($query)
    {
        return $query->where('type', 'visit');
    }

    public function scopeHospitalizations($query)
    {
        return $query->where('type', 'hospitalization');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeNeedingMedicalInfo($query)
    {
        return $query->where('medical_info_complete', false);
    }

    public function isHospitalization(): bool
    {
        return $this->type === 'hospitalization';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function canBeConverted(): bool
    {
        return $this->type === 'visit' && $this->isActive();
    }

    public function convertToHospitalization(): void
    {
        $this->type = 'hospitalization';
        $this->medical_info_complete = false;
        $this->save();
    }

    public function discharge(): void
    {
        $this->status = 'discharged';
        $this->discharge_date = Carbon::now();
        $this->save();
    }
}
