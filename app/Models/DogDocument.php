<?php

namespace App\Models;

use App\Enums\DocumentCategory;
use App\Models\Concerns\Auditable;
use Database\Factories\DogDocumentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A file attached to a dog's record (spec §12), stored on the private "local" disk.
 *
 * @property int $id
 * @property int $dog_id
 * @property DocumentCategory $category
 * @property string $title
 * @property string $file_path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property int|null $uploaded_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'dog_id',
    'category',
    'title',
    'file_path',
    'original_name',
    'mime_type',
    'size',
    'uploaded_by',
])]
#[Hidden(['file_path'])]
class DogDocument extends Model
{
    /** @use HasFactory<DogDocumentFactory> */
    use Auditable, HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = ['view_url', 'download_url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => DocumentCategory::class,
            'size' => 'integer',
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
     * @return BelongsTo<User, $this>
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Whether browsers can display the file inline (images and PDFs).
     */
    public function isPreviewable(): bool
    {
        return str_starts_with($this->mime_type, 'image/') || $this->mime_type === 'application/pdf';
    }

    /**
     * @return Attribute<string, never>
     */
    protected function viewUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => route('dogs.documents.show', ['dog' => $this->dog_id, 'document' => $this->id], absolute: false),
        );
    }

    /**
     * @return Attribute<string, never>
     */
    protected function downloadUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => route('dogs.documents.download', ['dog' => $this->dog_id, 'document' => $this->id], absolute: false),
        );
    }
}
