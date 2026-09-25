<?php

namespace App\Support\LegacyImport;

/**
 * Groups raw legacy-sheet rows into one entry per dog: a "primary" row
 * (has a Name) followed by zero or more "continuation" rows (blank Name,
 * but vaccine details/dates present) that record extra vaccination doses
 * for the dog in the preceding primary row.
 */
final class LegacyRowGrouper
{
    /**
     * @param  array<int, array<string, string>>  $rows  Row number => (column letter => trimmed cell text).
     * @return list<LegacyDogRowGroup>
     */
    public function group(array $rows): array
    {
        $groups = [];
        $currentRowNumber = null;
        $currentPrimary = null;
        $currentContinuations = [];

        foreach ($rows as $rowNumber => $row) {
            if (LegacyFooterRowDetector::isFooterOrJunk($row)) {
                continue;
            }

            $name = trim($row['E'] ?? '');

            if ($name !== '') {
                if ($currentPrimary !== null) {
                    $groups[] = new LegacyDogRowGroup($currentRowNumber, $currentPrimary, $currentContinuations);
                }

                $currentRowNumber = $rowNumber;
                $currentPrimary = $row;
                $currentContinuations = [];

                continue;
            }

            if ($this->hasVaccinationData($row) && $currentPrimary !== null) {
                $currentContinuations[] = $row;
            }

            // Anything else (a blank-name row with no vaccination data and
            // not already caught by the footer/junk check) is skipped —
            // it doesn't identify a dog and isn't a recognizable
            // continuation of one.
        }

        if ($currentPrimary !== null) {
            $groups[] = new LegacyDogRowGroup($currentRowNumber, $currentPrimary, $currentContinuations);
        }

        return $groups;
    }

    /**
     * @param  array<string, string>  $row
     */
    private function hasVaccinationData(array $row): bool
    {
        return trim($row['N'] ?? '') !== ''
            || trim($row['O'] ?? '') !== ''
            || trim($row['P'] ?? '') !== '';
    }
}
