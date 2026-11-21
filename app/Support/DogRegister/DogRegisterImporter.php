<?php

namespace App\Support\DogRegister;

use App\Enums\EnergyLevel;
use App\Enums\Gender;
use App\Enums\Size;
use App\Enums\Species;
use App\Models\Dog;
use App\Services\AuditLogger;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

/**
 * Imports the client's "Dog Register" workbook (one row per dog, keyed by
 * Dog ID) into dog profiles.
 *
 * Merge rules: a Dog ID that already exists updates that dog, a new Dog ID
 * adds a dog, and a dog is never added twice. A filled cell overwrites the
 * system value; a blank cell never erases what the system already holds.
 * Columns are found by their header text, so their order does not matter.
 */
final class DogRegisterImporter
{
    private const SHEET_NAME = 'Dog Register';

    private const MAX_ROWS = 2000;

    /**
     * Register headers (lowercased) mapped to the importer's field keys.
     * "Age (months)", "Bot sequence (calculated)" and "Microchip status" are
     * deliberately absent: the first two are spreadsheet formulas and the
     * last carries no chip number to store.
     *
     * @var array<string, string>
     */
    private const HEADERS = [
        'dog id' => 'scas_id',
        'dog name' => 'name',
        'publish to bot' => 'publish_to_bot',
        'adoption status' => 'adoption_status',
        'foster status' => 'foster_status',
        'sex' => 'gender',
        'breed or type' => 'breed',
        'date of birth' => 'date_of_birth',
        'birth date basis' => 'birth_date_basis',
        'current size' => 'size',
        'expected adult size' => 'expected_adult_size',
        'weight (kg)' => 'weight_kg',
        'date weighed' => 'weighed_on',
        'energy level' => 'energy_level',
        'personality' => 'personality_description',
        'children assessment' => 'children_assessment',
        'children observations' => 'children_observations',
        'dogs assessment' => 'dogs_assessment',
        'dogs observations' => 'dogs_observations',
        'cats assessment' => 'cats_assessment',
        'cats observations' => 'cats_observations',
        'home requirements' => 'home_requirements',
        'owner experience' => 'owner_experience',
        'alone time observations' => 'alone_time_observations',
        'house training' => 'house_training',
        'lead walking' => 'lead_walking',
        'basic commands' => 'basic_commands_notes',
        'vaccination status' => 'vaccination_status',
        'neutering status' => 'neutering_status',
        'approved care summary' => 'care_summary',
        'approved feeding guidance' => 'feeding_guidance',
        'known background' => 'known_background',
        'photo url' => 'register_photo_url',
        'profile url' => 'profile_url',
        'last reviewed' => 'last_reviewed_on',
        'reviewed by' => 'reviewed_by',
    ];

    /**
     * Fields copied to the dog as trimmed text, with their maximum length.
     *
     * @var array<string, int>
     */
    private const TEXT_FIELDS = [
        'name' => 255,
        'adoption_status' => 255,
        'foster_status' => 255,
        'breed' => 255,
        'personality_description' => 65535,
        'children_observations' => 65535,
        'dogs_observations' => 65535,
        'cats_observations' => 65535,
        'home_requirements' => 65535,
        'owner_experience' => 255,
        'alone_time_observations' => 65535,
        'basic_commands_notes' => 65535,
        'vaccination_status' => 255,
        'care_summary' => 65535,
        'feeding_guidance' => 65535,
        'reviewed_by' => 255,
    ];

    /**
     * Assessment columns, the yes/no profile flag each one sets, and the
     * register wording that means yes or no. Other wording ("Staff review",
     * "Not assessed") is stored but leaves the flag as it is.
     *
     * @var array<string, array{flag: string, yes: list<string>, no: list<string>}>
     */
    private const ASSESSMENT_FIELDS = [
        'children_assessment' => ['flag' => 'good_with_children', 'yes' => ['positive observations'], 'no' => ['not suitable']],
        'dogs_assessment' => ['flag' => 'good_with_dogs', 'yes' => ['positive observations'], 'no' => ['not suitable']],
        'cats_assessment' => ['flag' => 'good_with_cats', 'yes' => ['positive observations'], 'no' => ['not suitable']],
        'house_training' => ['flag' => 'potty_trained', 'yes' => ['yes'], 'no' => ['no']],
        'lead_walking' => ['flag' => 'leash_trained', 'yes' => ['trained'], 'no' => ['in progress']],
    ];

    /**
     * Register wording that carries no information for enum-backed fields.
     *
     * @var list<string>
     */
    private const NO_INFORMATION = ['unknown', 'not assessed', 'staff review'];

    private DogRegisterImportResult $result;

    /**
     * Header label of the column each attribute came from, for the summary.
     *
     * @var array<string, string>
     */
    private array $labels = [];

