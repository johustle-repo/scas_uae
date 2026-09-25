<?php

namespace App\Models;

use App\Enums\DogStatus;
use App\Enums\EnergyLevel;
use App\Enums\Gender;
use App\Enums\PlacementType;
use App\Enums\Size;
use App\Enums\Species;
use App\Enums\TrainingLevel;
use App\Models\Concerns\Auditable;
use Carbon\CarbonImmutable;
use Database\Factories\DogFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $scas_id
 * @property int|null $legacy_source_row
 * @property string $name
 * @property Species $species
 * @property string|null $breed
 * @property string|null $colour_markings
 * @property Gender|null $gender
 * @property Size|null $size
 * @property Carbon|null $date_of_birth
 * @property bool $date_of_birth_is_approximate
 * @property string|null $estimated_age_notes
 * @property DogStatus $current_status
 * @property string|null $current_location
 * @property string|null $photo_path
 * @property string|null $personality_description
 * @property EnergyLevel|null $energy_level
 * @property string|null $temperament
 * @property bool|null $good_with_dogs
 * @property bool|null $good_with_cats
 * @property bool|null $good_with_children
 * @property bool|null $good_with_adults
 * @property TrainingLevel|null $training_level
 * @property bool|null $potty_trained
 * @property bool|null $leash_trained
 * @property string|null $basic_commands_notes
 * @property string|null $behavioural_notes
 * @property string|null $special_requirements
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'scas_id',
    'legacy_source_row',
    'name',
    'species',
    'breed',
    'colour_markings',
    'gender',
    'size',
    'date_of_birth',
    'date_of_birth_is_approximate',
    'estimated_age_notes',
    'current_status',
    'current_location',
    'photo_path',
    'personality_description',
    'energy_level',
    'temperament',
    'good_with_dogs',
    'good_with_cats',
    'good_with_children',
    'good_with_adults',
    'training_level',
    'potty_trained',
    'leash_trained',
    'basic_commands_notes',
    'behavioural_notes',
    'special_requirements',
    'archived_at',
])]
class Dog extends Model
{
    /** @use HasFactory<DogFactory> */
    use Auditable, HasFactory;

