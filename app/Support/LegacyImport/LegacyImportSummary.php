<?php

namespace App\Support\LegacyImport;

final class LegacyImportSummary
{
    public int $created = 0;

    public int $updated = 0;

    public int $skipped = 0;

    public int $flagged = 0;

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'flagged' => $this->flagged,
        ];
    }
}
