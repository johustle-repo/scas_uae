<?php

namespace App\Support\LegacyImport;

use Carbon\CarbonImmutable;

/**
 * Parses the messy date strings found in the legacy "SCAS Management &
 * Records.xlsx" workbook. See ParsedLegacyDate for how ambiguous formats
 * are handled.
 */
final class LegacyDateParser
{
    /**
     * Matches a clean "DD.MM.YYYY" string with nothing else around it.
     */
    private const PLAIN_DATE_PATTERN = '/^(\d{1,2})\.(\d{1,2})\.(\d{4})$/';

    /**
     * Matches Excel's mangled "month.year" shorthand, e.g. "8.2018" or the
     * floating-point-corrupted "8.2018000000000004".
     */
    private const APPROXIMATE_MONTH_YEAR_PATTERN = '/^(\d{1,2})\.(\d{4})\d*$/';

    public static function parse(?string $raw): ParsedLegacyDate
    {
        $value = trim((string) $raw);

        if ($value === '') {
            return ParsedLegacyDate::blank();
        }

        if (preg_match(self::PLAIN_DATE_PATTERN, $value, $matches) === 1) {
            $date = self::toDate((int) $matches[3], (int) $matches[2], (int) $matches[1]);

            return $date === null
                ? new ParsedLegacyDate(date: null, isApproximate: false, annotation: $value)
                : new ParsedLegacyDate(date: $date, isApproximate: false, annotation: null);
        }

        if (preg_match(self::APPROXIMATE_MONTH_YEAR_PATTERN, $value, $matches) === 1) {
            $month = (int) $matches[1];
            $year = (int) $matches[2];

            if ($month >= 1 && $month <= 12) {
                $date = self::toDate($year, $month, 1);

                if ($date !== null) {
                    return new ParsedLegacyDate(date: $date, isApproximate: true, annotation: null);
                }
            }
        }

        // Anything else — "(Valid Until) DD.MM.YYYY", "(ED) DD.MM.YYYY",
        // "DD.MM.YYYY (ED: DD.MM.YYYY)", or any other unrecognized shape —
        // is preserved verbatim rather than guessed at.
        return new ParsedLegacyDate(date: null, isApproximate: false, annotation: $value);
    }

    private static function toDate(int $year, int $month, int $day): ?CarbonImmutable
    {
        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return CarbonImmutable::create($year, $month, $day)?->startOfDay();
    }
}
