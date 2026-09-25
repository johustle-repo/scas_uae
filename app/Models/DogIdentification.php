<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use App\Models\Concerns\Auditable;
use Database\Factories\DogIdentificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $dog_id
 * @property string|null $microchip_number
 * @property Carbon|null $microchip_date
 * @property string|null $microchip_location
 * @property RegistrationStatus|null $microchip_registration_status
 * @property string|null $passport_number
 * @property Carbon|null $passport_issue_date
 * @property Carbon|null $passport_expiry_date
 * @property string|null $passport_issuing_authority
 * @property RegistrationStatus|null $passport_status
 * @property string|null $pcc_number
 * @property Carbon|null $pcc_registration_date
 * @property RegistrationStatus|null $pcc_registration_status
 * @property string|null $supporting_documents_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'dog_id',
    'microchip_number',
    'microchip_date',
    'microchip_location',
    'microchip_registration_status',
    'passport_number',
    'passport_issue_date',
    'passport_expiry_date',
    'passport_issuing_authority',
    'passport_status',
    'pcc_number',
    'pcc_registration_date',
    'pcc_registration_status',
    'supporting_documents_notes',
])]
class DogIdentification extends Model
{
    /** @use HasFactory<DogIdentificationFactory> */
    use Auditable, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'microchip_date' => 'date',
            'microchip_registration_status' => RegistrationStatus::class,
            'passport_issue_date' => 'date',
            'passport_expiry_date' => 'date',
            'passport_status' => RegistrationStatus::class,
            'pcc_registration_date' => 'date',
            'pcc_registration_status' => RegistrationStatus::class,
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
