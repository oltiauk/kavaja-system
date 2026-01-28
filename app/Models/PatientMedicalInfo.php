<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedicalInfo extends Model
{
    /** @use HasFactory<\Database\Factories\PatientMedicalInfoFactory> */
    use HasFactory;

    protected $table = 'patient_medical_info';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'patient_id',
        'blood_type',
        'height_cm',
        'weight_kg',
        'has_allergies',
        'allergies',
        'smoking_status',
        'alcohol_use',
        'drug_use_history',
        'pacemaker_implants',
        'anesthesia_reactions',
        'current_medications',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'has_allergies' => 'boolean',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
