<?php

namespace App\Support\LegacyImport;

use Carbon\CarbonImmutable;

/**
 * The result of parsing one legacy spreadsheet date cell.
 *
 * `date` is only populated for unambiguous formats (plain DD.MM.YYYY, or the
 * corrupted "month.year" decimal). Anything annotated ("(Valid Until) ...",
 * "(ED) ...", "DD.MM.YYYY (ED: DD.MM.YYYY)") is intentionally left with a
 * null date and the raw text preserved in `annotation`, rather than guessing
 * which of the two dates a caller should treat as authoritative.
 */
final readonly class ParsedLegacyDate
{
    public function __construct(
        public ?CarbonImmutable $date,
        public bool $isApproximate,
        public ?string $annotation,
    ) {}

    public static function blank(): self
    {
        return new self(date: null, isApproximate: false, annotation: null);
    }
}
