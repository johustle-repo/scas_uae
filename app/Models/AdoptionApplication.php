<?php

namespace App\Models;

use App\Enums\AdoptionApplicationStatus;
use App\Enums\HomeType;
use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Database\Factories\AdoptionApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An adoption request submitted by a member of the public from the landing page.
 *
 * @property int $id
 * @property int|null $dog_id
 * @property string $full_name
 * @property string $email
 * @property string $phone
 * @property string $city
 * @property HomeType $home_type
 * @property bool $has_garden
 * @property bool $has_children
 * @property string|null $other_pets
 * @property string|null $experience
 * @property string $message
 * @property AdoptionApplicationStatus $status
 * @property string|null $staff_notes
 * @property int|null $reviewed_by
 * @property CarbonImmutable|null $reviewed_at
 * @property string|null $ip_address
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'dog_id',
    'full_name',
    'email',
    'phone',
    'city',
    'home_type',
    'has_garden',
    'has_children',
    'other_pets',
    'experience',
    'message',
    'status',
    'staff_notes',
    'reviewed_by',
    'reviewed_at',
    'ip_address',
])]
#[Hidden(['ip_address'])]
class AdoptionApplication extends Model
{
    /** @use HasFactory<AdoptionApplicationFactory> */
    use Auditable, HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'new',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'home_type' => HomeType::class,
            'has_garden' => 'boolean',
            'has_children' => 'boolean',
            'status' => AdoptionApplicationStatus::class,
            'reviewed_at' => 'datetime',
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
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
