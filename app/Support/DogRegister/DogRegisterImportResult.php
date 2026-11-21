<?php

namespace App\Support\DogRegister;

/**
 * Outcome of one Dog Register import, shown to staff after the upload.
 */
final class DogRegisterImportResult
{
    /** @var list<array{scas_id: string, name: string}> */
    public array $created = [];

    /** @var list<array{scas_id: string, name: string, fields: list<string>}> */
    public array $updated = [];

    public int $unchanged = 0;

    /** @var list<array{row: int, message: string}> */
    public array $skipped = [];

    /** @var list<array{row: int, scas_id: string|null, message: string}> */
    public array $warnings = [];

    public function summary(): string
    {
        return sprintf(
            'Dog Register imported: %d added, %d updated, %d unchanged, %d skipped.',
            count($this->created),
            count($this->updated),
            $this->unchanged,
            count($this->skipped),
        );
    }

    /**
     * @return array{created: list<array{scas_id: string, name: string}>, updated: list<array{scas_id: string, name: string, fields: list<string>}>, unchanged: int, skipped: list<array{row: int, message: string}>, warnings: list<array{row: int, scas_id: string|null, message: string}>}
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'unchanged' => $this->unchanged,
            'skipped' => $this->skipped,
            'warnings' => $this->warnings,
        ];
    }
}
