<?php

namespace App\Models\Concerns;

use App\Models\Dog;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Records created/updated/deleted events for a model in the audit trail.
 *
 * @mixin Model
 */
trait Auditable
{
    /**
     * Attributes never written to the audit trail.
     *
     * @var list<string>
     */
    protected static array $auditExcluded = ['created_at', 'updated_at', 'remember_token', 'password'];

    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            /** @var Model&self $model */
            AuditLogger::record(
                action: $model->auditKey().'.created',
                description: 'Created '.$model->auditDescription(),
                subject: $model,
                dogId: $model->auditDogId(),
                newValues: $model->auditableValues($model->getAttributes()),
            );
        });

        static::updated(function (Model $model): void {
            /** @var Model&self $model */
            $changes = $model->auditableValues($model->getChanges());

            if ($changes === []) {
                return;
            }

            AuditLogger::record(
                action: $model->auditKey().'.updated',
                description: 'Updated '.$model->auditDescription(),
                subject: $model,
                dogId: $model->auditDogId(),
                oldValues: Arr::only($model->auditableValues($model->getOriginal()), array_keys($changes)),
                newValues: $changes,
            );
        });

        static::deleted(function (Model $model): void {
            /** @var Model&self $model */
            AuditLogger::record(
                action: $model->auditKey().'.deleted',
                description: 'Deleted '.$model->auditDescription(),
                subject: $model,
                dogId: $model->auditDogId(),
                oldValues: $model->auditableValues($model->getAttributes()),
            );
        });
    }

    /**
     * Short machine name used in action keys, e.g. "medical_profile" for DogMedicalProfile.
     */
    public function auditKey(): string
    {
        $name = class_basename($this);

        return $name === 'Dog' ? 'dog' : Str::snake(Str::after($name, 'Dog') ?: $name);
    }

    /**
     * Human description of the record, e.g. "medical profile for Tessa (SCAS-001)".
     */
    public function auditDescription(): string
    {
        if ($this instanceof Dog) {
            return "dog {$this->name} ({$this->scas_id})";
        }

        $label = str_replace('_', ' ', $this->auditKey());
        $dogId = $this->auditDogId();
        $dog = $dogId === null ? null : Dog::query()->whereKey($dogId)->first();

        return $dog !== null ? "{$label} for {$dog->name} ({$dog->scas_id})" : $label;
    }

    /**
     * The dog this record belongs to, so it appears in that dog's history.
     */
    public function auditDogId(): ?int
    {
        if ($this instanceof Dog) {
            return $this->getKey();
        }

        $dogId = $this->getAttribute('dog_id');

        return $dogId === null ? null : (int) $dogId;
    }

    /**
     * Strip excluded attributes and mask secrets before storing values.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function auditableValues(array $values): array
    {
        $masked = array_key_exists('password', $values) ? ['password' => '(changed)'] : [];

        return [
            ...Arr::except($values, static::$auditExcluded),
            ...$masked,
        ];
    }
}