    /**
     * Import an .xlsx or .csv Dog Register. With $dryRun the same checks and
     * changes run inside a transaction that is rolled back, so the result
     * previews exactly what a real import would do without saving anything.
     */
    public function import(string $path, bool $dryRun = false): DogRegisterImportResult
    {
        $this->result = new DogRegisterImportResult;

        $sheet = $this->worksheet($path);
        [$headerRow, $columns] = $this->locateHeaders($sheet);
        $lastRow = min($sheet->getHighestDataRow(), $headerRow + self::MAX_ROWS);

        $run = function () use ($sheet, $headerRow, $columns, $lastRow): void {
            $seenIds = [];

            for ($row = $headerRow + 1; $row <= $lastRow; $row++) {
                $values = $this->readRow($sheet, $row, $columns);

                if ($values === null) {
                    continue;
                }

                $this->importRow($row, $values, $seenIds);
            }

            AuditLogger::record('import.dog_register', $this->result->summary());
        };

        if (! $dryRun) {
            DB::transaction($run);

            return $this->result;
        }

        DB::beginTransaction();

        try {
            $run();
        } finally {
            DB::rollBack();
        }

        return $this->result;
    }

    private function worksheet(string $path): Worksheet
    {
        try {
            $reader = IOFactory::createReaderForFile($path);
            $reader->setReadDataOnly(true);
            $spreadsheet = $reader->load($path);
        } catch (Throwable) {
            throw new DogRegisterImportException('The file could not be read as an Excel or CSV file.');
        }

        return $spreadsheet->getSheetByName(self::SHEET_NAME) ?? $spreadsheet->getSheet(0);
    }

    /**
     * Find the header row (within the first 10 rows) and map each known
     * header to its column letter.
     *
     * @return array{0: int, 1: array<string, string>}
     */
    private function locateHeaders(Worksheet $sheet): array
    {
        $highestColumn = $sheet->getHighestDataColumn();

        for ($row = 1; $row <= min(10, $sheet->getHighestDataRow()); $row++) {
            $columns = [];

            foreach ($sheet->getRowIterator($row, $row)->current()->getCellIterator('A', $highestColumn) as $cell) {
                $header = Str::lower(Str::squish((string) $cell->getValue()));

                if (isset(self::HEADERS[$header])) {
                    $columns[self::HEADERS[$header]] = $cell->getColumn();
                    $this->labels[self::HEADERS[$header]] = Str::squish((string) $cell->getValue());
                }
            }

            if (isset($columns['scas_id'], $columns['name'])) {
                return [$row, $columns];
            }
        }

        throw new DogRegisterImportException('The workbook has no "Dog ID" and "Dog name" header row. Upload the Dog Register file.');
    }

    /**
     * Read one row's cells by field key. Returns null for an empty row.
     *
     * @param  array<string, string>  $columns
     * @return array<string, mixed>|null
     */
    private function readRow(Worksheet $sheet, int $row, array $columns): ?array
    {
        $values = [];

        foreach ($columns as $field => $column) {
            $value = $this->cellValue($sheet->getCell($column.$row));

            if ($value !== null) {
                $values[$field] = $value;
            }
        }

        return $values === [] ? null : $values;
    }

