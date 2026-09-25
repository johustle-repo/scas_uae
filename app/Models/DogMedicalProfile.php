<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\DogMedicalProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $dog_id
 * @property bool|null $spayed_neutered
 * @property Carbon|null $spayed_neutered_date
 * @property Carbon|null $last_vet_check_date
 * @property string|null $veterinary_clinic
 * @property string|null $initial_health_assessment
 * @property string|null $medical_concerns
 * @property string|null $treatment_required
 * @property string|null $medications
 * @property string|null $medical_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'dog_id',
    'spayed_neutered',
    'spayed_neutered_date',
    'last_vet_check_date',
    'veterinary_clinic',
    'initial_health_assessment',
    'medical_concerns',
    'treatment_required',
    'medications',
    'medical_notes',
])]
class DogMedicalProfile extends Model
{
    /** @use HasFactory<DogMedicalProfileFactory> */
    use Auditable, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'spayed_neutered' => 'boolean',
            'spayed_neutered_date' => 'date',
            'last_vet_check_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Dog, $this>
     */
    public function dog(): BelongsTo
    {
        return $this->belongsTo(Dog::class);
    }
}
