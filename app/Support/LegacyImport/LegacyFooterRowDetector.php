<?php

namespace App\Support\LegacyImport;

/**
 * Identifies rows in the legacy workbook that aren't dog records: the
 * trailing "Dog Count:" summary row, and fully blank spacer rows used
 * between dog groups.
 */
final class LegacyFooterRowDetector
{
    /**
     * @param  array<string, string>  $row  Column letter => trimmed cell text.
     */
    public static function isFooterOrJunk(array $row): bool
    {
        $columnA = trim($row['A'] ?? '');

        if (stripos($columnA, 'Dog Count') !== false) {
            return true;
        }

        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}
