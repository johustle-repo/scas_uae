<?php

namespace App\Support\LegacyImport;

/**
 * One dog's primary row plus any continuation rows (extra vaccination
 * doses) that followed it in the sheet, before the next primary row.
 */
final readonly class LegacyDogRowGroup
{
    /**
     * @param  array<string, string>  $primary  Column letter => trimmed cell text.
     * @param  list<array<string, string>>  $continuations
     */
    public function __construct(
        public int $rowNumber,
        public array $primary,
        public array $continuations,
    ) {}
}
