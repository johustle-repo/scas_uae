<?php

namespace App\Models;

use App\Enums\ImportFlagType;
use Database\Factories\DogImportFlagFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $dog_id
 * @property ImportFlagType $flag_type
 * @property string $message
 * @property Carbon|null $created_at
 */
#[Fillable(['dog_id', 'flag_type', 'message'])]
class DogImportFlag extends Model
{
    /** @use HasFactory<DogImportFlagFactory> */
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'flag_type' => ImportFlagType::class,
            'created_at' => 'datetime',
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
