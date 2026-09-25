<?php

namespace App\Models;

use App\Enums\PlacementType;
use App\Models\Concerns\Auditable;
use Database\Factories\DogAdoptionRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $dog_id
 * @property PlacementType $adoption_type
 * @property string $adopter_name
 * @property string|null $adopter_location
 * @property string|null $adopter_contact
 * @property Carbon|null $adoption_date
 * @property Carbon|null $return_date
 * @property string|null $return_reason
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'dog_id',
    'adoption_type',
    'adopter_name',
    'adopter_location',
    'adopter_contact',
    'adoption_date',
    'return_date',
    'return_reason',
    'notes',
])]
class DogAdoptionRecord extends Model
{
    /** @use HasFactory<DogAdoptionRecordFactory> */
    use Auditable, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'adoption_type' => PlacementType::class,
            'adoption_date' => 'date',
            'return_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Dog, $this>
     */
    public function dog(): BelongsTo
    {
        return $this->belongsTo(Dog::class);
    }

    /**
     * @param  Builder<DogAdoptionRecord>  $query
     * @return Builder<DogAdoptionRecord>
     */
    public function scopeReturned(Builder $query): Builder
    {
        return $query->whereNotNull('return_date');
    }
}