    /**
     * @var list<string>
     */
    protected $appends = [
        'age',
        'microchipped',
        'vaccinations_up_to_date',
        'next_vaccination_date',
        'photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'species' => Species::class,
            'gender' => Gender::class,
            'size' => Size::class,
            'date_of_birth' => 'date',
            'date_of_birth_is_approximate' => 'boolean',
            'current_status' => DogStatus::class,
            'energy_level' => EnergyLevel::class,
            'good_with_dogs' => 'boolean',
            'good_with_cats' => 'boolean',
            'good_with_children' => 'boolean',
            'good_with_adults' => 'boolean',
            'training_level' => TrainingLevel::class,
            'potty_trained' => 'boolean',
            'leash_trained' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    /**
     * Boot the model, auto-assigning a SCAS ID to new dogs.
     */
    protected static function booted(): void
    {
        static::creating(function (Dog $dog): void {
            if (blank($dog->scas_id)) {
                $dog->scas_id = static::nextScasId();
            }
        });
    }

    /**
     * Generate the next sequential "SCAS-###" identifier.
     */
    public static function nextScasId(): string
    {
        $lastNumber = static::query()
            ->selectRaw('MAX(CAST(SUBSTR(scas_id, 6) AS UNSIGNED)) AS max_number')
            ->where('scas_id', 'like', 'SCAS-%')
            ->value('max_number');

        return sprintf('SCAS-%03d', ((int) $lastNumber) + 1);
    }

    /**
     * @return HasOne<DogMedicalProfile, $this>
     */
    public function medicalProfile(): HasOne
    {
        return $this->hasOne(DogMedicalProfile::class);
    }

    /**
     * @return HasOne<DogIdentification, $this>
     */
    public function identification(): HasOne
    {
        return $this->hasOne(DogIdentification::class);
    }

    /**
     * @return HasOne<DogRescueIntake, $this>
     */
    public function rescueIntake(): HasOne
    {
        return $this->hasOne(DogRescueIntake::class);
    }

    /**
     * @return HasMany<DogVaccination, $this>
     */
    public function vaccinations(): HasMany
    {
        return $this->hasMany(DogVaccination::class);
    }

    /**
     * @return HasMany<DogFosterRecord, $this>
     */
    public function fosterRecords(): HasMany
    {
        return $this->hasMany(DogFosterRecord::class);
    }

    /**
     * @return HasMany<DogAdoptionRecord, $this>
     */
    public function adoptionRecords(): HasMany
    {
        return $this->hasMany(DogAdoptionRecord::class);
    }

    /**
     * @return HasMany<DogImportFlag, $this>
     */
    public function importFlags(): HasMany
    {
        return $this->hasMany(DogImportFlag::class);
    }

    /**
     * @return HasMany<DogDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(DogDocument::class);
    }

    /**
     * @return HasMany<DogPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(DogPhoto::class);
    }

    /**
     * @return HasMany<AuditLog, $this>
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Dogs that have not been archived.
     *
     * @param  Builder<Dog>  $query
     * @return Builder<Dog>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('archived_at');
    }

    /**
     * Only archived dogs.
     *
     * @param  Builder<Dog>  $query
     * @return Builder<Dog>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->whereNotNull('archived_at');
    }

    /**
     * Filter dogs by one or more current statuses. Unknown values are ignored.
     *
     * @param  Builder<Dog>  $query
     * @param  DogStatus|string|array<int, DogStatus|string>|null  $statuses
     * @return Builder<Dog>
     */
    public function scopeStatus(Builder $query, DogStatus|string|array|null $statuses): Builder
    {
        $statuses = collect(is_array($statuses) ? $statuses : [$statuses])
            ->map(fn (DogStatus|string|null $status): ?DogStatus => $status instanceof DogStatus ? $status : DogStatus::tryFrom((string) $status))
            ->filter()
            ->values();

        if ($statuses->isEmpty()) {
            return $query;
        }

        return $query->whereIn('current_status', $statuses->all());
    }

    /**
     * Filter dogs by one or more placement types, derived from current status.
     *
     * @param  Builder<Dog>  $query
     * @param  PlacementType|string|array<int, PlacementType|string>|null  $placements
     * @return Builder<Dog>
     */
    public function scopePlacement(Builder $query, PlacementType|string|array|null $placements): Builder
    {
        $statuses = collect(is_array($placements) ? $placements : [$placements])
            ->map(fn (PlacementType|string|null $placement): ?PlacementType => $placement instanceof PlacementType ? $placement : PlacementType::tryFrom((string) $placement))
            ->filter()
            ->flatMap(fn (PlacementType $placement): array => match ($placement) {
                PlacementType::Local => [DogStatus::LocalFoster, DogStatus::AdoptedUae],
                PlacementType::International => [DogStatus::InternationalFoster, DogStatus::AdoptedInternationally],
            })
            ->values();

        if ($statuses->isEmpty()) {
            return $query;
        }

        return $query->whereIn('current_status', $statuses->all());
    }

    /**
     * Filter dogs by an age range in whole years, derived from date of birth.
     *
     * @param  Builder<Dog>  $query
     * @return Builder<Dog>
     */
    public function scopeAgeBetween(Builder $query, ?int $minimumAge, ?int $maximumAge): Builder
    {
        return $query
            ->when($minimumAge !== null, fn (Builder $query) => $query->whereDate('date_of_birth', '<=', now()->subYears($minimumAge)))
            ->when($maximumAge !== null, fn (Builder $query) => $query->whereDate('date_of_birth', '>', now()->subYears($maximumAge + 1)));
    }

    /**
     * Search dogs by name, SCAS ID, microchip number, or breed.
     *
     * @param  Builder<Dog>  $query
     * @return Builder<Dog>
     */
    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('name', 'like', "%{$term}%")
                ->orWhere('scas_id', 'like', "%{$term}%")
                ->orWhere('breed', 'like', "%{$term}%")
                ->orWhereHas('identification', function (Builder $query) use ($term): void {
                    $query->where('microchip_number', 'like', "%{$term}%");
                });
        });
    }

    /**
     * Dogs flagged during import or otherwise needing staff review.
     *
     * @param  Builder<Dog>  $query
     * @return Builder<Dog>
     */
    public function scopeNeedsReview(Builder $query): Builder
    {
        return $query->whereHas('importFlags');
    }

    /**
     * Dogs missing key profile information.
     *
     * @param  Builder<Dog>  $query
     * @return Builder<Dog>
     */
    public function scopeIncomplete(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->whereNull('breed')
                ->orWhereNull('gender')
                ->orWhereNull('size')
                ->orWhereNull('date_of_birth')
                ->orWhereDoesntHave('identification', function (Builder $query): void {
                    $query->whereNotNull('microchip_number');
                });
        });
    }

    /**
     * @return Attribute<int|null, never>
     */
    protected function age(): Attribute
    {
        return Attribute::make(
            get: fn (): ?int => $this->date_of_birth === null ? null : (int) floor($this->date_of_birth->diffInYears(now())),
        );
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function microchipped(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => filled($this->identification?->microchip_number),
        );
    }

    /**
     * @return Attribute<bool, never>
     */
    protected function vaccinationsUpToDate(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->currentVaccinations()
                ->doesntContain(fn (DogVaccination $vaccination): bool => $vaccination->next_due_date?->lt(today()) ?? false),
        );
    }

    /**
     * @return Attribute<CarbonImmutable|null, never>
     */
    protected function nextVaccinationDate(): Attribute
    {
        return Attribute::make(
            get: fn (): ?CarbonImmutable => $this->currentVaccinations()
                ->pluck('next_due_date')
                ->filter(fn (?CarbonImmutable $dueDate): bool => $dueDate?->gte(today()) ?? false)
                ->min(),
        );
    }

    /**
     * The most recent dose of each distinct vaccine. Earlier doses of the same
     * vaccine have been superseded by a booster, so their due dates no longer
     * count towards whether the dog is overdue.
     *
     * @return Collection<int, DogVaccination>
     */
    public function currentVaccinations(): Collection
    {
        $vaccinations = $this->relationLoaded('vaccinations')
            ? $this->vaccinations
            : $this->vaccinations()->get();

        return $vaccinations
            ->sortByDesc(fn (DogVaccination $vaccination): string => (($vaccination->administered_date ?? $vaccination->next_due_date)?->format('Y-m-d') ?? '0000-00-00').sprintf('|%010d', $vaccination->id))
            ->unique(fn (DogVaccination $vaccination): string => Str::lower(Str::squish($vaccination->vaccine_details)))
            ->values();
    }

    /**
     * URL of the profile photo. Uploaded photos are served through an
     * authenticated route; legacy absolute URLs are passed through.
     *
     * @return Attribute<string|null, never>
     */
    protected function photoUrl(): Attribute
    {
        return Attribute::make(
            get: function (): ?string {
                if (blank($this->photo_path)) {
                    return null;
                }

                if (preg_match('#^(https?:)?//#', $this->photo_path) === 1 || str_starts_with($this->photo_path, '/')) {
                    return $this->photo_path;
                }

                return route('dogs.photo', ['dog' => $this, 'v' => $this->updated_at?->getTimestamp()], absolute: false);
            },
        );
    }
}
