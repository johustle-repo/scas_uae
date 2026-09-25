<?php

namespace App\Models;

use App\Enums\PhotoKind;
use App\Models\Concerns\Auditable;
use Database\Factories\DogPhotoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A photo of a dog: profile, rescue/intake or gallery (spec §5.1, §7.2).
 *
 * @property int $id
 * @property int $dog_id
 * @property PhotoKind $kind
 * @property string $file_path
 * @property string|null $caption
 * @property int|null $uploaded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['dog_id', 'kind', 'file_path', 'caption', 'uploaded_by'])]
#[Hidden(['file_path'])]
class DogPhoto extends Model
{
    /** @use HasFactory<DogPhotoFactory> */
    use Auditable, HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => PhotoKind::class,
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
     * @return Attribute<string, never>
     */
    protected function url(): Attribute
    {
        return Attribute::make(
            get: fn (): string => route('dogs.photos.show', ['dog' => $this->dog_id, 'photo' => $this->id], absolute: false),
        );
    }
}
