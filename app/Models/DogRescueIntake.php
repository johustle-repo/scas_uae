<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\DogRescueIntakeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $dog_id
 * @property Carbon|null $date_received
 * @property string|null $rescue_location
 * @property string|null $how_found
 * @property string|null $initial_condition
 * @property string|null $source_of_intake
 * @property string|null $previous_owner_surrender_details
 * @property Carbon|null $intake_date
 * @property string|null $intake_notes
 * @property string|null $rescue_story
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'dog_id',
    'date_received',
    'rescue_location',
    'how_found',
    'initial_condition',
    'source_of_intake',
    'previous_owner_surrender_details',
    'intake_date',
    'intake_notes',
    'rescue_story',
])]
class DogRescueIntake extends Model
{
    /** @use HasFactory<DogRescueIntakeFactory> */
    use Auditable, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date_received' => 'date',
            'intake_date' => 'date',
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
