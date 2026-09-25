<?php

namespace App\Models;

use App\Enums\PlacementType;
use App\Models\Concerns\Auditable;
use Database\Factories\DogFosterRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $dog_id
 * @property PlacementType $foster_type
 * @property string $foster_family_name
 * @property string|null $location
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'dog_id',
    'foster_type',
    'foster_family_name',
    'location',
    'start_date',
    'end_date',
    'notes',
])]
class DogFosterRecord extends Model
{
    /** @use HasFactory<DogFosterRecordFactory> */
    use Auditable, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'foster_type' => PlacementType::class,
            'start_date' => 'date',
            'end_date' => 'date',
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