    private function cellValue(Cell $cell): mixed
    {
        $value = $cell->getValue();

        if (is_string($value) && str_starts_with($value, '=')) {
            try {
                $value = $cell->getCalculatedValue();
            } catch (Throwable) {
                return null;
            }
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_string($value)) {
            $value = Str::squish($value);
        }

        return $value === '' ? null : $value;
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, int>  $seenIds
     */
    private function importRow(int $row, array $values, array &$seenIds): void
    {
        $scasId = Str::upper((string) ($values['scas_id'] ?? ''));

        if ($scasId === '') {
            $this->result->skipped[] = ['row' => $row, 'message' => 'No Dog ID, so the row was not imported.'];

            return;
        }

        if (preg_match('/^SCAS-\d+$/', $scasId) !== 1) {
            $this->result->skipped[] = ['row' => $row, 'message' => "Dog ID \"{$scasId}\" is not in the SCAS-### format."];

            return;
        }

        if (isset($seenIds[$scasId])) {
            $this->result->skipped[] = ['row' => $row, 'message' => "{$scasId} appears again (first on row {$seenIds[$scasId]}); this row was ignored."];

            return;
        }

        $seenIds[$scasId] = $row;

        [$dogAttributes, $medicalAttributes, $rescueAttributes] = $this->mapValues($row, $scasId, $values);

        $dog = Dog::query()->with(['medicalProfile', 'rescueIntake'])->where('scas_id', $scasId)->first();

        if ($dog === null) {
            $this->createDog($row, $scasId, $dogAttributes, $medicalAttributes, $rescueAttributes);

            return;
        }

        $this->updateDog($row, $dog, $dogAttributes, $medicalAttributes, $rescueAttributes);
    }

    /**
     * @param  array<string, mixed>  $dogAttributes
     * @param  array<string, mixed>  $medicalAttributes
     * @param  array<string, mixed>  $rescueAttributes
     */
    private function createDog(int $row, string $scasId, array $dogAttributes, array $medicalAttributes, array $rescueAttributes): void
    {
        if (! isset($dogAttributes['name'])) {
            $this->result->skipped[] = ['row' => $row, 'message' => "{$scasId} is new but has no Dog name, so it was not added."];

            return;
        }

        $dog = Dog::create([
            'scas_id' => $scasId,
            'species' => Species::Canine,
            ...$dogAttributes,
            'register_imported_at' => now(),
        ]);

        if ($medicalAttributes !== []) {
            $dog->medicalProfile()->create($medicalAttributes);
        }

        if ($rescueAttributes !== []) {
            $dog->rescueIntake()->create($rescueAttributes);
        }

        $this->result->created[] = ['scas_id' => $dog->scas_id, 'name' => $dog->name];
    }

    /**
     * @param  array<string, mixed>  $dogAttributes
     * @param  array<string, mixed>  $medicalAttributes
     * @param  array<string, mixed>  $rescueAttributes
     */
    private function updateDog(int $row, Dog $dog, array $dogAttributes, array $medicalAttributes, array $rescueAttributes): void
    {
        $previousName = $dog->name;
        $models = array_filter([
            $dog->fill($dogAttributes),
            $medicalAttributes === [] ? null : ($dog->medicalProfile ?? $dog->medicalProfile()->make())->fill($medicalAttributes),
            $rescueAttributes === [] ? null : ($dog->rescueIntake ?? $dog->rescueIntake()->make())->fill($rescueAttributes),
        ]);

        $changed = array_diff(array_merge(...array_map(fn (Model $model): array => array_keys($model->getDirty()), $models)), ['dog_id']);

        if ($changed === []) {
            $this->result->unchanged++;

            return;
        }

        $dog->forceFill(['register_imported_at' => now()]);

        foreach ($models as $model) {
            if ($model->isDirty()) {
                $model->save();
            }
        }

        if ($dog->name !== $previousName) {
            $this->warn($row, $dog->scas_id, "Name changed from \"{$previousName}\" to \"{$dog->name}\". Check this is the same dog.");
        }

        $this->result->updated[] = [
            'scas_id' => $dog->scas_id,
            'name' => $dog->name,
            'fields' => array_values(array_unique(array_map($this->label(...), $changed))),
        ];
    }

    /**
     * Header of the register column an attribute is filled from, e.g.
     * good_with_cats ← "Cats assessment".
     */
    private function label(string $attribute): string
    {
        return $this->labels[$this->sourceField($attribute)] ?? $attribute;
    }

    /**
     * The register field an attribute is filled from.
     */
    private function sourceField(string $attribute): string
    {
        foreach (self::ASSESSMENT_FIELDS as $field => $mapping) {
            if ($mapping['flag'] === $attribute) {
                return $field;
            }
        }

        return match ($attribute) {
            'date_of_birth_is_approximate' => 'birth_date_basis',
            'spayed_neutered' => 'neutering_status',
            'rescue_story' => 'known_background',
            default => $attribute,
        };
    }

    /**
     * Turn a row's raw cells into attributes for the dog, its medical profile
     * and its rescue intake. Values that cannot be understood are reported
     * and left out, so they never overwrite good data.
     *
     * @param  array<string, mixed>  $values
     * @return array{0: array<string, mixed>, 1: array<string, mixed>, 2: array<string, mixed>}
     */
    private function mapValues(int $row, string $scasId, array $values): array
    {
        $dog = [];
        $medical = [];
        $rescue = [];

        foreach (self::TEXT_FIELDS as $field => $maxLength) {
            if (! isset($values[$field])) {
                continue;
            }

            $text = (string) $values[$field];

            if (mb_strlen($text) > $maxLength) {
                $this->warn($row, $scasId, "{$this->labels[$field]} is longer than {$maxLength} characters and was not imported.");

                continue;
            }

            $dog[$field] = $text;
        }

        foreach (self::ASSESSMENT_FIELDS as $field => $mapping) {
            if (! isset($values[$field])) {
                continue;
            }

            $text = (string) $values[$field];
            $dog[$field] = Str::limit($text, 255, '');
            $normalized = Str::lower($text);

            if (in_array($normalized, $mapping['yes'], true)) {
                $dog[$mapping['flag']] = true;
            } elseif (in_array($normalized, $mapping['no'], true)) {
                $dog[$mapping['flag']] = false;
            }
        }

        $choice = fn (string $field): ?string => isset($values[$field]) ? Str::lower((string) $values[$field]) : null;

        if (($publish = $choice('publish_to_bot')) !== null) {
            $this->assign($dog, 'publish_to_bot', match ($publish) {
                'yes' => true,
                'no' => false,
                default => null,
            }, $row, $scasId, $values['publish_to_bot']);
        }

        if (($sex = $choice('gender')) !== null && ! in_array($sex, self::NO_INFORMATION, true)) {
            $this->assign($dog, 'gender', Gender::tryFrom($sex), $row, $scasId, $values['gender']);
        }

        foreach (['size', 'expected_adult_size'] as $field) {
            if (($size = $choice($field)) !== null && ! in_array($size, self::NO_INFORMATION, true)) {
                $this->assign($dog, $field, Size::tryFrom($size), $row, $scasId, $values[$field]);
            }
        }

        if (($energy = $choice('energy_level')) !== null && ! in_array($energy, self::NO_INFORMATION, true)) {
            $this->assign($dog, 'energy_level', $energy === 'moderate' ? EnergyLevel::Medium : EnergyLevel::tryFrom($energy), $row, $scasId, $values['energy_level']);
        }

        if (($basis = $choice('birth_date_basis')) !== null && $basis !== 'unknown') {
            $this->assign($dog, 'date_of_birth_is_approximate', match ($basis) {
                'exact' => false,
                'estimated' => true,
                default => null,
            }, $row, $scasId, $values['birth_date_basis']);
        }

        if (($neutering = $choice('neutering_status')) !== null && $neutering !== 'unknown') {
            $this->assign($medical, 'spayed_neutered', match ($neutering) {
                'yes' => true,
                'no', 'scheduled' => false,
                default => null,
            }, $row, $scasId, $values['neutering_status']);
        }

        foreach (['date_of_birth', 'weighed_on', 'last_reviewed_on'] as $field) {
            if (isset($values[$field])) {
                $date = $this->parseDate($values[$field]);

                if ($date !== null && $field === 'date_of_birth' && $date->isFuture()) {
                    $this->warn($row, $scasId, 'Date of birth is in the future and was not imported.');

                    continue;
                }

                $this->assign($dog, $field, $date?->toDateString(), $row, $scasId, $values[$field]);
            }
        }

        if (isset($values['weight_kg'])) {
            $weight = is_numeric($values['weight_kg']) ? (float) $values['weight_kg'] : null;
            $this->assign($dog, 'weight_kg', $weight !== null && $weight > 0 && $weight < 1000 ? number_format($weight, 2, '.', '') : null, $row, $scasId, $values['weight_kg']);
        }

        foreach (['register_photo_url', 'profile_url'] as $field) {
            if (isset($values[$field])) {
                $url = (string) $values[$field];
                $isWebAddress = filter_var($url, FILTER_VALIDATE_URL) !== false
                    && in_array(Str::lower((string) parse_url($url, PHP_URL_SCHEME)), ['http', 'https'], true)
                    && mb_strlen($url) <= 2048;

                $this->assign($dog, $field, $isWebAddress ? $url : null, $row, $scasId, $url);
            }
        }

        if (isset($values['known_background'])) {
            $rescue['rescue_story'] = (string) $values['known_background'];
        }

        return [$dog, $medical, $rescue];
    }

    /**
     * Store a parsed value, or report the raw cell when it could not be understood.
     *
     * @param  array<string, mixed>  $attributes
     */
    private function assign(array &$attributes, string $field, mixed $parsed, int $row, string $scasId, mixed $raw): void
    {
        if ($parsed === null) {
            $this->warn($row, $scasId, "{$this->label($field)} value \"{$raw}\" was not recognised and was not imported.");

            return;
        }

        $attributes[$field] = $parsed;
    }

    private function parseDate(mixed $value): ?CarbonImmutable
    {
        try {
            if (is_numeric($value)) {
                return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((float) $value))->startOfDay();
            }

            // Text dates such as 01/12/2023 (common in CSV files) are day-first, as in the UAE.
            if (preg_match('#^\d{1,2}[/.-]\d{1,2}[/.-]\d{4}$#', (string) $value) === 1) {
                return CarbonImmutable::createFromFormat('!d/m/Y', (string) preg_replace('#[.-]#', '/', (string) $value)) ?: null;
            }

            return CarbonImmutable::parse((string) $value)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    private function warn(int $row, string $scasId, string $message): void
    {
        $this->result->warnings[] = ['row' => $row, 'scas_id' => $scasId, 'message' => $message];
    }
}
