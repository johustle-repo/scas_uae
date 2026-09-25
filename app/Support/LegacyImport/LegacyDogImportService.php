<?php

namespace App\Support\LegacyImport;

use App\Enums\Gender;
use App\Enums\ImportFlagType;
use App\Enums\Species;
use App\Models\Dog;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Imports the legacy "SCAS Management & Records.xlsx" DOGS sheet, cleaning
 * up the known data-quality problems documented in the implementation plan
 * (corrupted decimal DOBs, mixed date-annotation formats, denormalized
 * multi-row vaccine history, near-empty rows, a footer row) rather than
 * importing the sheet verbatim.
 */
final class LegacyDogImportService
{
    private const SHEET_NAME = 'DOGS';

    private const FIRST_DATA_ROW = 3;

    private const COLUMNS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V'];

    public function __construct(
        private readonly LegacyRowGrouper $grouper = new LegacyRowGrouper,
    ) {}

    public function import(string $path): LegacyImportSummary
    {
        $summary = new LegacyImportSummary;

        $rows = $this->readRows($path);

        foreach ($rows as $row) {
            if (LegacyFooterRowDetector::isFooterOrJunk($row)) {
                $summary->skipped++;
            }
        }

        $groups = $this->grouper->group($rows);

        foreach ($groups as $group) {
            $this->importGroup($group, $summary);
        }

        return $summary;
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function readRows(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getSheetByName(self::SHEET_NAME);

        if ($sheet === null) {
            throw new \RuntimeException('The workbook has no "'.self::SHEET_NAME.'" sheet.');
        }

        $highestRow = $sheet->getHighestDataRow();
        $rows = [];

        for ($r = self::FIRST_DATA_ROW; $r <= $highestRow; $r++) {
            $row = [];

            foreach (self::COLUMNS as $column) {
                $row[$column] = trim((string) $sheet->getCell($column.$r)->getFormattedValue());
            }

            $rows[$r] = $row;
        }

        return $rows;
    }

    private function importGroup(LegacyDogRowGroup $group, LegacyImportSummary $summary): void
    {
        $primary = $group->primary;
        $flags = [];

        $name = trim($primary['E']);
        $breed = $this->nullableString($primary['G']);
        $gender = $this->mapGender($primary['J']);
        $species = $this->mapSpecies($primary['F']);
        $colourMarkings = $this->nullableString($primary['M']);

        $dob = LegacyDateParser::parse($primary['K']);
        $estimatedAgeNotes = null;

        if ($dob->annotation !== null) {
            $estimatedAgeNotes = "Legacy date of birth value could not be parsed: \"{$dob->annotation}\".";
        }

        $microchipRaw = trim($primary['B']);
        $microchipNumber = $this->resolveMicrochipNumber($microchipRaw, $flags);

        $isNearEmpty = $breed === null && $gender === null
            && $this->nullableString($primary['F']) === null
            && $dob->date === null;

        if ($isNearEmpty) {
            $flags[] = [
                'type' => ImportFlagType::IncompleteRecord,
                'message' => 'Imported from the legacy spreadsheet with minimal data (name only); this profile needs staff follow-up.',
            ];
        }

        if ($dob->date !== null && $dob->isApproximate) {
            $flags[] = [
                'type' => ImportFlagType::ApproximateDob,
                'message' => "Date of birth recorded as \"{$primary['K']}\" in the legacy sheet; interpreted as {$dob->date->format('F Y')} (exact day unknown).",
            ];
        }

        $ownerName = $this->nullableString($primary['S']);
        $ownerAddress = $this->nullableString($primary['T']);
        $ownerPhone = $this->nullableString($primary['U']);

        if ($ownerName !== null || $ownerAddress !== null || $ownerPhone !== null) {
            $flags[] = [
                'type' => ImportFlagType::PossiblePlacementUnconfirmed,
                'message' => 'Legacy sheet listed contact details ('
                    .implode(', ', array_filter([$ownerName, $ownerAddress, $ownerPhone]))
                    .') — status left as "At SCAS"; confirm whether this represents a foster or adoption placement.',
            ];
        }

        $dogAttributes = [
            'name' => $name,
            'species' => $species,
            'breed' => $breed,
            'colour_markings' => $colourMarkings,
            'gender' => $gender,
            'date_of_birth' => $dob->date,
            'date_of_birth_is_approximate' => $dob->isApproximate,
            'estimated_age_notes' => $estimatedAgeNotes,
        ];

        $existed = Dog::query()->where('legacy_source_row', $group->rowNumber)->exists();

        $dog = Dog::query()->updateOrCreate(
            ['legacy_source_row' => $group->rowNumber],
            $dogAttributes,
        );

        $existed ? $summary->updated++ : $summary->created++;

        // Vaccinations, satellite records, and flags are fully replaced on
        // every run — the only writer of this data is this command, so
        // delete-then-recreate is the simplest safe way to stay idempotent.
        $dog->vaccinations()->delete();
        $dog->importFlags()->delete();

        $this->importVaccinations($dog, $group, $microchipRaw);
        $this->importIdentification($dog, $primary, $microchipNumber);
        $this->importMedicalProfile($dog, $primary);
        $this->importRescueIntake($dog, $primary);

        foreach ($flags as $flag) {
            $dog->importFlags()->create([
                'flag_type' => $flag['type'],
                'message' => $flag['message'],
            ]);
            $summary->flagged++;
        }
    }

    private function importVaccinations(Dog $dog, LegacyDogRowGroup $group, string $microchipRaw): void
    {
        $candidates = [$group->primary, ...$group->continuations];

        foreach ($candidates as $row) {
            $vaccineDetailsRaw = trim($row['N']);
            $administeredRaw = trim($row['O']);
            $nextDueRaw = trim($row['P']);

            if ($vaccineDetailsRaw === '' && $administeredRaw === '' && $nextDueRaw === '') {
                continue;
            }

            $administered = LegacyDateParser::parse($administeredRaw);
            $nextDue = LegacyDateParser::parse($nextDueRaw);

            $annotations = array_filter([$administered->annotation, $nextDue->annotation]);

            $dog->vaccinations()->create([
                'vaccine_details' => $vaccineDetailsRaw !== '' ? $vaccineDetailsRaw : 'Vaccination',
                'administered_date' => $administered->date,
                'next_due_date' => $nextDue->date,
                'expiry_annotation' => $annotations === [] ? null : implode('; ', $annotations),
            ]);
        }
    }

    /**
     * @param  array<string, string>  $primary
     */
    private function importIdentification(Dog $dog, array $primary, ?string $microchipNumber): void
    {
        $microchipDate = LegacyDateParser::parse($primary['C']);
        $pccNumber = $this->nullableString($primary['R']);
        $chipLocation = $this->nullableString($primary['D']);

        $notes = [];

        if ($microchipDate->annotation !== null) {
            $notes[] = "Legacy \"Date of Microchipping\" value: \"{$microchipDate->annotation}\".";
        }

        $attributes = [
            'microchip_number' => $microchipNumber,
            'microchip_date' => $microchipDate->date,
            'microchip_location' => $chipLocation,
            'pcc_number' => $pccNumber,
            'supporting_documents_notes' => $notes === [] ? null : implode(' ', $notes),
        ];

        if ($this->hasAnyValue($attributes)) {
            $dog->identification()->updateOrCreate([], $attributes);
        }
    }

    /**
     * @param  array<string, string>  $primary
     */
    private function importMedicalProfile(Dog $dog, array $primary): void
    {
        $neuterStatusRaw = trim($primary['H']);
        $neuterDate = LegacyDateParser::parse($primary['I']);

        $notes = [];

        if ($neuterDate->annotation !== null) {
            $notes[] = "Legacy \"Date of N/S\" value: \"{$neuterDate->annotation}\".";
        }

        $attributes = [
            'spayed_neutered' => $neuterStatusRaw === '' ? null : true,
            'spayed_neutered_date' => $neuterDate->date,
            'medical_notes' => $notes === [] ? null : implode(' ', $notes),
        ];

        if ($this->hasAnyValue($attributes)) {
            $dog->medicalProfile()->updateOrCreate([], $attributes);
        }
    }

    /**
     * @param  array<string, string>  $primary
     */
    private function importRescueIntake(Dog $dog, array $primary): void
    {
        $story = $this->nullableString($primary['V']);

        if ($story === null) {
            return;
        }

        $dog->rescueIntake()->updateOrCreate([], ['rescue_story' => $story]);
    }

    private function mapGender(string $raw): ?Gender
    {
        return match (strtolower(trim($raw))) {
            'male' => Gender::Male,
            'female' => Gender::Female,
            default => null,
        };
    }

    private function mapSpecies(string $raw): Species
    {
        return str_contains(strtolower($raw), 'puppy') ? Species::CaninePuppy : Species::Canine;
    }

    /**
     * @param  list<array{type: ImportFlagType, message: string}>  $flags
     */
    private function resolveMicrochipNumber(string $raw, array &$flags): ?string
    {
        if ($raw === '') {
            return null;
        }

        if ($raw === '1') {
            $flags[] = [
                'type' => ImportFlagType::PlaceholderMicrochip,
                'message' => 'Legacy sheet listed microchip number as "1", which is not a valid chip number; left blank pending verification.',
            ];

            return null;
        }

        return $raw;
    }

    private function nullableString(string $value): ?string
    {
        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function hasAnyValue(array $attributes): bool
    {
        foreach ($attributes as $value) {
            if ($value !== null) {
                return true;
            }
        }

        return false;
    }
}
