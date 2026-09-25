<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\DogVaccinationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $dog_id
 * @property string $vaccine_details
 * @property Carbon|null $administered_date
 * @property Carbon|null $next_due_date
 * @property string|null $expiry_annotation
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'dog_id',
    'vaccine_details',
    'administered_date',
    'next_due_date',
    'expiry_annotation',
    'notes',
])]
class DogVaccination extends Model
{
    /** @use HasFactory<DogVaccinationFactory> */
    use Auditable, HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'administered_date' => 'date',
            'next_due_date' => 'date',
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
     * @param  Builder<DogVaccination>  $query
     * @return Builder<DogVaccination>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->whereNotNull('next_due_date')
            ->where('next_due_date', '>=', now())
            ->orderBy('next_due_date');
    }
}
